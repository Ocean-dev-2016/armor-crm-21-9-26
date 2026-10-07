<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

// Check permissions
checkPermissionOrDeny('company-lead', 'views');

$encLeadId = $_GET['lead_id'] ?? ($_GET['id'] ?? '');
$filterLeadId = 0;
if (!empty($encLeadId)) {
    $filterLeadId = decrypt_id($encLeadId);
    if (!$filterLeadId && is_numeric($encLeadId)) {
        $filterLeadId = (int)$encLeadId;
    }
}

// Fetch single lead details if filtered by lead_id
$lead = null;
if ($filterLeadId > 0) {
    $leadSql = "SELECT cl.*, c.name as company_name, soi.name as source_name, u.name as assigned_name 
                FROM company_lead cl 
                LEFT JOIN company c ON c.id = cl.company_id 
                LEFT JOIN source_of_inquiry soi ON soi.id = cl.source_of_inquiry_id 
                LEFT JOIN users u ON u.id = cl.assigned_to 
                WHERE cl.id = $filterLeadId";
    if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
        $leadSql .= " AND cl.company_id = " . (int)$_SESSION['company_id'];
    }
    $userType = $_SESSION['user_type'] ?? '';
    $currentUserId = getCurrentUserId();
    if ($userType === 'user' && $currentUserId > 0) {
        $leadSql .= " AND cl.assigned_to = $currentUserId";
    }
    $leadSql .= " LIMIT 1";
    $lead = db_row($leadSql);
}

$pageNm = 'Company Lead Follow Up';
$breadcrumbType = 'list';
$fields = '';
$module = 'company-lead';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Lead Summary Card (if specific lead is opened) -->
<?php if ($lead): ?>
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-16" style="width: 44px; height: 44px;">
                                <?= strtoupper(substr($lead['customer_name'] ?? 'L', 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">
                                    <?= htmlspecialchars($lead['customer_name'] ?? '') ?>
                                    <span class="fs-13 fw-normal text-muted ms-2">(<?= htmlspecialchars($lead['inquiry_no'] ?: ('INQ-' . $lead['id'])) ?>)</span>
                                    <?php if ($isSuperadmin && !empty($lead['company_name'])): ?>
                                        <span class="badge bg-dark-subtle text-dark fs-11 ms-1"><?= htmlspecialchars($lead['company_name']) ?></span>
                                    <?php endif; ?>
                                </h5>
                                <div class="text-muted fs-13 d-flex flex-wrap align-items-center gap-3 mt-1">
                                    <span><i data-lucide="user" class="fs-13 align-middle text-muted me-1"></i><?= htmlspecialchars($lead['contact_person'] ?? '-') ?></span>
                                    <span><i data-lucide="phone" class="fs-13 align-middle text-muted me-1"></i><?= htmlspecialchars($lead['mobile_no'] ?? '-') ?></span>
                                    <?php if (!empty($lead['inquiry_status'])): ?>
                                        <span><i data-lucide="tag" class="fs-13 align-middle text-muted me-1"></i>Status: <strong><?= htmlspecialchars($lead['inquiry_status']) ?></strong></span>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['followup_status'])): ?>
                                        <span><i data-lucide="clock" class="fs-13 align-middle text-muted me-1"></i>Follow-up: <strong><?= htmlspecialchars($lead['followup_status']) ?></strong></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?= SITE_URL ?>company-lead-followup" class="btn btn-outline-secondary btn-sm">
                                <i data-lucide="list" class="fs-14 align-middle me-1"></i> All Followups
                            </a>
                            <button type="button" class="btn btn-primary btn-sm btn-quick-add-fu"
                                data-id="<?= $lead['id'] ?>"
                                data-company-id="<?= (int)$lead['company_id'] ?>"
                                data-inquiry-no="<?= htmlspecialchars($lead['inquiry_no'] ?: ('INQ-' . $lead['id']), ENT_QUOTES) ?>"
                                data-inquiry-date="<?= !empty($lead['inquiry_date']) ? date('d-m-Y', strtotime($lead['inquiry_date'])) : date('d-m-Y') ?>"
                                data-customer-name="<?= htmlspecialchars($lead['customer_name'] ?? '', ENT_QUOTES) ?>"
                                data-company-name="<?= htmlspecialchars($lead['company_name'] ?? '', ENT_QUOTES) ?>"
                                data-contact-person="<?= htmlspecialchars($lead['contact_person'] ?? '', ENT_QUOTES) ?>"
                                data-mobile="<?= htmlspecialchars($lead['mobile_no'] ?? '', ENT_QUOTES) ?>"
                                data-whatsapp="<?= htmlspecialchars($lead['whatsapp_no'] ?? '', ENT_QUOTES) ?>"
                                data-email="<?= htmlspecialchars($lead['email'] ?? '', ENT_QUOTES) ?>"
                                data-inquiry-status="<?= htmlspecialchars($lead['inquiry_status'] ?? '', ENT_QUOTES) ?>"
                                data-followup-status="<?= htmlspecialchars($lead['followup_status'] ?? '', ENT_QUOTES) ?>">
                                <i data-lucide="plus-circle" class="fs-14 align-middle me-1"></i> Add Follow-up
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Follow-up List Standard DataTable -->
<?php
$tbl = 'company_lead_followups';
$ajaxUrl = SITE_URL . 'admin/setting/company-lead/followup-ajax.php' . ($filterLeadId > 0 ? '?lead_id=' . $filterLeadId : '');

