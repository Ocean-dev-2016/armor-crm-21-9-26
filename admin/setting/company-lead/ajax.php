<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$userId = getCurrentUserId();
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// Handle AJAX POST Actions: Add Followup, Assign To, Convert To Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 1. ADD FOLLOWUP
    if ($_POST['action'] === 'add_followup') {
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $followupDateTime = trim($_POST['followup_date_time'] ?? '');
        $reasonId = (int)($_POST['reason_id'] ?? 0);
        $followupTypeId = (int)($_POST['followup_type_id'] ?? 0);
        $remarks = trim($_POST['followup_details'] ?? '');
        $fuStatus = trim($_POST['followup_status'] ?? '');
        $inqStatus = trim($_POST['inquiry_status'] ?? '');

        if ($leadId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid lead.']);
            exit;
        }

        // Verify lead existence and company authorization
        $lead = db_row("SELECT * FROM company_lead WHERE id = $leadId LIMIT 1");
        if (!$lead) {
            echo json_encode(['status' => false, 'message' => 'Lead record not found.']);
            exit;
        }

        $leadCompanyId = (int)$lead['company_id'];
        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ($leadCompanyId !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action for this company lead.']);
                exit;
            }
        }

        // Parse datetime (supports "d-m-Y h:i A" from flatpickr)
        $parsedDateTime = DateTime::createFromFormat('d-m-Y h:i A', $followupDateTime);
        if (!$parsedDateTime) {
            $parsedDateTime = DateTime::createFromFormat('d-m-Y H:i', $followupDateTime);
        }
        if (!$parsedDateTime) {
            $parsedDateTime = DateTime::createFromFormat('d-m-Y', $followupDateTime);
        }
        $formattedSqlDt = $parsedDateTime ? $parsedDateTime->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($followupDateTime));
        $formattedSqlDate = $parsedDateTime ? $parsedDateTime->format('Y-m-d') : date('Y-m-d', strtotime($followupDateTime));

        // Escape fields for SQL insertion
        $escRemarks  = db_escape($remarks);
        $escFuStatus = db_escape($fuStatus ?: ($lead['followup_status'] ?: 'Open'));
        $escInqStatus= db_escape($inqStatus ?: ($lead['inquiry_status'] ?: 'New Lead'));

        $insSql = "INSERT INTO `company_lead_followups` (
            `company_id`, `lead_id`, `followup_date`, `reason_id`, `followup_type_id`, `remarks`, `followup_status`, `inquiry_status`, `created_by`, `created_at`, `updated_at`
        ) VALUES (
            $leadCompanyId, $leadId, '$formattedSqlDt', $reasonId, $followupTypeId, '$escRemarks', '$escFuStatus', '$escInqStatus', $userId, NOW(), NOW()
        )";
        $inserted = db_query($insSql);

        if ($inserted) {
            // Update company_lead with the latest followup details and status
            $updParts = [
                "`followup_date` = '$formattedSqlDate'",
                "`followup_details` = '$escRemarks'",
                "`updated_by` = $userId",
                "`updated_at` = NOW()"
            ];
            if (!empty($fuStatus)) {
                $updParts[] = "`followup_status` = '$escFuStatus'";
            }
            if (!empty($inqStatus)) {
                $updParts[] = "`inquiry_status` = '$escInqStatus'";
            }

            db_query("UPDATE `company_lead` SET " . implode(', ', $updParts) . " WHERE `id` = $leadId");

            echo json_encode(['status' => true, 'message' => 'Follow up added successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to save follow up.']);
        }
        exit;
    }

    // 2. ASSIGN TO
    if ($_POST['action'] === 'assign_to') {
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);

        if ($leadId <= 0 || $assignedTo <= 0) {
            echo json_encode(['status' => false, 'message' => 'Please select user to assign.']);
            exit;
        }

        $lead = db_row("SELECT * FROM company_lead WHERE id = $leadId LIMIT 1");
        if (!$lead) {
            echo json_encode(['status' => false, 'message' => 'Lead not found.']);
            exit;
        }

        $upd = db_query("UPDATE `company_lead` SET `assigned_to` = $assignedTo, `updated_by` = $userId, `updated_at` = NOW() WHERE `id` = $leadId");

        if ($upd) {
            echo json_encode(['status' => true, 'message' => 'Lead assigned successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to assign lead.']);
        }
        exit;
    }

    // 3. CONVERT TO CUSTOMER
    if ($_POST['action'] === 'convert_to_customer') {
        $leadId = (int)($_POST['lead_id'] ?? 0);
        if ($leadId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid lead.']);
            exit;
        }

        $lead = db_row("SELECT * FROM company_lead WHERE id = $leadId LIMIT 1");
        if (!$lead) {
            echo json_encode(['status' => false, 'message' => 'Lead not found.']);
            exit;
        }

        // Update inquiry_status to 'Converted' (store ID if available)
        $convRow = db_row("SELECT id FROM marketing_status WHERE LOWER(name) = 'converted' AND type = 'Lead' LIMIT 1");
        $convVal = $convRow ? (string)$convRow['id'] : 'Converted';
        $upd = db_query("UPDATE `company_lead` SET `inquiry_status` = '$convVal', `updated_by` = $userId, `updated_at` = NOW() WHERE `id` = $leadId");

        if ($upd) {
            // Sync / Insert into customer table with company_lead_id
            syncLeadToCustomer($leadId, $userId);

            echo json_encode(['status' => true, 'message' => 'Lead successfully converted to Customer!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to convert lead.']);
        }
        exit;
    }

    // 3.1 UPDATE INQUIRY STATUS FROM LIST DROPDOWN
    if ($_POST['action'] === 'update_inquiry_status') {
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        if ($leadId <= 0 || empty($newStatus)) {
            echo json_encode(['status' => false, 'message' => 'Invalid parameters.']);
            exit;
        }

        $lead = db_row("SELECT * FROM company_lead WHERE id = $leadId LIMIT 1");
        if (!$lead) {
            echo json_encode(['status' => false, 'message' => 'Lead not found.']);
            exit;
        }

        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ((int)$lead['company_id'] !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action for this lead.']);
                exit;
            }
        }

        // If standard user, verify this lead is assigned to them
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType === 'user' && (int)($lead['assigned_to'] ?? 0) !== $userId) {
            echo json_encode(['status' => false, 'message' => 'You can only update status for leads assigned to you.']);
            exit;
        }

        // Resolve status ID & Name
        $statusId = 0;
        $statusName = $newStatus;
        if (is_numeric($newStatus)) {
            $statusId = (int)$newStatus;
            $stRow = db_row("SELECT name FROM marketing_status WHERE id = $statusId LIMIT 1");
            if ($stRow) {
                $statusName = $stRow['name'];
            }
        } else {
            $stRow = db_row("SELECT id, name FROM marketing_status WHERE name = '" . db_escape($newStatus) . "' AND type = 'Lead' LIMIT 1");
            if ($stRow) {
                $statusId = (int)$stRow['id'];
                $statusName = $stRow['name'];
            }
        }

        $storeStatusVal = ($statusId > 0) ? (string)$statusId : $newStatus;
        $escStatus = db_escape($storeStatusVal);
        $upd = db_query("UPDATE `company_lead` SET `inquiry_status` = '$escStatus', `updated_by` = $userId, `updated_at` = NOW() WHERE `id` = $leadId");

        if ($upd) {
            echo json_encode(['status' => true, 'message' => 'Inquiry status updated successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to update status.']);
        }
        exit;
    }

    // 4. GET COMPANY FOLLOWUP FOR EDIT
    if ($_POST['action'] === 'get_company_followup') {
        $fuId = (int)($_POST['followup_id'] ?? 0);
        if ($fuId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
            exit;
        }

        $fu = db_row("SELECT clf.*, cl.inquiry_no, cl.customer_name, cl.contact_person, cl.mobile_no, cl.whatsapp_no, cl.email, cl.inquiry_date, c.name as company_name 
                      FROM company_lead_followups clf 
                      LEFT JOIN company_lead cl ON cl.id = clf.lead_id 
                      LEFT JOIN company c ON c.id = clf.company_id 
                      WHERE clf.id = $fuId LIMIT 1");
        if (empty($fu)) {
            echo json_encode(['status' => false, 'message' => 'Follow-up record not found.']);
            exit;
        }

        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ((int)$fu['company_id'] !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action for this follow-up.']);
                exit;
            }
        }

        $formattedDate = '';
        if (!empty($fu['followup_date']) && $fu['followup_date'] !== '0000-00-00 00:00:00') {
            $formattedDate = date('d-m-Y h:i A', strtotime($fu['followup_date']));
        }
        $fu['formatted_date'] = $formattedDate;

        echo json_encode(['status' => true, 'data' => $fu]);
        exit;
    }

    // 5. UPDATE COMPANY FOLLOWUP
    if ($_POST['action'] === 'update_company_followup') {
        $fuId             = (int)($_POST['followup_id'] ?? 0);
        $followupDateTime = trim($_POST['followup_date_time'] ?? '');
        $reasonId         = (int)($_POST['reason_id'] ?? 0);
        $followupTypeId   = (int)($_POST['followup_type_id'] ?? 0);
        $remarks          = trim($_POST['followup_details'] ?? '');
        $inqStatus        = trim($_POST['inquiry_status'] ?? '');

        if ($fuId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
            exit;
        }

        $fu = db_row("SELECT * FROM company_lead_followups WHERE id = $fuId LIMIT 1");
        if (empty($fu)) {
            echo json_encode(['status' => false, 'message' => 'Follow-up record not found.']);
            exit;
        }

        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ((int)$fu['company_id'] !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action.']);
                exit;
            }
        }

        $parsedDateTime = DateTime::createFromFormat('d-m-Y h:i A', $followupDateTime);
        if (!$parsedDateTime) {
            $parsedDateTime = DateTime::createFromFormat('d-m-Y H:i', $followupDateTime);
        }
        if (!$parsedDateTime) {
            $parsedDateTime = DateTime::createFromFormat('d-m-Y', $followupDateTime);
        }
        $formattedSqlDt   = $parsedDateTime ? $parsedDateTime->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($followupDateTime));
        $formattedSqlDate = $parsedDateTime ? $parsedDateTime->format('Y-m-d') : date('Y-m-d', strtotime($followupDateTime));

        $escRemarks   = db_escape($remarks);
        $escInqStatus = db_escape($inqStatus ?: ($fu['inquiry_status'] ?: 'New Lead'));

        $upd = db_query("UPDATE company_lead_followups SET 
            `followup_date`    = '$formattedSqlDt',
            `reason_id`        = $reasonId,
            `followup_type_id` = $followupTypeId,
            `remarks`          = '$escRemarks',
            `inquiry_status`   = '$escInqStatus',
            `updated_at`       = NOW()
            WHERE `id` = $fuId");

        if ($upd) {
            // Also update lead's followup date and inquiry status
            $leadId = (int)$fu['lead_id'];
            $leadUpd = [
                "`followup_date` = '$formattedSqlDate'",
                "`followup_details` = '$escRemarks'",
                "`updated_by` = $userId",
                "`updated_at` = NOW()"
            ];
            if (!empty($inqStatus)) {
                $leadUpd[] = "`inquiry_status` = '$escInqStatus'";
            }
            db_query("UPDATE company_lead SET " . implode(', ', $leadUpd) . " WHERE `id` = $leadId");

            echo json_encode(['status' => true, 'message' => 'Follow up updated successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to update follow up.']);
        }
        exit;
    }

    // 6. DELETE COMPANY FOLLOWUP
    if ($_POST['action'] === 'delete_company_followup') {
        $fuId = (int)($_POST['followup_id'] ?? 0);
        if ($fuId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
            exit;
        }

        $fu = db_row("SELECT * FROM company_lead_followups WHERE id = $fuId LIMIT 1");
        if (empty($fu)) {
            echo json_encode(['status' => false, 'message' => 'Follow-up record not found.']);
            exit;
        }

        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ((int)$fu['company_id'] !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action.']);
                exit;
            }
        }

        $del = db_query("DELETE FROM company_lead_followups WHERE id = $fuId");
        if ($del) {
            echo json_encode(['status' => true, 'message' => 'Follow up deleted successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to delete follow up.']);
        }
        exit;
    }

    // 7. SUBMIT COMPANY FOLLOWUP RESPONSE
    if ($_POST['action'] === 'submit_company_followup_response') {
        $fuId            = (int)($_POST['followup_id'] ?? 0);
        $responseRemarks = trim($_POST['response'] ?? '');
        $fuAction        = trim($_POST['followup_action'] ?? ''); // 'next-followup' or 'end-followup'

        if ($fuId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
            exit;
        }

        if (empty($responseRemarks)) {
            echo json_encode(['status' => false, 'message' => 'Please enter response remarks.']);
            exit;
        }

        if (!in_array($fuAction, ['next-followup', 'end-followup'], true)) {
            echo json_encode(['status' => false, 'message' => 'Please select a valid Follow-up Action.']);
            exit;
        }

        $fu = db_row("SELECT clf.*, cl.company_id as lead_company_id FROM company_lead_followups clf 
                      LEFT JOIN company_lead cl ON cl.id = clf.lead_id 
                      WHERE clf.id = $fuId LIMIT 1");
        if (empty($fu)) {
            echo json_encode(['status' => false, 'message' => 'Follow-up record not found.']);
            exit;
        }

        $leadId = (int)$fu['lead_id'];
        $companyId = (int)($fu['company_id'] ?: $fu['lead_company_id']);

        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ($companyId !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action.']);
                exit;
            }
        }

        // 1. Mark current followup as completed and store response in dedicated 'response' column
        $escResponseRemarks = db_escape($responseRemarks);

        db_query("UPDATE company_lead_followups SET 
            `followup_status` = 'completed',
            `response` = '$escResponseRemarks',
            `updated_at` = NOW()
            WHERE `id` = $fuId");

        // 2. If action is next-followup, create next followup record with status 'pending' / 'open'
        if ($fuAction === 'next-followup') {
            $nextDateRaw    = trim($_POST['next_followup_date'] ?? '');
            $nextReasonId   = (int)($_POST['next_reason_id'] ?? 0);
            $nextTypeId     = (int)($_POST['next_followup_type_id'] ?? 0);
            $nextDiscuss    = trim($_POST['next_discuss_topic'] ?? '');
            $nextInqStatus  = trim($_POST['next_inquiry_status'] ?? ($fu['inquiry_status'] ?? 'New Lead'));

            if (empty($nextDateRaw)) {
                echo json_encode(['status' => false, 'message' => 'Please select Next Follow-up Date & Time.']);
                exit;
            }

            $parsedDate = DateTime::createFromFormat('d-m-Y h:i A', $nextDateRaw);
            if (!$parsedDate) {
                $parsedDate = DateTime::createFromFormat('d-m-Y H:i', $nextDateRaw);
            }
            if (!$parsedDate) {
                $parsedDate = DateTime::createFromFormat('d-m-Y', $nextDateRaw);
            }
            $nextSqlDt   = $parsedDate ? $parsedDate->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($nextDateRaw));
            $nextSqlDate = $parsedDate ? $parsedDate->format('Y-m-d') : date('Y-m-d', strtotime($nextDateRaw));

            $nextRemarksEsc = db_escape($nextDiscuss ?: $responseRemarks);
            $nextInqEsc     = db_escape($nextInqStatus);

            $insNextSql = "INSERT INTO `company_lead_followups` (
                `company_id`, `lead_id`, `followup_date`, `reason_id`, `followup_type_id`, `remarks`, `followup_status`, `inquiry_status`, `created_by`, `created_at`, `updated_at`
            ) VALUES (
                $companyId, $leadId, '$nextSqlDt', $nextReasonId, $nextTypeId, '$nextRemarksEsc', 'pending', '$nextInqEsc', $userId, NOW(), NOW()
            )";
            db_query($insNextSql);

            // Update company_lead with the newly scheduled next followup date
            db_query("UPDATE `company_lead` SET 
                `followup_date` = '$nextSqlDate',
                `followup_details` = '$nextRemarksEsc',
                `updated_by` = $userId,
                `updated_at` = NOW()
                WHERE `id` = $leadId");
        } else {
            // End followup: update lead's details
            $escResp = db_escape($responseRemarks);
            db_query("UPDATE `company_lead` SET 
                `followup_details` = '$escResp',
                `updated_by` = $userId,
                `updated_at` = NOW()
                WHERE `id` = $leadId");
        }

        $msg = ($fuAction === 'next-followup') ? 'Response saved and Next Follow-up scheduled successfully!' : 'Follow-up ended and response saved successfully!';
        echo json_encode(['status' => true, 'message' => $msg]);
        exit;
    }
}

