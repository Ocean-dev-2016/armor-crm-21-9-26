<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$userId = getCurrentUserId();
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// Handle AJAX POST Actions: add_followup, get_followup, update_followup, delete_followup, submit_followup_response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 1. ADD CUSTOMER FOLLOWUP
    if ($_POST['action'] === 'add_customer_followup') {
        $customerId       = (int)($_POST['customer_id'] ?? 0);
        $followupDateTime = trim($_POST['followup_date_time'] ?? '');
        $reasonId         = (int)($_POST['reason_id'] ?? 0);
        $followupTypeId   = (int)($_POST['followup_type_id'] ?? 0);
        $remarks          = trim($_POST['followup_details'] ?? '');
        $fuStatus         = trim($_POST['followup_status'] ?? 'pending');

        if ($customerId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid customer.']);
            exit;
        }

        // Verify customer
        $cust = db_row("SELECT * FROM customer WHERE id = $customerId LIMIT 1");
        if (!$cust) {
            echo json_encode(['status' => false, 'message' => 'Customer not found.']);
            exit;
        }

        $custCompanyId = (int)$cust['company_id'];
        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ($custCompanyId !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action for this company customer.']);
                exit;
            }
        }

        // Parse datetime (e.g. "d-m-Y h:i A" or "Y-m-d H:i:s")
        $parsedDateTime = DateTime::createFromFormat('d-m-Y h:i A', $followupDateTime);
        if (!$parsedDateTime) {
            $parsedDateTime = DateTime::createFromFormat('d-m-Y H:i', $followupDateTime);
        }
        if (!$parsedDateTime) {
            $parsedDateTime = DateTime::createFromFormat('d-m-Y', $followupDateTime);
        }
        $formattedSqlDt = $parsedDateTime ? $parsedDateTime->format('Y-m-d H:i:s') : (!empty($followupDateTime) ? date('Y-m-d H:i:s', strtotime($followupDateTime)) : date('Y-m-d H:i:s'));

        $escRemarks  = db_escape($remarks);
        $escFuStatus = db_escape(!empty($fuStatus) ? $fuStatus : 'pending');

        $insSql = "INSERT INTO `customer_followups` (
            `company_id`, `customer_id`, `followup_date`, `reason_id`, `followup_type_id`, `remarks`, `followup_status`, `created_by`, `created_at`, `updated_at`
        ) VALUES (
            $custCompanyId, $customerId, '$formattedSqlDt', $reasonId, $followupTypeId, '$escRemarks', '$escFuStatus', $userId, NOW(), NOW()
        )";
        $inserted = db_query($insSql);

        if ($inserted) {
            echo json_encode(['status' => true, 'message' => 'Follow-up added successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to save follow-up: ' . mysqli_error($GLOBALS['conn'])]);
        }
        exit;
    }

    // 2. GET CUSTOMER FOLLOWUP FOR EDIT
    if ($_POST['action'] === 'get_customer_followup') {
        $fuId = (int)($_POST['followup_id'] ?? 0);
        if ($fuId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
            exit;
        }

        $fu = db_row("SELECT cf.*, cust.name as customer_name, cust.mobile_no, cust.email, cust.address, c.name as company_name 
                      FROM customer_followups cf 
                      LEFT JOIN customer cust ON cust.id = cf.customer_id 
                      LEFT JOIN company c ON c.id = cf.company_id 
                      WHERE cf.id = $fuId LIMIT 1");
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

    // 3. UPDATE CUSTOMER FOLLOWUP
    if ($_POST['action'] === 'update_customer_followup') {
        $fuId             = (int)($_POST['followup_id'] ?? 0);
        $followupDateTime = trim($_POST['followup_date_time'] ?? '');
        $reasonId         = (int)($_POST['reason_id'] ?? 0);
        $followupTypeId   = (int)($_POST['followup_type_id'] ?? 0);
        $remarks          = trim($_POST['followup_details'] ?? '');

        if ($fuId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
            exit;
        }

        $fu = db_row("SELECT * FROM customer_followups WHERE id = $fuId LIMIT 1");
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
        $formattedSqlDt = $parsedDateTime ? $parsedDateTime->format('Y-m-d H:i:s') : (!empty($followupDateTime) ? date('Y-m-d H:i:s', strtotime($followupDateTime)) : date('Y-m-d H:i:s'));

        $escRemarks = db_escape($remarks);

        $upd = db_query("UPDATE `customer_followups` SET 
            `followup_date`    = '$formattedSqlDt',
            `reason_id`        = $reasonId,
            `followup_type_id` = $followupTypeId,
            `remarks`          = '$escRemarks',
            `updated_by`       = $userId,
            `updated_at`       = NOW()
            WHERE `id` = $fuId");

        if ($upd) {
            echo json_encode(['status' => true, 'message' => 'Follow up updated successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to update follow up.']);
        }
        exit;
    }

    // 4. DELETE CUSTOMER FOLLOWUP
    if ($_POST['action'] === 'delete_customer_followup') {
        $fuId = (int)($_POST['followup_id'] ?? 0);
        if ($fuId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid follow-up ID.']);
            exit;
        }

        $fu = db_row("SELECT * FROM customer_followups WHERE id = $fuId LIMIT 1");
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

        $del = db_query("DELETE FROM `customer_followups` WHERE id = $fuId");
        if ($del) {
            echo json_encode(['status' => true, 'message' => 'Follow up deleted successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to delete follow up.']);
        }
        exit;
    }

    // 5. SUBMIT CUSTOMER FOLLOWUP RESPONSE
    if ($_POST['action'] === 'submit_customer_followup_response') {
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

        $fu = db_row("SELECT cf.*, cust.company_id as cust_company_id FROM customer_followups cf 
                      LEFT JOIN customer cust ON cust.id = cf.customer_id 
                      WHERE cf.id = $fuId LIMIT 1");
        if (empty($fu)) {
            echo json_encode(['status' => false, 'message' => 'Follow-up record not found.']);
            exit;
        }

        $customerId = (int)$fu['customer_id'];
        $companyId  = (int)($fu['company_id'] ?: $fu['cust_company_id']);

        if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
            if ($companyId !== (int)$_SESSION['company_id']) {
                echo json_encode(['status' => false, 'message' => 'Unauthorized action.']);
                exit;
            }
        }

        // 1. Mark current follow-up as completed and save response
        $escResponseRemarks = db_escape($responseRemarks);

        db_query("UPDATE `customer_followups` SET 
            `followup_status` = 'completed',
            `response`        = '$escResponseRemarks',
            `updated_by`      = $userId,
            `updated_at`      = NOW()
            WHERE `id` = $fuId");

        // 2. If action is next-followup, insert next follow-up record
        if ($fuAction === 'next-followup') {
            $nextDateRaw   = trim($_POST['next_followup_date'] ?? '');
            $nextReasonId  = (int)($_POST['next_reason_id'] ?? 0);
            $nextTypeId    = (int)($_POST['next_followup_type_id'] ?? 0);
            $nextDiscuss   = trim($_POST['next_discuss_topic'] ?? '');

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
            $nextSqlDt = $parsedDate ? $parsedDate->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($nextDateRaw));

            $nextRemarksEsc = db_escape($nextDiscuss ?: $responseRemarks);

            $insNextSql = "INSERT INTO `customer_followups` (
                `company_id`, `customer_id`, `followup_date`, `reason_id`, `followup_type_id`, `remarks`, `followup_status`, `created_by`, `created_at`, `updated_at`
            ) VALUES (
                $companyId, $customerId, '$nextSqlDt', $nextReasonId, $nextTypeId, '$nextRemarksEsc', 'pending', $userId, NOW(), NOW()
            )";
            db_query($insNextSql);
        }

        echo json_encode(['status' => true, 'message' => 'Follow up response submitted successfully!']);
        exit;
    }

    echo json_encode(['status' => false, 'message' => 'Unknown action.']);
    exit;
}

// -------------------------------------------------------------
// Server-Side DataTable for Customer Follow Up List
// -------------------------------------------------------------
$tbl = 'customer_followups';
$filterCustId = (int)($_GET['customer_id'] ?? ($_POST['customer_id'] ?? 0));

$baseConditions = [];

// Company restriction: If superadmin, see all. Else see own company.
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "cf.company_id = " . (int)$_SESSION['company_id'];
}

