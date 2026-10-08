<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

include BASE_PATH . '/include/header.php';

$pageNm = 'Lead';
$tbl = 'lead';

// Only Superadmin can access SaaS Leads
if (!$isSuperadmin) {
    echo "<div class='container py-5 text-center'><h3 class='text-danger'>Access Denied</h3><p>Only Superadmin can access SaaS Platform Leads.</p><a href='" . SITE_URL . "' class='btn btn-primary'>Back to Home</a></div>";
    include BASE_PATH . '/include/footer.php';
    exit;
}

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
if (!$id && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
}

if ($id <= 0) {
    die('Invalid lead ID.');
}

$lead = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
if (!$lead) {
    die($pageNm . ' not found.');
}

// Format dates
$leadInqDate = (!empty($lead['created_at']) && $lead['created_at'] !== '0000-00-00 00:00:00') ? date('d-m-Y', strtotime($lead['created_at'])) : '--';
$demoDateFormatted = (!empty($lead['demo_date']) && $lead['demo_date'] !== '0000-00-00 00:00:00') ? date('d-m-Y', strtotime($lead['demo_date'])) : '--';

// Resolve names
$countryName = !empty($lead['country_id']) ? (db_row("SELECT name FROM country WHERE id = " . (int)$lead['country_id'])['name'] ?? '--') : '--';
$stateName   = !empty($lead['state_id']) ? (db_row("SELECT name FROM state WHERE id = " . (int)$lead['state_id'])['name'] ?? '--') : '--';
$cityName    = !empty($lead['city_id']) ? (db_row("SELECT name FROM city WHERE id = " . (int)$lead['city_id'])['name'] ?? '--') : (!empty($lead['city']) ? $lead['city'] : '--');
$sourceName  = !empty($lead['lead_source']) ? (db_row("SELECT name FROM lead_source_of_inquiry WHERE id = " . (int)$lead['lead_source'] . " OR name = '" . db_escape($lead['lead_source']) . "'")['name'] ?? $lead['lead_source']) : '--';
$assignedUserName = !empty($lead['assign_to']) ? (db_row("SELECT name FROM lead_employee WHERE id = " . (int)$lead['assign_to'] . " OR name = '" . db_escape($lead['assign_to']) . "'")['name'] ?? $lead['assign_to']) : '--';
$createdUserName  = !empty($lead['created_by']) ? (db_row("SELECT name FROM users WHERE id = " . (int)$lead['created_by'])['name'] ?? 'Superadmin') : 'Superadmin';
$interestedPlanName = !empty($lead['interested_plan_id']) ? (db_row("SELECT name FROM plan WHERE id = " . (int)$lead['interested_plan_id'])['name'] ?? '--') : '--';

// Lead stage label & color
$stageVal = $lead['lead_stage'] ?? '';
$inqStatusLabel = get_lead_stage_label($stageVal) ?: 'New Lead';
$stageColor = get_lead_status_color($stageVal) ?: '#4f46e5';

// Check if tenant company is already created for this lead
$hasCompany = !empty($lead['converted_company_id']) && (int)$lead['converted_company_id'] > 0;
if ($hasCompany) {
    $inqStatusLabel = 'Converted';
    $stageColor = '#10b981';
}

