<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
if (!$isSuperadmin) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$tbl = 'lead_followups';

$leadId = (int)($_GET['lead_id'] ?? ($_POST['lead_id'] ?? 0));

$baseConditions = [];
if ($leadId > 0) {
    $baseConditions[] = "f.lead_id = $leadId";
}

if ($leadId > 0) {
    $columns = [
        0 => null,
        1 => 'f.followup_type',
        2 => 'f.followup_date',
        3 => 'f.stage',
        4 => 'f.remarks',
        5 => 'f.response',
        6 => 'f.reminder_status',
        7 => 'f.created_by',
        8 => 'f.created_at',
        9 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'l.business_name',
        2 => 'f.followup_type',
        3 => 'f.followup_date',
        4 => 'f.stage',
        5 => 'f.remarks',
        6 => 'f.response',
        7 => 'f.reminder_status',
        8 => 'f.created_by',
        9 => 'f.created_at',
        10 => null
    ];
}

handle_datatable([
    'table'                => 'lead_followups f',
    'joins'                => 'LEFT JOIN lead l ON l.id = f.lead_id',
    'select'               => 'f.*, l.business_name, l.contact_name, l.mobile_no, l.whatsapp_no, l.lead_number, l.lead_stage, l.created_at AS lead_created_at',
    'search_columns'       => ['f.remarks', 'f.response', 'f.stage', 'f.followup_type', 'l.business_name', 'l.contact_name', 'l.mobile_no'],
    'order_columns'        => $columns,
    'default_order_column' => 'f.followup_date',
    'default_order_dir'    => 'DESC',
    'base_conditions'      => $baseConditions,
    'row_callback'         => function ($row, $srNo) use ($tbl, $leadId) {
        $typeVal = $row['followup_type'] ?? '';
        $typeName = get_lead_followup_type_label($typeVal);

        $typeLower = strtolower($typeName);
        $typeIcon = 'phone-call';
        if (strpos($typeLower, 'whatsapp') !== false) $typeIcon = 'message-circle';
        elseif (strpos($typeLower, 'demo') !== false) $typeIcon = 'monitor';
        elseif (strpos($typeLower, 'meet') !== false) $typeIcon = 'users';
        elseif (strpos($typeLower, 'mail') !== false || strpos($typeLower, 'note') !== false) $typeIcon = 'mail';

        $typeBadge = '<span class="badge bg-primary-subtle text-primary fs-11 px-2 py-1"><i data-lucide="' . $typeIcon . '" class="fs-11 align-middle me-1"></i>' . htmlspecialchars($typeName) . '</span>';

        $dateFormatted = '-';
        if (!empty($row['followup_date']) && $row['followup_date'] !== '0000-00-00 00:00:00') {
            $dateFormatted = date('d-m-Y h:i A', strtotime($row['followup_date']));
        }

        $createdAtFormatted = '-';
        if (!empty($row['created_at']) && $row['created_at'] !== '0000-00-00 00:00:00') {
            $createdAtFormatted = date('d-m-Y h:i A', strtotime($row['created_at']));
        }

        $stageColor = get_lead_status_color($row['stage'] ?? '');
        $stageLabel = get_lead_stage_label($row['stage'] ?? '');
        $stageBadge = '<span class="badge fs-11 px-2 py-1 rounded-pill" style="background-color: ' . $stageColor . '26; color: ' . $stageColor . '; border: 1px solid ' . $stageColor . '4D;">' . htmlspecialchars($stageLabel) . '</span>';

        $statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11 px-2 py-1">Pending</span>';
        if ($row['reminder_status'] === 'completed') {
            $statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle fs-11 px-2 py-1"><i data-lucide="check" class="fs-10 align-middle me-1"></i>Completed</span>';
        } elseif ($row['reminder_status'] === 'missed') {
            $statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-11 px-2 py-1">Missed</span>';
        }

        // Creator name
        $userName = '-';
        if (!empty($row['created_by'])) {
            $uRow = db_row("SELECT name FROM users WHERE id = " . (int)$row['created_by'] . " LIMIT 1");
            if (!empty($uRow['name'])) {
                $userName = htmlspecialchars($uRow['name']);
            }
        }

        // Action buttons: Edit (only if pending), Response (Check mark if pending), and Delete
        $actionsHtml = '<div class="d-flex align-items-center justify-content-center gap-1">';
        if ($row['reminder_status'] === 'pending') {
            $actionsHtml .= '<button type="button" class="btn btn-outline-primary btn-sm btn-icon btn-edit-fu" data-id="' . $row['id'] . '" title="Edit Follow-up"><i data-lucide="edit" class="fs-14"></i></button>';
            $inquiryNo = !empty($row['lead_number']) ? $row['lead_number'] : ('INQ-' . $row['lead_id']);
            $inqDate = !empty($row['lead_created_at']) ? date('d-m-Y', strtotime($row['lead_created_at'])) : date('d-m-Y', strtotime($row['created_at']));
            $custName = htmlspecialchars($row['business_name'] ?? ('Lead #' . $row['lead_id']), ENT_QUOTES);
            $contactPerson = htmlspecialchars($row['contact_name'] ?? '-', ENT_QUOTES);
            $mobileNo = htmlspecialchars($row['mobile_no'] ?? '-', ENT_QUOTES);
            $whatsappNo = htmlspecialchars($row['whatsapp_no'] ?? ($row['mobile_no'] ?? '-'), ENT_QUOTES);
            $leadStage = htmlspecialchars($row['stage'] ?? ($row['lead_stage'] ?? ''), ENT_QUOTES);

            $actionsHtml .= '<button type="button" class="btn btn-outline-success btn-sm btn-icon btn-open-response-modal" ' .
                'data-id="' . $row['id'] . '" ' .
                'data-lead-id="' . $row['lead_id'] . '" ' .
                'data-inquiry="' . htmlspecialchars($inquiryNo, ENT_QUOTES) . '" ' .
                'data-inquiry-date="' . htmlspecialchars($inqDate, ENT_QUOTES) . '" ' .
                'data-cust-name="' . $custName . '" ' .
                'data-contact-person="' . $contactPerson . '" ' .
                'data-mobile="' . $mobileNo . '" ' .
                'data-whatsapp="' . $whatsappNo . '" ' .
                'data-stage="' . $leadStage . '" ' .
                'title="Add Followup Response"><i data-lucide="check" class="fs-14"></i></button>';
        }

        // Delete Follow-up button
        $actionsHtml .= '<button type="button" class="btn btn-outline-danger btn-sm btn-icon btn-delete-fu" data-id="' . $row['id'] . '" title="Delete Follow-up"><i data-lucide="trash-2" class="fs-14"></i></button>';

        $actionsHtml .= '</div>';

        // Only remarks data in remarks column
        $remarksHtml = '<div class="text-wrap" style="max-width: 280px; font-size: 13px;">' . nl2br(htmlspecialchars($row['remarks'] ?? '')) . '</div>';

        // Only response data in response column
        $responseHtml = !empty($row['response']) ? '<div class="text-wrap text-success fw-medium" style="max-width: 280px; font-size: 13px;">' . nl2br(htmlspecialchars($row['response'])) . '</div>' : '<span class="text-muted fs-12">-</span>';

        // When leadId is not provided, show Lead information column
        if ($leadId <= 0) {
            $leadEncId = encrypt_id($row['lead_id']);
            $busName = htmlspecialchars($row['business_name'] ?? 'Lead #' . $row['lead_id']);
            $contactInfo = htmlspecialchars($row['contact_name'] ?? '');
            if (!empty($row['mobile_no'])) {
                $contactInfo .= ($contactInfo ? ' (' : '') . htmlspecialchars($row['mobile_no']) . ($contactInfo ? ')' : '');
            }

            $leadHtml = '<div>';
            $leadHtml .= '<a href="' . SITE_URL . 'lead/followup-history/' . $leadEncId . '" class="fw-semibold text-primary text-decoration-none d-block fs-13">';
            $leadHtml .= $busName;
            $leadHtml .= '</a>';
            if ($contactInfo) {
                $leadHtml .= '<small class="text-muted fs-11">' . $contactInfo . '</small>';
            }
            $leadHtml .= '</div>';

            return [
                $srNo,
                $leadHtml,
                $typeBadge,
                htmlspecialchars($dateFormatted),
                $stageBadge,
                $remarksHtml,
                $responseHtml,
                $statusBadge,
                $userName,
                htmlspecialchars($createdAtFormatted),
                $actionsHtml
            ];
        }

        return [
            $srNo,
            $typeBadge,
            htmlspecialchars($dateFormatted),
            $stageBadge,
            $remarksHtml,
            $responseHtml,
            $statusBadge,
            $userName,
            htmlspecialchars($createdAtFormatted),
            $actionsHtml
        ];
    }
]);