// Customer restriction if filtered
if ($filterCustId > 0) {
    $baseConditions[] = "cf.customer_id = $filterCustId";
}

// User restriction: If standard user, only see follow-ups created by them
$userType = $_SESSION['user_type'] ?? '';
if ($userType === 'user' && $userId > 0) {
    $baseConditions[] = "cf.created_by = $userId";
}

if ($isSuperadmin) {
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'cust.name',
        3 => 'lft.name',
        4 => 'cf.followup_date',
        5 => 'fr.name',
        6 => 'cf.remarks',
        7 => 'cf.response',
        8 => 'cf.followup_status',
        9 => 'u.name',
        10 => 'cf.created_at',
        11 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'cust.name',
        2 => 'lft.name',
        3 => 'cf.followup_date',
        4 => 'fr.name',
        5 => 'cf.remarks',
        6 => 'cf.response',
        7 => 'cf.followup_status',
        8 => 'u.name',
        9 => 'cf.created_at',
        10 => null
    ];
}

$joins = "LEFT JOIN customer cust ON cust.id = cf.customer_id 
          LEFT JOIN customer_type ct ON ct.id = cust.customer_type_id 
          LEFT JOIN company c ON c.id = cf.company_id 
          LEFT JOIN lead_followup_type lft ON lft.id = cf.followup_type_id 
          LEFT JOIN customer_followup_reason cfr ON cfr.id = cf.reason_id 
          LEFT JOIN followup_reason fr ON fr.id = cf.reason_id 
          LEFT JOIN users u ON u.id = cf.created_by";

