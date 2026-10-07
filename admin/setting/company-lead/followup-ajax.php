<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$tbl = 'company_lead_followups';
$filterLeadId = (int)($_GET['lead_id'] ?? ($_POST['lead_id'] ?? 0));

$baseConditions = [];

// Company restriction: If superadmin, see all company followups. If not superadmin, only see own company's followups.
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "clf.company_id = " . (int)$_SESSION['company_id'];
}

// Lead restriction if specific lead is selected
if ($filterLeadId > 0) {
    $baseConditions[] = "clf.lead_id = $filterLeadId";
}

// User restriction: If standard user, only see follow-ups of leads assigned to them (or created by them)
$userType = $_SESSION['user_type'] ?? '';
$currentUserId = getCurrentUserId();
if ($userType === 'user' && $currentUserId > 0) {
    $baseConditions[] = "(cl.assigned_to = $currentUserId OR clf.created_by = $currentUserId)";
}

if ($isSuperadmin) {
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'cl.customer_name',
        3 => 'lft.name',
        4 => 'clf.followup_date',
        5 => 'fr.name',
        6 => 'clf.inquiry_status',
        7 => 'clf.remarks',
        8 => 'clf.response',
        9 => 'clf.followup_status',
        10 => 'u.name',
        11 => 'clf.created_at',
        12 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'cl.customer_name',
        2 => 'lft.name',
        3 => 'clf.followup_date',
        4 => 'fr.name',
        5 => 'clf.inquiry_status',
        6 => 'clf.remarks',
        7 => 'clf.response',
        8 => 'clf.followup_status',
        9 => 'u.name',
        10 => 'clf.created_at',
        11 => null
    ];
}

$joins = "LEFT JOIN company_lead cl ON cl.id = clf.lead_id 
          LEFT JOIN company c ON c.id = clf.company_id 
          LEFT JOIN lead_followup_type lft ON lft.id = clf.followup_type_id 
          LEFT JOIN followup_reason fr ON fr.id = clf.reason_id 
          LEFT JOIN users u ON u.id = clf.created_by 
          LEFT JOIN marketing_status msl ON (msl.id = clf.inquiry_status OR msl.name = clf.inquiry_status) AND msl.type = 'Lead' AND (msl.company_id = clf.company_id OR msl.company_id = 0)";

$select = "clf.*, 
           cl.inquiry_no, cl.customer_name, cl.contact_person, cl.mobile_no, cl.whatsapp_no, cl.email,
           c.name as company_name, 
           lft.name as followup_through_name, 
           fr.name as reason_name, 
           u.name as creator_name,
           msl.name as lead_status_display, msl.color as lead_color";