$tbl = 'company_lead';
$moduleKey = 'company-lead';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "cl.company_id = " . (int)$_SESSION['company_id'];
}

// If logged in as standard user (team person), show only leads assigned to this user
$userType= $_SESSION['user_type'] ?? '';
if ($userType === 'user' && $userId > 0) {
    $baseConditions[] = "cl.assigned_to = $userId";
}

// Filter by Inquiry Status (via dropdown filter)
$filterInquiryStatus = trim($_POST['inquiry_status'] ?? ($_GET['inquiry_status'] ?? ''));
if ($filterInquiryStatus !== '' && $filterInquiryStatus !== 'all') {
    if (is_numeric($filterInquiryStatus)) {
        $filterStatusId = (int)$filterInquiryStatus;
        // Match either ID or name in marketing_status
        $baseConditions[] = "(cl.inquiry_status = '$filterStatusId' OR msl.id = $filterStatusId)";
    } else {
        $safeInqStatus = db_escape($filterInquiryStatus);
        $baseConditions[] = "(cl.inquiry_status = '$safeInqStatus' OR msl.name = '$safeInqStatus')";
    }
}

$columns = [
    0 => null,
    1 => 'cl.inquiry_no',
    2 => 'cl.inquiry_date',
    3 => 'cl.customer_name',
    4 => 'cl.contact_person',
    5 => 'cl.mobile_no',
    6 => 'soi.name',
    7 => 'u.name',
    8 => 'cl.inquiry_status',
    9 => null
];