if ($isSuperadmin) {
    $tableHeaders = [
        'Sr No.',
        'Company',
        'Inquiry / Customer',
        'Follow-up Through',
        'Follow-up Date & Time',
        'Reason',
        'Inquiry Status',
        'Remarks / Details',
        'Response',
        'Status',
        'Created By',
        'Created Date',
        'Action'
    ];
} else {
    $tableHeaders = [
        'Sr No.',
        'Inquiry / Customer',
        'Follow-up Through',
        'Follow-up Date & Time',
        'Reason',
        'Inquiry Status',
        'Remarks / Details',
        'Response',
        'Status',
        'Created By',
        'Created Date',
        'Action'
    ];
}

include BASE_PATH . '/component/datatable.php';
?>

<?php
// Active followup types
$followupTypes = db_rows("SELECT id, name FROM lead_followup_type WHERE status = 1");

// Active followup reasons
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$fuReasonCondition = "status = 1";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $fuReasonCondition .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
}
$followupReasons = db_rows("SELECT id, name, company_id FROM followup_reason WHERE $fuReasonCondition");

// Active marketing statuses
$mktLeadCondition = "status = 1 AND type = 'Lead'";
$mktFuCondition   = "status = 1 AND type = 'Follow Up'";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $mktLeadCondition .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
    $mktFuCondition   .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
}
$inquiryStatuses  = db_rows("SELECT id, name, color, company_id FROM marketing_status WHERE $mktLeadCondition ORDER BY order_by ASC, name ASC");
$followupStatuses = db_rows("SELECT id, name, color, company_id FROM marketing_status WHERE $mktFuCondition ORDER BY order_by ASC, name ASC");
?>

