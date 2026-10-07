<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'customer';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Customer';
$tbl = 'customer';
$showAdd = hasPermission($moduleKey, 'adds');
$breadcrumbType = 'list';
$addType = 'redirect';
$addUrl = SITE_URL . 'customer/add';
$fields = 'customer_type_id:Customer Type,name:Name,email:Email,mobile_no:Mobile No,address:Address,country_id:Country,state_id:State,city_id:City';
$module = ' customer';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/sales/customer/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, [
    'Customer Type',
    'Name',
    'Mobile No',
    'Email',
    'City',
    'State',
    'Assign To',
    'Status',
    'Action'
]);
include BASE_PATH . '/component/datatable.php';
?>

<?php
// Load follow-up types & customer reasons for customer follow-up modal
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$fuReasonCondition = "status = 1";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $fuReasonCondition .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
}
$followupReasons = db_rows("SELECT id, name, company_id FROM customer_followup_reason WHERE $fuReasonCondition ORDER BY name ASC");
$followupTypes   = db_rows("SELECT id, name FROM lead_followup_type WHERE status = 1");
?>

<!-- MODAL: Add Follow Up for Customer from Customer List -->
<div class="modal fade" id="addCustomerListFollowupModal" tabindex="-1" aria-labelledby="addCustListFuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="calendar-plus" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-bold text-dark mb-0" id="addCustListFuModalLabel">
                            Add Customer Follow Up
                        </h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCustListFollowupForm">
                <input type="hidden" name="action" value="add_customer_followup">
                <input type="hidden" name="customer_id" id="clf_customer_id" value="0">

                <div class="modal-body p-4">
                    <!-- Customer Summary Display -->
                    <div class="row g-2 mb-3 bg-light-subtle p-3 rounded border">
                        <div class="col-md-6">
                            <div class="mb-1"><strong class="text-dark">Customer Name :-</strong> <span id="clf_disp_customer_name" class="fw-semibold text-primary">-</span></div>
                            <div class="mb-1"><strong class="text-dark">Company :-</strong> <span id="clf_disp_company_name" class="text-muted">-</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-1"><strong class="text-dark">Mobile No :-</strong> <a href="javascript:void(0);" id="clf_disp_mobile_link" class="text-primary">-</a></div>
                            <div class="mb-1"><strong class="text-dark">Email :-</strong> <a href="javascript:void(0);" id="clf_disp_email_link" class="text-primary">-</a></div>
                        </div>
                    </div>

                    <!-- Row 1: Follow Up Date and Time | Follow Up Reason -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Follow Up Date and Time</label>
                            <input type="text" class="form-control" name="followup_date_time" id="clf_date_time" placeholder="Select Follow Up Date and Time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-13 fw-medium">Follow Up Reason</label>
                            <select class="form-select" name="reason_id" id="clf_reason_id" placeholder="Select Followup Reason">
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
                            <select class="form-select" name="followup_type_id" id="clf_type_id" placeholder="Select Follow Up Through">
                                <option value="">Select Follow Up Through</option>
                                <?php foreach ($followupTypes as $ft): ?>
                                    <option value="<?= (int)$ft['id'] ?>">
                                        <?= htmlspecialchars($ft['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="followup_status" id="clf_status" value="pending">

                    <!-- Row 3: Follow Up Details -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fs-13 fw-medium">Follow Up Details / Remarks</label>
                            <textarea class="form-control" name="followup_details" id="clf_details" rows="3" placeholder="Enter Follow Up Details"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4" id="submitClfBtn">
                        <span id="submitClfText">Add Followup</span>
                        <span id="submitClfLoader" class="spinner-border spinner-border-sm d-none ms-1"></span>
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

    // Always reload DataTable when add followup modal is closed/dismissed
    $('#addCustomerListFollowupModal').on('hidden.bs.modal', function () {
        reloadDataTableSafe();
    });

    let tomClfReason = null;
    let tomClfType = null;

    if (typeof TomSelect !== 'undefined') {
        if ($('#clf_reason_id').length) {
            tomClfReason = new TomSelect('#clf_reason_id', { 
                create: false, 
                allowEmptyOption: true,
                placeholder: 'Select Followup Reason',
                dropdownParent: 'body'
            });
        }
        if ($('#clf_type_id').length) {
            tomClfType = new TomSelect('#clf_type_id', { 
                create: false, 
                allowEmptyOption: true,
                placeholder: 'Select Follow Up Through',
                dropdownParent: 'body'
            });
        }
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#clf_date_time', {
            enableTime: true,
            dateFormat: "d-m-Y h:i K",
            time_24hr: false,
            defaultDate: new Date()
        });
    }

    // Trigger modal from action dropdown
    $(document).on('click', '.btn-customer-add-fu', function(e) {
        e.preventDefault();
        let btn = $(this);
        let custId = btn.data('customer-id');
        let custName = btn.data('name');
        let compName = btn.data('company-name');
        let mobile = btn.data('mobile');
        let email = btn.data('email');

        $('#clf_customer_id').val(custId);
        $('#clf_disp_customer_name').text(custName || '-');
        $('#clf_disp_company_name').text(compName || '-');

        if (mobile && mobile !== '-') {
            $('#clf_disp_mobile_link').text(mobile).attr('href', 'tel:' + mobile);
        } else {
            $('#clf_disp_mobile_link').text('-').removeAttr('href');
        }

        if (email && email !== '-') {
            $('#clf_disp_email_link').text(email).attr('href', 'mailto:' + email);
        } else {
            $('#clf_disp_email_link').text('-').removeAttr('href');
        }

        $('#clf_details').val('');
        $('#clf_status').val('pending');
        if (tomClfReason) tomClfReason.setValue('', true);
        if (tomClfType) tomClfType.setValue('', true);

        if ($('#clf_date_time')[0]._flatpickr) {
            $('#clf_date_time')[0]._flatpickr.setDate(new Date());
        }

        $('#addCustomerListFollowupModal').modal('show');
    });

    // Handle AJAX submit
    $('#addCustListFollowupForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let custId = parseInt($('#clf_customer_id').val() || 0);

        if (custId <= 0) {
            if (typeof showToast === 'function') showToast('Invalid customer.', 'warning');
            else alert('Invalid customer.');
            return;
        }

        $('#submitClfBtn').prop('disabled', true);
        $('#submitClfLoader').removeClass('d-none');

        $.ajax({
            url: '<?= SITE_URL ?>admin/sales/customer-followup/ajax.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                $('#submitClfBtn').prop('disabled', false);
                $('#submitClfLoader').addClass('d-none');

                if (res.status === true) {
                    if (typeof showToast === 'function') showToast(res.message, 'success');
                    else alert(res.message);
                    $('#addCustomerListFollowupModal').modal('hide');
                } else {
                    if (typeof showToast === 'function') showToast(res.message, 'error');
                    else alert(res.message);
                }
            },
            error: function() {
                $('#submitClfBtn').prop('disabled', false);
                $('#submitClfLoader').addClass('d-none');
                if (typeof showToast === 'function') showToast('Server communication error', 'error');
            }
        });
    });
});
</script>
</body>
</html>
