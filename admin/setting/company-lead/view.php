<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

include BASE_PATH . '/include/header.php';

$pageNm = 'Company Lead';
$tbl = 'company_lead';
$moduleKey = 'company-lead';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
if (!$id && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
}

checkPermissionOrDeny($moduleKey, 'views');

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

if ($id <= 0) {
    die('Invalid lead ID.');
}

$lead = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
if (!$lead) {
    die($pageNm . ' not found.');
}

if (!$isSuperadmin && $sessionCompanyId > 0 && (int)$lead['company_id'] !== $sessionCompanyId) {
    die('Unauthorized access.');
}

$userType = $_SESSION['user_type'] ?? '';
$currentUserId = getCurrentUserId();
if ($userType === 'user' && (int)($lead['assigned_to'] ?? 0) !== $currentUserId) {
    die('Unauthorized access.');
}

// Format dates
$leadInqDate = (!empty($lead['inquiry_date']) && $lead['inquiry_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($lead['inquiry_date'])) : '--';

// Resolve names
$countryName = !empty($lead['country_id']) ? (db_row("SELECT name FROM country WHERE id = " . (int)$lead['country_id'])['name'] ?? '--') : '--';
$stateName   = !empty($lead['state_id']) ? (db_row("SELECT name FROM state WHERE id = " . (int)$lead['state_id'])['name'] ?? '--') : '--';
$cityName    = !empty($lead['city_id']) ? (db_row("SELECT name FROM city WHERE id = " . (int)$lead['city_id'])['name'] ?? '--') : '--';
$sourceName  = !empty($lead['source_of_inquiry_id']) ? (db_row("SELECT name FROM source_of_inquiry WHERE id = " . (int)$lead['source_of_inquiry_id'])['name'] ?? '--') : '--';
$assignedUserName = !empty($lead['assigned_to']) ? (db_row("SELECT name FROM users WHERE id = " . (int)$lead['assigned_to'])['name'] ?? '--') : '--';
$createdUserName  = !empty($lead['created_by']) ? (db_row("SELECT name FROM users WHERE id = " . (int)$lead['created_by'])['name'] ?? '--') : '--';

// Resolve inquiry status if numeric
$inqStatusLabel = $lead['inquiry_status'] ?: 'New Lead';
if (!empty($lead['inquiry_status']) && is_numeric($lead['inquiry_status'])) {
    $mInq = db_row("SELECT name FROM marketing_status WHERE id = " . (int)$lead['inquiry_status']);
    if ($mInq && !empty($mInq['name'])) {
        $inqStatusLabel = $mInq['name'];
    }
}

// Check if customer is already created for this lead
$existingCust = db_row("SELECT id FROM customer WHERE company_lead_id = {$lead['id']} LIMIT 1");
$hasCustomer = !empty($existingCust);
if ($hasCustomer) {
    $inqStatusLabel = 'Converted';
}

// Fetch all follow-ups of this lead
$allLeadFollowups = db_rows("SELECT clf.*, 
                                    lft.name as followup_through_name, 
                                    fr.name as reason_name, 
                                    u.name as creator_name,
                                    msl.name as lead_status_display,
                                    msl.color as lead_color
                             FROM company_lead_followups clf
                             LEFT JOIN lead_followup_type lft ON lft.id = clf.followup_type_id
                             LEFT JOIN followup_reason fr ON fr.id = clf.reason_id
                             LEFT JOIN users u ON u.id = clf.created_by
                             LEFT JOIN marketing_status msl ON (msl.id = clf.inquiry_status OR msl.name = clf.inquiry_status) AND msl.type = 'Lead' AND (msl.company_id = clf.company_id OR msl.company_id = 0)
                             WHERE clf.lead_id = {$lead['id']}
                             ORDER BY clf.followup_date DESC");

// Filter followups where response is present or status is completed
$responseFollowups = array_filter($allLeadFollowups, function($f) {
    $st = strtolower(trim((string)$f['followup_status']));
    return in_array($st, ['completed', 'close', 'closed', '4', 'end']) || !empty(trim((string)$f['response']));
});

$breadcrumbType = 'form';
$parentUrl = SITE_URL . 'company-lead';
$customName = 'View';
include BASE_PATH . '/component/breadcrumb.php';
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom p-0">
                <div class="d-flex align-items-center justify-content-between px-3 pt-2">
                    <ul class="nav nav-tabs border-bottom-0" id="leadViewTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-medium px-4 py-3" id="inquiry-details-tab" data-bs-toggle="tab" data-bs-target="#inquiry-details-pane" type="button" role="tab">
                                Inquiry Details
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-medium px-4 py-3" id="inquiry-history-tab" data-bs-toggle="tab" data-bs-target="#inquiry-history-pane" type="button" role="tab">
                                Inquiry History
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-medium px-4 py-3" id="followups-history-tab" data-bs-toggle="tab" data-bs-target="#followups-history-pane" type="button" role="tab">
                                Follow-ups History
                            </button>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <?php if (!$hasCustomer): ?>
                            <a href="<?= SITE_URL ?>company-lead/edit/<?= encrypt_id($lead['id']) ?>" class="btn btn-primary btn-sm px-3">
                                <i data-lucide="edit" class="fs-13 align-middle me-1"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="leadViewTabContent">
                    <!-- TAB 1: INQUIRY DETAILS (Matching image design) -->
                    <div class="tab-pane fade show active" id="inquiry-details-pane" role="tabpanel">
                        <div class="row g-4 fs-13 text-secondary">
                            <!-- Column 1 -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Inquiry No:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['inquiry_no'] ?: ('INQ-' . $lead['id'])) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Contact Person Name:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['contact_person'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Email:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['email'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Followup Status:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['followup_status'] ?: 'Open') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Inquiry Assigned To:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($assignedUserName) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Pincode:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['pincode'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">City:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($cityName) ?></span>
                                </div>
                            </div>

                            <!-- Column 2 -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Inquiry Date:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($leadInqDate) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Mobile No:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['mobile_no'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Website:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['website'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Source of Inquiry:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($sourceName) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Address:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['address'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Country:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($countryName) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Area:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['area'] ?: '--') ?></span>
                                </div>
                            </div>

                            <!-- Column 3 -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Customer Name:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['customer_name'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">WhatsApp No:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['whatsapp_no'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Inquiry Status:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($inqStatusLabel) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Inquiry Created By:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($createdUserName) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">State:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($stateName) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Requirement Details & Attachments Full-width rows -->
                        <div class="row g-3 mt-1 fs-13">
                            <div class="col-12">
                                <div>
                                    <span class="text-dark fw-bold">Requirement Details:</span>
                                    <span class="ms-1 text-muted"><?= nl2br(htmlspecialchars($lead['requirement_details'] ?: '--')) ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div>
                                    <span class="text-dark fw-bold">Attachments:</span>
                                    <span class="ms-1 text-muted">
                                        <?php if (!empty($lead['attachment'])): ?>
                                            <a href="<?= SITE_URL ?>uploads/lead/<?= htmlspecialchars($lead['attachment']) ?>" target="_blank" class="text-primary text-decoration-none">
                                                <i data-lucide="paperclip" class="fs-13 align-middle me-1"></i> View Attachment (<?= htmlspecialchars($lead['attachment']) ?>)
                                            </a>
                                        <?php else: ?>
                                            No attachments available
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: INQUIRY HISTORY (All followups of this inquiry) -->
                    <div class="tab-pane fade" id="inquiry-history-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Follow-up Activity for Inquiry: <?= htmlspecialchars($lead['inquiry_no'] ?: ('INQ-' . $lead['id'])) ?></h6>
                            <a href="<?= SITE_URL ?>company-lead-followup?lead_id=<?= $lead['id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i data-lucide="external-link" class="fs-13 align-middle me-1"></i> Open Follow-up Manager
                            </a>
                        </div>
                        <?php if (empty($allLeadFollowups)): ?>
                            <div class="alert alert-light text-center border py-4 text-muted">
                                <i data-lucide="calendar-x" class="fs-32 text-muted mb-2 d-block mx-auto"></i>
                                No follow-ups recorded for this lead yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">Sr.</th>
                                            <th>Follow-up Through</th>
                                            <th>Follow-up Date & Time</th>
                                            <th>Reason</th>
                                            <th>Inquiry Status</th>
                                            <th>Remarks</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Created Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sr = 1; foreach ($allLeadFollowups as $f): 
                                            $st = strtolower(trim((string)$f['followup_status']));
                                            $isDone = in_array($st, ['completed', 'close', 'closed', '4', 'end']);
                                        ?>
                                            <tr>
                                                <td><?= $sr++ ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($f['followup_through_name'] ?: '-') ?></span></td>
                                                <td><?= !empty($f['followup_date']) ? date('d-m-Y h:i A', strtotime($f['followup_date'])) : '-' ?></td>
                                                <td><?= htmlspecialchars($f['reason_name'] ?: '-') ?></td>
                                                <td>
                                                    <?php 
                                                        $inqLabel = !empty($f['lead_status_display']) ? $f['lead_status_display'] : ($f['inquiry_status'] ?: '-');
                                                        $inqColor = !empty($f['lead_color']) ? $f['lead_color'] : '#6c757d';
                                                    ?>
                                                    <span class="badge fs-11 px-2 py-1 rounded-pill" style="background-color: <?= $inqColor ?>26; color: <?= $inqColor ?>; border: 1px solid <?= $inqColor ?>4D;">
                                                        <?= htmlspecialchars($inqLabel) ?>
                                                    </span>
                                                </td>
                                                <td><div class="text-wrap" style="max-width:250px; font-size:13px;"><?= nl2br(htmlspecialchars($f['remarks'] ?: '-')) ?></div></td>
                                                <td>
                                                    <?php if ($isDone): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i data-lucide="check" class="fs-10 align-middle me-1"></i>Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($f['creator_name'] ?: '-') ?></td>
                                                <td><?= !empty($f['created_at']) ? date('d-m-Y h:i A', strtotime($f['created_at'])) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 3: FOLLOW-UPS HISTORY (Displaying responses of followups) -->
                    <div class="tab-pane fade" id="followups-history-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Follow-up Response History</h6>
                        </div>
                        <?php if (empty($responseFollowups)): ?>
                            <div class="alert alert-light text-center border py-4 text-muted">
                                <i data-lucide="message-square" class="fs-32 text-muted mb-2 d-block mx-auto"></i>
                                No completed follow-up responses recorded yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">Sr.</th>
                                            <th>Follow-up Date & Time</th>
                                            <th>Follow-up Through</th>
                                            <th>Reason</th>
                                            <th>Discussion Remarks</th>
                                            <th>Response</th>
                                            <th>Recorded By</th>
                                            <th>Completed At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sr = 1; foreach ($responseFollowups as $f): ?>
                                            <tr>
                                                <td><?= $sr++ ?></td>
                                                <td><?= !empty($f['followup_date']) ? date('d-m-Y h:i A', strtotime($f['followup_date'])) : '-' ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($f['followup_through_name'] ?: '-') ?></span></td>
                                                <td><?= htmlspecialchars($f['reason_name'] ?: '-') ?></td>
                                                <td><div class="text-wrap" style="max-width:250px; font-size:13px;"><?= nl2br(htmlspecialchars($f['remarks'] ?: '-')) ?></div></td>
                                                <td><div class="text-wrap text-success fw-medium" style="max-width:280px; font-size:13px;"><?= nl2br(htmlspecialchars($f['response'] ?: '-')) ?></div></td>
                                                <td><?= htmlspecialchars($f['creator_name'] ?: '-') ?></td>
                                                <td><?= !empty($f['updated_at']) ? date('d-m-Y h:i A', strtotime($f['updated_at'])) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include BASE_PATH . '/include/footer.php';
?>

<script>
$(document).ready(function() {
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
});
</script>
</body>
</html>