$select = "cf.*, 
           cust.name as customer_name, cust.mobile_no, cust.email, cust.address,
           ct.name as customer_type_name,
           c.name as company_name, 
           lft.name as followup_through_name, 
           COALESCE(cfr.name, fr.name) as reason_name, 
           u.name as creator_name";

handle_datatable([
    'table'                => "$tbl cf",
    'joins'                => $joins,
    'select'               => $select,
    'search_columns'       => ['cf.remarks', 'cf.response', 'cf.followup_status', 'cust.name', 'cust.mobile_no', 'cust.email', 'c.name', 'lft.name', 'fr.name', 'u.name'],
    'order_columns'        => $columns,
    'default_order_column' => 'cf.followup_date',
    'default_order_dir'    => 'DESC',
    'base_conditions'      => $baseConditions,
    'row_callback'         => function ($row, $srNo) use ($isSuperadmin) {
        // Format follow-up date and time
        $fuDateFormatted = '-';
        if (!empty($row['followup_date']) && $row['followup_date'] !== '0000-00-00 00:00:00') {
            $fuDateFormatted = date('d-m-Y h:i A', strtotime($row['followup_date']));
        }

        // Format created date
        $createdFormatted = '-';
        if (!empty($row['created_at']) && $row['created_at'] !== '0000-00-00 00:00:00') {
            $createdFormatted = date('d-m-Y h:i A', strtotime($row['created_at']));
        }

        // Customer details
        $custEncId = encrypt_id($row['customer_id']);
        $custHtml = '<div>';
        $custHtml .= '<a href="' . SITE_URL . 'customer/edit/' . $custEncId . '" class="fw-semibold text-primary text-decoration-none d-block fs-13">';
        $custHtml .= htmlspecialchars($row['customer_name'] ?? 'Customer #' . $row['customer_id']);
        $custHtml .= '</a>';
        $subDetails = [];
        if (!empty($row['customer_type_name'])) {
            $subDetails[] = '<span class="badge bg-light text-dark border fs-10">' . htmlspecialchars($row['customer_type_name']) . '</span>';
        }
        if (!empty($row['mobile_no'])) {
            $subDetails[] = '<span class="text-muted fs-11"><i data-lucide="phone" class="fs-11 align-middle me-1"></i>' . htmlspecialchars($row['mobile_no']) . '</span>';
        }
        if (!empty($subDetails)) {
            $custHtml .= '<div class="mt-1 d-flex flex-wrap align-items-center gap-1">' . implode(' ', $subDetails) . '</div>';
        }
        $custHtml .= '</div>';

        // Followup Through
        $throughName = !empty($row['followup_through_name']) ? $row['followup_through_name'] : '-';
        $throughLower = strtolower($throughName);
        $throughIcon = 'calendar';
        if (strpos($throughLower, 'call') !== false || strpos($throughLower, 'phone') !== false) {
            $throughIcon = 'phone-call';
        } elseif (strpos($throughLower, 'whatsapp') !== false) {
            $throughIcon = 'message-circle';
        } elseif (strpos($throughLower, 'mail') !== false) {
            $throughIcon = 'mail';
        } elseif (strpos($throughLower, 'meet') !== false || strpos($throughLower, 'visit') !== false) {
            $throughIcon = 'users';
        }
        $throughBadge = '<span class="badge bg-light text-dark border fs-11 px-2 py-1"><i data-lucide="' . $throughIcon . '" class="fs-11 align-middle me-1"></i>' . htmlspecialchars($throughName) . '</span>';

        // Reason
        $reasonText = !empty($row['reason_name']) ? htmlspecialchars($row['reason_name']) : '-';

        // Followup Status (Pending vs Completed)
        $rawStatus = strtolower(trim((string)($row['followup_status'] ?? 'pending')));
        $isCompleted = in_array($rawStatus, ['completed', 'close', 'closed', 'end']);

        if ($isCompleted) {
            $statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle fs-11 px-2 py-1"><i data-lucide="check" class="fs-10 align-middle me-1"></i>Completed</span>';
        } else {
            $statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11 px-2 py-1">Pending</span>';
        }

        // Remarks
        $remarksHtml = '<div class="text-wrap" style="max-width: 280px; font-size: 13px;">' . nl2br(htmlspecialchars($row['remarks'] ?? '')) . '</div>';

        // Response
        $responseHtml = !empty($row['response']) ? '<div class="text-wrap text-success" style="max-width: 280px; font-size: 13px;">' . nl2br(htmlspecialchars($row['response'])) . '</div>' : '<span class="text-muted fs-12">-</span>';

        // Creator
        $creatorName = !empty($row['creator_name']) ? htmlspecialchars($row['creator_name']) : '-';

        // Action Buttons: Edit, Delete, Response
        $custName      = htmlspecialchars($row['customer_name'] ?? ('Customer #' . $row['customer_id']), ENT_QUOTES);
        $custMobile    = htmlspecialchars($row['mobile_no'] ?? '-', ENT_QUOTES);
        $custEmail     = htmlspecialchars($row['email'] ?? '-', ENT_QUOTES);
        $custCompany   = htmlspecialchars($row['company_name'] ?? '-', ENT_QUOTES);

        $actionsHtml = '<div class="d-flex align-items-center justify-content-center gap-1">';
        // Edit button (only if pending)
        if (!$isCompleted) {
            $actionsHtml .= '<button type="button" class="btn btn-outline-primary btn-sm btn-icon btn-edit-customer-fu" data-id="' . (int)$row['id'] . '" title="Edit Follow-up"><i data-lucide="edit" class="fs-14"></i></button>';
        }

        // Delete button
        $actionsHtml .= '<button type="button" class="btn btn-outline-danger btn-sm btn-icon btn-delete-customer-fu" data-id="' . (int)$row['id'] . '" title="Delete Follow-up"><i data-lucide="trash-2" class="fs-14"></i></button>';

        // Response button (only if pending)
        if (!$isCompleted) {
            $actionsHtml .= '<button type="button" class="btn btn-outline-success btn-sm btn-icon btn-open-cust-response-modal" ' .
                'data-id="' . (int)$row['id'] . '" ' .
                'data-customer-id="' . (int)$row['customer_id'] . '" ' .
                'data-company-id="' . (int)$row['company_id'] . '" ' .
                'data-cust-name="' . $custName . '" ' .
                'data-mobile="' . $custMobile . '" ' .
                'data-email="' . $custEmail . '" ' .
                'data-company-name="' . $custCompany . '" ' .
                'title="Add Followup Response"><i data-lucide="check" class="fs-14"></i></button>';
        }
        $actionsHtml .= '</div>';

        if ($isSuperadmin) {
            $compName = '<span class="fw-medium text-dark">' . htmlspecialchars($row['company_name'] ?? '-') . '</span>';
            return [
                $srNo,
                $compName,
                $custHtml,
                $throughBadge,
                $fuDateFormatted,
                $reasonText,
                $remarksHtml,
                $responseHtml,
                $statusBadge,
                $creatorName,
                $createdFormatted,
                $actionsHtml
            ];
        } else {
            return [
                $srNo,
                $custHtml,
                $throughBadge,
                $fuDateFormatted,
                $reasonText,
                $remarksHtml,
                $responseHtml,
                $statusBadge,
                $creatorName,
                $createdFormatted,
                $actionsHtml
            ];
        }
    }
]);
