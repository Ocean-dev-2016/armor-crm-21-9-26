<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

include BASE_PATH . '/include/header.php';

$pageNm = 'Customer';
$tbl = 'customer';
$moduleKey = 'customer';

// Check permissions for customer view
checkPermissionOrDeny($moduleKey, 'views');

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$userType = $_SESSION['user_type'] ?? '';
$currentUserId = getCurrentUserId();

// Customer ID can be passed as encrypted string or numeric ID
$customerIdParam = $_GET['customer_id'] ?? ($_GET['id'] ?? '');
$customerId = 0;
if (!empty($customerIdParam)) {
    $customerId = decrypt_id($customerIdParam);
    if (!$customerId && is_numeric($customerIdParam)) {
        $customerId = (int)$customerIdParam;
    }
}

if ($customerId <= 0) {
    die('<div class="alert alert-danger m-3">Invalid customer ID.</div>');
}

// Fetch Customer Record
$customer = db_row("SELECT cust.*, 
                           c.name AS company_name, 
                           ct.name AS customer_type_name, 
                           ci.name AS city_name, 
                           s.name AS state_name, 
                           co.name AS country_name, 
                           u.name AS assigned_to_name
                    FROM customer cust
                    LEFT JOIN company c ON c.id = cust.company_id 
                    LEFT JOIN customer_type ct ON ct.id = cust.customer_type_id 
                    LEFT JOIN city ci ON ci.id = cust.city_id 
                    LEFT JOIN state s ON s.id = cust.state_id 
                    LEFT JOIN country co ON co.id = cust.country_id
                    LEFT JOIN users u ON u.id = cust.assigned_to
                    WHERE cust.id = $customerId 
                    LIMIT 1");

if (!$customer) {
    die('<div class="alert alert-danger m-3">Customer not found.</div>');
}

// Permission & Tenant Isolation Check
if (!$isSuperadmin && $sessionCompanyId > 0 && (int)$customer['company_id'] !== $sessionCompanyId) {
    die('<div class="alert alert-danger m-3">Unauthorized access.</div>');
}

if ($userType === 'user' && $currentUserId > 0) {
    if ((int)($customer['assigned_to'] ?? 0) !== $currentUserId && (int)($customer['created_by'] ?? 0) !== $currentUserId) {
        die('<div class="alert alert-danger m-3">Unauthorized access.</div>');
    }
}

$leadId = (int)($customer['company_lead_id'] ?? 0);
$lead = null;
$allLeadFollowups = [];
$responseFollowups = [];

if ($leadId > 0) {
    $lead = db_row("SELECT * FROM company_lead WHERE id = $leadId LIMIT 1");
    if ($lead) {
        // Resolve names for the lead
        $leadCountryName = !empty($lead['country_id']) ? (db_row("SELECT name FROM country WHERE id = " . (int)$lead['country_id'])['name'] ?? '--') : '--';
        $leadStateName   = !empty($lead['state_id']) ? (db_row("SELECT name FROM state WHERE id = " . (int)$lead['state_id'])['name'] ?? '--') : '--';
        $leadCityName    = !empty($lead['city_id']) ? (db_row("SELECT name FROM city WHERE id = " . (int)$lead['city_id'])['name'] ?? '--') : '--';
        $leadSourceName  = !empty($lead['source_of_inquiry_id']) ? (db_row("SELECT name FROM source_of_inquiry WHERE id = " . (int)$lead['source_of_inquiry_id'])['name'] ?? '--') : '--';
        $leadAssignedTo  = !empty($lead['assigned_to']) ? (db_row("SELECT name FROM users WHERE id = " . (int)$lead['assigned_to'])['name'] ?? '--') : '--';
        $leadCreatedBy   = !empty($lead['created_by']) ? (db_row("SELECT name FROM users WHERE id = " . (int)$lead['created_by'])['name'] ?? '--') : '--';

        // Resolve inquiry status label & color
        $inqStatusLabel = $lead['inquiry_status'] ?: 'Lead';
        $inqStatusColor = '#0d6efd';
        if (!empty($lead['inquiry_status'])) {
            $mInq = db_row("SELECT name, color FROM marketing_status WHERE (id = '" . (int)$lead['inquiry_status'] . "' OR name = '" . addslashes($lead['inquiry_status']) . "') AND type = 'Lead' LIMIT 1");
            if ($mInq && !empty($mInq['name'])) {
                $inqStatusLabel = $mInq['name'];
                if (!empty($mInq['color'])) {
                    $inqStatusColor = $mInq['color'];
                }
            }
        }

        // Fetch all follow-ups of this lead
        $allLeadFollowups = db_rows("SELECT clf.*, 
                                            lft.name AS followup_through_name, 
                                            fr.name AS reason_name, 
                                            u.name AS creator_name,
                                            msl.name AS lead_status_display,
                                            msl.color AS lead_color
                                     FROM company_lead_followups clf
                                     LEFT JOIN lead_followup_type lft ON lft.id = clf.followup_type_id
                                     LEFT JOIN followup_reason fr ON fr.id = clf.reason_id
                                     LEFT JOIN users u ON u.id = clf.created_by
                                     LEFT JOIN marketing_status msl ON (msl.id = clf.inquiry_status OR msl.name = clf.inquiry_status) AND msl.type = 'Lead' AND (msl.company_id = clf.company_id OR msl.company_id = 0)
                                     WHERE clf.lead_id = $leadId
                                     ORDER BY clf.followup_date DESC");

        // Filter followups with responses or closed
        $responseFollowups = array_filter($allLeadFollowups, function($f) {
            $st = strtolower(trim((string)$f['followup_status']));
            return in_array($st, ['completed', 'close', 'closed', '4', 'end']) || !empty(trim((string)$f['response']));
        });
    }
}

$breadcrumbType = 'form';
$parentUrl = SITE_URL . 'customer';
$customName = 'Lead History';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Customer Header Card -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-lg rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-20" style="width: 52px; height: 52px;">
                            <?= strtoupper(substr($customer['name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($customer['name'] ?? '') ?></h4>
                                <span class="badge bg-success-subtle text-success fs-12 px-2 py-1">Customer</span>
                                <?php if ($isSuperadmin && !empty($customer['company_name'])): ?>
                                    <span class="badge bg-dark-subtle text-dark fs-12"><?= htmlspecialchars($customer['company_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted fs-13 d-flex flex-wrap align-items-center gap-3 mt-1">
                                <?php if (!empty($customer['company_name_org'])): ?>
                                    <span><i data-lucide="building" class="fs-13 align-middle me-1"></i><?= htmlspecialchars($customer['company_name_org']) ?></span>
                                <?php endif; ?>
                                <span><i data-lucide="phone" class="fs-13 align-middle me-1"></i><?= htmlspecialchars($customer['mobile_no'] ?: '--') ?></span>
                                <span><i data-lucide="mail" class="fs-13 align-middle me-1"></i><?= htmlspecialchars($customer['email'] ?: '--') ?></span>
                                <span><i data-lucide="map-pin" class="fs-13 align-middle me-1"></i><?= htmlspecialchars($customer['city_name'] ?: '--') ?>, <?= htmlspecialchars($customer['state_name'] ?: '--') ?></span>
                                <span><i data-lucide="user-check" class="fs-13 align-middle me-1"></i>Assigned To: <strong><?= htmlspecialchars($customer['assigned_to_name'] ?: '--') ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  
        </div>
    </div>
</div>

<?php if (!$lead): ?>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center py-5">
                    <div class="avatar avatar-xl rounded-circle bg-warning-subtle text-warning mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i data-lucide="alert-circle" class="fs-28"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Lead Associated</h5>
                    <p class="text-muted mb-4">This customer was created directly without linking to an inquiry/company lead, or the original lead record is no longer available.</p>
                    <a href="<?= SITE_URL ?>customer" class="btn btn-primary px-4">
                        <i data-lucide="arrow-left" class="fs-14 align-middle me-1"></i> Back to Customers
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Lead Tabs Details & Follow-up History -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom p-0">
                    <div class="d-flex align-items-center justify-content-between px-3 pt-2">
                        <ul class="nav nav-tabs border-bottom-0" id="leadViewTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-medium px-4 py-3" id="inquiry-details-tab" data-bs-toggle="tab" data-bs-target="#inquiry-details-pane" type="button" role="tab">
                                    <i data-lucide="file-text" class="fs-14 align-middle me-1"></i> Inquiry Details
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-medium px-4 py-3" id="inquiry-history-tab" data-bs-toggle="tab" data-bs-target="#inquiry-history-pane" type="button" role="tab">
                                    <i data-lucide="clock" class="fs-14 align-middle me-1"></i> Follow-up Timeline & History
                                    <span class="badge bg-primary-subtle text-primary ms-1"><?= count($allLeadFollowups) ?></span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-medium px-4 py-3" id="followups-history-tab" data-bs-toggle="tab" data-bs-target="#followups-history-pane" type="button" role="tab">
                                    <i data-lucide="message-square" class="fs-14 align-middle me-1"></i> Response History
                                    <span class="badge bg-success-subtle text-success ms-1"><?= count($responseFollowups) ?></span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content" id="leadViewTabContent">
                        <!-- TAB 1: INQUIRY DETAILS -->
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
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($leadAssignedTo) ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-dark fw-bold">Pincode:</span>
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($lead['pincode'] ?: '--') ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-dark fw-bold">City:</span>
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($leadCityName) ?></span>
                                    </div>
                                </div>

                                <!-- Column 2 -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <span class="text-dark fw-bold">Inquiry Date:</span>
                                        <span class="ms-1 text-muted"><?= (!empty($lead['inquiry_date']) && $lead['inquiry_date'] !== '0000-00-00') ? date('d-m-Y', strtotime($lead['inquiry_date'])) : '--' ?></span>
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
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($leadSourceName) ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-dark fw-bold">Address:</span>
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($lead['address'] ?: '--') ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-dark fw-bold">Country:</span>
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($leadCountryName) ?></span>
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
                                        <span class="ms-1 badge rounded-pill px-2 py-1 fs-12" style="background-color: <?= $inqStatusColor ?>26; color: <?= $inqStatusColor ?>; border: 1px solid <?= $inqStatusColor ?>4D;">
                                            <?= htmlspecialchars($inqStatusLabel) ?>
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-dark fw-bold">Inquiry Created By:</span>
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($leadCreatedBy) ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-dark fw-bold">State:</span>
                                        <span class="ms-1 text-muted"><?= htmlspecialchars($leadStateName) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Requirement Details & Attachments -->
                            <div class="row g-3 mt-1 fs-13">
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <span class="text-dark fw-bold d-block mb-1">Requirement Details:</span>
                                        <div class="text-muted"><?= nl2br(htmlspecialchars($lead['requirement_details'] ?: 'No requirement details recorded.')) ?></div>
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
                                <h6 class="fw-bold text-dark mb-0">Follow-up History for Inquiry: <?= htmlspecialchars($lead['inquiry_no'] ?: ('INQ-' . $lead['id'])) ?></h6>
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
                                                <th>Remarks / Topic</th>
                                                <th>Status</th>
                                                <th>Follow-up By</th>
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
                                                    <td><div class="text-wrap" style="max-width:260px; font-size:13px;"><?= nl2br(htmlspecialchars($f['remarks'] ?: '-')) ?></div></td>
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

                        <!-- TAB 3: FOLLOW-UPS RESPONSE HISTORY -->
                        <div class="tab-pane fade" id="followups-history-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">Follow-up Response Details</h6>
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
                                                <th>Response Received</th>
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
<?php endif; ?>

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
