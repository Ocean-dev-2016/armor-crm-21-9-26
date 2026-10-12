<?php
require_once __DIR__ . '/../../../conn/db.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('company', 'views');

$pageNm = 'Company';
$showAdd = hasPermission('company', 'adds');
$addType = 'redirect';
$addUrl  = SITE_URL . 'company/add';
$breadcrumbType = 'list';
$fields = 'name:Name, person_name:Person Name, mobile_no:Mobile No, email:Email, header_image:Header Image, app_logo:App Logo, favicon:Favicon';
$module = 'company';
$tbl = 'company';
include BASE_PATH . '/component/breadcrumb.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/setting/company/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Name',
    'Person Name',
    'Mobile No',
    'Email',
    'Country',
    'State',
    'City',
    'Plan',
    'Status',
    'Actions'
];
include BASE_PATH . '/component/datatable.php';

// Fetch all active plans for upgrade modal dropdown
$allPlans = db_rows("SELECT id, name, price, days FROM plan WHERE status = 1 ORDER BY days ASC, price ASC");
?>

<!-- MODAL 1: Subscription History Modal (Image 1) -->
<div class="modal fade" id="subscriptionHistoryModal" tabindex="-1" aria-labelledby="subscriptionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="history" class="text-primary fs-20"></i>
                    <h5 class="modal-title fs-16 fw-semibold mb-0" id="subscriptionHistoryModalLabel">
                        Subscription & Payment Details - <span id="historyModalCompanyName" class="text-primary"></span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="subscriptionHistoryTable">
                        <thead>
                            <tr>
                                <th class="text-uppercase fs-12 fw-bold">Company</th>
                                <th class="text-uppercase fs-12 fw-bold">Plan</th>
                                <th class="text-uppercase fs-12 fw-bold">Plan Validity</th>
                                <th class="text-uppercase fs-12 fw-bold">Expiry Date</th>
                                <th class="text-uppercase fs-12 fw-bold">Payment Summary</th>
                                <th class="text-uppercase fs-12 fw-bold">Status</th>
                                <th class="text-uppercase fs-12 fw-bold text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="subscriptionHistoryBody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <span class="spinner-border spinner-border-sm me-2"></span> Loading subscription history...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Installment Payment Schedule -->
