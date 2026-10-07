<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

// Check permissions
checkPermissionOrDeny('customer-followup', 'views');

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

$encCustId = $_GET['customer_id'] ?? ($_GET['id'] ?? '');
$filterCustId = 0;
if (!empty($encCustId)) {
    $filterCustId = decrypt_id($encCustId);
    if (!$filterCustId && is_numeric($encCustId)) {
        $filterCustId = (int)$encCustId;
    }
}

// Fetch single customer details if filtered by customer_id
$customer = null;
if ($filterCustId > 0) {
    $custSql = "SELECT cust.*, c.name as company_name, ct.name as customer_type_name 
                FROM customer cust 
                LEFT JOIN company c ON c.id = cust.company_id 
                LEFT JOIN customer_type ct ON ct.id = cust.customer_type_id 
                WHERE cust.id = $filterCustId";
    if (!$isSuperadmin && $sessionCompanyId > 0) {
        $custSql .= " AND cust.company_id = " . $sessionCompanyId;
    }
    $custSql .= " LIMIT 1";
    $customer = db_row($custSql);
}

$pageNm = 'Customer Follow Up';
$breadcrumbType = 'list';
$fields = '';
$module = 'customer-followup';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Customer Summary Card (if specific customer is opened) -->
<?php if ($customer): ?>
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-16" style="width: 44px; height: 44px;">
                                <?= strtoupper(substr($customer['name'] ?? 'C', 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">
                                    <?= htmlspecialchars($customer['name'] ?? '') ?>
                                    <?php if (!empty($customer['customer_type_name'])): ?>
                                        <span class="badge bg-light text-dark border fs-12 ms-2"><?= htmlspecialchars($customer['customer_type_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($isSuperadmin && !empty($customer['company_name'])): ?>
                                        <span class="badge bg-dark-subtle text-dark fs-11 ms-1"><?= htmlspecialchars($customer['company_name']) ?></span>
                                    <?php endif; ?>
                                </h5>
                                <div class="text-muted fs-13 d-flex flex-wrap align-items-center gap-3 mt-1">
                                    <span><i data-lucide="phone" class="fs-13 align-middle text-muted me-1"></i><?= htmlspecialchars($customer['mobile_no'] ?? '-') ?></span>
                                    <span><i data-lucide="mail" class="fs-13 align-middle text-muted me-1"></i><?= htmlspecialchars($customer['email'] ?? '-') ?></span>
                                    <?php if (!empty($customer['address'])): ?>
                                        <span><i data-lucide="map-pin" class="fs-13 align-middle text-muted me-1"></i><?= htmlspecialchars($customer['address']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?= SITE_URL ?>customer-followup" class="btn btn-outline-secondary btn-sm">
                                <i data-lucide="list" class="fs-14 align-middle me-1"></i> All Customer Followups
                            </a>
                            <button type="button" class="btn btn-primary btn-sm btn-quick-add-cust-fu"
                                data-customer-id="<?= $customer['id'] ?>"
                                data-company-id="<?= (int)$customer['company_id'] ?>"
                                data-name="<?= htmlspecialchars($customer['name'] ?? '', ENT_QUOTES) ?>"
                                data-company-name="<?= htmlspecialchars($customer['company_name'] ?? '', ENT_QUOTES) ?>"
                                data-mobile="<?= htmlspecialchars($customer['mobile_no'] ?? '', ENT_QUOTES) ?>"
                                data-email="<?= htmlspecialchars($customer['email'] ?? '', ENT_QUOTES) ?>">
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
$tbl = 'customer_followups';
$ajaxUrl = SITE_URL . 'admin/sales/customer-followup/ajax.php' . ($filterCustId > 0 ? '?customer_id=' . $filterCustId : '');

if ($isSuperadmin) {
    $tableHeaders = [
        'Sr No.',
        'Company',
        'Customer',
        'Follow-up Through',
        'Follow-up Date & Time',
        'Reason',
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
        'Customer',
        'Follow-up Through',
        'Follow-up Date & Time',
        'Reason',
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

// Active customer followup reasons
$fuReasonCondition = "status = 1";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $fuReasonCondition .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
}
$followupReasons = db_rows("SELECT id, name, company_id FROM customer_followup_reason WHERE $fuReasonCondition ORDER BY name ASC");

// Active customers for dropdown if opened from all followups
$custCond = "status = 1";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $custCond .= " AND company_id = $sessionCompanyId";
}
$userType = $_SESSION['user_type'] ?? '';
$currentUserId = getCurrentUserId();
if ($userType === 'user' && $currentUserId > 0) {
    $custCond .= " AND (assigned_to = $currentUserId OR created_by = $currentUserId)";
}
$customersList = db_rows("SELECT id, name, mobile_no, email, company_id FROM customer WHERE $custCond ORDER BY name ASC");
?>

<!-- MODAL: Add / Edit Follow Up for Customer -->
<div class="modal fade" id="addCustomerFollowupModal" tabindex="-1" aria-labelledby="addCustomerFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="calendar-clock" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-bold text-dark mb-0" id="addCustomerFollowupModalLabel">
                            Add Customer Follow Up
                        </h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCustomerFollowupForm">
                <input type="hidden" name="action" id="cfu_form_action" value="add_customer_followup">
                <input type="hidden" name="followup_id" id="cfu_form_fu_id" value="0">
                <input type="hidden" name="customer_id" id="cfu_customer_id" value="<?= $filterCustId ?>">

                <div class="modal-body p-4">
                    <!-- Customer Summary Display or Selector -->
                    <div id="cfu_customer_summary_box" class="row g-2 mb-3 bg-light-subtle p-3 rounded border">
                        <div class="col-md-6">
                            <div class="mb-1"><strong class="text-dark">Customer Name :-</strong> <span id="cfu_disp_customer_name" class="fw-semibold text-primary">-</span></div>
                            <div class="mb-1"><strong class="text-dark">Company :-</strong> <span id="cfu_disp_company_name" class="text-muted">-</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-1"><strong class="text-dark">Mobile No :-</strong> <a href="javascript:void(0);" id="cfu_disp_mobile_link" class="text-primary">-</a></div>
                            <div class="mb-1"><strong class="text-dark">Email :-</strong> <a href="javascript:void(0);" id="cfu_disp_email_link" class="text-primary">-</a></div>
                        </div>
                    </div>

                    <!-- Customer Selector (shown when not filtered) -->
                    <div class="row g-3 mb-3 <?= ($filterCustId > 0) ? 'd-none' : '' ?>" id="cfu_customer_select_wrapper">
                        <div class="col-12">
                            <label class="form-label fs-13 fw-medium">Select Customer</label>
                            <select class="form-select" id="cfu_select_customer" placeholder="Choose a customer...">
                                <option value="">Select Customer</option>
                                <?php foreach ($customersList as $cItem): ?>
                                    <option value="<?= $cItem['id'] ?>" 
                                            data-name="<?= htmlspecialchars($cItem['name'], ENT_QUOTES) ?>"
                                            data-mobile="<?= htmlspecialchars($cItem['mobile_no'] ?? '', ENT_QUOTES) ?>"
                                            data-email="<?= htmlspecialchars($cItem['email'] ?? '', ENT_QUOTES) ?>"
                                            data-company-id="<?= (int)($cItem['company_id'] ?? 0) ?>">
                                        <?= htmlspecialchars($cItem['name']) ?> <?= !empty($cItem['mobile_no']) ? ('(' . htmlspecialchars($cItem['mobile_no']) . ')') : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 1: Follow Up Date and Time | Follow Up Reason -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Follow Up Date and Time</label>
                            <input type="text" class="form-control" name="followup_date_time" id="cfu_date_time" placeholder="Select Follow Up Date and Time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Follow Up Reason</label>
                            <select class="form-select" name="reason_id" id="cfu_reason_id" placeholder="Select Followup Reason">
                                <option value="">Select Followup Reason</option>
                                <?php foreach ($followupReasons as $fr): ?>
                                    <option value="<?= (int)$fr['id'] ?>" data-company-id="<?= (int)($fr['company_id'] ?? 0) ?>">
                                        <?= htmlspecialchars($fr['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Follow Up Through -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label fs-13 fw-medium">Follow Up Through</label>
                            <select class="form-select" name="followup_type_id" id="cfu_type_id" placeholder="Select Follow Up Through">
                                <option value="">Select Follow Up Through</option>
                                <?php foreach ($followupTypes as $ft): ?>
                                    <option value="<?= (int)$ft['id'] ?>">
                                        <?= htmlspecialchars($ft['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="followup_status" id="cfu_status" value="pending">

                    <!-- Row 3: Follow Up Details -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fs-13 fw-medium">Follow Up Details / Remarks</label>
                            <textarea class="form-control" name="followup_details" id="cfu_details" rows="3" placeholder="Enter Follow Up Details"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4" id="submitCustFuBtn">
                        <span id="submitCustFuText">Add Followup</span>
                        <span id="submitCustFuLoader" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Add Customer Followup Response Modal -->
<div class="modal fade" id="customerFollowupResponseModal" tabindex="-1" aria-labelledby="custFuResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="message-square-check" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-semibold mb-0" id="custFuResponseModalLabel">Add Followup Response</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customerFollowupResponseForm">
                <input type="hidden" name="action" value="submit_customer_followup_response">
                <input type="hidden" name="followup_id" id="respCustFollowupId" value="0">
                <input type="hidden" name="customer_id" id="respCustomerId" value="0">
                <input type="hidden" name="company_id" id="respCustCompanyId" value="0">
                
                <div class="modal-body p-4">
                    <!-- Customer Info Header Cards -->
                    <div class="row g-2 mb-3 bg-light-subtle p-3 rounded border">
                        <div class="col-md-6">
                            <div class="fs-13 text-dark"><strong>Customer Name :-</strong> <span id="respCustName" class="fw-semibold text-primary">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Company :-</strong> <span id="respCustCompanyName" class="fw-medium">-</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="fs-13 text-dark"><strong>Mobile No :-</strong> <a href="#" id="respCustMobileNo" class="text-primary text-decoration-none">-</a></div>
                            <div class="fs-13 text-dark mt-1"><strong>Email :-</strong> <a href="#" id="respCustEmail" class="text-primary text-decoration-none">-</a></div>
                        </div>
                    </div>

                    <!-- Response Input -->
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Response</label>
                        <textarea class="form-control" name="response" id="respCustResponseText" rows="3" placeholder="Enter Follow Up Response Details" required></textarea>
                    </div>

                    <!-- Follow-up Action Select -->
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Follow-up Action</label>
                        <select class="form-select" name="followup_action" id="respCustFollowupAction" required>
                            <option value="">Select Follow-up Action</option>
                            <option value="next-followup">Next Follow-up</option>
                            <option value="end-followup">End Follow-up</option>
                        </select>
                    </div>

                    <!-- Conditional Next Followup Fields -->
                    <div id="custNextFollowupFields" class="d-none border rounded p-3 bg-light-subtle mb-2">
                        <h6 class="fs-13 fw-bold text-dark mb-3 d-flex align-items-center gap-1">
                            <i data-lucide="calendar-plus" class="fs-15 text-primary"></i> Schedule Next Follow-up
                        </h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Next Follow-up Date & Time</label>
                                <input type="text" class="form-control" name="next_followup_date" id="respCustNextDateTime" placeholder="Select Next Date & Time">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Next Follow-up Reason</label>
                                <select class="form-select" name="next_reason_id" id="respCustNextReason">
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
                                <select class="form-select" name="next_followup_type_id" id="respCustNextTypeId">
                                    <option value="">Select Follow Up Through</option>
                                    <?php foreach ($followupTypes as $ft): ?>
                                        <option value="<?= (int)$ft['id'] ?>">
                                            <?= htmlspecialchars($ft['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Topic to Discuss</label>
                                <input type="text" class="form-control" name="next_discuss_topic" id="respCustNextDiscuss" placeholder="Discussion summary / topic">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success px-4" id="submitCustRespBtn">
                        <span id="submitCustRespText">Submit Response</span>
                        <span id="submitCustRespLoader" class="spinner-border spinner-border-sm d-none ms-1"></span>
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
    $('#addCustomerFollowupModal, #customerFollowupResponseModal').on('hidden.bs.modal', function () {
        reloadDataTableSafe();
    });

    let tomReason = null;
    let tomType = null;
    let tomCustSelect = null;

    if (typeof TomSelect !== 'undefined') {
        if ($('#cfu_reason_id').length) {
            tomReason = new TomSelect('#cfu_reason_id', { 
                create: false, 
                allowEmptyOption: true,
                placeholder: 'Select Followup Reason',
                dropdownParent: 'body'
            });
        }
        if ($('#cfu_type_id').length) {
            tomType = new TomSelect('#cfu_type_id', { 
                create: false, 
                allowEmptyOption: true,
                placeholder: 'Select Follow Up Through',
                dropdownParent: 'body'
            });
        }
        if ($('#cfu_select_customer').length) {
            tomCustSelect = new TomSelect('#cfu_select_customer', { 
                create: false, 
                allowEmptyOption: true,
                placeholder: 'Choose a customer...',
                dropdownParent: 'body',
                onChange: function(val) {
                    $('#cfu_customer_id').val(val);
                    if (val) {
                        let opt = $('#cfu_select_customer option[value="' + val + '"]');
                        $('#cfu_disp_customer_name').text(opt.data('name') || '-');
                        $('#cfu_disp_company_name').text(opt.data('company-name') || '-');
                        let mob = opt.data('mobile') || '';
                        if (mob) {
                            $('#cfu_disp_mobile_link').text(mob).attr('href', 'tel:' + mob);
                        } else {
                            $('#cfu_disp_mobile_link').text('-').removeAttr('href');
                        }
                        let em = opt.data('email') || '';
                        if (em) {
                            $('#cfu_disp_email_link').text(em).attr('href', 'mailto:' + em);
                        } else {
                            $('#cfu_disp_email_link').text('-').removeAttr('href');
                        }
                        $('#cfu_customer_summary_box').removeClass('d-none');
                    }
                }
            });
        }
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#cfu_date_time', {
            enableTime: true,
            dateFormat: "d-m-Y h:i K",
            time_24hr: false,
            defaultDate: new Date()
        });
        flatpickr('#respCustNextDateTime', {
            enableTime: true,
            dateFormat: "d-m-Y h:i K",
            time_24hr: false
        });
    }

    // Toggle next follow-up fields in response modal
    $('#respCustFollowupAction').on('change', function() {
        if ($(this).val() === 'next-followup') {
            $('#custNextFollowupFields').removeClass('d-none');
            $('#respCustNextDateTime').prop('required', true);
            if ($('#respCustNextDateTime')[0]._flatpickr && !$('#respCustNextDateTime').val()) {
                let tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                $('#respCustNextDateTime')[0]._flatpickr.setDate(tomorrow);
            }
        } else {
            $('#custNextFollowupFields').addClass('d-none');
            $('#respCustNextDateTime').prop('required', false);
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    // 1. OPEN QUICK ADD MODAL (from header card or button)
    $(document).on('click', '.btn-quick-add-cust-fu', function(e) {
        e.preventDefault();
        let btn = $(this);
        let custId = btn.data('customer-id');
        let companyId = parseInt(btn.data('company-id') || 0);

        $('#cfu_customer_id').val(custId);
        $('#cfu_disp_customer_name').text(btn.data('name') || '-');
        $('#cfu_disp_company_name').text(btn.data('company-name') || '-');

        let mob = btn.data('mobile') || '';
        if (mob) {
            $('#cfu_disp_mobile_link').text(mob).attr('href', 'tel:' + mob);
        } else {
            $('#cfu_disp_mobile_link').text('-').removeAttr('href');
        }

        let em = btn.data('email') || '';
        if (em) {
            $('#cfu_disp_email_link').text(em).attr('href', 'mailto:' + em);
        } else {
            $('#cfu_disp_email_link').text('-').removeAttr('href');
        }

        $('#cfu_details').val('');
        $('#cfu_status').val('pending');
        if (tomType) tomType.setValue('', true);
        if (tomReason) tomReason.setValue('', true);

        if ($('#cfu_date_time')[0]._flatpickr) {
            $('#cfu_date_time')[0]._flatpickr.setDate(new Date());
        }

        $('#cfu_form_action').val('add_customer_followup');
        $('#cfu_form_fu_id').val('0');
        $('#addCustomerFollowupModalLabel').text('Add Customer Follow Up');
        $('#submitCustFuText').text('Add Followup');

        $('#cfu_customer_select_wrapper').addClass('d-none');
        $('#cfu_customer_summary_box').removeClass('d-none');

        $('#addCustomerFollowupModal').modal('show');
    });

    // 2. OPEN EDIT MODAL
    $(document).on('click', '.btn-edit-customer-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        if (!fuId) return;

        $.ajax({
            url: '<?= SITE_URL ?>admin/sales/customer-followup/ajax.php',
            type: 'POST',
            data: { action: 'get_customer_followup', followup_id: fuId },
            dataType: 'json',
            success: function(res) {
                if (res.status === true && res.data) {
                    let d = res.data;
                    $('#cfu_form_action').val('update_customer_followup');
                    $('#cfu_form_fu_id').val(d.id);
                    $('#cfu_customer_id').val(d.customer_id);
                    $('#addCustomerFollowupModalLabel').text('Edit Customer Follow Up');
                    $('#submitCustFuText').text('Update Followup');

                    $('#cfu_disp_customer_name').text(d.customer_name || '-');
                    $('#cfu_disp_company_name').text(d.company_name || '-');

                    if (d.mobile_no) {
                        $('#cfu_disp_mobile_link').text(d.mobile_no).attr('href', 'tel:' + d.mobile_no);
                    } else {
                        $('#cfu_disp_mobile_link').text('-').removeAttr('href');
                    }

                    if (d.email) {
                        $('#cfu_disp_email_link').text(d.email).attr('href', 'mailto:' + d.email);
                    } else {
                        $('#cfu_disp_email_link').text('-').removeAttr('href');
                    }

                    $('#cfu_details').val(d.remarks || '');
                    $('#cfu_status').val(d.followup_status || 'pending');

                    if (tomReason) tomReason.setValue(String(d.reason_id || ''), true);
                    if (tomType) tomType.setValue(String(d.followup_type_id || ''), true);

                    if (d.formatted_date && $('#cfu_date_time')[0]._flatpickr) {
                        $('#cfu_date_time')[0]._flatpickr.setDate(d.formatted_date);
                    }

                    $('#cfu_customer_select_wrapper').addClass('d-none');
                    $('#cfu_customer_summary_box').removeClass('d-none');

                    $('#addCustomerFollowupModal').modal('show');
                } else {
                    if (typeof showToast === 'function') showToast(res.message || 'Error fetching record', 'error');
                    else alert(res.message || 'Error');
                }
            }
        });
    });

    // 3. SUBMIT ADD / EDIT FORM
    $('#addCustomerFollowupForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let custId = parseInt($('#cfu_customer_id').val() || 0);

        if (custId <= 0) {
            if (typeof showToast === 'function') showToast('Please select a customer.', 'warning');
            else alert('Please select a customer.');
            return;
        }

        $('#submitCustFuBtn').prop('disabled', true);
        $('#submitCustFuLoader').removeClass('d-none');

        $.ajax({
            url: '<?= SITE_URL ?>admin/sales/customer-followup/ajax.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                $('#submitCustFuBtn').prop('disabled', false);
                $('#submitCustFuLoader').addClass('d-none');

                if (res.status === true) {
                    if (typeof showToast === 'function') showToast(res.message, 'success');
                    else alert(res.message);
                    $('#addCustomerFollowupModal').modal('hide');
                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    reloadDataTableSafe();
                } else {
                    if (typeof showToast === 'function') showToast(res.message, 'error');
                    else alert(res.message);
                }
            },
            error: function() {
                $('#submitCustFuBtn').prop('disabled', false);
                $('#submitCustFuLoader').addClass('d-none');
                if (typeof showToast === 'function') showToast('Server communication error', 'error');
            }
        });
    });

    // 4. OPEN RESPONSE MODAL
    $(document).on('click', '.btn-open-cust-response-modal', function(e) {
        e.preventDefault();
        let btn = $(this);
        let fuId = btn.data('id');
        let custId = btn.data('customer-id');
        let compId = btn.data('company-id');
        let custName = btn.data('cust-name');
        let mobile = btn.data('mobile');
        let email = btn.data('email');
        let compName = btn.data('company-name');

        $('#respCustFollowupId').val(fuId);
        $('#respCustomerId').val(custId);
        $('#respCustCompanyId').val(compId);

        $('#respCustName').text(custName || '-');
        $('#respCustCompanyName').text(compName || '-');

        if (mobile && mobile !== '-') {
            $('#respCustMobileNo').text(mobile).attr('href', 'tel:' + mobile);
        } else {
            $('#respCustMobileNo').text('-').removeAttr('href');
        }

        if (email && email !== '-') {
            $('#respCustEmail').text(email).attr('href', 'mailto:' + email);
        } else {
            $('#respCustEmail').text('-').removeAttr('href');
        }

        $('#respCustResponseText').val('');
        $('#respCustFollowupAction').val('');
        $('#custNextFollowupFields').addClass('d-none');
        $('#respCustNextDateTime').val('').prop('required', false);
        $('#respCustNextReason').val('');
        $('#respCustNextTypeId').val('');
        $('#respCustNextDiscuss').val('');

        $('#customerFollowupResponseModal').modal('show');
    });

    // 5. SUBMIT RESPONSE FORM
    $('#customerFollowupResponseForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);

        $('#submitCustRespBtn').prop('disabled', true);
        $('#submitCustRespLoader').removeClass('d-none');

        $.ajax({
            url: '<?= SITE_URL ?>admin/sales/customer-followup/ajax.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                $('#submitCustRespBtn').prop('disabled', false);
                $('#submitCustRespLoader').addClass('d-none');

                if (res.status === true) {
                    if (typeof showToast === 'function') showToast(res.message, 'success');
                    else alert(res.message);
                    $('#customerFollowupResponseModal').modal('hide');
                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    reloadDataTableSafe();
                } else {
                    if (typeof showToast === 'function') showToast(res.message, 'error');
                    else alert(res.message);
                }
            },
            error: function() {
                $('#submitCustRespBtn').prop('disabled', false);
                $('#submitCustRespLoader').addClass('d-none');
                if (typeof showToast === 'function') showToast('Server communication error', 'error');
            }
        });
    });

    // 6. DELETE FOLLOWUP
    $(document).on('click', '.btn-delete-customer-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        if (!fuId) return;

        let executeDelete = function() {
            $.ajax({
                url: '<?= SITE_URL ?>admin/sales/customer-followup/ajax.php',
                type: 'POST',
                data: { action: 'delete_customer_followup', followup_id: fuId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === true) {
                        if (typeof showToast === 'function') showToast(res.message, 'success');
                        else alert(res.message);
                        if (typeof refreshFollowupNotifications === 'function') {
                            refreshFollowupNotifications();
                        }
                        reloadDataTableSafe();
                    } else {
                        if (typeof showToast === 'function') showToast(res.message, 'error');
                        else alert(res.message);
                    }
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you really want to delete this follow up?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDelete();
                }
            });
        } else if (confirm('Are you sure you want to delete this follow up?')) {
            executeDelete();
        }
    });
});
</script>
</body>
</html>
