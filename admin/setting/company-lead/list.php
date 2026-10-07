<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('company-lead', 'views');

$pageNm = 'Company Lead';
$tbl = 'company_lead';
$showAdd = hasPermission('company-lead', 'adds');
$addType = 'redirect';
$addUrl  = SITE_URL . 'company-lead/add';
$breadcrumbType = 'list';
$fields = 'inquiry_no:Inquiry No, customer_name:Customer Name, mobile_no:Mobile No, email:Email';
$module = 'company-lead';
include BASE_PATH . '/component/breadcrumb.php';

$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

// Active marketing statuses: Lead
$mktLeadCondition = "status = 1 AND type = 'Lead'";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $mktLeadCondition .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
}
$inquiryStatuses  = db_rows("SELECT id, name, color, company_id FROM marketing_status WHERE $mktLeadCondition ORDER BY order_by ASC, name ASC");

// Active followup types from lead_followup_type table
$followupTypes = db_rows("SELECT id, name FROM lead_followup_type WHERE status = 1");

// Active followup reasons from followup_reason table (filtered by company if tenant)
$fuReasonCondition = "status = 1";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $fuReasonCondition .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
}
$followupReasons = db_rows("SELECT id, name, company_id FROM followup_reason WHERE $fuReasonCondition");

// Active Team Persons for Assign To modal
$teamCondition = "status = 1";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $teamCondition .= " AND company_id = $sessionCompanyId";
}
$teamPersons = db_rows("SELECT id, name, company_id FROM users WHERE $teamCondition ORDER BY name ASC");

// Determine default selected status (First status in ordered list, e.g. "New" or order_by ASC)
$defaultStatusId = '';
if (!empty($inquiryStatuses)) {
    // Check if there is one explicitly named 'New' or 'New Lead', else take the first one
    foreach ($inquiryStatuses as $st) {
        if (in_array(strtolower(trim($st['name'])), ['new', 'new lead', 'new inquiry'])) {
            $defaultStatusId = (string)$st['id'];
            break;
        }
    }
    if ($defaultStatusId === '') {
        $defaultStatusId = (string)$inquiryStatuses[0]['id'];
    }
}