<div class="modal fade" id="installmentScheduleModal" tabindex="-1" aria-labelledby="installmentScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="calendar-check" class="text-primary fs-20"></i>
                    <h5 class="modal-title fs-16 fw-semibold mb-0" id="installmentScheduleModalLabel">
                        Subscription Payment Details &mdash; <span id="instSchedCompanyName" class="text-primary"></span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="paymentSetupPanel" class="p-3 border-bottom bg-light-subtle d-none">
                    <div class="alert alert-info py-2 mb-3">
                        No payment has been recorded for this subscription yet. Choose a payment type to add its payment details.
                    </div>
                    <form id="setupPaymentForm">
                        <input type="hidden" name="action" value="setup_payment">
                        <input type="hidden" name="company_id" id="setupPaymentCompanyId" value="">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-4">
                                <label class="form-label fs-13 fw-semibold">Payment Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="setupPayTypeFull" value="full" checked>
                                        <label class="form-check-label" for="setupPayTypeFull">Full Payment</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="setupPayTypeInstallment" value="installment">
                                        <label class="form-check-label" for="setupPayTypeInstallment">Installment</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8" id="setupFullPaymentFields">
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label class="form-label fs-12 fw-semibold">Payment Method</label>
                                        <select name="full_payment_method" class="form-select form-select-sm">
                                            <option value="Cash">Cash</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fs-12 fw-semibold">Transaction / Reference No.</label>
                                        <input type="text" name="full_transaction_no" class="form-control form-control-sm" maxlength="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="setupInstallmentFields" class="d-none">
                            <div class="row g-2 mb-3">
                                <div class="col-sm-4">
                                    <label class="form-label fs-12 fw-semibold">No. of Installments</label>
                                    <select id="setupInstallmentCount" class="form-select form-select-sm">
                                        <option value="2">2 Installments</option>
                                        <option value="3">3 Installments</option>
                                        <option value="4" selected>4 Installments</option>
                                        <option value="5">5 Installments</option>
                                        <option value="6">6 Installments</option>
                                        <option value="12">12 Installments</option>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label fs-12 fw-semibold">Frequency</label>
                                    <select id="setupInstallmentFrequency" class="form-select form-select-sm">
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="half_yearly">Half Yearly</option>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label fs-12 fw-semibold">1st Installment Date</label>
                                    <input type="date" id="setupFirstDueDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="table-responsive border rounded bg-white mb-3">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:45px">#</th>
                                            <th>Due Date</th>
                                            <th>Amount (₹)</th>
                                            <th>Initial Status</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody id="setupInstallmentScheduleBody"></tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Scheduled Total</th>
                                            <th id="setupInstallmentTotal">₹0.00</th>
                                            <th colspan="2"><span id="setupInstallmentTotalStatus" class="badge bg-success">Matched</span></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Plan amount: <strong id="setupPaymentPlanAmount">₹0.00</strong></span>
                            <button type="submit" class="btn btn-success btn-sm" id="btnSaveSetupPayment">
                                <span id="btnSaveSetupPaymentText">Save Payment Details</span>
                                <span id="btnSaveSetupPaymentLoader" class="spinner-border spinner-border-sm d-none"></span>
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Payment Summary Bar -->
                <div id="instSchedSummaryBar" class="px-4 py-3 border-bottom bg-light-subtle d-none">
                    <div class="row g-3 fs-13">
                        <div class="col-sm-3">
                            <label class="text-muted d-block">Payment Type</label>
                            <span class="fw-semibold text-capitalize" id="instSchedPayType">-</span>
                        </div>
                        <div class="col-sm-3">
                            <label class="text-muted d-block">Total Amount</label>
                            <span class="fw-semibold" id="instSchedTotal">-</span>
                        </div>
                        <div class="col-sm-3">
                            <label class="text-muted d-block">Paid Amount</label>
                            <span class="fw-semibold text-success" id="instSchedPaid">-</span>
                        </div>
                        <div class="col-sm-3">
                            <label class="text-muted d-block">Pending Amount</label>
                            <span class="fw-semibold text-danger" id="instSchedPending">-</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-13" id="instSchedTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px">#</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Paid Amount</th>
                                <th>Pending</th>
                                <th>Paid Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th class="text-center" style="width:110px">Action</th>
                            </tr>
                        </thead>
                        <tbody id="instSchedBody">
                            <tr><td colspan="8" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span> Loading...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="payInstallmentModal" tabindex="-1" aria-labelledby="payInstallmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="wallet" class="text-success fs-20"></i>
                    <h5 class="modal-title fs-16 fw-semibold mb-0" id="payInstallmentModalLabel">Record Installment Payment</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="payInstallmentForm">
                <input type="hidden" name="action" value="pay_installment">
                <input type="hidden" name="installment_id" id="modalPayInstId" value="">
                <input type="hidden" name="company_id" id="modalPayCompanyId" value="">

                <div class="modal-body p-4">
                    <!-- Installment Info Box -->
                    <div class="alert alert-light border rounded-3 p-3 mb-3 bg-light-subtle">
                        <div class="row g-2 fs-13">
                            <div class="col-6"><strong>Company:</strong> <span id="modalPayCompName">-</span></div>
                            <div class="col-6"><strong>Installment:</strong> <span id="modalPayInstNo">-</span></div>
                            <div class="col-6"><strong>Due Date:</strong> <span id="modalPayDueDate">-</span></div>
                            <div class="col-6"><strong>Installment Amount:</strong> <span class="badge bg-primary fs-12" id="modalPayTotalAmount">₹0.00</span></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold">Paying Amount (₹)</label>
                            <input type="number" step="0.01" name="pay_amount" id="modalPayAmountInput" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold">Payment Date</label>
                            <input type="date" name="payment_date" id="modalPayDateInput" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold">Payment Method</label>
                            <select name="payment_method" id="modalPayMethod" class="form-select" required>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold">Transaction / Cheque No.</label>
                            <input type="text" name="transaction_no" id="modalPayTxnNo" class="form-control" placeholder="e.g. UPI Ref / Cheque No">
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-12 fw-semibold">Remarks (Optional)</label>
                            <textarea name="remarks" id="modalPayRemarks" class="form-control" rows="2" placeholder="Payment received details..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitPayInst">
                        <span id="btnSubmitPayInstText"><i data-lucide="check" class="fs-13 align-middle me-1"></i> Confirm Payment</span>
                        <span id="btnSubmitPayInstLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Add Days on Subscription Plan (Image 2) -->