handle_datatable([
    'table'                => "$tbl clf",
    'joins'                => $joins,
    'select'               => $select,
    'search_columns'       => ['clf.remarks', 'clf.inquiry_status', 'clf.followup_status', 'cl.inquiry_no', 'cl.customer_name', 'cl.mobile_no', 'c.name', 'lft.name', 'fr.name', 'u.name'],
    'order_columns'        => $columns,
    'default_order_column' => 'clf.followup_date',
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

        // Customer info with link
        $leadEncId = encrypt_id($row['lead_id']);
        $custHtml = '<div>';
        $custHtml .= '<a href="' . SITE_URL . 'company-lead/view/' . $leadEncId . '" class="fw-semibold text-primary text-decoration-none d-block fs-13">';
        $custHtml .= htmlspecialchars($row['customer_name'] ?? 'Lead #' . $row['lead_id']);
        $custHtml .= '</a>';
        $custHtml .= '<small class="text-muted fs-11">' . htmlspecialchars($row['inquiry_no'] ?: ('INQ-' . $row['lead_id']));
        if (!empty($row['mobile_no'])) {
            $custHtml .= ' | ' . htmlspecialchars($row['mobile_no']);
        }
        $custHtml .= '</small></div>';

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

        // Inquiry Status Badge
        $inqColor = !empty($row['lead_color']) ? $row['lead_color'] : '#6c757d';
        $inqLabel = !empty($row['lead_status_display']) ? $row['lead_status_display'] : ($row['inquiry_status'] ?: '-');
        $inqBadge = '<span class="badge fs-11 px-2 py-1 rounded-pill" style="background-color: ' . $inqColor . '26; color: ' . $inqColor . '; border: 1px solid ' . $inqColor . '4D;">' . htmlspecialchars($inqLabel) . '</span>';

        // Followup Status (Pending/Open vs Completed)
        $rawStatus = strtolower(trim((string)($row['followup_status'] ?? 'pending')));
        $isPending = in_array($rawStatus, ['pending', 'open', '3', '']);
        $isCompleted = in_array($rawStatus, ['completed', 'close', 'closed', '4', 'end']);

        if ($isCompleted) {
            $statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle fs-11 px-2 py-1"><i data-lucide="check" class="fs-10 align-middle me-1"></i>Completed</span>';
        } else {
            $statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11 px-2 py-1">Pending</span>';
        }

        // Remarks
        $remarksHtml = '<div class="text-wrap" style="max-width: 280px; font-size: 13px;">' . nl2br(htmlspecialchars($row['remarks'] ?? '')) . '</div>';

        // Followup Response
        $responseHtml = !empty($row['response']) ? '<div class="text-wrap text-success" style="max-width: 280px; font-size: 13px;">' . nl2br(htmlspecialchars($row['response'])) . '</div>' : '<span class="text-muted fs-12">-</span>';

        // Created by
        $creatorName = !empty($row['creator_name']) ? htmlspecialchars($row['creator_name']) : '-';

        // Action Buttons: Edit, Delete, Response (if pending) matching lead follow-up history style
        $inquiryNo = !empty($row['inquiry_no']) ? $row['inquiry_no'] : ('INQ-' . $row['lead_id']);
        $inqDate = !empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : date('d-m-Y');
        $custName = htmlspecialchars($row['customer_name'] ?? ('Lead #' . $row['lead_id']), ENT_QUOTES);
        $contactPerson = htmlspecialchars($row['contact_person'] ?? '-', ENT_QUOTES);
        $mobileNo = htmlspecialchars($row['mobile_no'] ?? '-', ENT_QUOTES);
        $whatsappNo = htmlspecialchars($row['whatsapp_no'] ?? ($row['mobile_no'] ?? '-'), ENT_QUOTES);
        $companyName = htmlspecialchars($row['company_name'] ?? '-', ENT_QUOTES);

        $actionsHtml = '<div class="d-flex align-items-center justify-content-center gap-1">';
        // Edit button (Only shown when status is pending)
        if ($isPending) {
            $actionsHtml .= '<button type="button" class="btn btn-outline-primary btn-sm btn-icon btn-edit-company-fu" data-id="' . (int)$row['id'] . '" title="Edit Follow-up"><i data-lucide="edit" class="fs-14"></i></button>';
        }

        // Delete button
        $actionsHtml .= '<button type="button" class="btn btn-outline-danger btn-sm btn-icon btn-delete-company-fu" data-id="' . (int)$row['id'] . '" title="Delete Follow-up"><i data-lucide="trash-2" class="fs-14"></i></button>';

        // Response button (Only shown when status is pending, hidden when completed)
        if ($isPending) {
            $actionsHtml .= '<button type="button" class="btn btn-outline-success btn-sm btn-icon btn-open-response-modal" ' .
                'data-id="' . (int)$row['id'] . '" ' .
                'data-lead-id="' . (int)$row['lead_id'] . '" ' .
                'data-company-id="' . (int)$row['company_id'] . '" ' .
                'data-inquiry="' . htmlspecialchars($inquiryNo, ENT_QUOTES) . '" ' .
                'data-inquiry-date="' . htmlspecialchars($inqDate, ENT_QUOTES) . '" ' .
                'data-company-name="' . $companyName . '" ' .
                'data-cust-name="' . $custName . '" ' .
                'data-contact-person="' . $contactPerson . '" ' .
                'data-mobile="' . $mobileNo . '" ' .
                'data-whatsapp="' . $whatsappNo . '" ' .
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
                $inqBadge,
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
                $inqBadge,
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
