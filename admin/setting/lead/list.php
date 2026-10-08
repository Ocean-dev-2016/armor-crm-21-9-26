<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

// Only Superadmin can access SaaS Leads
if (!$isSuperadmin) {
    echo "<div class='container py-5 text-center'><h3 class='text-danger'>Access Denied</h3><p>Only Superadmin can access SaaS Platform Leads.</p><a href='" . SITE_URL . "' class='btn btn-primary'>Back to Home</a></div>";
    include BASE_PATH . '/include/footer.php';
    exit;
}

$pageNm = 'Lead';
$tbl = 'lead';
$showAdd = true;
$addType = 'redirect';
$addUrl  = SITE_URL . 'lead/add';
$breadcrumbType = 'list';
$fields = 'business_name:Business Name, contact_name:Contact Person, mobile_no:Mobile, email:Email';
$module = 'lead';
include BASE_PATH . '/component/breadcrumb.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/setting/lead/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Lead No.',
    'Business Name',
    'Contact Person',
    'Mobile No.',
    'Email',
    'Team Size',
    ' Date',
    'Lead Status',
    'Actions'
];
include BASE_PATH . '/component/datatable.php';

// Active plans for convert modal dropdown
$activePlans = db_rows("SELECT id, name, price, days FROM plan WHERE status = 1 ORDER BY days ASC, price ASC");
// Active lead statuses from lead_status table
$leadStatuses = db_rows("SELECT id, name, color FROM lead_status WHERE status = 1 ORDER BY order_by ASC");
// Active followup types from lead_followup_type table
$leadFollowupTypes = db_rows("SELECT id, name FROM lead_followup_type WHERE status = 1 ORDER BY id ASC");
?>

<!-- MODAL 1: Convert Lead to Tenant Company (deal closed/won) -->
<div class="modal fade" id="convertLeadModal" tabindex="-1" aria-labelledby="convertLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="building-2" class="text-primary fs-20"></i>
                    <h5 class="modal-title fs-16 fw-semibold mb-0" id="convertLeadModalLabel">
                        Convert Lead to Tenant Company
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="convertLeadForm">
                <input type="hidden" name="action" value="convert_to_company">
                <input type="hidden" name="lead_id" id="convertLeadId" value="0">
                <div class="modal-body py-3">
                    <div class="alert alert-info-subtle border border-info-subtle py-2 px-3 fs-13 mb-3">
                        <i data-lucide="check-circle" class="fs-14 align-middle me-1"></i>
                        This will automatically create a new <strong>Company</strong>, a <strong>Company Admin User</strong>, and activate their <strong>Subscription Plan</strong> using Lead details.
                    </div>

                    <!-- Lead Details Preview Card (Read-only) -->
                    <div class="card border bg-light-subtle rounded-3 p-3 mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                            <i data-lucide="info" class="fs-15 text-primary"></i>
                            <span class="fs-12 fw-bold text-uppercase text-muted">Lead Details</span>
                        </div>
                        <div class="row g-2 fs-13">
                            <div class="col-4 text-muted">Company:</div>
                            <div class="col-8 fw-semibold text-dark text-truncate" id="showConvertCompanyName">-</div>

                            <div class="col-4 text-muted">Contact Person:</div>
                            <div class="col-8 fw-medium text-dark text-truncate" id="showConvertContactName">-</div>

                            <div class="col-4 text-muted">Mobile No.:</div>
                            <div class="col-8 fw-medium text-dark" id="showConvertMobileNo">-</div>

                            <div class="col-4 text-muted">Email:</div>
                            <div class="col-8 fw-medium text-dark text-truncate" id="showConvertEmail">-</div>
                        </div>
                    </div>

                    <!-- Editable Fields: Initial Password and Subscription Plan only -->
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Password</label>
                        <input type="password" class="form-control" name="password" id="convertPassword" value="" placeholder="Enter password (e.g. Test@1234)" minlength="8" maxlength="12" required>
                        <!-- <div class="input-group">
                            <span class="input-group-text"><i data-lucide="key" class="fs-14"></i></span>
                        </div> -->
                        <div class="form-text fs-11 text-muted">
                            Must be 8-12 characters, include at least 1 uppercase letter, 1 lowercase letter, and 1 number.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Assign Subscription Plan</label>
                        <select class="form-select" name="plan_id" id="convertPlanId" required>
                            <option value="">-- Select Plan --</option>
                            <?php foreach ($activePlans as $pl): ?>
                                <option value="<?= (int)$pl['id'] ?>">
                                    <?= htmlspecialchars($pl['name']) ?> (₹<?= number_format((float)$pl['price']) ?> / <?= (int)$pl['days'] ?> Days)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitConvertBtn">
                        <span id="submitConvertText"><i data-lucide="check" class="fs-14 align-middle me-1"></i> Convert & Onboard</span>
                        <span id="submitConvertLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Change Lead Stage Modal -->