// Card Header Right Filter for Inquiry Status
$filterFormId = '#leadFilterForm';
$cardHeaderRight = function() use ($inquiryStatuses, $defaultStatusId) {
    ob_start();
    ?>
    <form id="leadFilterForm" class="d-flex align-items-center gap-2 m-0" onsubmit="return false;">
        <div class="d-flex align-items-center gap-2">
            <label for="filter_inquiry_status" class="form-label fs-13 fw-semibold text-nowrap mb-0 text-muted">
                Inquiry Status:
            </label>
            <div style="min-width: 200px; max-width: 260px;">
                <select name="inquiry_status" id="filter_inquiry_status">
                    <option value="all">All Status</option>
                    <?php foreach ($inquiryStatuses as $st): ?>
                        <?php $isDef = ((string)$st['id'] === (string)$defaultStatusId) ? 'selected' : ''; ?>
                        <option value="<?= (int)$st['id'] ?>" data-color="<?= htmlspecialchars($st['color'] ?? '') ?>" <?= $isDef ?>>
                            <?= htmlspecialchars($st['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary <?= ($defaultStatusId !== '' && $defaultStatusId !== 'all') ? '' : 'd-none' ?>" id="btnClearLeadFilter" title="Reset to All Status">
            <i data-lucide="rotate-ccw" class="fs-13"></i>
        </button>
    </form>
    <?php
    return ob_get_clean();
};
?>
<?php
$ajaxUrl = SITE_URL . 'admin/setting/company-lead/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Inquiry No.',
    'Inquiry Date',
    'Customer Name',
    'Contact Person',
    'Mobile No.',
    'Source of Inquiry',
    'Assigned To',
    'Inquiry Status',
    'Actions'
];
include BASE_PATH . '/component/datatable.php';
?>
?>

<!-- MODAL: Add Follow Up for Inquiry (Matches user's 2nd image exactly) -->
<div class="modal fade" id="addCompanyFollowupModal" tabindex="-1" aria-labelledby="addCompanyFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fs-16 fw-bold text-dark mb-0" id="addCompanyFollowupModalLabel">
                    Add Follow Up for Inquiry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCompanyFollowupForm">
                <input type="hidden" name="action" value="add_followup">
                <input type="hidden" name="lead_id" id="fu_lead_id" value="0">

                <div class="modal-body p-4">
                    <!-- Inquiry Summary Grid (Matching User's Screenshot) -->
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

<!-- MODAL: Assign To Modal -->
<div class="modal fade" id="assignToModal" tabindex="-1" aria-labelledby="assignToModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fs-16 fw-semibold mb-0" id="assignToModalLabel">
                    <i data-lucide="user-check" class="fs-18 text-primary align-middle me-1"></i> Assign Lead To Team Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignToForm">
                <input type="hidden" name="action" value="assign_to">
                <input type="hidden" name="lead_id" id="assign_lead_id" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fs-13 text-muted">Inquiry No.</label>
                        <p class="fw-bold fs-14 mb-0 text-dark" id="assign_disp_inquiry">-</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Select Team Member</label>
                        <select class="form-select" name="assigned_to" id="assign_team_person" required>
                            <option value="">Select Team Member</option>
                            <?php foreach ($teamPersons as $tp): ?>
                                <option value="<?= (int)$tp['id'] ?>" data-company-id="<?= (int)($tp['company_id'] ?? 0) ?>">
                                    <?= htmlspecialchars($tp['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitAssignBtn">
                        <span id="submitAssignText">Assign Lead</span>
                        <span id="submitAssignLoader" class="spinner-border spinner-border-sm d-none ms-1"></span>
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
    let allTeamPersons = <?= json_encode($teamPersons) ?>;

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

    // Always reload DataTable when follow-up or assign modal is closed/dismissed
    $('#addCompanyFollowupModal, #assignToModal').on('hidden.bs.modal', function () {
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
                placeholder: placeholderText || 'Select Option',
                dropdownParent: 'body'
            });
        }
        return null;
    }

    // Helper: Update TomSelect options with placeholder
    function updateTomOptions(selector, items, placeholderText, selectedVal) {
        let el = document.querySelector(selector);
        if (!el) return;

        if (el.tomselect) {
            let ts = el.tomselect;
            ts.settings.placeholder = placeholderText;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({ value: '', text: placeholderText });
            if (items && items.length) {
                $.each(items, function(i, item) {
                    ts.addOption({ value: String(item.id || item.name), text: item.name });
                });
            }
            if (selectedVal) {
                ts.setValue(String(selectedVal));
            } else {
                ts.setValue('');
            }
            ts.inputState();
            ts.refreshOptions(false);
        } else {
            let $select = $(selector);
            $select.empty();
            $select.append('<option value="">' + placeholderText + '</option>');
            if (items && items.length) {
                $.each(items, function(i, item) {
                    let val = item.id || item.name;
                    let isSel = (String(selectedVal) === String(val)) ? 'selected' : '';
                    $select.append('<option value="' + val + '" ' + isSel + '>' + item.name + '</option>');
                });
            }
        }
    }

    // Initialize TomSelect instances on Modal Dropdowns & Filters
    let tsFilterInq = initTom('#filter_inquiry_status', 'Select Status');
    let defaultStatusId = '<?= $defaultStatusId ?>';
    if (tsFilterInq && defaultStatusId) {
        tsFilterInq.setValue(defaultStatusId, true);
    }
    let tsReason    = initTom('#fu_reason_id', 'Select Followup Reason');
    let tsThrough   = initTom('#fu_type_id', 'Select Follow Up Through');
    let tsInqStatus = initTom('#fu_inquiry_status', 'Select Inquiry Status');
    let tsAssign    = initTom('#assign_team_person', 'Select Team Member');

    // 1. OPEN ADD FOLLOWUP MODAL
    $(document).on('click', '.btn-add-followup', function() {
        let btn = $(this);
        let leadId = btn.data('id');
        let companyId = parseInt(btn.data('company-id') || 0);
        let inquiryNo = btn.data('inquiry-no') || '-';
        let inquiryDate = btn.data('inquiry-date') || '-';
        let companyName = btn.data('company-name') || '-';
        let customerName = btn.data('customer-name') || '-';
        let contactPerson = btn.data('contact-person') || '-';
        let mobileNo = btn.data('mobile-no') || '-';
        let whatsappNo = btn.data('whatsapp-no') || '-';
        let email = btn.data('email') || '-';
        let inqStatus = btn.data('inquiry-status') || '';
        let fuStatus = btn.data('followup-status') || '';

        // Set hidden lead id
        $('#fu_lead_id').val(leadId);

        // Populate Summary
        $('#fu_disp_inquiry_no').text(inquiryNo);
        $('#fu_disp_inquiry_date').text(inquiryDate);
        $('#fu_disp_company_name').text(companyName);
        $('#fu_disp_customer_name').text(customerName);
        $('#fu_disp_contact_person').text(contactPerson);

        if (email && email !== '-') {
            $('#fu_disp_email_link').text(email).attr('href', 'mailto:' + email);
        } else {
            $('#fu_disp_email_link').text('-').removeAttr('href');
        }

        if (mobileNo && mobileNo !== '-') {
            $('#fu_disp_mobile_link').text(mobileNo).attr('href', 'tel:' + mobileNo);
        } else {
            $('#fu_disp_mobile_link').text('-').removeAttr('href');
        }

        if (whatsappNo && whatsappNo !== '-') {
            let cleanWa = String(whatsappNo).replace(/[^0-9]/g, '');
            $('#fu_disp_whatsapp_link').text(whatsappNo).attr('href', 'https://wa.me/' + cleanWa).attr('target', '_blank');
        } else {
            $('#fu_disp_whatsapp_link').text('-').removeAttr('href').removeAttr('target');
        }

        // Reset details
        $('#fu_details').val('');

        // Filter Followup Reasons by company
        let filteredReasons = [];
        $.each(allReasons, function(i, item) {
            let itemCid = parseInt(item.company_id || 0);
            if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                filteredReasons.push(item);
            }
        });
        updateTomOptions('#fu_reason_id', filteredReasons, 'Select Followup Reason', '');

        // Followup Through
        if (tsThrough) {
            tsThrough.setValue('');
        } else {
            $('#fu_type_id').val('');
        }

        // Filter Inquiry Status by company
        let filteredInqStatuses = [];
        $.each(allInqStatuses, function(i, item) {
            let itemCid = parseInt(item.company_id || 0);
            if (!companyId || isSuperadmin || itemCid === 0 || itemCid === companyId) {
                filteredInqStatuses.push(item);
            }
        });
        updateTomOptions('#fu_inquiry_status', filteredInqStatuses, 'Select Inquiry Status', inqStatus);

        // Reset flatpickr to now
        if ($('#fu_date_time')[0]._flatpickr) {
            $('#fu_date_time')[0]._flatpickr.setDate(new Date());
        }

        $('#addCompanyFollowupModal').modal('show');
    });

    // SUBMIT ADD FOLLOWUP FORM
    $('#addCompanyFollowupForm').on('submit', function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $('#submitAddFuBtn');
        let $text = $('#submitAddFuText');
        let $loader = $('#submitAddFuLoader');

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
                $text.text('Add Followup');
                $loader.addClass('d-none');

                if (res.status === true) {
                    showToast(res.message, 'success');
                    $('#addCompanyFollowupModal').modal('hide');
                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    if ($('.data-table').length && $.fn.DataTable.isDataTable('.data-table')) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to save follow up.', 'error');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $text.text('Add Followup');
                $loader.addClass('d-none');
                showToast('An unexpected error occurred.', 'error');
            }
        });
    });

    // 2. OPEN ASSIGN TO MODAL
    $(document).on('click', '.btn-assign-to', function() {
        let btn = $(this);
        let leadId = btn.data('id');
        let companyId = parseInt(btn.data('company-id') || 0);
        let inquiryNo = btn.data('inquiry-no') || '-';
        let assignedTo = btn.data('assigned-to') || '';

        $('#assign_lead_id').val(leadId);
        $('#assign_disp_inquiry').text(inquiryNo);

        // Filter team persons by company
        let filteredTeam = [];
        $.each(allTeamPersons, function(i, tp) {
            let tpCid = parseInt(tp.company_id || 0);
            if (!companyId || isSuperadmin || tpCid === 0 || tpCid === companyId) {
                filteredTeam.push(tp);
            }
        });
        updateTomOptions('#assign_team_person', filteredTeam, 'Select Team Member', assignedTo);

        $('#assignToModal').modal('show');
    });

    // SUBMIT ASSIGN TO FORM
    $('#assignToForm').on('submit', function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $('#submitAssignBtn');
        let $text = $('#submitAssignText');
        let $loader = $('#submitAssignLoader');

        $btn.prop('disabled', true);
        $text.text('Assigning...');
        $loader.removeClass('d-none');

        $.ajax({
            url: '<?= SITE_URL ?>admin/setting/company-lead/ajax.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false);
                $text.text('Assign Lead');
                $loader.addClass('d-none');

                if (res.status === true) {
                    showToast(res.message, 'success');
                    $('#assignToModal').modal('hide');
                    if ($('.data-table').length && $.fn.DataTable.isDataTable('.data-table')) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to assign lead.', 'error');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $text.text('Assign Lead');
                $loader.addClass('d-none');
                showToast('An unexpected error occurred.', 'error');
            }
        });
    });

    // 3. CONVERT TO CUSTOMER
    $(document).on('click', '.btn-convert-customer', function() {
        let btn = $(this);
        let leadId = btn.data('id');
        let customerName = btn.data('customer-name') || 'this lead';

        Swal.fire({
            title: 'Convert to Customer?',
            text: 'Are you sure you want to convert ' + customerName + ' to Customer?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= SITE_URL ?>admin/setting/company-lead/ajax.php',
                    type: 'POST',
                    data: {
                        action: 'convert_to_customer',
                        lead_id: leadId
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === true) {
                            showToast(res.message, 'success');
                            if ($('.data-table').length && $.fn.DataTable.isDataTable('.data-table')) {
                                $('.data-table').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            showToast(res.message || 'Failed to convert lead.', 'error');
                        }
                    },
                    error: function() {
                        showToast('An unexpected error occurred.', 'error');
                    }
                });
            }
        });
    });

    // 4. QUICK INLINE UPDATE INQUIRY STATUS FROM DROPDOWN
    $(document).on('change', '.lead-status-select', function() {
        let $select = $(this);
        let leadId = $select.data('id');
        let newStatus = $select.val();
        let originalStatus = $select.data('original');
        let selectedOption = $select.find('option:selected');
        let newColor = selectedOption.data('color') || '#0e5a6c';

        // Update dropdown inline style immediately
        $select.css({
            'background-color': newColor + '15',
            'color': newColor,
            'border-color': newColor + '60'
        });

        $select.prop('disabled', true);

        $.ajax({
            url: '<?= SITE_URL ?>admin/setting/company-lead/ajax.php',
            type: 'POST',
            data: {
                action: 'update_inquiry_status',
                lead_id: leadId,
                status: newStatus
            },
            dataType: 'json',
            success: function(res) {
                $select.prop('disabled', false);
                if (res.status === true) {
                    showToast(res.message, 'success');
                    $select.data('original', newStatus);
                    if ($('.data-table').length && $.fn.DataTable.isDataTable('.data-table')) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to update status.', 'error');
                    $select.val(originalStatus);
                    let origOpt = $select.find('option[value="' + originalStatus + '"]');
                    let origColor = origOpt.data('color') || '#0e5a6c';
                    $select.css({
                        'background-color': origColor + '15',
                        'color': origColor,
                        'border-color': origColor + '60'
                    });
                }
            },
            error: function() {
                $select.prop('disabled', false);
                showToast('An unexpected error occurred.', 'error');
                $select.val(originalStatus);
                let origOpt = $select.find('option[value="' + originalStatus + '"]');
                let origColor = origOpt.data('color') || '#0e5a6c';
                $select.css({
                    'background-color': origColor + '15',
                    'color': origColor,
                    'border-color': origColor + '60'
                });
            }
        });
    });

    // 5. INQUIRY STATUS FILTER CHANGE
    $(document).on('change', '#filter_inquiry_status', function() {
        let val = $(this).val();
        if (val !== '' && val !== 'all') {
            $('#btnClearLeadFilter').removeClass('d-none');
        } else {
            $('#btnClearLeadFilter').addClass('d-none');
        }
        reloadDataTableSafe();
    });

    $(document).on('click', '#btnClearLeadFilter', function() {
        if (tsFilterInq) {
            tsFilterInq.setValue('all');
        } else {
            $('#filter_inquiry_status').val('all');
        }
        $(this).addClass('d-none');
        reloadDataTableSafe();
    });

    // Re-render lucide icons when datatable redraws
    $(document).on('draw.dt', '.data-table', function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
});
</script>

</body>
</html>
