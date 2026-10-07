<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$userId = getCurrentUserId();
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

if ($userId <= 0 || !$isSuperadmin) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$tbl = 'lead';

$columns = [
    0 => null,
    1 => 'lead_number',
    2 => 'business_name',
    3 => 'contact_name',
    4 => 'mobile_no',
    5 => 'email',
    6 => 'team_size',
    7 => 'demo_date',
    8 => 'lead_stage',
    9 => null
];

// Handle 1: Update Lead Stage
if (isset($_POST['action']) && $_POST['action'] === 'update_stage') {
    header('Content-Type: application/json');
    $leadId    = (int)($_POST['lead_id'] ?? 0);
    $newStage  = trim($_POST['new_stage'] ?? '');
    $stageNote = trim($_POST['stage_note'] ?? '');

    if ($leadId <= 0 || empty($newStage)) {
        echo json_encode(['status' => false, 'message' => 'Invalid lead details.']);
        exit;
    }

    $lead = db_row("SELECT * FROM lead WHERE id = $leadId LIMIT 1");
    if (empty($lead)) {
        echo json_encode(['status' => false, 'message' => 'Lead not found.']);
        exit;
    }

    $newStageKey = is_numeric($newStage) ? (int)$newStage : 0;
    $stageSlug = ($newStageKey > 0) ? get_lead_stage_slug($newStageKey) : strtolower($newStage);
    $stageLabel = get_lead_stage_label($newStage);

    $stageSql = db_escape($newStage); // stores key if numeric, or slug
    $appendNote = '';
    if (!empty($stageNote)) {
        $existingNotes = $lead['notes'] ?? '';
        $appendNote = "\n[" . date('d-m-Y H:i') . " - Stage changed to $stageLabel]: " . $stageNote;
        $allNotes = trim($existingNotes . $appendNote);
        $notesSql = ", `notes` = '" . db_escape($allNotes) . "'";
    } else {
        $notesSql = "";
    }

    $upd = db_query("UPDATE lead SET `lead_stage` = '$stageSql' $notesSql, `updated_by` = $userId, `updated_at` = NOW() WHERE `id` = $leadId");
    if ($upd) {
        echo json_encode(['status' => true, 'message' => 'Lead stage updated successfully.']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to update lead stage.']);
    }
    exit;
}

// Handle: Fetch Follow-up History & Reminders for a Lead
if (isset($_POST['action']) && $_POST['action'] === 'get_followups') {
    header('Content-Type: application/json');
    $leadId = (int)($_POST['lead_id'] ?? 0);
    if ($leadId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid lead ID']);
        exit;
    }

    $lead = db_row("SELECT id, lead_number, business_name, contact_name, mobile_no, lead_stage FROM lead WHERE id = $leadId LIMIT 1");
    if (empty($lead)) {
        echo json_encode(['status' => false, 'message' => 'Lead not found']);
        exit;
    }

    $followups = db_rows("SELECT f.*, u.name as created_user_name 
                          FROM lead_followups f 
                          LEFT JOIN users u ON u.id = f.created_by 
                          WHERE f.lead_id = $leadId 
                          ORDER BY f.followup_date DESC");

    if (!empty($followups)) {
        foreach ($followups as &$fuItem) {
            $fuItem['stage_label'] = get_lead_stage_label($fuItem['stage'] ?? '');
            $fuItem['type_label'] = get_lead_followup_type_label($fuItem['followup_type'] ?? '');

            // Format date to d-m-Y format (e.g. 14-10-2026 12:00 PM)
            if (!empty($fuItem['followup_date']) && $fuItem['followup_date'] !== '0000-00-00 00:00:00') {
                $fuItem['formatted_date'] = date('d-m-Y h:i A', strtotime($fuItem['followup_date']));
            } else {
                $fuItem['formatted_date'] = '-';
            }
        }
        unset($fuItem);
    }

    echo json_encode([
        'status' => true,
        'lead'   => $lead,
        'data'   => $followups ?: []
    ]);
    exit;
}

// Handle: Add New Follow-up & Reminder
if (isset($_POST['action']) && $_POST['action'] === 'add_followup') {
    header('Content-Type: application/json');
    $leadId       = (int)($_POST['lead_id'] ?? 0);
    $followupType = trim($_POST['followup_type'] ?? 'call');
    $followupDate = trim($_POST['followup_date'] ?? '');
    $stage        = trim($_POST['stage'] ?? 'contacted');
    $remarks      = trim($_POST['remarks'] ?? '');

    if ($leadId <= 0 || empty($followupDate) || empty($remarks)) {
        echo json_encode(['status' => false, 'message' => 'Please provide Follow-up Date/Time and Remarks.']);
        exit;
    }

    // Sanitize and parse date format (handles d-m-Y H:i and other standard formats)
    $fTypeSql  = db_escape($followupType);
    $parsedDate = DateTime::createFromFormat('d-m-Y H:i', $followupDate);
    if (!$parsedDate) {
        $parsedDate = DateTime::createFromFormat('d-m-Y', $followupDate);
    }
    if ($parsedDate) {
        $fDateSql = db_escape($parsedDate->format('Y-m-d H:i:s'));
    } else {
        $fDateSql = db_escape(date('Y-m-d H:i:s', strtotime($followupDate)));
    }
    $stageSql  = db_escape($stage);
    $remSql    = db_escape($remarks);

    $insSql = "INSERT INTO lead_followups (
        `lead_id`, `followup_type`, `followup_date`, `stage`, `remarks`, `reminder_status`, `created_by`, `created_at`, `updated_at`
    ) VALUES (
        $leadId, '$fTypeSql', '$fDateSql', '$stageSql', '$remSql', 'pending', $userId, NOW(), NOW()
    )";
    $inserted = db_query($insSql);

    if ($inserted) {
        // Also update lead's stage and append notes
        $lead = db_row("SELECT notes FROM lead WHERE id = $leadId LIMIT 1");
        $currNotes = $lead['notes'] ?? '';
        $stageLabel = get_lead_stage_label($stage);
        $typeLabel  = get_lead_followup_type_label($followupType);
        $noteLog = "\n[" . date('d-m-Y H:i') . " - Follow-up ($typeLabel)]: " . $remarks . " (Stage: $stageLabel)";
        $updatedNotes = db_escape(trim($currNotes . $noteLog));

        db_query("UPDATE lead SET `lead_stage` = '$stageSql', `notes` = '$updatedNotes', `updated_by` = $userId, `updated_at` = NOW() WHERE `id` = $leadId");

        echo json_encode(['status' => true, 'message' => 'Follow-up and Reminder recorded successfully!']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to save follow-up record.']);
    }
    exit;
}

// Handle: Fetch single follow-up for Edit
if (isset($_POST['action']) && $_POST['action'] === 'get_followup') {
    header('Content-Type: application/json');
    $fuId = (int)($_POST['followup_id'] ?? 0);
    if ($fuId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $fu = db_row("SELECT * FROM lead_followups WHERE id = $fuId LIMIT 1");
    if (empty($fu)) {
        echo json_encode(['status' => false, 'message' => 'Follow-up record not found']);
        exit;
    }

    // Format date for flatpickr input (d-m-Y H:i)
    $formattedDate = '';
    if (!empty($fu['followup_date']) && $fu['followup_date'] !== '0000-00-00 00:00:00') {
        $formattedDate = date('d-m-Y H:i', strtotime($fu['followup_date']));
    }
    $fu['formatted_date'] = $formattedDate;

    echo json_encode(['status' => true, 'data' => $fu]);
    exit;
}

// Handle: Edit / Update Follow-up
if (isset($_POST['action']) && $_POST['action'] === 'update_followup') {
    header('Content-Type: application/json');
    $fuId         = (int)($_POST['followup_id'] ?? 0);
    $followupType = trim($_POST['followup_type'] ?? '');
    $followupDate = trim($_POST['followup_date'] ?? '');
    $stage        = trim($_POST['stage'] ?? '');
    $remarks      = trim($_POST['remarks'] ?? '');

    if ($fuId <= 0 || empty($followupDate) || empty($remarks)) {
        echo json_encode(['status' => false, 'message' => 'Please provide Follow-up Date/Time and Remarks.']);
        exit;
    }

    $existingFu = db_row("SELECT * FROM lead_followups WHERE id = $fuId LIMIT 1");
    if (empty($existingFu)) {
        echo json_encode(['status' => false, 'message' => 'Record not found.']);
        exit;
    }

    // Only allow editing if reminder_status is pending
    if ($existingFu['reminder_status'] !== 'pending') {
        echo json_encode(['status' => false, 'message' => 'Only pending follow-ups can be edited.']);
        exit;
    }

    $parsedDate = DateTime::createFromFormat('d-m-Y H:i', $followupDate);
    if (!$parsedDate) {
        $parsedDate = DateTime::createFromFormat('d-m-Y', $followupDate);
    }
    if ($parsedDate) {
        $fDateSql = db_escape($parsedDate->format('Y-m-d H:i:s'));
    } else {
        $fDateSql = db_escape(date('Y-m-d H:i:s', strtotime($followupDate)));
    }

    $fTypeSql = db_escape($followupType);
    $stageSql = db_escape($stage);
    $remSql   = db_escape($remarks);

    $upd = db_query("UPDATE lead_followups SET 
        `followup_type` = '$fTypeSql',
        `followup_date` = '$fDateSql',
        `stage`         = '$stageSql',
        `remarks`       = '$remSql',
        `updated_at`    = NOW()
        WHERE `id` = $fuId");

    if ($upd) {
        echo json_encode(['status' => true, 'message' => 'Follow-up updated successfully!']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to update follow-up.']);
    }
    exit;
}

// Handle: Mark Follow-up Reminder Status (completed / missed)
if (isset($_POST['action']) && $_POST['action'] === 'update_followup_status') {
    header('Content-Type: application/json');
    $followupId = (int)($_POST['followup_id'] ?? 0);
    $newStatus  = trim($_POST['status'] ?? 'completed');

    if ($followupId <= 0 || !in_array($newStatus, ['pending', 'completed', 'missed'])) {
        echo json_encode(['status' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    $statSql = db_escape($newStatus);
    $upd = db_query("UPDATE lead_followups SET `reminder_status` = '$statSql', `updated_at` = NOW() WHERE `id` = $followupId");

    if ($upd) {
        echo json_encode(['status' => true, 'message' => 'Reminder status updated to ' . $newStatus]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to update reminder status']);
    }
    exit;
}

// Handle: Submit Followup Response (From Right icon click)
if (isset($_POST['action']) && $_POST['action'] === 'submit_followup_response') {
    header('Content-Type: application/json');
    $followupId     = (int)($_POST['followup_id'] ?? 0);
    $responseRemarks= trim($_POST['response'] ?? '');
    $fuAction       = trim($_POST['followup_action'] ?? ''); // 'next-followup' or 'end-followup'

    if ($followupId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
        exit;
    }

    if (empty($responseRemarks)) {
        echo json_encode(['status' => false, 'message' => 'Please enter response remarks.']);
        exit;
    }

    if (!in_array($fuAction, ['next-followup', 'end-followup'])) {
        echo json_encode(['status' => false, 'message' => 'Please select a valid Follow-up Action.']);
        exit;
    }

    // Fetch existing follow-up
    $currFu = db_row("SELECT * FROM lead_followups WHERE id = $followupId LIMIT 1");
    if (empty($currFu)) {
        echo json_encode(['status' => false, 'message' => 'Follow-up record not found.']);
        exit;
    }

    $leadId = (int)$currFu['lead_id'];

    // 1. Mark current follow-up completed and append response notes
    $respEscaped = db_escape($responseRemarks);
    $mergedRemarks = $currFu['remarks'];
    if (!empty($mergedRemarks)) {
        $mergedRemarks .= "\n[Response]: " . $responseRemarks;
    } else {
        $mergedRemarks = $responseRemarks;
    }
    $mergedRemarksEscaped = db_escape($mergedRemarks);

    $updCurrent = db_query("UPDATE lead_followups SET 
        `reminder_status` = 'completed',
        `remarks` = '$mergedRemarksEscaped',
        `updated_at` = NOW()
        WHERE `id` = $followupId");

    // Also update lead's notes
    $leadRow = db_row("SELECT notes, lead_stage FROM lead WHERE id = $leadId LIMIT 1");
    $currNotes = $leadRow['notes'] ?? '';
    $stageVal  = $currFu['stage'] ?? ($leadRow['lead_stage'] ?? '');
    $stageLabel = get_lead_stage_label($stageVal);
    $typeLabel  = get_lead_followup_type_label($currFu['followup_type'] ?? '');
    $noteLog = "\n[" . date('d-m-Y H:i') . " - Follow-up Response ($typeLabel)]: " . $responseRemarks;

    // 2. If action is next-followup, create next followup record
    if ($fuAction === 'next-followup') {
        $nextDateRaw = trim($_POST['next_followup_date'] ?? '');
        $nextType    = trim($_POST['next_followup_type'] ?? '');
        $nextDiscuss = trim($_POST['next_discuss_topic'] ?? '');
        $nextStage   = trim($_POST['next_stage'] ?? $stageVal);

        if (empty($nextDateRaw)) {
            echo json_encode(['status' => false, 'message' => 'Please select Next Follow-up Date & Time.']);
            exit;
        }

        if (empty($nextType)) {
            echo json_encode(['status' => false, 'message' => 'Please select Next Follow-up Type.']);
            exit;
        }

        $parsedDate = DateTime::createFromFormat('d-m-Y H:i', $nextDateRaw);
        if (!$parsedDate) {
            $parsedDate = DateTime::createFromFormat('d-m-Y', $nextDateRaw);
        }
        if ($parsedDate) {
            $fDateSql = db_escape($parsedDate->format('Y-m-d H:i:s'));
        } else {
            $fDateSql = db_escape(date('Y-m-d H:i:s', strtotime($nextDateRaw)));
        }

        $nextTypeSql  = db_escape($nextType);
        $nextStageSql = db_escape($nextStage ?: $stageVal);
        $nextRemSql   = db_escape($nextDiscuss ?: $responseRemarks);

        $insSql = "INSERT INTO lead_followups (
            `lead_id`, `followup_type`, `followup_date`, `stage`, `remarks`, `reminder_status`, `created_by`, `created_at`, `updated_at`
        ) VALUES (
            $leadId, '$nextTypeSql', '$fDateSql', '$nextStageSql', '$nextRemSql', 'pending', $userId, NOW(), NOW()
        )";
        db_query($insSql);

        $nextTypeLabel = get_lead_followup_type_label($nextType);
        $noteLog .= "\n[Next Follow-up Scheduled (" . $nextTypeLabel . " on " . $nextDateRaw . ")]: " . ($nextDiscuss ?: '-');
    }

    $updatedNotes = db_escape(trim($currNotes . $noteLog));
    db_query("UPDATE lead SET `notes` = '$updatedNotes', `updated_by` = $userId, `updated_at` = NOW() WHERE `id` = $leadId");

    $msg = ($fuAction === 'next-followup') ? 'Response saved and Next Follow-up scheduled successfully!' : 'Follow-up ended and response saved successfully!';
    echo json_encode(['status' => true, 'message' => $msg]);
    exit;
}

// Handle 2: Convert Lead to Tenant Company (Complete Onboarding without email)
if (isset($_POST['action']) && $_POST['action'] === 'convert_to_company') {
    header('Content-Type: application/json');
    $leadId      = (int)($_POST['lead_id'] ?? 0);
    $passwordRaw = trim($_POST['password'] ?? '123456');
    $planId      = (int)($_POST['plan_id'] ?? 0);

    if ($leadId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid Lead ID.']);
        exit;
    }

    if ($planId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Please select a subscription plan.']);
        exit;
    }

    if (empty($passwordRaw)) {
        echo json_encode(['status' => false, 'message' => 'Please enter initial password.']);
        exit;
    }

    // Strong password validation matching Company module (8-12 chars, 1 uppercase, 1 lowercase, 1 number)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,12}$/', $passwordRaw)) {
        echo json_encode([
            'status' => false, 
            'message' => 'Password must contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and be between 8 and 12 characters.'
        ]);
        exit;
    }

    // 1. Fetch lead details from `lead` table
    $lead = db_row("SELECT * FROM lead WHERE id = $leadId LIMIT 1");
    if (empty($lead)) {
        echo json_encode(['status' => false, 'message' => 'Lead not found.']);
        exit;
    }

    $companyName = trim($lead['business_name'] ?? '');
    if (empty($companyName)) {
        $companyName = 'Company ' . ($lead['lead_number'] ?: ('OI-' . $leadId));
    }
    $personName  = trim($lead['contact_name'] ?? '');
    if (empty($personName)) {
        $personName = $companyName;
    }
    $mobileNo    = trim($lead['mobile_no'] ?? '');
    $email       = strtolower(trim($lead['email'] ?? ''));

    if (empty($email)) {
        echo json_encode(['status' => false, 'message' => 'Lead does not have an email address. Please edit lead to add email before converting.']);
        exit;
    }

    // 2. Fetch selected Plan
    $plan = db_row("SELECT * FROM plan WHERE id = $planId LIMIT 1");
    if (empty($plan)) {
        echo json_encode(['status' => false, 'message' => 'Selected subscription plan not found.']);
        exit;
    }

    // Default company type
    $typeRow = db_row("SELECT id FROM company_type WHERE status = 1 LIMIT 1");
    $companyTypeId = !empty($typeRow['id']) ? (int)$typeRow['id'] : 1;

    $cNameEsc   = db_escape($companyName);
    $pNameEsc   = db_escape($personName);
    $mobEsc     = db_escape($mobileNo);
    $emailEsc   = db_escape($email);
    $hashedPass = password_hash($passwordRaw, PASSWORD_BCRYPT);
    $countryEsc = !empty($lead['country_id']) ? (int)$lead['country_id'] : "NULL";
    $stateEsc   = !empty($lead['state_id']) ? (int)$lead['state_id'] : "NULL";
    $cityEsc    = !empty($lead['city_id']) ? (int)$lead['city_id'] : "NULL";
    $addressEsc = !empty($lead['address']) ? "'" . db_escape($lead['address']) . "'" : "NULL";

    // 3. Create Company in `company` table (matching company module store.php)
    $insertCompanySql = "INSERT INTO `company` (
        `lead_id`, `company_type_id`, `name`, `person_name`, `mobile_no`, `email`, 
        `password`, `country_id`, `state_id`, `city_id`, `address`, `plan_id`, 
        `is_stock_check`, `stock_manage_by`, `status`, `created_by`, `created_at`, `updated_at`
    ) VALUES (
        $leadId, $companyTypeId, '$cNameEsc', '$pNameEsc', '$mobEsc', '$emailEsc',
        '$hashedPass', $countryEsc, $stateEsc, $cityEsc, $addressEsc, $planId, 
        0, 'fifo', 1, $userId, NOW(), NOW()
    )";
    $compRes = db_query($insertCompanySql);
    $newCompanyId = db_insert_id($conn);

    if ($newCompanyId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Failed to create Company record.']);
        exit;
    }

    // 4. Role Create (matching company/store.php: name = company name)
    $roleName = $companyName;
    $roleNameEsc = db_escape($roleName);
    $rolesData = db_row("SELECT * FROM roles WHERE company_id = $newCompanyId AND name = '$roleNameEsc' LIMIT 1");
    if (!empty($rolesData['id'])) {
        $roleId = (int)$rolesData['id'];
    } else {
        $comSql = "INSERT INTO roles (company_id, name, created_by, created_at, updated_at) 
                   VALUES ('$newCompanyId', '$roleNameEsc', '$userId', NOW(), NOW())";
        db_query($comSql);
        $roleId = db_insert_id($conn);
    }

    // 5. User Create (company_admin) (matching company/store.php)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userData = db_row("SELECT * FROM users WHERE company_id = $newCompanyId AND role_id = $roleId LIMIT 1");
    if (!empty($userData['id'])) {
        $adminUserId = (int)$userData['id'];
        $sql = "UPDATE users SET 
                    name = '$pNameEsc', 
                    username = '$cNameEsc', 
                    email = '$emailEsc', 
                    mobile_no = '$mobEsc', 
                    country_id = $countryEsc, 
                    state_id = $stateEsc, 
                    city_id = $cityEsc, 
                    company_plan_id = '$planId', 
                    password = '$hashedPass',
                    ip_address = '$ip', 
                    updated_at = NOW() 
                WHERE id = $adminUserId";
        db_query($sql);
    } else {
        $sql = "INSERT INTO users (
                    role_id, company_id, company_plan_id, name, username, email, password, mobile_no, 
                    country_id, state_id, city_id, user_type, ip_address, created_by, created_at, updated_at
                ) VALUES (
                    '$roleId', '$newCompanyId', '$planId', '$pNameEsc', '$cNameEsc', '$emailEsc', '$hashedPass', '$mobEsc', 
                    $countryEsc, $stateEsc, $cityEsc, 'company_admin', '$ip', '$userId', NOW(), NOW()
                )";
        db_query($sql);
        $adminUserId = db_insert_id($conn);
    }

    // 6. Role Permissions based on Plan's panel_right modules (matching company/store.php)
    if ($planId > 0 && !empty($newCompanyId) && !empty($roleId)) {
        $planRow = db_row("SELECT * FROM plan WHERE id = " . (int)$planId . " LIMIT 1");
        if (!empty($planRow['panel_right'])) {
            $moduleIds = array_filter(array_map('trim', explode(',', $planRow['panel_right'])));
            foreach ($moduleIds as $mId) {
                $mId = (int)$mId;
                if ($mId <= 0) continue;

                $existPerm = db_row("SELECT id FROM role_permissions WHERE company_id = $newCompanyId AND role_id = $roleId AND module_id = $mId LIMIT 1");
                if (!empty($existPerm)) {
                    $permSql = "UPDATE role_permissions 
                                SET views = 1, adds = 1, updates = 1, deletes = 1, print = 1, excel = 1, created_by = $userId, updated_at = NOW() 
                                WHERE id = " . (int)$existPerm['id'];
                } else {
                    $permSql = "INSERT INTO role_permissions (company_id, role_id, module_id, views, adds, updates, deletes, print, excel, created_by, created_at, updated_at) 
                                VALUES ($newCompanyId, $roleId, $mId, 1, 1, 1, 1, 1, 1, $userId, NOW(), NOW())";
                }
                db_query($permSql);
            }
        }
    }

    // 7. Store in company_subscription_plan table (matching company/store.php)
    $planDays = (int)($plan['days'] ?? 0);
    if ($planDays <= 0) {
        $planDays = 30; // fallback default
    }
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$planDays days"));
    $expiryDate = $endDate;

    $extraDetailArray = [
        'id'             => (int)$plan['id'],
        'name'           => $plan['name'] ?? '',
        'price'          => (float)($plan['price'] ?? 0),
        'days'           => $planDays,
        'plan_valid_day' => $planDays,
        'max_team_user'  => (int)($plan['max_team_user'] ?? 0),
        'max_customer'   => (int)($plan['max_customer'] ?? 0),
        'max_inquiry'    => (int)($plan['max_inquiry'] ?? 0),
    ];
    $extraDetailJson = db_escape(json_encode($extraDetailArray, JSON_UNESCAPED_UNICODE));

    $insertSubSql = "INSERT INTO company_subscription_plan (
                        company_id, plan_id, plan_from, plan_to, extra_detail, 
                        plan_expiry_date, subscription_status, platform, 
                        created_by, created_at, updated_at
                    ) VALUES (
                        $newCompanyId, $planId, '$startDate', '$endDate', '$extraDetailJson',
                        '$expiryDate', 'active', NULL, $userId, NOW(), NOW()
                    )";
    db_query($insertSubSql);

    // 8. Update Lead to Converted & link converted company ID
    db_query("UPDATE lead SET `lead_stage` = 'converted', `converted_company_id` = $newCompanyId, `updated_by` = $userId, `updated_at` = NOW() WHERE `id` = $leadId");

    // Construct direct share message for Superadmin (WhatsApp / Copy) without email sending
    $waText = urlencode("Hello $personName,\n\nWelcome to our platform! Your workspace for *$companyName* is ready.\n\nLogin URL: " . SITE_URL . "\nEmail: $email\nPassword: $passwordRaw\n\nPlan: " . ($plan['name'] ?? '') . " (Valid for $planDays days).\n\nThank you!");

    echo json_encode([
        'status' => true,
        'message' => "Success! Company '$companyName' created & activated on " . htmlspecialchars($plan['name']) . ".",
        'data' => [
            'company_name' => $companyName,
            'person_name'  => $personName,
            'plan_name'    => $plan['name'] ?? '',
            'email'        => $email,
            'password'     => $passwordRaw,
            'mobile_no'    => $mobileNo,
            'login_url'    => SITE_URL,
            'wa_link'      => "https://wa.me/" . preg_replace('/[^0-9]/', '', $mobileNo) . "?text=" . $waText
        ]
    ]);
    exit;
}

$allLeadStatuses = db_rows("SELECT id, name, color FROM lead_status WHERE status = 1 ORDER BY id ASC");

handle_datatable([
    'table'                => $tbl,
    'search_columns'       => ['business_name', 'contact_name', 'mobile_no', 'email', 'lead_number'],
    'order_columns'        => $columns,
    'default_order_column' => 'id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $allLeadStatuses) {
        $encId = encrypt_id($row['id']);

        // Stage label and dropdown styling with active status color
        $stageVal = $row['lead_stage'] ?? '';
        $stageSlug = get_lead_stage_slug($stageVal);
        $stageColor = get_lead_status_color($stageVal);
        if (empty($stageColor)) {
            $stageColor = '#4f46e5';
        }

        // URL encoded SVG chevron with matching stage color
        $svgColorEsc = str_replace('#', '%23', $stageColor);
        $chevronSvg = "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='{$svgColorEsc}' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e\")";

        // Build premium pill badge select for Lead Status
        $stageDropdown = "<div class='lead-status-select-wrap'>";
        $stageDropdown .= "<select class='lead-status-badge-select lead-stage-inline-select' 
                            data-id='{$row['id']}' 
                            data-current='" . htmlspecialchars($stageVal, ENT_QUOTES) . "' 
                            style=\"color: {$stageColor}; background-color: {$stageColor}1F; border-color: {$stageColor}4D; background-image: {$chevronSvg};\">";

        $matched = false;
        foreach ($allLeadStatuses as $st) {
            $isSel = ((string)$stageVal === (string)$st['id'] || strtolower((string)$stageVal) === strtolower($st['name']));
            if ($isSel) $matched = true;
            $selAttr = $isSel ? 'selected' : '';
            $stColor = !empty($st['color']) ? $st['color'] : '#4f46e5';
            $stageDropdown .= "<option value='{$st['id']}' data-color='{$stColor}' {$selAttr}>";
            $stageDropdown .= htmlspecialchars($st['name']);
            $stageDropdown .= "</option>";
        }

        // If current value is not in active statuses (e.g. legacy string), display as custom selected option
        if (!$matched && !empty($stageVal)) {
            $stageText = get_lead_stage_label($stageVal);
            $stageDropdown .= "<option value='" . htmlspecialchars($stageVal, ENT_QUOTES) . "' data-color='{$stageColor}' selected>" . htmlspecialchars($stageText) . "</option>";
        }
        $stageDropdown .= "</select></div>";

        // Plan Name
        $planName = '-';
        if (!empty($row['interested_plan_id'])) {
            $planRow = db_row("SELECT name FROM plan WHERE id = " . (int)$row['interested_plan_id'] . " LIMIT 1");
            if (!empty($planRow['name'])) {
                $planName = htmlspecialchars($planRow['name']);
            }
        }

        // Custom action dropdown items
        $extraItems = [];

        // Follow-up & Reminders options
        if ($stageSlug !== 'converted') {
            $extraItems[] = '<li>
                <a class="dropdown-item text-primary fw-medium btn-followup-lead-action" href="javascript:void(0);" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['business_name'], ENT_QUOTES) . '" data-stage="' . htmlspecialchars($stageVal, ENT_QUOTES) . '">
                   <i data-lucide="plus-circle" class="fs-14 align-middle me-1"></i> Add Follow-up & Reminder
                </a>
            </li>';
        }

        // Dedicated Follow-up History Page option
        $extraItems[] = '<li>
            <a class="dropdown-item text-secondary fw-medium" href="' . SITE_URL . 'lead/followup-history/' . $encId . '">
               <i data-lucide="history" class="fs-14 align-middle me-1 text-info"></i> Follow-up History
            </a>
        </li>';

        // Convert to Company option (if not yet converted)
        if ($stageSlug !== 'converted') {
            $extraItems[] = '<li>
                <a class="dropdown-item text-success fw-semibold btn-convert-lead-action" href="javascript:void(0);" 
                   data-id="' . (int)$row['id'] . '" 
                   data-name="' . htmlspecialchars($row['business_name'] ?? '', ENT_QUOTES) . '" 
                   data-person="' . htmlspecialchars($row['contact_name'] ?? '', ENT_QUOTES) . '" 
                   data-mobile="' . htmlspecialchars($row['mobile_no'] ?? '', ENT_QUOTES) . '" 
                   data-email="' . htmlspecialchars($row['email'] ?? '', ENT_QUOTES) . '" 
                   data-plan-id="' . (int)($row['interested_plan_id'] ?? 0) . '">
                   <i data-lucide="user-check" class="fs-14 align-middle me-1"></i> Convert to Company
                </a>
            </li>';
        } else {
            $extraItems[] = '<li>
                <a class="dropdown-item text-success" href="' . SITE_URL . 'company">
                   <i data-lucide="building" class="fs-14 align-middle me-1"></i> View in Companies
                </a>
            </li>';
        }

        $demoDateFormatted = (!empty($row['demo_date']) && $row['demo_date'] !== '0000-00-00 00:00:00') 
            ? '<span class="badge bg-light text-dark border px-2 py-1"><i data-lucide="calendar" class="fs-11 align-middle me-1 text-primary"></i>' . date('d-m-Y', strtotime($row['demo_date'])) . '</span>'
            : '<span class="text-muted fs-12">-</span>';

        return [
            $srNo,
            htmlspecialchars($row['lead_number'] ?? ('OI-' . $row['id'])),
            htmlspecialchars($row['business_name'] ?? ''),
            htmlspecialchars($row['contact_name'] ?? ''),
            htmlspecialchars($row['mobile_no'] ?? ''),
            htmlspecialchars($row['email'] ?? '-'),
            htmlspecialchars(get_lead_team_size_label($row['team_size'] ?? 1)),
            $demoDateFormatted,
            $stageDropdown,
            dt_action_dropdown($encId, $tbl, [
                'can_edit'     => true,
                'can_delete'   => true,
                'is_encrypted' => true,
                'edit_url'     => SITE_URL . 'lead/edit/' . $encId,
                'extra_items'  => $extraItems
            ])
        ];
    }
]);