// Fetch all follow-ups of this lead
$allLeadFollowups = db_rows("SELECT lf.*, 
                                    lft.name as followup_through_name, 
                                    u.name as creator_name,
                                    ls.name as lead_status_display,
                                    ls.color as lead_color
                             FROM lead_followups lf
                             LEFT JOIN lead_followup_type lft ON (lft.id = lf.followup_type OR lft.name = lf.followup_type)
                             LEFT JOIN users u ON u.id = lf.created_by
                             LEFT JOIN lead_status ls ON (ls.id = lf.stage OR ls.name = lf.stage)
                             WHERE lf.lead_id = {$lead['id']}
                             ORDER BY lf.followup_date DESC");

// Filter followups where response is present or status is completed
$responseFollowups = array_filter($allLeadFollowups, function($f) {
    $st = strtolower(trim((string)$f['reminder_status']));
    return $st === 'completed' || !empty(trim((string)$f['response']));
});

$breadcrumbType = 'form';
$parentUrl = SITE_URL . 'lead';
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
                        <?php if (!$hasCompany): ?>
                            <a href="<?= SITE_URL ?>lead/edit/<?= encrypt_id($lead['id']) ?>" class="btn btn-primary btn-sm px-3">
                                <i data-lucide="edit" class="fs-13 align-middle me-1"></i> Edit
                            </a>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>lead" class="btn btn-outline-secondary btn-sm px-3">
                            <i data-lucide="arrow-left" class="fs-13 align-middle me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="leadViewTabContent">
                    <!-- TAB 1: INQUIRY DETAILS (Matching Company Lead Design) -->
                    <div class="tab-pane fade show active" id="inquiry-details-pane" role="tabpanel">
                        <div class="row g-4 fs-13 text-secondary">
                            <!-- Column 1 -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Inquiry / Lead No:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['lead_number'] ?: ('OI-' . $lead['id'])) ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Contact Person Name:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['contact_name'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Email:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['email'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Team Size:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars(get_lead_team_size_label($lead['team_size'] ?? 1)) ?></span>
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
                                    <span class="text-dark fw-bold">Demo / Meeting Date:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($demoDateFormatted) ?></span>
                                </div>
                            </div>

                            <!-- Column 3 -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Business Name:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['business_name'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">WhatsApp No:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($lead['whatsapp_no'] ?: '--') ?></span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Inquiry Status:</span>
                                    <span class="ms-1">
                                        <span class="badge rounded-pill px-2 py-1" style="background-color: <?= $stageColor ?>26; color: <?= $stageColor ?>; border: 1px solid <?= $stageColor ?>4D;">
                                            <?= htmlspecialchars($inqStatusLabel) ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-dark fw-bold">Interested Plan:</span>
                                    <span class="ms-1 text-muted"><?= htmlspecialchars($interestedPlanName) ?></span>
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

                        <!-- Requirement Details & Notes Full-width rows -->
                        <div class="row g-3 mt-1 fs-13">
                            <div class="col-12">
                                <div>
                                    <span class="text-dark fw-bold">Requirements:</span>
                                    <span class="ms-1 text-muted"><?= nl2br(htmlspecialchars($lead['requirements'] ?: '--')) ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div>
                                    <span class="text-dark fw-bold">Notes / Interaction History:</span>
                                    <span class="ms-1 text-muted"><?= nl2br(htmlspecialchars($lead['notes'] ?: '--')) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: INQUIRY HISTORY (All followups of this inquiry) -->
                    <div class="tab-pane fade" id="inquiry-history-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Follow-up Activity for Inquiry: <?= htmlspecialchars($lead['lead_number'] ?: ('OI-' . $lead['id'])) ?></h6>
                            <a href="<?= SITE_URL ?>lead/followup-history/<?= encrypt_id($lead['id']) ?>" class="btn btn-outline-primary btn-sm">
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
                                            <th>Lead Status</th>
                                            <th>Discussion Remarks</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Created Date</th>
                                            <th style="width: 80px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sr = 1; foreach ($allLeadFollowups as $f): 
                                            $st = strtolower(trim((string)$f['reminder_status']));
                                            $isDone = ($st === 'completed');
                                            $typeName = $f['followup_through_name'] ?: get_lead_followup_type_label($f['followup_type']);
                                            $stgName = $f['lead_status_display'] ?: get_lead_stage_label($f['stage']);
                                            $stgColor = $f['lead_color'] ?: get_lead_status_color($f['stage']);
                                        ?>
                                            <tr id="fu-row-<?= (int)$f['id'] ?>">
                                                <td><?= $sr++ ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($typeName ?: '-') ?></span></td>
                                                <td><?= !empty($f['followup_date']) ? date('d-m-Y h:i A', strtotime($f['followup_date'])) : '-' ?></td>
                                                <td>
                                                    <span class="badge fs-11 px-2 py-1 rounded-pill" style="background-color: <?= $stgColor ?>26; color: <?= $stgColor ?>; border: 1px solid <?= $stgColor ?>4D;">
                                                        <?= htmlspecialchars($stgName ?: '-') ?>
                                                    </span>
                                                </td>
                                                <td><div class="text-wrap" style="max-width:260px; font-size:13px;"><?= nl2br(htmlspecialchars($f['remarks'] ?: '-')) ?></div></td>
                                                <td>
                                                    <?php if ($isDone): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i data-lucide="check" class="fs-10 align-middle me-1"></i>Completed</span>
                                                    <?php elseif ($st === 'missed'): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Missed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($f['creator_name'] ?: '-') ?></td>
                                                <td><?= !empty($f['created_at']) ? date('d-m-Y h:i A', strtotime($f['created_at'])) : '-' ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-icon btn-delete-view-fu" data-id="<?= (int)$f['id'] ?>" title="Delete Follow-up">
                                                        <i data-lucide="trash-2" class="fs-14"></i>
                                                    </button>
                                                </td>
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
                                            <th>Lead Status</th>
                                            <th>Discussion Remarks</th>
                                            <th>Response</th>
                                            <th>Recorded By</th>
                                            <th>Completed At</th>
                                            <th style="width: 80px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sr = 1; foreach ($responseFollowups as $f): 
                                            $typeName = $f['followup_through_name'] ?: get_lead_followup_type_label($f['followup_type']);
                                            $stgName = $f['lead_status_display'] ?: get_lead_stage_label($f['stage']);
                                            $stgColor = $f['lead_color'] ?: get_lead_status_color($f['stage']);
                                            $respText = !empty($f['response']) ? $f['response'] : '-';
                                        ?>
                                            <tr id="fu-resp-row-<?= (int)$f['id'] ?>">
                                                <td><?= $sr++ ?></td>
                                                <td><?= !empty($f['followup_date']) ? date('d-m-Y h:i A', strtotime($f['followup_date'])) : '-' ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($typeName ?: '-') ?></span></td>
                                                <td>
                                                    <span class="badge fs-11 px-2 py-1 rounded-pill" style="background-color: <?= $stgColor ?>26; color: <?= $stgColor ?>; border: 1px solid <?= $stgColor ?>4D;">
                                                        <?= htmlspecialchars($stgName ?: '-') ?>
                                                    </span>
                                                </td>
                                                <td><div class="text-wrap" style="max-width:250px; font-size:13px;"><?= nl2br(htmlspecialchars($f['remarks'] ?: '-')) ?></div></td>
                                                <td><div class="text-wrap text-success fw-medium" style="max-width:280px; font-size:13px;"><?= nl2br(htmlspecialchars($respText)) ?></div></td>
                                                <td><?= htmlspecialchars($f['creator_name'] ?: '-') ?></td>
                                                <td><?= !empty($f['updated_at']) ? date('d-m-Y h:i A', strtotime($f['updated_at'])) : '-' ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-icon btn-delete-view-fu" data-id="<?= (int)$f['id'] ?>" title="Delete Follow-up">
                                                        <i data-lucide="trash-2" class="fs-14"></i>
                                                    </button>
                                                </td>
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

    // Delete follow-up inside View page
    $(document).on('click', '.btn-delete-view-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        if (!fuId) return;

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to delete this follow-up record?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: SITE_URL + "admin/setting/lead/ajax.php",
                    type: "POST",
                    data: { action: 'delete_followup', followup_id: fuId },
                    dataType: "json",
                    success: function(res) {
                        if (res.status === true) {
                            showToast(res.message, 'success');
                            $('#fu-row-' + fuId).fadeOut(300, function() { $(this).remove(); });
                            $('#fu-resp-row-' + fuId).fadeOut(300, function() { $(this).remove(); });
                        } else {
                            showToast(res.message || 'Failed to delete follow-up', 'error');
                        }
                    },
                    error: function() {
                        showToast('Server error while deleting follow-up.', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