<!-- MODAL: Add / Edit Follow Up for Inquiry -->
<div class="modal fade" id="addCompanyFollowupModal" tabindex="-1" aria-labelledby="addCompanyFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="calendar-clock" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-bold text-dark mb-0" id="addCompanyFollowupModalLabel">
                            Add Follow Up for Inquiry
                        </h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCompanyFollowupForm">
                <input type="hidden" name="action" id="fu_form_action" value="add_followup">
                <input type="hidden" name="followup_id" id="fu_form_fu_id" value="0">
                <input type="hidden" name="lead_id" id="fu_lead_id" value="0">

                <div class="modal-body p-4">
                    <!-- Inquiry Summary Grid -->
                    <div class="row g-2 mb-4 fs-13">
                        <div class="col-md-6">
                            <div class="mb-1"><strong class="text-dark">Inquiry :-</strong> <span id="fu_disp_inquiry_no" class="text-muted">-</span></div>
                            <div class="mb-1"><strong class="text-dark">Company Name :-</strong> <span id="fu_disp_company_name" class="text-muted">-</span></div>
                            <div class="mb-1"><strong class="text-dark">Contact Person Name :-</strong> <span id="fu_disp_contact_person" class="text-muted">-</span></div>
                            <div class="mb-1"><strong class="text-dark">Email :-</strong> <a href="javascript:void(0);" id="fu_disp_email_link" class="text-primary">-</a></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-1"><strong class="text-dark">Inquiry Date :-</strong> <span id="fu_disp_inquiry_date" class="text-muted">-</span></div>
                            <div class="mb-1"><strong class="text-dark">Customer Name :-</strong> <span id="fu_disp_customer_name" class="text-muted">-</span></div>
                            <div class="mb-1"><strong class="text-dark">Mobile No :-</strong> <a href="javascript:void(0);" id="fu_disp_mobile_link" class="text-primary">-</a></div>
                            <div class="mb-1"><strong class="text-dark">Whatsapp No :-</strong> <a href="javascript:void(0);" id="fu_disp_whatsapp_link" class="text-primary">-</a></div>
                        </div>
                    </div>

                    <!-- Row 1: Follow Up Date and Time | Follow Up Reason -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Follow Up Date and Time</label>
                            <input type="text" class="form-control" name="followup_date_time" id="fu_date_time" placeholder="Select Follow Up Date and Time">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Follow Up Reason</label>
                            <select class="form-select" name="reason_id" id="fu_reason_id" placeholder="Select Followup Reason">
                                <option value="">Select Followup Reason</option>
                                <?php foreach ($followupReasons as $fr): ?>
                                    <option value="<?= (int)$fr['id'] ?>" data-company-id="<?= (int)($fr['company_id'] ?? 0) ?>">
                                        <?= htmlspecialchars($fr['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Follow Up Through | Inquiry Status -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Follow Up Through</label>
                            <select class="form-select" name="followup_type_id" id="fu_type_id" placeholder="Select Follow Up Through">
                                <option value="">Select Follow Up Through</option>
                                <?php foreach ($followupTypes as $ft): ?>
                                    <option value="<?= (int)$ft['id'] ?>">
                                        <?= htmlspecialchars($ft['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Inquiry Status</label>
                            <select class="form-select" name="inquiry_status" id="fu_inquiry_status" placeholder="Select Inquiry Status">
                                <option value="">Select Inquiry Status</option>
                                <?php foreach ($inquiryStatuses as $inqst): ?>
                                    <option value="<?= (int)$inqst['id'] ?>" data-company-id="<?= (int)($inqst['company_id'] ?? 0) ?>">
                                        <?= htmlspecialchars($inqst['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Follow Up Details -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fs-13 fw-medium">Follow Up Details</label>
                            <textarea class="form-control" name="followup_details" id="fu_details" rows="2" placeholder="Enter Follow Up Details"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4" id="submitAddFuBtn">
                        <span id="submitAddFuText">Add Followup</span>
                        <span id="submitAddFuLoader" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Add Followup Response Modal (Matching Lead Followup History Design) -->
<div class="modal fade" id="followupResponseModal" tabindex="-1" aria-labelledby="followupResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="message-square-check" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-semibold mb-0" id="followupResponseModalLabel">Add Followup Response</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followupResponseForm">
                <input type="hidden" name="action" value="submit_company_followup_response">
                <input type="hidden" name="followup_id" id="respFollowupId" value="0">
                <input type="hidden" name="lead_id" id="respLeadId" value="0">
                <input type="hidden" name="company_id" id="respCompanyId" value="0">
                
                <div class="modal-body p-4">
                    <!-- Inquiry / Customer Info Header Cards -->
                    <div class="row g-2 mb-3 bg-light-subtle p-3 rounded border">
                        <div class="col-md-6">
                            <div class="fs-13 text-dark"><strong>Inquiry :-</strong> <span id="respInquiryNo" class="text-primary fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Company Name :-</strong> <span id="respCompanyName" class="fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Customer Name :-</strong> <span id="respCustomerName" class="fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Mobile No :-</strong> <a href="#" id="respMobileNo" class="text-primary text-decoration-none">-</a></div>
                        </div>
                        <div class="col-md-6">
                            <div class="fs-13 text-dark"><strong>Inquiry Date :-</strong> <span id="respInquiryDate" class="fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Contact Person Name :-</strong> <span id="respContactPerson" class="fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Whatsapp No :-</strong> <a href="#" id="respWhatsappNo" class="text-success text-decoration-none">-</a></div>
                        </div>
                    </div>

                    <!-- Response Input -->
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Response</label>
                        <textarea class="form-control" name="response" id="respResponseText" rows="3" placeholder="Enter Follow Up Response Details" required></textarea>
                    </div>

                    <!-- Follow-up Action Select -->
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Follow-up Action</label>
                        <select class="form-select" name="followup_action" id="respFollowupAction" required>
                            <option value="">Select Follow-up Action</option>
                            <option value="next-followup">Next Follow-up</option>
                            <option value="end-followup">End Follow-up</option>
                        </select>
                    </div>

                    <!-- Conditional Next Followup Fields (Shown when 'next-followup' selected) -->
                    <div id="nextFollowupFields" class="d-none border rounded p-3 bg-light-subtle mb-2">
                        <h6 class="fs-13 fw-bold text-dark mb-3 d-flex align-items-center gap-1">
                            <i data-lucide="calendar-plus" class="fs-15 text-primary"></i> Schedule Next Follow-up
                        </h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Next Follow-up Date & Time</label>
                                <input type="text" class="form-control" name="next_followup_date" id="respNextDateTime" placeholder="Select Next Date & Time">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Next Follow-up Reason</label>
                                <select class="form-select" name="next_reason_id" id="respNextReason">
                                    <option value="">Select Followup Reason</option>
                                    <?php foreach ($followupReasons as $fr): ?>
                                        <option value="<?= (int)$fr['id'] ?>" data-company-id="<?= (int)($fr['company_id'] ?? 0) ?>">
                                            <?= htmlspecialchars($fr['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Next Follow-up Through</label>
                                <select class="form-select" name="next_followup_type_id" id="respNextType">
                                    <option value="">Select Follow Up Through</option>
                                    <?php foreach ($followupTypes as $ft): ?>
                                        <option value="<?= (int)$ft['id'] ?>">
                                            <?= htmlspecialchars($ft['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Inquiry Status</label>
                                <select class="form-select" name="next_inquiry_status" id="respNextInqStatus">
                                    <option value="">Select Inquiry Status</option>
                                    <?php foreach ($inquiryStatuses as $inqst): ?>
                                        <option value="<?= (int)$inqst['id'] ?>" data-company-id="<?= (int)($inqst['company_id'] ?? 0) ?>">
                                            <?= htmlspecialchars($inqst['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fs-12 fw-semibold">Discuss Topic / Next Plan</label>
                                <textarea class="form-control" name="next_discuss_topic" id="respNextDiscuss" rows="2" placeholder="e.g. Call back after price quotation review, discuss customized features..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitRespModal">
                        <span id="btnSubmitRespText"><i data-lucide="check" class="fs-13 align-middle me-1"></i> Submit Response</span>
                        <span id="btnSubmitRespLoader" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include BASE_PATH . '/include/footer.php';
?>

<script>
$(document).ready(function() {
    let isSuperadmin = <?= $isSuperadmin ? 'true' : 'false' ?>;

    // Cache Master Options
    let allReasons = <?= json_encode($followupReasons) ?>;
    let allInqStatuses = <?= json_encode($inquiryStatuses) ?>;
    let allFuStatuses = <?= json_encode($followupStatuses) ?>;

    // Initialize flatpickr on followup datetime field with 12hr AM/PM format
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#fu_date_time", {
            enableTime: true,
            dateFormat: "d-m-Y h:i K",
            time_24hr: false,
            defaultDate: new Date()
        });
    }

    // Helper: Safely reload DataTable
    function reloadDataTableSafe() {
        if ($.fn.DataTable && $('.data-table').length) {
            $('.data-table').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().ajax.reload(null, false);
                }
            });
        }
    }

    // Always reload DataTable when modals are closed/dismissed
    $('#addCompanyFollowupModal, #followupResponseModal').on('hidden.bs.modal', function () {
        reloadDataTableSafe();
    });

    // Helper: Initialize TomSelect on dropdown element
    function initTom(selector, placeholderText) {
        let el = document.querySelector(selector);
        if (el && typeof TomSelect !== 'undefined') {
            if (el.tomselect) {
                return el.tomselect;
            }
            return new TomSelect(el, {
                create: false,
                allowEmptyOption: true,
                placeholder: placeholderText || "Select...",
                dropdownParent: 'body'
            });
        }
        return null;
    }

    let tomReason = initTom('#fu_reason_id', 'Select Followup Reason');
    let tomType   = initTom('#fu_type_id', 'Select Follow Up Through');
    let tomInq    = initTom('#fu_inquiry_status', 'Select Inquiry Status');

    function updateTomOptions(selector, items, placeholderText, selectedVal) {
        let el = document.querySelector(selector);
        if (!el) return;
        if (el.tomselect) {
            let ts = el.tomselect;
            ts.clear();
            ts.clearOptions();
            ts.addOption({ value: '', text: placeholderText });
            $.each(items, function(i, item) {
                let val = (item.id !== undefined && item.name !== undefined && selector.indexOf('status') === -1) ? item.id : item.name;
                ts.addOption({ value: String(val), text: item.name });
            });
            ts.refreshOptions(false);
            if (selectedVal !== undefined && selectedVal !== null && selectedVal !== '') {
                ts.setValue(String(selectedVal), true);
            }
        } else {
            let $select = $(selector);
            $select.empty().append('<option value="">' + placeholderText + '</option>');
            $.each(items, function(i, item) {
                let val = (item.id !== undefined && item.name !== undefined && selector.indexOf('status') === -1) ? item.id : item.name;
                let isSel = (String(val) === String(selectedVal)) ? ' selected' : '';
                $select.append('<option value="' + val + '"' + isSel + '>' + item.name + '</option>');
            });
        }
    }

    // Quick Add Followup button
    $(document).on('click', '.btn-quick-add-fu', function() {
        let btn = $(this);
        let leadId = btn.data('id');
        let companyId = parseInt(btn.data('company-id') || 0);

        $('#fu_lead_id').val(leadId);
        $('#fu_disp_inquiry_no').text(btn.data('inquiry-no') || '-');
        $('#fu_disp_company_name').text(btn.data('company-name') || '-');
        $('#fu_disp_contact_person').text(btn.data('contact-person') || '-');

        let emailVal = btn.data('email') || '';
        if (emailVal) {
            $('#fu_disp_email_link').text(emailVal).attr('href', 'mailto:' + emailVal);
        } else {
            $('#fu_disp_email_link').text('-').removeAttr('href');
        }

        $('#fu_disp_inquiry_date').text(btn.data('inquiry-date') || '-');
        $('#fu_disp_customer_name').text(btn.data('customer-name') || '-');

        let mobileVal = btn.data('mobile') != null ? String(btn.data('mobile')) : '';
        if (mobileVal) {
            $('#fu_disp_mobile_link').text(mobileVal).attr('href', 'tel:' + mobileVal);
        } else {
            $('#fu_disp_mobile_link').text('-').removeAttr('href');
        }

        let rawWa = btn.data('whatsapp');
        let whatsappVal = (rawWa != null && rawWa !== '') ? String(rawWa) : mobileVal;
        if (whatsappVal) {
            let cleanPhone = String(whatsappVal).replace(/[^0-9]/g, '');
            $('#fu_disp_whatsapp_link').text(whatsappVal).attr('href', 'https://wa.me/' + cleanPhone).attr('target', '_blank');
        } else {
            $('#fu_disp_whatsapp_link').text('-').removeAttr('href');
        }

        $('#fu_details').val('');

        let filteredReasons = [];
        $.each(allReasons, function(i, item) {
            let itemCid = parseInt(item.company_id || 0);
            if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                filteredReasons.push(item);
            }
        });
        updateTomOptions('#fu_reason_id', filteredReasons, 'Select Followup Reason', '');

        if (tomType) tomType.setValue('', true);

        let inqStatus = btn.data('inquiry-status') || '';
        let filteredInqStatuses = [];
        $.each(allInqStatuses, function(i, item) {
            let itemCid = parseInt(item.company_id || 0);
            if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                filteredInqStatuses.push(item);
            }
        });
        updateTomOptions('#fu_inquiry_status', filteredInqStatuses, 'Select Inquiry Status', inqStatus);

        if ($('#fu_date_time')[0]._flatpickr) {
            $('#fu_date_time')[0]._flatpickr.setDate(new Date());
        }

        $('#fu_form_action').val('add_followup');
        $('#fu_form_fu_id').val('0');
        $('#addCompanyFollowupModalLabel').text('Add Follow Up for Inquiry');
        $('#submitAddFuText').text('Add Followup');

        $('#addCompanyFollowupModal').modal('show');
    });

    // 2. OPEN EDIT COMPANY FOLLOWUP MODAL
    $(document).on('click', '.btn-edit-company-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        if (!fuId) return;

        $.ajax({
            url: '<?= SITE_URL ?>admin/setting/company-lead/ajax.php',
            type: 'POST',
            data: { action: 'get_company_followup', followup_id: fuId },
            dataType: 'json',
            success: function(res) {
                if (res.status === true && res.data) {
                    let d = res.data;
                    let companyId = parseInt(d.company_id || 0);

                    $('#fu_form_action').val('update_company_followup');
                    $('#fu_form_fu_id').val(d.id);
                    $('#fu_lead_id').val(d.lead_id);
                    $('#addCompanyFollowupModalLabel').text('Edit Follow Up for Inquiry');
                    $('#submitAddFuText').text('Update Followup');

                    $('#fu_disp_inquiry_no').text(d.inquiry_no || ('INQ-' + d.lead_id));
                    $('#fu_disp_company_name').text(d.company_name || '-');
                    $('#fu_disp_contact_person').text(d.contact_person || '-');

                    if (d.email) {
                        $('#fu_disp_email_link').text(d.email).attr('href', 'mailto:' + d.email);
                    } else {
                        $('#fu_disp_email_link').text('-').removeAttr('href');
                    }

                    let inqDt = d.inquiry_date ? d.inquiry_date : (d.created_at ? d.created_at.substring(0, 10) : '-');
                    $('#fu_disp_inquiry_date').text(inqDt);
                    $('#fu_disp_customer_name').text(d.customer_name || '-');

                    if (d.mobile_no) {
                        $('#fu_disp_mobile_link').text(d.mobile_no).attr('href', 'tel:' + d.mobile_no);
                    } else {
                        $('#fu_disp_mobile_link').text('-').removeAttr('href');
                    }

                    if (d.whatsapp_no || d.mobile_no) {
                        let wa = String(d.whatsapp_no || d.mobile_no);
                        let cleanWa = wa.replace(/[^0-9]/g, '');
                        $('#fu_disp_whatsapp_link').text(wa).attr('href', 'https://wa.me/' + cleanWa).attr('target', '_blank');
                    } else {
                        $('#fu_disp_whatsapp_link').text('-').removeAttr('href');
                    }

                    $('#fu_details').val(d.remarks || '');

                    // Reason options
                    let filteredReasons = [];
                    $.each(allReasons, function(i, item) {
                        let itemCid = parseInt(item.company_id || 0);
                        if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                            filteredReasons.push(item);
                        }
                    });
                    updateTomOptions('#fu_reason_id', filteredReasons, 'Select Followup Reason', d.reason_id || '');

                    // Type
                    if (tomType) tomType.setValue(String(d.followup_type_id || ''), true);

                    // Inquiry status
                    let filteredInqStatuses = [];
                    $.each(allInqStatuses, function(i, item) {
                        let itemCid = parseInt(item.company_id || 0);
                        if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                            filteredInqStatuses.push(item);
                        }
                    });
                    updateTomOptions('#fu_inquiry_status', filteredInqStatuses, 'Select Inquiry Status', d.inquiry_status || '');

                    if ($('#fu_date_time')[0]._flatpickr && d.formatted_date) {
                        $('#fu_date_time')[0]._flatpickr.setDate(d.formatted_date, true, "d-m-Y h:i K");
                    }

                    $('#addCompanyFollowupModal').modal('show');
                } else {
                    showToast(res.message || 'Error fetching record', 'error');
                }
            },
            error: function() {
                showToast('Server error while fetching follow up.', 'error');
            }
        });
    });

    // 3. DELETE COMPANY FOLLOWUP
    $(document).on('click', '.btn-delete-company-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        if (!fuId) return;

        Swal.fire({
            title: 'Delete Follow-up?',
            text: 'Are you sure you want to delete this follow-up record? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= SITE_URL ?>admin/setting/company-lead/ajax.php',
                    type: 'POST',
                    data: { action: 'delete_company_followup', followup_id: fuId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === true) {
                            showToast(res.message, 'success');
                            if (typeof refreshFollowupNotifications === 'function') {
                                refreshFollowupNotifications();
                            }
                            reloadDataTableSafe();
                        } else {
                            showToast(res.message || 'Failed to delete follow up.', 'error');
                        }
                    },
                    error: function() {
                        showToast('Server error while deleting follow up.', 'error');
                    }
                });
            }
        });
    });

    // 4. OPEN FOLLOWUP RESPONSE MODAL
    let nextFpInstance = null;
    if (typeof flatpickr !== 'undefined') {
        nextFpInstance = flatpickr("#respNextDateTime", {
            enableTime: true,
            dateFormat: "d-m-Y h:i K",
            time_24hr: false,
            defaultDate: new Date(Date.now() + 86400000)
        });
    }

    let tomRespNextReason = initTom('#respNextReason', 'Select Followup Reason');
    let tomRespNextType   = initTom('#respNextType', 'Select Follow Up Through');
    let tomRespNextInq    = initTom('#respNextInqStatus', 'Select Inquiry Status');

    $(document).on('click', '.btn-open-response-modal', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let fuId          = $btn.data('id');
        let leadId        = $btn.data('lead-id') || 0;
        let companyId     = parseInt($btn.data('company-id') || 0);
        let inquiry       = $btn.data('inquiry') || ('INQ-' + leadId);
        let inqDate       = $btn.data('inquiry-date') || '-';
        let companyName   = $btn.data('company-name') || '-';
        let custName      = $btn.data('cust-name') || '-';
        let contactPerson = $btn.data('contact-person') || '-';
        let mobile        = String($btn.data('mobile') || '').trim();
        let whatsapp      = String($btn.data('whatsapp') || mobile).trim();

        $('#respFollowupId').val(fuId);
        $('#respLeadId').val(leadId);
        $('#respCompanyId').val(companyId);
        $('#respInquiryNo').text(inquiry);
        $('#respCompanyName').text(companyName);
        $('#respInquiryDate').text(inqDate);
        $('#respCustomerName').text(custName);
        $('#respContactPerson').text(contactPerson);

        $('#respMobileNo').text(mobile || '-');
        if (mobile && mobile !== '-') {
            $('#respMobileNo').attr('href', 'tel:' + mobile);
        } else {
            $('#respMobileNo').removeAttr('href');
        }

        $('#respWhatsappNo').text(whatsapp || '-');
        if (whatsapp && whatsapp !== '-') {
            let cleanPhone = whatsapp.replace(/\D/g, '');
            if (cleanPhone) {
                $('#respWhatsappNo').attr('href', 'https://wa.me/' + cleanPhone).attr('target', '_blank');
            } else {
                $('#respWhatsappNo').removeAttr('href');
            }
        } else {
            $('#respWhatsappNo').removeAttr('href');
        }

        $('#respResponseText').val('');
        $('#respFollowupAction').val('');
        $('#nextFollowupFields').addClass('d-none');
        $('#respNextDiscuss').val('');

        if (tomRespNextType) tomRespNextType.setValue('', true);

        // Update reason options for next follow-up
        let filteredReasons = [];
        $.each(allReasons, function(i, item) {
            let itemCid = parseInt(item.company_id || 0);
            if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                filteredReasons.push(item);
            }
        });
        updateTomOptions('#respNextReason', filteredReasons, 'Select Followup Reason', '');

        // Update inq status options
        let filteredInqStatuses = [];
        $.each(allInqStatuses, function(i, item) {
            let itemCid = parseInt(item.company_id || 0);
            if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                filteredInqStatuses.push(item);
            }
        });
        updateTomOptions('#respNextInqStatus', filteredInqStatuses, 'Select Inquiry Status', '');

        if (nextFpInstance) {
            nextFpInstance.setDate(new Date(Date.now() + 86400000));
        }

        $('#followupResponseModal').modal('show');
        if (window.lucide) lucide.createIcons();
    });

    // 5. Follow-up Action Change: show/hide Next Follow-up fields
    $('#respFollowupAction').on('change', function() {
        let val = $(this).val();
        if (val === 'next-followup') {
            $('#nextFollowupFields').removeClass('d-none');
            $('#respNextDateTime').prop('required', true);
        } else {
            $('#nextFollowupFields').addClass('d-none');
            $('#respNextDateTime').prop('required', false);
        }
        if (window.lucide) lucide.createIcons();
    });

    // 6. Submit Followup Response
    $('#followupResponseForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#btnSubmitRespModal');
        let $text = $('#btnSubmitRespText');
        let $loader = $('#btnSubmitRespLoader');

        $btn.prop('disabled', true);
        $text.addClass('d-none');
        $loader.removeClass('d-none');

        $.ajax({
            url: '<?= SITE_URL ?>admin/setting/company-lead/ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    $('#followupResponseModal').modal('hide');
                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    reloadDataTableSafe();
                } else {
                    showToast(res.message || 'Failed to submit response.', 'error');
                }
            },
            error: function() {
                showToast('Server error while saving response.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $text.removeClass('d-none');
                $loader.addClass('d-none');
                if (window.lucide) lucide.createIcons();
            }
        });
    });

    // SUBMIT ADD / EDIT FOLLOWUP FORM
    $('#addCompanyFollowupForm').on('submit', function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $('#submitAddFuBtn');
        let $text = $('#submitAddFuText');
        let $loader = $('#submitAddFuLoader');

        let origText = $text.text();
        $btn.prop('disabled', true);
        $text.text('Saving...');
        $loader.removeClass('d-none');

        $.ajax({
            url: '<?= SITE_URL ?>admin/setting/company-lead/ajax.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false);
                $text.text(origText);
                $loader.addClass('d-none');

                if (res.status === true) {
                    showToast(res.message, 'success');
                    $('#addCompanyFollowupModal').modal('hide');
                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    reloadDataTableSafe();
                } else {
                    showToast(res.message || 'Failed to save follow up.', 'error');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $text.text(origText);
                $loader.addClass('d-none');
                showToast('An unexpected error occurred.', 'error');
            }
        });
    });

    // Re-render Lucide icons on datatable redraw
    if (typeof dtTable !== 'undefined') {
        dtTable.on('draw', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    } else if ($.fn.DataTable && $('.data-table').length) {
        $('.data-table').on('draw.dt', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    }
});
</script>