<div class="modal fade" id="leadStageModal" tabindex="-1" aria-labelledby="leadStageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="trending-up" class="text-primary fs-20"></i>
                    <h5 class="modal-title fs-16 fw-semibold mb-0" id="leadStageModalLabel">Update Lead Stage</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="leadStageForm">
                <input type="hidden" name="action" value="update_stage">
                <input type="hidden" name="lead_id" id="stageLeadId" value="0">
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Current Business / Contact</label>
                        <p class="mb-0 fw-bold text-dark" id="stageLeadTitle">-</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Select New Stage</label>
                        <select class="form-select" name="new_stage" id="stageSelect">
                            <?php foreach ($leadStatuses as $stg): ?>
                                <option value="<?= (int)$stg['id'] ?>"><?= htmlspecialchars($stg['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Note / Remarks</label>
                        <textarea class="form-control" name="stage_note" rows="2" placeholder="e.g. Spoke on call, interested in 10-user plan, demo tomorrow..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitStageBtn">Update Stage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: Converted Onboarding Credentials Modal (No Email Sent - Copy / WhatsApp Share) -->
<div class="modal fade" id="leadOnboardSuccessModal" tabindex="-1" aria-labelledby="leadOnboardSuccessModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="check-check" class="fs-18"></i>
                    <h5 class="modal-title fs-16 fw-semibold text-white mb-0" id="leadOnboardSuccessModalLabel">Tenant Successfully Onboarded!</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-success-subtle border border-success-subtle py-2 px-3 fs-13 mb-3">
                    <i data-lucide="shield-check" class="fs-14 align-middle me-1"></i>
                    Company created, admin privileges assigned, and subscription activated. <strong>(No email sent)</strong>
                </div>

                <div class="card border rounded-3 bg-light p-3 mb-3">
                    <div class="row g-2 fs-13">
                        <div class="col-4 text-muted">Company:</div>
                        <div class="col-8 fw-semibold text-dark" id="succCompName">-</div>

                        <div class="col-4 text-muted">Contact:</div>
                        <div class="col-8 fw-semibold text-dark" id="succContactName">-</div>

                        <div class="col-4 text-muted">Plan:</div>
                        <div class="col-8"><span class="badge bg-primary-subtle text-primary" id="succPlanName">-</span></div>

                        <div class="col-4 text-muted">Login URL:</div>
                        <div class="col-8 text-break fw-semibold text-primary" id="succLoginUrl">-</div>

                        <div class="col-4 text-muted">Username/Email:</div>
                        <div class="col-8 fw-semibold text-dark font-monospace" id="succEmail">-</div>

                        <div class="col-4 text-muted">Password:</div>
                        <div class="col-8 fw-bold text-success font-monospace" id="succPassword">-</div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-50" id="btnCopyCreds">
                        <i data-lucide="copy" class="fs-14 align-middle me-1"></i> Copy Credentials
                    </button>
                    <a href="#" target="_blank" class="btn btn-success w-50" id="btnWaShare">
                        <i data-lucide="message-circle" class="fs-14 align-middle me-1"></i> Send on WhatsApp
                    </a>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 4: Follow-up & Reminder Modal -->
<div class="modal fade" id="leadFollowupModal" tabindex="-1" aria-labelledby="leadFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="calendar-clock" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-semibold mb-0" id="leadFollowupModalLabel">Lead Follow-ups & Reminders</h5>
                        <small class="text-muted" id="followupLeadSub">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Add New Follow-up Box -->
                <div class="card border rounded-3 p-3 mb-3 bg-light-subtle">
                    <h6 class="fs-13 fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                        <i data-lucide="plus-circle" class="fs-14 text-primary"></i> Schedule New Follow-up / Reminder
                    </h6>
                    <form id="addFollowupForm">
                        <input type="hidden" name="action" value="add_followup">
                        <input type="hidden" name="lead_id" id="followupLeadId" value="0">
                        
                        <div class="row g-2 mb-2">
                            <div class="col-md-3">
                                <label class="form-label fs-12 fw-semibold">Type</label>
                                <select class="form-select form-select-sm" name="followup_type" id="fuType" required>
                                    <?php foreach ($leadFollowupTypes as $fType): ?>
                                        <option value="<?= htmlspecialchars($fType['id']) ?>"><?= htmlspecialchars($fType['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fs-12 fw-semibold">Date & Reminder Time</label>
                                <input type="text" class="form-control form-control-sm" name="followup_date" id="fuDateTime" placeholder="Select Date & Time" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12 fw-semibold">Lead Stage</label>
                                <select class="form-select form-select-sm" name="stage" id="fuStage">
                                    <?php foreach ($leadStatuses as $stg): ?>
                                        <option value="<?= (int)$stg['id'] ?>"><?= htmlspecialchars($stg['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fs-12 fw-semibold">Discussion Remarks / Action Plan</label>
                            <textarea class="form-control form-control-sm" name="remarks" id="fuRemarks" rows="2" placeholder="e.g. Call to clarify pricing packages, user interested in 10-account tier..." required></textarea>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-light btn-sm me-1" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSubmitFollowup">
                                <i data-lucide="bell" class="fs-13 align-middle me-1"></i> Save Reminder & Follow-up
                            </button>
                        </div>
                    </form>
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
    // 1. Open "Convert to Company" Modal
    $(document).on('click', '.btn-convert-lead-action', function(e) {
        e.preventDefault();
        let leadId = $(this).data('id');
        let busName = $(this).data('name') || '';
        let person = $(this).data('person') || '';
        let mobile = $(this).data('mobile') || '';
        let email = $(this).data('email') || '';
        let planId = $(this).data('plan-id') || '';

        $('#convertLeadId').val(leadId);
        $('#showConvertCompanyName').text(busName || '-');
        $('#showConvertContactName').text(person || '-');
        $('#showConvertMobileNo').text(mobile || '-');
        $('#showConvertEmail').text(email || '-');
        $('#convertPassword').val('');

        if (planId) {
            $('#convertPlanId').val(planId);
        } else {
            $('#convertPlanId').val('');
        }

        let convModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('convertLeadModal'));
        convModal.show();
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    // 2. Submit Convert Form
    $('#convertLeadForm').on('submit', function(e) {
        e.preventDefault();

        // Strong password validation matching Company module
        let pwd = $('#convertPassword').val().trim();
        let pwdRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,12}$/;
        if (!pwdRegex.test(pwd)) {
            showToast('Password must contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and be between 8 and 12 characters.', 'error');
            $('#convertPassword').focus();
            return false;
        }

        let $btn = $('#submitConvertBtn');
        let $text = $('#submitConvertText');
        let $loader = $('#submitConvertLoader');

        $btn.prop('disabled', true);
        $text.addClass('d-none');
        $loader.removeClass('d-none');

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    let modalObj = bootstrap.Modal.getInstance(document.getElementById('convertLeadModal'));
                    if (modalObj) modalObj.hide();

                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }

                    // Display credentials modal for instant copying / WhatsApp sharing (Without email)
                    if (res.data) {
                        $('#succCompName').text(res.data.company_name);
                        $('#succContactName').text(res.data.person_name);
                        $('#succPlanName').text(res.data.plan_name);
                        $('#succLoginUrl').text(res.data.login_url);
                        $('#succEmail').text(res.data.email);
                        $('#succPassword').text(res.data.password);
                        $('#btnWaShare').attr('href', res.data.wa_link);

                        // Attach copy logic
                        $('#btnCopyCreds').off('click').on('click', function() {
                            let copyText = "Workspace: " + res.data.company_name + "\nLogin URL: " + res.data.login_url + "\nEmail: " + res.data.email + "\nPassword: " + res.data.password;
                            navigator.clipboard.writeText(copyText).then(function() {
                                showToast('Credentials copied to clipboard!', 'success');
                            });
                        });

                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }

                        let succModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('leadOnboardSuccessModal'));
                        succModal.show();
                    }
                } else {
                    showToast(res.message || 'Failed to convert lead', 'error');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                showToast('Server error while converting lead.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $text.removeClass('d-none');
                $loader.addClass('d-none');
            }
        });
    });

    // 3. Open "Change Stage" Modal
    $(document).on('click', '.btn-stage-lead-action', function(e) {
        e.preventDefault();
        let leadId = $(this).data('id');
        let busName = $(this).data('name') || '';
        let stage = $(this).data('stage') || 'new';

        $('#stageLeadId').val(leadId);
        $('#stageLeadTitle').text(busName);
        $('#stageSelect').val(stage);

        let stageModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('leadStageModal'));
        stageModal.show();
    });

    // 4. Submit Change Stage Form
    $('#leadStageForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#submitStageBtn');
        $btn.prop('disabled', true);

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    let modalObj = bootstrap.Modal.getInstance(document.getElementById('leadStageModal'));
                    if (modalObj) modalObj.hide();

                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to update stage', 'error');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                showToast('Server error while updating stage.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // 4.1 Quick Inline Change Lead Status from Table Dropdown
    $(document).on('change', '.lead-stage-inline-select', function() {
        let $select = $(this);
        let leadId = $select.data('id');
        let newStage = $select.val();
        let oldStage = $select.data('current');
        let newColor = $select.find('option:selected').data('color') || '#4f46e5';

        if (!leadId || !newStage) return;

        // Instantly update color on selectbox and chevron SVG icon
        let svgColor = encodeURIComponent(newColor);
        let newChevron = "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='" + svgColor + "' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e\")";

        $select.css({
            'color': newColor,
            'background-color': newColor + '1F',
            'border-color': newColor + '4D',
            'background-image': newChevron
        });

        $select.prop('disabled', true);

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: {
                action: 'update_stage',
                lead_id: leadId,
                new_stage: newStage
            },
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message || 'Lead status updated successfully.', 'success');
                    $select.data('current', newStage);
                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to update status', 'error');
                    $select.val(oldStage);
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                showToast('Server error while updating status.', 'error');
                $select.val(oldStage);
            },
            complete: function() {
                $select.prop('disabled', false);
            }
        });
    });

    // 5. Open "Follow-up & Reminder" Modal
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#fuDateTime', {
            enableTime: true,
            dateFormat: "d-m-Y H:i",
            altInput: true,
            altFormat: "d-m-Y h:i K",
            time_24hr: false,
            minDate: "today"
        });
    }

    function loadFollowupList(leadId) {
        let $box = $('#fuListContainer');
        $box.html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading follow-ups...</div>');

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: { action: 'get_followups', lead_id: leadId },
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    $('#fuCountBadge').text((res.data ? res.data.length : 0) + ' records');
                    if (!res.data || res.data.length === 0) {
                        $box.html('<p class="text-muted text-center fs-12 py-3 mb-0">No follow-ups recorded yet. Schedule one above!</p>');
                        return;
                    }

                    let html = '<div class="d-flex flex-column gap-2">';
                    res.data.forEach(function(item) {
                        let typeIcon = 'phone-call';
                        let typeLower = (item.type_label || item.followup_type || '').toLowerCase();
                        if (typeLower.indexOf('whatsapp') !== -1) typeIcon = 'message-circle';
                        else if (typeLower.indexOf('demo') !== -1) typeIcon = 'monitor';
                        else if (typeLower.indexOf('meet') !== -1) typeIcon = 'users';
                        else if (typeLower.indexOf('mail') !== -1 || typeLower.indexOf('note') !== -1) typeIcon = 'mail';

                        let statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11">Pending</span>';
                        if (item.reminder_status === 'completed') {
                            statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle fs-11"><i data-lucide="check" class="fs-10 align-middle"></i> Completed</span>';
                        } else if (item.reminder_status === 'missed') {
                            statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-11">Missed</span>';
                        }

                        let actionBtns = '';
                        if (item.reminder_status === 'pending') {
                            actionBtns = '<button type="button" class="btn btn-outline-success btn-xs py-0 px-2 fs-11 btn-mark-fu" data-id="' + item.id + '" data-status="completed" title="Mark Done"><i data-lucide="check" class="fs-10 align-middle"></i> Done</button>';
                        }

                        let displayDate = item.formatted_date || item.followup_date || '-';
                        let displayType = item.type_label || item.followup_type || 'Followup';

                        html += '<div class="p-2 rounded-2 border bg-light-subtle d-flex flex-column gap-1">' +
                                    '<div class="d-flex align-items-center justify-content-between">' +
                                        '<div class="d-flex align-items-center gap-2">' +
                                            '<span class="badge bg-primary-subtle text-primary text-uppercase fs-10 px-2 py-1"><i data-lucide="' + typeIcon + '" class="fs-11 align-middle me-1"></i>' + displayType + '</span>' +
                                            '<span class="fw-bold fs-12 text-dark">' + displayDate + '</span>' +
                                            '<span class="badge bg-secondary-subtle text-secondary fs-10">' + (item.stage_label || item.stage || 'Stage') + '</span>' +
                                        '</div>' +
                                        '<div class="d-flex align-items-center gap-1">' +
                                            statusBadge +
                                            actionBtns +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="fs-12 text-muted ps-1">' + $('<div>').text(item.remarks).html() + '</div>' +
                                '</div>';
                    });
                    html += '</div>';
                    $box.html(html);

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                } else {
                    $box.html('<p class="text-danger text-center fs-12 py-3 mb-0">' + (res.message || 'Error loading') + '</p>');
                }
            },
            error: function() {
                $box.html('<p class="text-danger text-center fs-12 py-3 mb-0">Server error loading follow-ups.</p>');
            }
        });
    }

    $(document).on('click', '.btn-followup-lead-action', function(e) {
        e.preventDefault();
        let leadId = $(this).data('id');
        let busName = $(this).data('name') || '';
        let stage = $(this).data('stage') || 'contacted';

        $('#followupLeadId').val(leadId);
        $('#followupLeadSub').text(busName + ' (Lead #' + leadId + ')');
        $('#fuStage').val(stage);
        $('#fuRemarks').val('');

        loadFollowupList(leadId);

        let fuModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('leadFollowupModal'));
        fuModal.show();
    });

    // 6. Submit New Follow-up
    $('#addFollowupForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#btnSubmitFollowup');
        $btn.prop('disabled', true);
        let leadId = $('#followupLeadId').val();

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    $('#fuRemarks').val('');
                    let fuModalInstance = bootstrap.Modal.getInstance(document.getElementById('leadFollowupModal'));
                    if (fuModalInstance) fuModalInstance.hide();

                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }

                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to save follow-up', 'error');
                }
            },
            error: function() {
                showToast('Server error while saving follow-up.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // 7. Mark Follow-up Completed
    $(document).on('click', '.btn-mark-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        let newStatus = $(this).data('status');
        let leadId = $('#followupLeadId').val();

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: { action: 'update_followup_status', followup_id: fuId, status: newStatus },
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    loadFollowupList(leadId);
                } else {
                    showToast(res.message || 'Error updating status', 'error');
                }
            }
        });
    });
});
</script>
</body>
</html>