<div class="modal fade" id="addDaysModal" tabindex="-1" aria-labelledby="addDaysModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fs-16 fw-semibold" id="addDaysModalLabel">Add days on Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDaysForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add_days">
                    <input type="hidden" name="subscription_id" id="modalAddDaysSubId" value="">
                    <input type="hidden" name="company_id" id="modalAddDaysCompanyId" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" id="modalAddDaysCompanyName" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plan Name</label>
                            <input type="text" id="modalAddDaysPlanName" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="form-label">Add days</label>
                            <div class="input-group">
                                <input type="number" name="add_days" id="modalAddDaysInput" class="form-control" placeholder="Enter Add Days" min="1" required>
                                <span class="input-group-text bg-light text-muted">days</span>
                            </div>
                            <small class="text-muted d-block mt-1">Current Expiry Date: <strong id="modalCurrentExpiryText">-</strong></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-end gap-2 py-3 px-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4" id="submitAddDaysBtn">
                        <span id="submitAddDaysText">Submit</span>
                        <span id="submitAddDaysLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: Upgrade Company Plan (Image 3) -->
<div class="modal fade" id="upgradePlanModal" tabindex="-1" aria-labelledby="upgradePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fs-16 fw-semibold" id="upgradePlanModalLabel">Upgrade Company Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="upgradePlanForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="upgrade_plan">
                    <input type="hidden" name="company_id" id="modalUpgradeCompanyId" value="">

                    <!-- Current Plan Banner -->
                    <div class="alert alert-info py-2 px-3 rounded-3 mb-3 d-flex align-items-center" style="background-color: #e0f7fa; border-color: #b2ebf2; color: #00838f;">
                        <span class="fw-semibold" id="modalCurrentPlanBannerText">Current Plan: -</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Plan to Upgrade</label>
                        <select name="plan_id" id="modalUpgradePlanSelect" class="form-select" required>
                            <option value="">Select Plan</option>
                            <?php foreach ($allPlans as $p): ?>
                                <option value="<?= $p['id'] ?>" data-days="<?= (int)($p['days'] ?? 0) ?>" data-price="<?= (float)($p['price'] ?? 0) ?>">
                                    <?= htmlspecialchars($p['name']) ?> (<?= (int)($p['days'] ?? 0) ?> days)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Only higher tier plans are available for upgrade.</small>
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-end gap-2 py-3 px-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success px-4" id="submitUpgradePlanBtn">
                        <span id="submitUpgradePlanText">Upgrade Plan</span>
                        <span id="submitUpgradePlanLoader" class="spinner-border spinner-border-sm d-none"></span>
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
    let currentActiveCompanyId = 0;
    let currentActiveCompanyName = '';
    let activePaymentDetailsTrigger = null;
    let setupPlanPrice = 0;

    function updateSetupInstallmentTotal() {
        let cents = 0;
        $('#setupInstallmentScheduleBody .setup-inst-amount').each(function() {
            cents += Math.round((parseFloat($(this).val()) || 0) * 100);
        });
        let amount = cents / 100;
        $('#setupInstallmentTotal').text('₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        let $badge = $('#setupInstallmentTotalStatus');
        if (cents === Math.round(setupPlanPrice * 100)) {
            $badge.removeClass('bg-warning bg-danger').addClass('bg-success').text('Matched');
        } else if (cents < Math.round(setupPlanPrice * 100)) {
            $badge.removeClass('bg-success bg-danger').addClass('bg-warning').text('Under by ₹' + (setupPlanPrice - amount).toFixed(2));
        } else {
            $badge.removeClass('bg-success bg-warning').addClass('bg-danger').text('Exceeds by ₹' + (amount - setupPlanPrice).toFixed(2));
        }
    }

    function generateSetupInstallments() {
        let count = parseInt($('#setupInstallmentCount').val(), 10) || 4;
        let frequency = $('#setupInstallmentFrequency').val();
        let firstDate = $('#setupFirstDueDate').val() || new Date().toISOString().split('T')[0];
        let baseDate = new Date(firstDate + 'T00:00:00');
        let perInstallment = Math.floor((setupPlanPrice / count) * 100) / 100;
        let remainder = Math.round((setupPlanPrice - perInstallment * count) * 100) / 100;
        let rows = '';

        for (let i = 1; i <= count; i++) {
            let dueDate = new Date(baseDate);
            if (i > 1) {
                let monthsPerInstallment = frequency === 'quarterly' ? 3 : (frequency === 'half_yearly' ? 6 : 1);
                let monthOffset = baseDate.getMonth() + ((i - 1) * monthsPerInstallment);
                let year = baseDate.getFullYear() + Math.floor(monthOffset / 12);
                let month = monthOffset % 12;
                dueDate = new Date(year, month, Math.min(baseDate.getDate(), new Date(year, month + 1, 0).getDate()));
            }

            let dateValue = dueDate.getFullYear() + '-' +
                String(dueDate.getMonth() + 1).padStart(2, '0') + '-' +
                String(dueDate.getDate()).padStart(2, '0');
            let amount = i === 1 ? perInstallment + remainder : perInstallment;
            rows += '<tr>' +
                '<td class="text-center">' + i + '</td>' +
                '<td><input type="date" class="form-control form-control-sm" name="installments[' + i + '][due_date]" value="' + dateValue + '" required></td>' +
                '<td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm setup-inst-amount" name="installments[' + i + '][amount]" value="' + amount.toFixed(2) + '" required></td>' +
                '<td><select class="form-select form-select-sm" name="installments[' + i + '][status]">' +
                    '<option value="pending" selected>Pending</option><option value="paid">Paid</option>' +
                '</select></td>' +
                '<td><select class="form-select form-select-sm" name="installments[' + i + '][payment_method]">' +
                    '<option value="Cash">Cash</option>' +
                '</select></td>' +
                '</tr>';
        }
        $('#setupInstallmentScheduleBody').html(rows);
        updateSetupInstallmentTotal();
    }

    function toggleSetupPaymentFields() {
        let installment = $('#setupPayTypeInstallment').is(':checked');
        $('#setupFullPaymentFields').toggleClass('d-none', installment);
        $('#setupInstallmentFields').toggleClass('d-none', !installment);
        $('#setupFullPaymentFields :input').prop('disabled', installment);
        $('#setupInstallmentFields :input').prop('disabled', !installment);
        if (installment) {
            generateSetupInstallments();
        }
    }

    $('input[name="payment_type"]').on('change', toggleSetupPaymentFields);
    $('#setupInstallmentCount, #setupInstallmentFrequency, #setupFirstDueDate').on('change', generateSetupInstallments);
    $(document).on('input', '.setup-inst-amount', updateSetupInstallmentTotal);

    $('#setupPaymentForm').on('submit', function(e) {
        e.preventDefault();
        if ($('#setupPayTypeInstallment').is(':checked')) {
            updateSetupInstallmentTotal();
            if (!$('#setupInstallmentTotalStatus').hasClass('bg-success')) {
                showToast('Installment amounts must equal the plan amount.', 'error');
                return;
            }
        }

        let $button = $('#btnSaveSetupPayment');
        $button.prop('disabled', true);
        $('#btnSaveSetupPaymentText').addClass('d-none');
        $('#btnSaveSetupPaymentLoader').removeClass('d-none');

        $.ajax({
            url: SITE_URL + "admin/setting/company/subscription_action.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status) {
                    showToast(res.message || 'Payment details saved.', 'success');
                    if (activePaymentDetailsTrigger) {
                        activePaymentDetailsTrigger.trigger('click');
                    }
                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to save payment details.', 'error');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                showToast('Error saving payment details. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
                $('#btnSaveSetupPaymentText').removeClass('d-none');
                $('#btnSaveSetupPaymentLoader').addClass('d-none');
            }
        });
    });

    // Function to load and refresh subscription history table rows via AJAX
    function loadSubscriptionHistory(companyId, companyName) {
        currentActiveCompanyId = companyId;
        if (companyName) {
            currentActiveCompanyName = companyName;
            $('#historyModalCompanyName').text(companyName);
        }

        $('#subscriptionHistoryBody').html('<tr><td colspan="9" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Loading subscription history...</td></tr>');

        $.ajax({
            url: SITE_URL + "admin/setting/company/subscription_action.php",
            type: "GET",
            data: { action: "get_history_rows", company_id: companyId },
            dataType: "json",
            success: function(res) {
                if (res.status && res.html) {
                    $("#subscriptionHistoryBody").html(res.html);
                    if (res.company_name && !companyName) {
                        currentActiveCompanyName = res.company_name;
                        $('#historyModalCompanyName').text(res.company_name);
                    }
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                } else {
                    $("#subscriptionHistoryBody").html('<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load subscription history.</td></tr>');
                }
            },
            error: function() {
                $("#subscriptionHistoryBody").html('<tr><td colspan="9" class="text-center py-4 text-danger">Error loading data.</td></tr>');
            }
        });
    }

    // 1. Click Handler on Dropdown menu: "Subscription History"
    $(document).on('click', '.btn-view-subscription-history', function(e) {
        e.preventDefault();
        let companyId = $(this).data('id');
        let companyName = $(this).data('name') || '';

        loadSubscriptionHistory(companyId, companyName);

        let historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('subscriptionHistoryModal'));
        historyModal.show();
    });

    // 2. Click Handler: Open "Add Days" Modal (Image 2)
    $(document).on('click', '.btn-add-days', function() {
        let subId = $(this).data('id');
        let companyId = $(this).data('company-id') || currentActiveCompanyId;
        let companyName = $(this).data('company-name') || currentActiveCompanyName;
        let planName = $(this).data('plan-name') || '';
        let expiry = $(this).data('expiry') || '-';

        $('#modalAddDaysSubId').val(subId);
        $('#modalAddDaysCompanyId').val(companyId);
        $('#modalAddDaysCompanyName').val(companyName);
        $('#modalAddDaysPlanName').val(planName);
        $('#modalCurrentExpiryText').text(expiry);
        $('#modalAddDaysInput').val('');

        let addDaysModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addDaysModal'));
        addDaysModal.show();
    });

    // 3. Submit Handler: Add Days form
    $('#addDaysForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#submitAddDaysBtn');
        let $text = $('#submitAddDaysText');
        let $loader = $('#submitAddDaysLoader');

        let daysVal = parseInt($('#modalAddDaysInput').val());
        if (!daysVal || daysVal <= 0) {
            showToast('Please enter a valid number of days.', 'error');
            return;
        }

        $btn.prop('disabled', true);
        $text.text('Adding...');
        $loader.removeClass('d-none');

        $.ajax({
            url: SITE_URL + "admin/setting/company/subscription_action.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.status === true) {
                    showToast(response.message, 'success');
                    let modalEl = document.getElementById('addDaysModal');
                    let modalObj = bootstrap.Modal.getInstance(modalEl);
                    if (modalObj) modalObj.hide();

                    // Refresh history modal table
                    loadSubscriptionHistory(currentActiveCompanyId);

                    // Also reload main datatable if available
                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                showToast('Error processing request. Please try again.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $text.text('Submit');
                $loader.addClass('d-none');
            }
        });
    });

    // 4. Click Handler: Open "Upgrade Plan" Modal (Image 3)
    $(document).on('click', '.btn-upgrade-plan', function() {
        let companyId = $(this).data('company-id') || currentActiveCompanyId;
        let currentPlanId = $(this).data('plan-id') || '';
        let currentPlanName = $(this).data('plan-name') || '';
        let currentPlanDays = $(this).data('plan-days') || '';

        $('#modalUpgradeCompanyId').val(companyId);
        $('#modalCurrentPlanBannerText').text('Current Plan: ' + currentPlanName + (currentPlanDays ? ' (' + currentPlanDays + ' days)' : ''));

        // In the dropdown, disable the current plan so only other/higher tier plans can be selected
        $('#modalUpgradePlanSelect option').each(function() {
            let optVal = $(this).val();
            if (optVal && parseInt(optVal) === parseInt(currentPlanId)) {
                $(this).prop('disabled', true).text(currentPlanName + ' (Current Plan)');
            } else if (optVal) {
                $(this).prop('disabled', false);
            }
        });
        $('#modalUpgradePlanSelect').val('');

        let upgradeModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('upgradePlanModal'));
        upgradeModal.show();
    });

    // 5. Submit Handler: Upgrade Plan form
    $('#upgradePlanForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#submitUpgradePlanBtn');
        let $text = $('#submitUpgradePlanText');
        let $loader = $('#submitUpgradePlanLoader');

        let selectedPlan = $('#modalUpgradePlanSelect').val();
        if (!selectedPlan) {
            showToast('Please select a plan to upgrade.', 'error');
            return;
        }

        $btn.prop('disabled', true);
        $text.text('Upgrading...');
        $loader.removeClass('d-none');

        $.ajax({
            url: SITE_URL + "admin/setting/company/subscription_action.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.status === true) {
                    showToast(response.message, 'success');
                    let modalEl = document.getElementById('upgradePlanModal');
                    let modalObj = bootstrap.Modal.getInstance(modalEl);
                    if (modalObj) modalObj.hide();

                    // Refresh history modal table
                    loadSubscriptionHistory(currentActiveCompanyId);

                    // Also reload main datatable if available
                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                showToast('Error processing upgrade. Please try again.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $text.text('Upgrade Plan');
                $loader.addClass('d-none');
            }
        });
    });

    // 6. Toggle Installment Schedule Details in History Modal
    $(document).on('click', '.btn-toggle-inst', function(e) {
        e.preventDefault();
        let target = $(this).data('target');
        $(target).toggleClass('d-none');
        if (window.lucide) lucide.createIcons();
    });

    // 7. Open Pay Installment Modal
    $(document).on('click', '.btn-pay-inst', function(e) {
        e.preventDefault();
        let instId = $(this).data('id');
        let companyId = $(this).data('company-id') || currentActiveCompanyId;
        let compName = $(this).data('company-name') || currentActiveCompanyName;
        let instNo = $(this).data('inst-no');
        let amount = $(this).data('amount');
        let dueDate = $(this).data('due-date');

        $('#modalPayInstId').val(instId);
        $('#modalPayCompanyId').val(companyId);
        $('#modalPayCompName').text(compName);
        $('#modalPayInstNo').text('Installment #' + instNo);
        $('#modalPayDueDate').text(dueDate);
        $('#modalPayTotalAmount').text('₹' + parseFloat(amount).toFixed(2));
        $('#modalPayAmountInput').val(parseFloat(amount).toFixed(2));
        $('#modalPayRemarks').val('');
        $('#modalPayTxnNo').val('');

        let payModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('payInstallmentModal'));
        payModal.show();
        if (window.lucide) lucide.createIcons();
    });

    // 8. Submit Handler: Pay Installment form
    $('#payInstallmentForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#btnSubmitPayInst');
        let $text = $('#btnSubmitPayInstText');
        let $loader = $('#btnSubmitPayInstLoader');

        $btn.prop('disabled', true);
        $text.addClass('d-none');
        $loader.removeClass('d-none');

        $.ajax({
            url: SITE_URL + "admin/setting/company/subscription_action.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    let payModalEl = document.getElementById('payInstallmentModal');
                    let payModalObj = bootstrap.Modal.getInstance(payModalEl);
                    if (payModalObj) payModalObj.hide();

                    // Refresh subscription history
                    loadSubscriptionHistory(currentActiveCompanyId);
                } else {
                    showToast(res.message || 'Payment failed', 'error');
                }
            },
            error: function() {
                showToast('Server error while saving payment.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $text.removeClass('d-none');
                $loader.addClass('d-none');
            }
        });
    });

    $(document).on('click', '.btn-view-subscription-history', function(e) {
        e.preventDefault();
        let companyId = $(this).data('id');
        let companyName = $(this).data('name') || '';

        loadSubscriptionHistory(companyId, companyName);

        let historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('subscriptionHistoryModal'));
        historyModal.show();
    });

    $(document).on('click', '.btn-view-payment-details', function(e) {

    e.preventDefault();

    let companyId = $(this).data('id');
    let companyName = $(this).data('name') || '';
    activePaymentDetailsTrigger = $(this);
    $('#setupPaymentCompanyId').val(companyId);
    $('#setupPaymentPanel').addClass('d-none');
    $('#setupPayTypeFull').prop('checked', true);
    $('#setupInstallmentScheduleBody').empty();
    toggleSetupPaymentFields();

    // Reset popup
    $('#instSchedCompanyName').text(companyName || '-');

    $('#instSchedSummaryBar').addClass('d-none');

    $('#instSchedPayType').text('-');
    $('#instSchedTotal').text('-');
    $('#instSchedPaid').text('-');
    $('#instSchedPending').text('-');

    $('#instSchedBody').html(`
        <tr>
            <td colspan="8" class="text-center py-4 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Loading...
            </td>
        </tr>
    `);

    // Show modal immediately
    let historyModal = bootstrap.Modal.getOrCreateInstance(
        document.getElementById('installmentScheduleModal')
    );

    historyModal.show();

    // Load payment details
    $.ajax({

        url: SITE_URL + "admin/setting/company/subscription_action.php",

        type: "GET",

        data: {
            action: "get_payment_details",
            company_id: companyId
        },

        dataType: "json",

        success: function(res) {

            console.log(res);

            if (res.status) {
                let paymentSetupRequired = res.payment_setup_required === true;
                $('#paymentSetupPanel').toggleClass('d-none', !paymentSetupRequired);
                $('#instSchedSummaryBar').toggleClass('d-none', paymentSetupRequired);
                if (paymentSetupRequired) {
                    setupPlanPrice = parseFloat(res.plan_price) || 0;
                    $('#setupPaymentPlanAmount').text('₹' + setupPlanPrice.toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    $('#setupPayTypeFull').prop('checked', true);
                    toggleSetupPaymentFields();
                }

                // -----------------------------------------
                // Company Name
                // -----------------------------------------

                if (res.company_name) {
                    $('#instSchedCompanyName')
                        .text(res.company_name);
                } else if (companyName) {
                    $('#instSchedCompanyName')
                        .text(companyName);
                }

                // -----------------------------------------
                // Table HTML
                // -----------------------------------------

                if (res.html) {

                    $('#instSchedBody')
                        .html(res.html);

                } else {

                    $('#instSchedBody').html(`
                        <tr>
                            <td colspan="8"
                                class="text-center py-4 text-muted">
                                No payment details found.
                            </td>
                        </tr>
                    `);
                }

                // -----------------------------------------
                // Payment Summary
                // -----------------------------------------

                $('#instSchedPayType').text(
                    res.payment_type
                        ? res.payment_type
                        : '-'
                );

                $('#instSchedTotal').text(
                    '₹' + Number(
                        res.total_amount || 0
                    ).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })
                );

                $('#instSchedPaid').text(
                    '₹' + Number(
                        res.paid_amount || 0
                    ).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })
                );

                $('#instSchedPending').text(
                    '₹' + Number(
                        res.pending_amount || 0
                    ).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })
                );

                if (!paymentSetupRequired) {
                    $('#instSchedSummaryBar').removeClass('d-none');
                }

                // -----------------------------------------
                // Lucide Icons
                // -----------------------------------------

                if (window.lucide) {
                    lucide.createIcons();
                }

            } else {

                $('#instSchedBody').html(`
                    <tr>
                        <td colspan="8"
                            class="text-center py-4 text-danger">

                            ${res.message || 'Failed to load payment details.'}

                        </td>
                    </tr>
                `);

            }
        },

        error: function(xhr, status, error) {

            console.log(xhr.responseText);

            $('#instSchedBody').html(`
                <tr>
                    <td colspan="8"
                        class="text-center py-4 text-danger">

                        Error loading payment details.

                    </td>
                </tr>
            `);
        }
    });
});

    // Auto open Subscription History modal if URL has company parameter (e.g. from index.php dashboard alert)
    const urlParams = new URLSearchParams(window.location.search);
    const subCompanyId = urlParams.get('open_subscription') || urlParams.get('sub_company_id');
    const subCompanyName = urlParams.get('company_name') || '';
    if (subCompanyId && parseInt(subCompanyId) > 0) {
        let compId = parseInt(subCompanyId);
        loadSubscriptionHistory(compId, subCompanyName);
        let historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('subscriptionHistoryModal'));
        historyModal.show();
    }
});
</script>
</body>
</html>