$joins = "LEFT JOIN source_of_inquiry soi ON soi.id = cl.source_of_inquiry_id 
          LEFT JOIN users u ON u.id = cl.assigned_to 
          LEFT JOIN marketing_status msl ON (msl.id = cl.inquiry_status OR msl.name = cl.inquiry_status) AND msl.type = 'Lead' AND (msl.company_id = cl.company_id OR msl.company_id = 0)
          LEFT JOIN company c ON c.id = cl.company_id
          LEFT JOIN customer cust ON cust.company_lead_id = cl.id";

$select = "cl.*, soi.name as source_name, u.name as assigned_to_name, msl.name as lead_status_name, msl.color as lead_color, c.name as company_name, cust.id as customer_id";

$allStatuses = db_rows("SELECT id, name, color, company_id FROM marketing_status WHERE status = 1 AND type = 'Lead' ORDER BY order_by ASC, name ASC");

handle_datatable([
    'table'                => "$tbl cl",
    'joins'                => $joins,
    'select'               => $select,
    'base_conditions'      => $baseConditions,
    'search_columns'       => ['cl.inquiry_no', 'cl.customer_name', 'cl.contact_person', 'cl.mobile_no', 'cl.email', 'soi.name', 'u.name', 'cl.inquiry_status', 'msl.name'],
    'order_columns'        => $columns,
    'default_order_column' => 'cl.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $allStatuses, $isSuperadmin, $userType, $userId) {
        $encId = encrypt_id($row['id']);
        $inqDateFormatted = !empty($row['inquiry_date']) ? date('d-m-Y', strtotime($row['inquiry_date'])) : '-';

        $leadColor = !empty($row['lead_color']) ? htmlspecialchars($row['lead_color']) : '#0e5a6c';

        $leadStatusText = !empty($row['lead_status_name']) ? $row['lead_status_name'] : ($row['inquiry_status'] ?? '-');

        // Build inline status dropdown matching the lead's company
        $rowCompanyId = (int)($row['company_id'] ?? 0);
        $statusOptionsHtml = '';
        foreach ($allStatuses as $st) {
            $stCid = (int)($st['company_id'] ?? 0);
            if (!$isSuperadmin && $rowCompanyId > 0 && $stCid !== 0 && $stCid !== $rowCompanyId) {
                continue;
            }
            $isSel = (strcasecmp($st['name'], $leadStatusText) === 0 || (string)$st['id'] === (string)($row['inquiry_status'] ?? '')) ? 'selected' : '';
            $stColor = !empty($st['color']) ? htmlspecialchars($st['color']) : '#495057';
            $statusOptionsHtml .= '<option value="' . (int)$st['id'] . '" data-name="' . htmlspecialchars($st['name'], ENT_QUOTES) . '" data-color="' . $stColor . '" style="color: ' . $stColor . '; font-weight: 600; background-color: #ffffff;" ' . $isSel . '>' . htmlspecialchars($st['name']) . '</option>';
        }

        $hasCustomer = !empty($row['customer_id']) && (int)$row['customer_id'] > 0;
        $isAssignedToCurrentUser = ($userType === 'user' && (int)($row['assigned_to'] ?? 0) === $userId);
        $canChangeStatus = ($canEdit || $isAssignedToCurrentUser);

        // If customer already created for this lead, show static badge/label 'Converted' instead of dropdown
        if ($hasCustomer) {
            $convertedColor = !empty($row['lead_color']) ? htmlspecialchars($row['lead_color']) : '#0e5a6c';
            $leadBadge = '<span class="badge border" style="background-color: ' . $convertedColor . '1a; color: ' . $convertedColor . '; border-color: ' . $convertedColor . '40 !important; font-size: 12px; padding: 5px 12px; border-radius: 20px;">' . htmlspecialchars($leadStatusText ?: 'Converted') . '</span>';
        } elseif ($canChangeStatus) {
            $leadBadge = '
            <div class="d-inline-block position-relative" style="min-width: 130px;">
                <select class="form-select form-select-sm fw-semibold shadow-none lead-status-select" 
                        data-id="' . (int)$row['id'] . '" 
                        data-original="' . htmlspecialchars($leadStatusText, ENT_QUOTES) . '"
                        style="background-color: ' . $leadColor . '15; color: ' . $leadColor . '; border: 1.5px solid ' . $leadColor . '60 !important; border-radius: 20px; font-size: 12px; padding: 3px 24px 3px 10px; cursor: pointer; height: auto;">
                    ' . $statusOptionsHtml . '
                </select>
            </div>';
        } else {
            $leadBadge = '<span class="badge border" style="background-color: ' . $leadColor . '1a; color: ' . $leadColor . '; border-color: ' . $leadColor . '40 !important;">' . htmlspecialchars($leadStatusText) . '</span>';
        }

        // Prepare data attributes for Add Followup Modal (matching User's 2nd image)
        $dataAttrs = ' data-id="' . (int)$row['id'] . '"' .
                     ' data-company-id="' . (int)$row['company_id'] . '"' .
                     ' data-inquiry-no="' . htmlspecialchars($row['inquiry_no'] ?? '', ENT_QUOTES) . '"' .
                     ' data-inquiry-date="' . htmlspecialchars($inqDateFormatted, ENT_QUOTES) . '"' .
                     ' data-company-name="' . htmlspecialchars($row['company_name'] ?? '', ENT_QUOTES) . '"' .
                     ' data-customer-name="' . htmlspecialchars($row['customer_name'] ?? '', ENT_QUOTES) . '"' .
                     ' data-contact-person="' . htmlspecialchars($row['contact_person'] ?? '', ENT_QUOTES) . '"' .
                     ' data-mobile-no="' . htmlspecialchars($row['mobile_no'] ?? '', ENT_QUOTES) . '"' .
                     ' data-whatsapp-no="' . htmlspecialchars($row['whatsapp_no'] ?? '', ENT_QUOTES) . '"' .
                     ' data-email="' . htmlspecialchars($row['email'] ?? '', ENT_QUOTES) . '"' .
                     ' data-inquiry-status="' . htmlspecialchars($row['inquiry_status'] ?? '', ENT_QUOTES) . '"' .
                     ' data-inquiry-status-name="' . htmlspecialchars($leadStatusText, ENT_QUOTES) . '"' .
                     ' data-assigned-to="' . (int)($row['assigned_to'] ?? 0) . '"';

        $actionDropdownHtml = '
        <div class="dropdown">
            <button class="btn btn-icon btn-sm btn-light" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                <i data-lucide="more-horizontal" class="fs-16"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                ' . ($canEdit && !$hasCustomer ? '<li>
                    <a class="dropdown-item text-primary" href="' . SITE_URL . 'company-lead/edit/' . $encId . '">
                       <i data-lucide="edit" class="fs-14 align-middle me-1"></i> Edit
                    </a>
                </li>' : '') . '
                <li>
                    <a class="dropdown-item text-secondary" href="' . SITE_URL . 'company-lead/view/' . $encId . '">
                       <i data-lucide="eye" class="fs-14 align-middle me-1"></i> View
                    </a>
                </li>
                ' . (!$hasCustomer ? '<li>
                    <a class="dropdown-item text-dark btn-convert-customer" href="javascript:void(0);" ' . $dataAttrs . '>
                       <i data-lucide="user-check" class="fs-14 align-middle me-1"></i> Convert to Customer
                    </a>
                </li>' : '') . '
                ' . (!$hasCustomer ? '<li>
                    <a class="dropdown-item text-dark btn-add-followup" href="javascript:void(0);" ' . $dataAttrs . '>
                       <i data-lucide="plus" class="fs-14 align-middle me-1"></i> Add Followup
                    </a>
                </li>' : '') . '
                <li>
                    <a class="dropdown-item text-dark" href="' . SITE_URL . 'company-lead-followup?lead_id=' . $encId . '">
                       <i data-lucide="history" class="fs-14 align-middle me-1"></i> Follow-up History
                    </a>
                </li>
                ' . (!$hasCustomer ? '<li>
                    <a class="dropdown-item text-dark btn-assign-to" href="javascript:void(0);" ' . $dataAttrs . '>
                       <i data-lucide="user" class="fs-14 align-middle me-1"></i> Assign to
                    </a>
                </li>' : '') . '
                ' . ($canDelete ? '<li>
                    <a class="dropdown-item text-danger delete-record" href="javascript:void(0);" data-id="' . $encId . '" data-tbl="' . htmlspecialchars($tbl) . '">
                       <i data-lucide="trash-2" class="fs-14 align-middle me-1"></i> Delete
                    </a>
                </li>' : '') . '
            </ul>
        </div>';

        return [
            $srNo,
            '<span class="fw-semibold text-primary">' . htmlspecialchars($row['inquiry_no'] ?? '-') . '</span>',
            $inqDateFormatted,
            htmlspecialchars($row['customer_name'] ?? '-'),
            htmlspecialchars($row['contact_person'] ?? '-'),
            htmlspecialchars($row['mobile_no'] ?? '-'),
            htmlspecialchars($row['source_name'] ?? '-'),
            htmlspecialchars($row['assigned_to_name'] ?? '-'),
            $leadBadge,
            $actionDropdownHtml
        ];
    }
]);
