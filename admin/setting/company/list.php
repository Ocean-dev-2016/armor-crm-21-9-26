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
                        Subscription History - <span id="historyModalCompanyName" class="text-primary"></span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="subscriptionHistoryTable">
                        <thead>
                            <tr>
                                <th class="text-uppercase fs-12 fw-bold">Company Name</th>
                                <th class="text-uppercase fs-12 fw-bold">Plan Name</th>
                                <th class="text-uppercase fs-12 fw-bold">Plan From</th>
                                <th class="text-uppercase fs-12 fw-bold">Plan To</th>
                                <th class="text-uppercase fs-12 fw-bold">Plan Expiry Date</th>
                                <th class="text-uppercase fs-12 fw-bold">Subscription Status</th>
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
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" id="modalAddDaysCompanyName" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plan Name <span class="text-danger">*</span></label>
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
                        <label class="form-label">Select Plan to Upgrade <span class="text-danger">*</span></label>
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

    // Function to load and refresh subscription history table rows via AJAX
    function loadSubscriptionHistory(companyId, companyName) {
        currentActiveCompanyId = companyId;
        if (companyName) {
            currentActiveCompanyName = companyName;
            $('#historyModalCompanyName').text(companyName);
        }

        $('#subscriptionHistoryBody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Loading subscription history...</td></tr>');

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
                    $("#subscriptionHistoryBody").html('<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load subscription history.</td></tr>');
                }
            },
            error: function() {
                $("#subscriptionHistoryBody").html('<tr><td colspan="7" class="text-center py-4 text-danger">Error loading data.</td></tr>');
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