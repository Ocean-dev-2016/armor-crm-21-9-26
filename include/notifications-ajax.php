<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/../conn/dbqry.php';
require_once __DIR__ . '/../conn/helper.php';

header('Content-Type: application/json');

$userId = getCurrentUserId();
if ($userId <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// User / Company context
$headerUserId = (int)($_SESSION['user_id'] ?? 0);
$headerUser = null;
if ($headerUserId > 0) {
    $headerUser = db_row("SELECT id, name, username, email, company_id FROM users WHERE id = $headerUserId LIMIT 1");
}
$headerCompanyId = (int)($headerUser['company_id'] ?? ($_SESSION['company_id'] ?? 0));
$notifCompId = (int)($headerCompanyId ?: ($_SESSION['company_id'] ?? 0));

$notifFollowups = [];
$notifTotalPending = 0;
$notifOverdue = [];
$notifToday = [];
$notifUpcoming = [];

$nowTs = time();
$currentUserType = $_SESSION['user_type'] ?? '';
$userFollowupCondLead = "";
$userFollowupCondCust = "";
if ($currentUserType === 'user' && $userId > 0) {
    $userFollowupCondLead = " AND clf.created_by = $userId";
    $userFollowupCondCust = " AND cf.created_by = $userId";
}

if (!$isSuperadmin && $notifCompId > 0) {
    // 1. Company login: Company Lead Followups
    $companyPendingRows = db_rows("SELECT clf.*, 
                                          cl.customer_name, 
                                          cl.contact_person, 
                                          cl.mobile_no, 
                                          cl.inquiry_no, 
                                          lft.name as through_name 
                                   FROM company_lead_followups clf 
                                   LEFT JOIN company_lead cl ON cl.id = clf.lead_id 
                                   LEFT JOIN lead_followup_type lft ON lft.id = clf.followup_type_id 
                                   WHERE clf.company_id = $notifCompId $userFollowupCondLead
                                     AND (LOWER(TRIM(clf.followup_status)) IN ('pending', 'open', '3', '') OR clf.followup_status IS NULL)
                                   ORDER BY clf.followup_date ASC");

    foreach ($companyPendingRows as $cfRow) {
        $cfRow['source_type'] = 'lead';
        $fDateRaw = (!empty($cfRow['followup_date']) && $cfRow['followup_date'] !== '0000-00-00 00:00:00') ? $cfRow['followup_date'] : '';
        $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
        $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

        if (empty($fTs) || $fTs < $nowTs) {
            $cfRow['timing_category'] = 'overdue';
            $cfRow['timing_label'] = 'Overdue';
            $cfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
            $notifOverdue[] = $cfRow;
        } elseif ($fDateOnly === $todayDate) {
            $cfRow['timing_category'] = 'today';
            $cfRow['timing_label'] = 'Today';
            $cfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
            $notifToday[] = $cfRow;
        } else {
            $cfRow['timing_category'] = 'upcoming';
            $cfRow['timing_label'] = 'Upcoming';
            $cfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
            $notifUpcoming[] = $cfRow;
        }
        $notifFollowups[] = $cfRow;
    }

    // 2. Customer Follow-ups (Sales & Marketing)
    $customerPendingRows = db_rows("SELECT cf.*, 
                                           cust.name as customer_name, 
                                           cust.mobile_no, 
                                           cust.email, 
                                           lft.name as through_name,
                                           cfr.name as reason_name
                                    FROM customer_followups cf 
                                    LEFT JOIN customer cust ON cust.id = cf.customer_id 
                                    LEFT JOIN lead_followup_type lft ON lft.id = cf.followup_type_id 
                                    LEFT JOIN customer_followup_reason cfr ON cfr.id = cf.reason_id
                                    WHERE cf.company_id = $notifCompId $userFollowupCondCust
                                      AND (LOWER(TRIM(cf.followup_status)) IN ('pending', 'open', '3', '') OR cf.followup_status IS NULL)
                                    ORDER BY cf.followup_date ASC");

    foreach ($customerPendingRows as $cfRow) {
        $cfRow['source_type'] = 'customer';
        $cfRow['contact_person'] = $cfRow['customer_name'] ?? '';
        $fDateRaw = (!empty($cfRow['followup_date']) && $cfRow['followup_date'] !== '0000-00-00 00:00:00') ? $cfRow['followup_date'] : '';
        $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
        $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

        if (empty($fTs) || $fTs < $nowTs) {
            $cfRow['timing_category'] = 'overdue';
            $cfRow['timing_label'] = 'Overdue';
            $cfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
            $notifOverdue[] = $cfRow;
        } elseif ($fDateOnly === $todayDate) {
            $cfRow['timing_category'] = 'today';
            $cfRow['timing_label'] = 'Today';
            $cfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
            $notifToday[] = $cfRow;
        } else {
            $cfRow['timing_category'] = 'upcoming';
            $cfRow['timing_label'] = 'Upcoming';
            $cfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
            $notifUpcoming[] = $cfRow;
        }
        $notifFollowups[] = $cfRow;
    }

    $notifTotalPending = count($companyPendingRows) + count($customerPendingRows);
} else {
    // Superadmin: Fetch pending followups from lead_followups
    $leadPendingRows = db_rows("SELECT f.*, 
                                       l.business_name, 
                                       l.contact_name, 
                                       l.mobile_no, 
                                       l.lead_number,
                                       lft.name as through_name
                                FROM lead_followups f 
                                JOIN lead l ON l.id = f.lead_id 
                                LEFT JOIN lead_followup_type lft ON (lft.id = f.followup_type OR LOWER(lft.name) = LOWER(f.followup_type))
                                WHERE LOWER(TRIM(f.reminder_status)) = 'pending' 
                                ORDER BY f.followup_date ASC");

    foreach ($leadPendingRows as $lfRow) {
        $lfRow['source_type'] = 'lead';
        $fDateRaw = (!empty($lfRow['followup_date']) && $lfRow['followup_date'] !== '0000-00-00 00:00:00') ? $lfRow['followup_date'] : '';
        $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
        $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

        if (empty($fTs) || $fTs < $nowTs) {
            $lfRow['timing_category'] = 'overdue';
            $lfRow['timing_label'] = 'Overdue';
            $lfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
            $notifOverdue[] = $lfRow;
        } elseif ($fDateOnly === $todayDate) {
            $lfRow['timing_category'] = 'today';
            $lfRow['timing_label'] = 'Today';
            $lfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
            $notifToday[] = $lfRow;
        } else {
            $lfRow['timing_category'] = 'upcoming';
            $lfRow['timing_label'] = 'Upcoming';
            $lfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
            $notifUpcoming[] = $lfRow;
        }
        if (empty($lfRow['through_name']) && !empty($lfRow['followup_type'])) {
            $lfRow['through_name'] = function_exists('get_lead_followup_type_label') ? get_lead_followup_type_label($lfRow['followup_type']) : ucfirst($lfRow['followup_type']);
        }
        $notifFollowups[] = $lfRow;
    }

    // Superadmin: Customer Follow-ups
    $custAdminRows = db_rows("SELECT cf.*, 
                                     cust.name as customer_name, 
                                     cust.mobile_no, 
                                     cust.email, 
                                     lft.name as through_name,
                                     cfr.name as reason_name
                              FROM customer_followups cf 
                              LEFT JOIN customer cust ON cust.id = cf.customer_id 
                              LEFT JOIN lead_followup_type lft ON lft.id = cf.followup_type_id 
                              LEFT JOIN customer_followup_reason cfr ON cfr.id = cf.reason_id
                              WHERE (LOWER(TRIM(cf.followup_status)) IN ('pending', 'open', '3', '') OR cf.followup_status IS NULL)
                              ORDER BY cf.followup_date ASC");

    foreach ($custAdminRows as $cfRow) {
        $cfRow['source_type'] = 'customer';
        $cfRow['contact_person'] = $cfRow['customer_name'] ?? '';
        $fDateRaw = (!empty($cfRow['followup_date']) && $cfRow['followup_date'] !== '0000-00-00 00:00:00') ? $cfRow['followup_date'] : '';
        $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
        $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

        if (empty($fTs) || $fTs < $nowTs) {
            $cfRow['timing_category'] = 'overdue';
            $cfRow['timing_label'] = 'Overdue';
            $cfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
            $notifOverdue[] = $cfRow;
        } elseif ($fDateOnly === $todayDate) {
            $cfRow['timing_category'] = 'today';
            $cfRow['timing_label'] = 'Today';
            $cfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
            $notifToday[] = $cfRow;
        } else {
            $cfRow['timing_category'] = 'upcoming';
            $cfRow['timing_label'] = 'Upcoming';
            $cfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
            $notifUpcoming[] = $cfRow;
        }
        $notifFollowups[] = $cfRow;
    }

    $notifTotalPending = count($leadPendingRows) + count($custAdminRows);
}

// Generate the items HTML
ob_start();
if (empty($notifFollowups)): ?>
    <div class="text-center py-4 px-3 text-muted">
        <i data-lucide="check-circle-2" class="fs-24 text-success mb-2"></i>
        <p class="mb-0 fs-12">No pending follow-ups found.</p>
    </div>
<?php else:
    foreach ($notifFollowups as $tf): 
        $fDateRaw = (!empty($tf['followup_date']) && $tf['followup_date'] !== '0000-00-00 00:00:00') ? $tf['followup_date'] : '';
        $timingLabel = $tf['timing_label'] ?? 'Pending';
        $timingBadge = $tf['timing_badge'] ?? 'bg-secondary-subtle text-secondary';
        $dateText = $fDateRaw ? date('d M, h:i A', strtotime($fDateRaw)) : '-';
        
        $isCustomerType = (($tf['source_type'] ?? '') === 'customer');
        $titleName = htmlspecialchars($tf['customer_name'] ?? ($tf['business_name'] ?? ($isCustomerType ? 'Customer' : 'Lead')));
        $personName = htmlspecialchars($tf['contact_person'] ?? ($tf['contact_name'] ?? ($tf['mobile_no'] ?? '')));
        $thName = htmlspecialchars($tf['through_name'] ?? 'Call');
        $reasonName = htmlspecialchars($tf['reason_name'] ?? '');
        $remText = htmlspecialchars($tf['remarks'] ?? '');

        if ($isCustomerType) {
            $viewUrl = !empty($tf['customer_id']) ? (SITE_URL . 'customer-followup?customer_id=' . encrypt_id($tf['customer_id'])) : (SITE_URL . 'customer-followup');
        } elseif ($isSuperadmin) {
            $viewUrl = !empty($tf['lead_id']) ? (SITE_URL . 'lead/followup-history/' . encrypt_id($tf['lead_id'])) : (SITE_URL . 'lead/followup-history');
        } else {
            $viewUrl = !empty($tf['lead_id']) ? (SITE_URL . 'company-lead/view/' . encrypt_id($tf['lead_id'])) : (SITE_URL . 'company-lead-followup');
        }

        // Icon and background highlight based on category
        $isOverdue = ($timingLabel === 'Overdue');
        $isToday = ($timingLabel === 'Today');
        $itemBgClass = $isOverdue ? 'bg-danger-subtle bg-opacity-10' : ($isToday ? 'bg-warning-subtle bg-opacity-10' : '');
        $iconName = $isOverdue ? 'alert-circle' : ($isToday ? 'calendar-clock' : 'calendar');
        $iconColor = $isOverdue ? 'text-danger bg-danger-subtle' : ($isToday ? 'text-warning bg-warning-subtle' : 'text-info bg-info-subtle');
    ?>
        <a href="<?= $viewUrl ?>" class="dropdown-item py-2 px-3 d-flex align-items-start border-bottom text-wrap <?= $itemBgClass ?>">
            <div class="avatar avatar-sm <?= $iconColor ?> me-2 flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i data-lucide="<?= $iconName ?>" class="fs-14"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <div class="d-flex align-items-center gap-1 overflow-hidden">
                        <span class="badge <?= $isCustomerType ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?> fs-9 px-1 py-0.5 rounded">
                            <?= $isCustomerType ? 'Customer' : 'Lead' ?>
                        </span>
                        <p class="mb-0 fw-bold text-dark fs-12 text-truncate" style="max-width: 135px;" title="<?= $titleName ?>">
                            <?= $titleName ?>
                        </p>
                    </div>
                    <span class="badge <?= $timingBadge ?> fs-10 px-2 py-0.5 rounded-pill flex-shrink-0"><?= $timingLabel ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center text-muted fs-11">
                    <span class="text-truncate" style="max-width: 140px;">
                        <?= $reasonName ? $reasonName : ($personName ?: '-') ?>
                    </span>
                    <span class="text-nowrap fs-10 text-muted"><i data-lucide="clock" class="fs-10 align-middle"></i> <?= $dateText ?></span>
                </div>
                <?php if ($remText): ?>
                    <div class="text-secondary small text-truncate mt-1" style="font-size: 11px;" title="<?= $remText ?>">
                        <i data-lucide="message-square" class="fs-10 align-middle me-1"></i><?= $remText ?>
                    </div>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach;
endif;
$itemsHtml = ob_get_clean();

echo json_encode([
    'status' => true,
    'total_pending' => $notifTotalPending,
    'badge_text' => $notifTotalPending > 99 ? '99+' : (string)$notifTotalPending,
    'pending_label' => $notifTotalPending . ' Pending',
    'html' => $itemsHtml
]);
exit;
