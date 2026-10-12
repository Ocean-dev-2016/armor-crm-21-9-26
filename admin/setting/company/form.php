<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$pageNm = 'Company';
$tbl = 'company';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isEdit = $id > 0;
$company = [
    'id' => 0,
    'company_type_id' => '',
    'name' => '',
    'prefix' => '',
    'gst' => '',
    'indiamart_api_key' => '',
    'pan_card' => '',
    'header_image' => '',
    'footer_image' => '',
    'order_prefix' => '',
    'order_title' => '',
    'order_view_color' => '#117087',
    'quotation_prefix' => '',
    'quotation_title' => '',
    'quotation_view_color' => '#117087',
    'dispatch_prefix' => '',
    'dispatch_title' => '',
    'dispatch_view_color' => '#117087',
    'packing_slip_prefix' => '',
    'packing_slip_title' => '',
    'packing_slip_view_color' => '#117087',
    'is_stock_check' => 0,
    'stock_manage_by' => 'fifo',
    'favicon' => '',
    'app_logo' => '',
    'bg_dark_color' => '#212529',
    'bg_light_color' => '#f8f9fa',
    'customer_panel_login' => 0,
    'address' => '',
    'bank_details' => '',
    'terms_conditions' => '',
    'person_name' => '',
    'mobile_no' => '',
    'email' => '',
    'country_id' => '',
    'state_id' => '',
    'city_id' => '',
    'plan_id' => '',
];
if ($isEdit) {
    $companyData = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$companyData) {
        die($pageNm . ' not found.');
    }
    $company = array_merge($company, $companyData);
}
$companyTypes = db_rows("SELECT id, name FROM company_type WHERE status = 1 ORDER BY name ASC");
$sql_country = "SELECT * FROM country WHERE status = 1";
$country = db_rows($sql_country);

$sql_plan = "SELECT * FROM plan WHERE status = 1";
$plan = db_rows($sql_plan);

// Edit mode: existing payment & installments fetch karo
$existingPayment = null;
$existingInstallments = [];
if ($isEdit && !empty($company['plan_id'])) {
    $activeSub = db_row("SELECT id FROM company_subscription_plan WHERE company_id = $id AND subscription_status = 'active' LIMIT 1");
    if (!empty($activeSub)) {
        $existingPayment = db_row("SELECT * FROM company_subscription_payment WHERE company_subscription_plan_id = " . (int)$activeSub['id'] . " ORDER BY id DESC LIMIT 1");
        if (!empty($existingPayment)) {
            $existingInstallments = db_rows("SELECT * FROM company_subscription_installment WHERE payment_id = " . (int)$existingPayment['id'] . " ORDER BY installment_no ASC");
        }
    }
}

include BASE_PATH . '/include/header.php';

$requiredAction = $isEdit ? 'updates' : 'adds';
checkPermissionOrDeny('company', $requiredAction);

$breadcrumbType = 'form';
$isEdit = $isEdit ? true : false;
$parentUrl = SITE_URL . 'company';
include BASE_PATH . '/component/breadcrumb.php';
?>
<style>
    @media (min-width: 992px) {
        .col-lg-fifth {
            flex: 0 0 auto;
            width: 20%;
        }
    }
</style>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><?= $isEdit ? 'Edit ' . $pageNm : 'Add ' . $pageNm ?></h6>
            </div>
            <div class="card-body">
                <?php if ($isEdit): ?>
                    <!-- Step Wizard Navigation (Edit Mode) -->
                    <div class="wizard-steps-nav mb-4">
                        <button type="button" class="wizard-step-item active" data-step="1">
                            <span class="wizard-step-circle">1</span>
                            <span class="wizard-step-label">Company Information</span>
                        </button>
                        <button type="button" class="wizard-step-item" data-step="2">
                            <span class="wizard-step-circle">2</span>
                            <span class="wizard-step-label">Prefix & Title</span>
                        </button>
                        <button type="button" class="wizard-step-item" data-step="3">
                            <span class="wizard-step-circle">3</span>
                            <span class="wizard-step-label">Product Related Setting</span>
                        </button>
                        <button type="button" class="wizard-step-item" data-step="4">
                            <span class="wizard-step-circle">4</span>
                            <span class="wizard-step-label">Image Setting</span>
                        </button>
                        <button type="button" class="wizard-step-item" data-step="5">
                            <span class="wizard-step-circle">5</span>
                            <span class="wizard-step-label">General Setting</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form id="companyForm" enctype="multipart/form-data">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= (int)$company['id'] ?>" id="id">
                    <?php endif; ?>

                    <!-- STEP 1: Company Information -->
                    <div class="wizard-step <?= $isEdit ? '' : 'active' ?>" data-step="1">
                        <?php 
                            $firstRowCol = $isEdit ? 'col-lg-fifth col-md-4 col-sm-6' : 'col-lg-2 col-md-4 col-sm-6';
                        ?>

                        <!-- Row 1: Company Type, Company Name, Prefix, Person Name, Email, (Password only on Add) -->
                        <div class="row g-3">
                            <div class="<?= $firstRowCol ?>">
                                <div class="mb-3">
                                    <label class="form-label">Company Type</label>
                                    <select name="company_type_id" id="select-company-type" class="form-select">
                                        <option value="">Select Company Type</option>
                                        <?php foreach ($companyTypes as $type): ?>
                                            <option value="<?= $type['id'] ?>" <?= ((int)$type['id'] === (int)$company['company_type_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($type['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="<?= $firstRowCol ?>">
                                <div class="mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter Company Name" 
                                        value="<?= htmlspecialchars($company['name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="<?= $firstRowCol ?>">                                
                                <div class="mb-3">
                                    <label class="form-label">Prefix</label>
                                    <input type="text" name="prefix" id="prefix" class="form-control" placeholder="Enter Prefix" 
                                        value="<?= htmlspecialchars($company['prefix'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="<?= $firstRowCol ?>">
                                <div class="mb-3">
                                    <label class="form-label">Person Name</label>
                                    <input type="text" name="person_name" id="person_name" class="form-control" placeholder="Enter Person Name" 
                                        value="<?= htmlspecialchars($company['person_name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="<?= $firstRowCol ?>">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="text" name="email" id="email" class="form-control" placeholder="Enter Email" 
                                        value="<?= htmlspecialchars($company['email'] ?? '') ?>">
                                </div>
                            </div>
                            <?php if (!$isEdit): ?>
                                <div class="<?= $firstRowCol ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter Password">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Row 2: Mobile No, Plan, GST, Pan Card -->
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Mobile No</label>
                                    <input type="text" name="mobile_no" id="mobile_no" class="form-control" placeholder="Enter Mobile No" 
                                        value="<?= htmlspecialchars($company['mobile_no'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Plan</label>
                                    <select name="plan_id" id="select-plan" class="form-select">
                                        <option value="">Select Plan</option>
                                        <?php foreach ($plan as $p): ?>
                                            <option value="<?= $p['id'] ?>" data-price="<?= (float)($p['price'] ?? 0) ?>" data-days="<?= (int)($p['days'] ?? 0) ?>" <?= ((int)$p['id'] === (int)$company['plan_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['name']) ?> (₹<?= number_format((float)($p['price'] ?? 0), 2) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">GST</label>
                                    <input type="text" name="gst" id="gst" class="form-control text-uppercase" placeholder="Enter GST" 
                                        maxlength="15" value="<?= htmlspecialchars($company['gst'] ?? '') ?>">
                                    <small class="text-muted d-block mt-1">Note: Format e.g. 22AAAAA0000A1Z5</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Pan Card</label>
                                    <input type="text" name="pan_card" id="pan_card" class="form-control text-uppercase" placeholder="Enter Pan Card" 
                                        maxlength="10" value="<?= htmlspecialchars($company['pan_card'] ?? '') ?>">
                                    <small class="text-muted d-block mt-1">Note: Format e.g. ABCDE1234F</small>
                                </div>
                            </div>
                        </div>

                        <!-- Subscription Payment & Installment Setup (Shown when Plan is selected, Add mode only) -->
                        <div id="planPaymentContainer" class="card border border-primary-subtle bg-light-subtle rounded-3 p-3 mb-3 <?= ($isEdit || empty($company['plan_id'])) ? 'd-none' : '' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h6 class="fs-14 fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                                    <i data-lucide="credit-card" class="fs-16"></i> Plan Payment & Installment Setup
                                </h6>
                                <div class="fs-13 fw-semibold text-dark">
                                    Total Plan Price: <span class="badge bg-primary text-white fs-13" id="displayPlanPrice">₹0.00</span>
                                </div>
                            </div>

                            <input type="hidden" name="total_plan_price" id="inputTotalPlanPrice" value="0">

                            <div class="row g-3 align-items-center mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Payment Mode</label>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="form-check" id="fullRadioWrapper">
                                            <input class="form-check-input" type="radio" name="payment_type" id="payTypeFull" value="full" checked>
                                            <label class="form-check-label fw-medium" for="payTypeFull">Full Payment</label>
                                        </div>
                                        <div class="form-check" id="installmentRadioWrapper">
                                            <input class="form-check-input" type="radio" name="payment_type" id="payTypeInstallment" value="installment">
                                            <label class="form-check-label fw-medium" for="payTypeInstallment">Installment (EMI)</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8 payment-full-fields">
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label class="form-label fs-12 fw-semibold">Payment Method</label>
                                            <select name="full_payment_method" class="form-select form-select-sm">
                                                <option value="Cash">Cash</option>
                                                <option value="UPI">UPI / QR Code</option>
                                                <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                                <option value="Cheque">Cheque</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fs-12 fw-semibold">Transaction / Ref No.</label>
                                            <input type="text" name="full_transaction_no" class="form-control form-control-sm" placeholder="e.g. UPI Ref / Cheque No">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Installment Configuration Box (Shown only when Installment selected) -->
                            <div id="installmentConfigBox" class="d-none border-top pt-3">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fs-12 fw-semibold">No. of Installments</label>
                                        <select name="installment_count" id="installmentCount" class="form-select form-select-sm">
                                            <option value="2">2 Installments</option>
                                            <option value="3">3 Installments</option>
                                            <option value="4" selected>4 Installments</option>
                                            <option value="5">5 Installments</option>
                                            <option value="6">6 Installments</option>
                                            <option value="12">12 Installments</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fs-12 fw-semibold">Frequency</label>
                                        <select name="installment_frequency" id="installmentFrequency" class="form-select form-select-sm">
                                            <option value="monthly" selected>Monthly</option>
                                            <option value="quarterly">Quarterly (Every 3 Months)</option>
                                            <option value="half_yearly">Half Yearly (Every 6 Months)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fs-12 fw-semibold">1st Installment Date</label>
                                        <input type="date" name="first_due_date" id="firstDueDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-3 col-sm-6 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnRegenerateSchedule">
                                            <i data-lucide="refresh-cw" class="fs-12 align-middle me-1"></i> Recalculate
                                        </button>
                                    </div>
                                </div>

                                <!-- Schedule Preview Table -->
                                <div class="table-responsive rounded-2 border bg-white">
                                    <table class="table table-sm table-bordered align-middle mb-0" id="installmentPreviewTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width: 70px;">#</th>
                                                <th>Due Date</th>
                                                <th style="width: 220px;">Amount (₹)</th>
                                                <th>Initial Status</th>
                                                <th>Payment Method (if Paid)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="installmentScheduleBody">
                                            <!-- Dynamically generated rows -->
                                        </tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="2" class="text-end">Total Scheduled:</td>
                                                <td><span id="installmentTotalSum">₹0.00</span></td>
                                                <td colspan="2"><span id="installmentTotalMatchBadge" class="badge bg-success">Matched</span></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i data-lucide="info" class="fs-12 align-middle me-1"></i>
                                    You can adjust the individual installment amounts or due dates. The first installment can be marked as Paid immediately if down-payment is received today.
                                </small>
                            </div>
                        </div>

                        <!-- Row 2: Indiamart API Key, Country, State, City -->
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Indiamart API Key</label>
                                    <input type="text" name="indiamart_api_key" id="indiamart_api_key" class="form-control" placeholder="Enter Indiamart API Key" 
                                        value="<?= htmlspecialchars($company['indiamart_api_key'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <select name="country_id" id="select-single" class="form-select country_base_state">
                                        <option value="">Select a Country</option>
                                        <?php foreach ($country as $val) { ?>
                                            <option value="<?= $val['id'] ?>" <?= ((int)$val['id'] === (int)$company['country_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($val['name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">State</label>
                                    <select name="state_id" id="select-state" class="form-select state_base_city">
                                        <option value="">Select a State</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">City</label>
                                    <select name="city_id" id="select-city" class="form-select city_base_state">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: Address, Bank Details, Terms And Condition (Quill Editors) -->
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <div id="address-editor" class="editor-container">
                                        <?= $company['address'] ?? '' ?>
                                    </div>
                                    <input type="hidden" name="address" id="address" value="<?= htmlspecialchars($company['address'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Bank Details</label>
                                    <div id="bank-editor" class="editor-container">
                                        <?= $company['bank_details'] ?? '' ?>
                                    </div>
                                    <input type="hidden" name="bank_details" id="bank_details" value="<?= htmlspecialchars($company['bank_details'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Terms And Condition</label>
                                    <div id="terms-editor" class="editor-container">
                                        <?= $company['terms_conditions'] ?? '' ?>
                                    </div>
                                    <input type="hidden" name="terms_conditions" id="terms_conditions" value="<?= htmlspecialchars($company['terms_conditions'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <?php if (!$isEdit): ?>
                            <!-- On Add screen: Header & Footer Image are shown here as existing -->
                            <div class="row g-3">
                                <div class="col-lg-6 col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Header Image <small class="text-muted">(933 X 184)</small></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light" id="headerImageNameDisplay" 
                                                value="<?= !empty($company['header_image']) ? htmlspecialchars($company['header_image']) : '' ?>" 
                                                placeholder="No File Selected" readonly>
                                            <input type="file" name="header_image" id="headerImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                            <button class="btn btn-select-file" type="button" id="selectHeaderImageBtn">
                                                SELECT FILE
                                            </button>
                                        </div>
                                        <input type="hidden" name="remove_header_image" id="remove_header_image" value="0">
                                        <div id="headerPreviewContainer" class="image-preview-wrapper d-none">
                                            <img id="headerPreview" src="" alt="Header Preview" class="image-preview-thumb">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeHeaderBtn" title="Remove image">
                                                <i data-lucide="x" class="fs-12"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Footer Image <small class="text-muted">(943 X 103)</small></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light" id="footerImageNameDisplay" 
                                                value="<?= !empty($company['footer_image']) ? htmlspecialchars($company['footer_image']) : '' ?>" 
                                                placeholder="No File Selected" readonly>
                                            <input type="file" name="footer_image" id="footerImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                            <button class="btn btn-select-file" type="button" id="selectFooterImageBtn">
                                                SELECT FILE
                                            </button>
                                        </div>
                                        <input type="hidden" name="remove_footer_image" id="remove_footer_image" value="0">
                                        <div id="footerPreviewContainer" class="image-preview-wrapper d-none">
                                            <img id="footerPreview" src="" alt="Footer Preview" class="image-preview-thumb">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeFooterBtn" title="Remove image">
                                                <i data-lucide="x" class="fs-12"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isEdit): ?>
                        <!-- STEP 2: Prefix & Title -->
                        <div class="wizard-step d-none" data-step="2">
                            <!-- Order Section -->
                            <div class="card mb-3 border">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-bold text-dark">Order Setting</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-4">
                                            <label class="form-label">Order Prefix</label>
                                            <input type="text" name="order_prefix" id="order_prefix" class="form-control" placeholder="e.g. ORD-" 
                                                value="<?= htmlspecialchars($company['order_prefix'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-5 col-md-5">
                                            <label class="form-label">Order Title</label>
                                            <input type="text" name="order_title" id="order_title" class="form-control" placeholder="e.g. Sales Order" 
                                                value="<?= htmlspecialchars($company['order_title'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-3 col-md-3">
                                            <label class="form-label">Order View Color</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="color" name="order_view_color" id="order_view_color" class="form-control form-control-color" 
                                                    value="<?= !empty($company['order_view_color']) ? htmlspecialchars($company['order_view_color']) : '#117087' ?>">
                                                <input type="text" class="form-control color-text-sync" data-target="#order_view_color" 
                                                    value="<?= !empty($company['order_view_color']) ? htmlspecialchars($company['order_view_color']) : '#117087' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3 border">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-bold text-dark">Quotation Setting</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-4">
                                            <label class="form-label">Quotation Prefix</label>
                                            <input type="text" name="quotation_prefix" id="quotation_prefix" class="form-control" placeholder="e.g. QUOT-" 
                                                value="<?= htmlspecialchars($company['quotation_prefix'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-5 col-md-5">
                                            <label class="form-label">Quotation Title</label>
                                            <input type="text" name="quotation_title" id="quotation_title" class="form-control" placeholder="e.g. Quotation" 
                                                value="<?= htmlspecialchars($company['quotation_title'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-3 col-md-3">
                                            <label class="form-label">Quotation View Color</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="color" name="quotation_view_color" id="quotation_view_color" class="form-control form-control-color" 
                                                    value="<?= !empty($company['quotation_view_color']) ? htmlspecialchars($company['quotation_view_color']) : '#117087' ?>">
                                                <input type="text" class="form-control color-text-sync" data-target="#quotation_view_color" 
                                                    value="<?= !empty($company['quotation_view_color']) ? htmlspecialchars($company['quotation_view_color']) : '#117087' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dispatch Section -->
                            <div class="card mb-3 border">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-bold text-dark">Dispatch Setting</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-4">
                                            <label class="form-label">Dispatch Prefix</label>
                                            <input type="text" name="dispatch_prefix" id="dispatch_prefix" class="form-control" placeholder="e.g. DISP-" 
                                                value="<?= htmlspecialchars($company['dispatch_prefix'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-5 col-md-5">
                                            <label class="form-label">Dispatch Title</label>
                                            <input type="text" name="dispatch_title" id="dispatch_title" class="form-control" placeholder="e.g. Dispatch Note" 
                                                value="<?= htmlspecialchars($company['dispatch_title'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-3 col-md-3">
                                            <label class="form-label">Dispatch View Color</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="color" name="dispatch_view_color" id="dispatch_view_color" class="form-control form-control-color" 
                                                    value="<?= !empty($company['dispatch_view_color']) ? htmlspecialchars($company['dispatch_view_color']) : '#117087' ?>">
                                                <input type="text" class="form-control color-text-sync" data-target="#dispatch_view_color" 
                                                    value="<?= !empty($company['dispatch_view_color']) ? htmlspecialchars($company['dispatch_view_color']) : '#117087' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Packing Slip Section -->
                            <div class="card mb-3 border">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-bold text-dark">Packing Slip Setting</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-4">
                                            <label class="form-label">Packing Slip Prefix</label>
                                            <input type="text" name="packing_slip_prefix" id="packing_slip_prefix" class="form-control" placeholder="e.g. PS-" 
                                                value="<?= htmlspecialchars($company['packing_slip_prefix'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-5 col-md-5">
                                            <label class="form-label">Packing Slip Title</label>
                                            <input type="text" name="packing_slip_title" id="packing_slip_title" class="form-control" placeholder="e.g. Packing Slip" 
                                                value="<?= htmlspecialchars($company['packing_slip_title'] ?? '') ?>">
                                        </div>
                                        <div class="col-lg-3 col-md-3">
                                            <label class="form-label">Packing Slip View Color</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="color" name="packing_slip_view_color" id="packing_slip_view_color" class="form-control form-control-color" 
                                                    value="<?= !empty($company['packing_slip_view_color']) ? htmlspecialchars($company['packing_slip_view_color']) : '#117087' ?>">
                                                <input type="text" class="form-control color-text-sync" data-target="#packing_slip_view_color" 
                                                    value="<?= !empty($company['packing_slip_view_color']) ? htmlspecialchars($company['packing_slip_view_color']) : '#117087' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Product Related Setting -->
                        <div class="wizard-step d-none" data-step="3">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="row g-4">
                                        <div class="col-lg-6 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Stock Check</label>
                                                <select name="is_stock_check" id="is_stock_check" class="form-select">
                                                    <option value="1" <?= ((int)$company['is_stock_check'] === 1) ? 'selected' : '' ?>>Yes</option>
                                                    <option value="0" <?= ((int)$company['is_stock_check'] === 0) ? 'selected' : '' ?>>No</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label d-block">Stock Manage By</label>
                                                <div class="d-flex align-items-center gap-4 mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="stock_manage_by" id="stock_fifo" value="fifo" 
                                                            <?= (($company['stock_manage_by'] ?? 'fifo') === 'fifo') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="stock_fifo">FIFO (First In First Out)</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="stock_manage_by" id="stock_lifo" value="lifo" 
                                                            <?= (($company['stock_manage_by'] ?? '') === 'lifo') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="stock_lifo">LIFO (Last In First Out)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 4: Image Setting -->
                        <div class="wizard-step d-none" data-step="4">
                            <div class="row g-3">
                                <!-- Header Image -->
                                <div class="col-lg-6 col-md-12">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <label class="form-label fw-bold">Header Image <small class="text-muted">(933 X 184)</small></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light" id="headerImageNameDisplay" 
                                                    value="<?= !empty($company['header_image']) ? htmlspecialchars($company['header_image']) : '' ?>" 
                                                    placeholder="No File Selected" readonly>
                                                <input type="file" name="header_image" id="headerImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                                <button class="btn btn-select-file" type="button" id="selectHeaderImageBtn">
                                                    SELECT FILE
                                                </button>
                                            </div>
                                            <input type="hidden" name="remove_header_image" id="remove_header_image" value="0">
                                            <?php 
                                                $hasHeaderImage = !empty($company['header_image']) && file_exists(BASE_PATH . '/uploads/company/' . $company['header_image']);
                                                $headerImageUrl = $hasHeaderImage ? SITE_URL . 'uploads/company/' . $company['header_image'] : '';
                                            ?>
                                            <div id="headerPreviewContainer" class="image-preview-wrapper <?= $hasHeaderImage ? '' : 'd-none' ?>">
                                                <img id="headerPreview" src="<?= $headerImageUrl ?>" alt="Header Preview" class="image-preview-thumb">
                                                <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeHeaderBtn" title="Remove image">
                                                    <i data-lucide="x" class="fs-12"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Image -->
                                <div class="col-lg-6 col-md-12">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <label class="form-label fw-bold">Footer Image <small class="text-muted">(943 X 103)</small></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light" id="footerImageNameDisplay" 
                                                    value="<?= !empty($company['footer_image']) ? htmlspecialchars($company['footer_image']) : '' ?>" 
                                                    placeholder="No File Selected" readonly>
                                                <input type="file" name="footer_image" id="footerImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                                <button class="btn btn-select-file" type="button" id="selectFooterImageBtn">
                                                    SELECT FILE
                                                </button>
                                            </div>
                                            <input type="hidden" name="remove_footer_image" id="remove_footer_image" value="0">
                                            <?php 
                                                $hasFooterImage = !empty($company['footer_image']) && file_exists(BASE_PATH . '/uploads/company/' . $company['footer_image']);
                                                $footerImageUrl = $hasFooterImage ? SITE_URL . 'uploads/company/' . $company['footer_image'] : '';
                                            ?>
                                            <div id="footerPreviewContainer" class="image-preview-wrapper <?= $hasFooterImage ? '' : 'd-none' ?>">
                                                <img id="footerPreview" src="<?= $footerImageUrl ?>" alt="Footer Preview" class="image-preview-thumb">
                                                <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeFooterBtn" title="Remove image">
                                                    <i data-lucide="x" class="fs-12"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Favicon -->
                                <div class="col-lg-6 col-md-12">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <label class="form-label fw-bold">Favicon <small class="text-muted">(e.g. 32 X 32 / PNG, ICO)</small></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light" id="faviconNameDisplay" 
                                                    value="<?= !empty($company['favicon']) ? htmlspecialchars($company['favicon']) : '' ?>" 
                                                    placeholder="No File Selected" readonly>
                                                <input type="file" name="favicon" id="faviconInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp, image/x-icon, image/vnd.microsoft.icon">
                                                <button class="btn btn-select-file" type="button" id="selectFaviconBtn">
                                                    SELECT FILE
                                                </button>
                                            </div>
                                            <input type="hidden" name="remove_favicon" id="remove_favicon" value="0">
                                            <?php 
                                                $hasFavicon = !empty($company['favicon']) && file_exists(BASE_PATH . '/uploads/company/' . $company['favicon']);
                                                $faviconUrl = $hasFavicon ? SITE_URL . 'uploads/company/' . $company['favicon'] : '';
                                            ?>
                                            <div id="faviconPreviewContainer" class="image-preview-wrapper <?= $hasFavicon ? '' : 'd-none' ?>">
                                                <img id="faviconPreview" src="<?= $faviconUrl ?>" alt="Favicon Preview" class="image-preview-thumb">
                                                <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeFaviconBtn" title="Remove image">
                                                    <i data-lucide="x" class="fs-12"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- App Logo -->
                                <div class="col-lg-6 col-md-12">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <label class="form-label fw-bold">App Logo <small class="text-muted">(e.g. 200 X 50 / PNG, WEBP)</small></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light" id="appLogoNameDisplay" 
                                                    value="<?= !empty($company['app_logo']) ? htmlspecialchars($company['app_logo']) : '' ?>" 
                                                    placeholder="No File Selected" readonly>
                                                <input type="file" name="app_logo" id="appLogoInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                                <button class="btn btn-select-file" type="button" id="selectAppLogoBtn">
                                                    SELECT FILE
                                                </button>
                                            </div>
                                            <input type="hidden" name="remove_app_logo" id="remove_app_logo" value="0">
                                            <?php 
                                                $hasAppLogo = !empty($company['app_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $company['app_logo']);
                                                $appLogoUrl = $hasAppLogo ? SITE_URL . 'uploads/company/' . $company['app_logo'] : '';
                                            ?>
                                            <div id="appLogoPreviewContainer" class="image-preview-wrapper <?= $hasAppLogo ? '' : 'd-none' ?>">
                                                <img id="appLogoPreview" src="<?= $appLogoUrl ?>" alt="App Logo Preview" class="image-preview-thumb">
                                                <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeAppLogoBtn" title="Remove image">
                                                    <i data-lucide="x" class="fs-12"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Login Logo -->
                                <div class="col-lg-6 col-md-12">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <label class="form-label fw-bold">Login Logo <small class="text-muted">(Dedicated Logo on Login Screen / PNG, WEBP)</small></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light" id="loginLogoNameDisplay" 
                                                    value="<?= !empty($company['login_logo']) ? htmlspecialchars($company['login_logo']) : '' ?>" 
                                                    placeholder="No File Selected" readonly>
                                                <input type="file" name="login_logo" id="loginLogoInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                                <button class="btn btn-select-file" type="button" id="selectLoginLogoBtn">
                                                    SELECT FILE
                                                </button>
                                            </div>
                                            <input type="hidden" name="remove_login_logo" id="remove_login_logo" value="0">
                                            <?php 
                                                $hasLoginLogo = !empty($company['login_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $company['login_logo']);
                                                $loginLogoUrl = $hasLoginLogo ? SITE_URL . 'uploads/company/' . $company['login_logo'] : '';
                                            ?>
                                            <div id="loginLogoPreviewContainer" class="image-preview-wrapper <?= $hasLoginLogo ? '' : 'd-none' ?>">
                                                <img id="loginLogoPreview" src="<?= $loginLogoUrl ?>" alt="Login Logo Preview" class="image-preview-thumb">
                                                <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeLoginLogoBtn" title="Remove image">
                                                    <i data-lucide="x" class="fs-12"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 5: General Setting -->
                        <div class="wizard-step d-none" data-step="5">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Background Dark Color</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="bg_dark_color" id="bg_dark_color" class="form-control form-control-color" 
                                                        value="<?= !empty($company['bg_dark_color']) ? htmlspecialchars($company['bg_dark_color']) : '#212529' ?>">
                                                    <input type="text" class="form-control color-text-sync" data-target="#bg_dark_color" 
                                                        value="<?= !empty($company['bg_dark_color']) ? htmlspecialchars($company['bg_dark_color']) : '#212529' ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Background Light Color</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="bg_light_color" id="bg_light_color" class="form-control form-control-color" 
                                                        value="<?= !empty($company['bg_light_color']) ? htmlspecialchars($company['bg_light_color']) : '#f8f9fa' ?>">
                                                    <input type="text" class="form-control color-text-sync" data-target="#bg_light_color" 
                                                        value="<?= !empty($company['bg_light_color']) ? htmlspecialchars($company['bg_light_color']) : '#f8f9fa' ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label d-block">Customer Panel Login</label>
                                                <div class="d-flex align-items-center gap-4 mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="customer_panel_login" id="cust_login_yes" value="1" 
                                                            <?= ((int)$company['customer_panel_login'] === 1) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="cust_login_yes">Yes</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="customer_panel_login" id="cust_login_no" value="0" 
                                                            <?= ((int)$company['customer_panel_login'] === 0) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="cust_login_no">No</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action / Step Navigation Buttons -->
                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ($isEdit): ?>
                                <button type="button" class="btn btn-secondary px-4 d-none" id="stepPrevBtn">
                                    <i data-lucide="chevron-left" class="fs-16 align-middle me-1"></i> Previous
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if ($isEdit): ?>
                                <button type="button" class="btn btn-primary px-4" id="stepNextBtn">
                                    Next <i data-lucide="chevron-right" class="fs-16 align-middle ms-1"></i>
                                </button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary px-4 <?= $isEdit ? 'd-none' : '' ?>" id="submitBtn">
                                <span id="submitText"><?= $isEdit ? 'Update' : 'Submit' ?></span>
                                <span id="submitLoader" class="spinner-border spinner-border-sm d-none"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).ready(function() {
        if (window.lucide) {
            lucide.createIcons();
        }

        // Initialize TomSelect for Company Type
        if (typeof TomSelect !== 'undefined' && $('#select-company-type').length && !$('#select-company-type')[0].tomselect) {
            new TomSelect('#select-company-type', {
                create: true,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        }

        // Preselect and load dynamic state/city
        <?php if ($isEdit && !empty($company['country_id'])): ?>
            let editCountryId = '<?= $company['country_id'] ?>';
            let editStateId = '<?= $company['state_id'] ?>';
            let editCityId = '<?= $company['city_id'] ?>';

            let countryEl = $('#select-single')[0];
            if (countryEl && countryEl.tomselect) {
                countryEl.tomselect.setValue(editCountryId);
            } else {
                $('#select-single').val(editCountryId);
            }

            loadStatesByCountry(editCountryId, editStateId, function() {
                if (editStateId) {
                    loadCitiesByState(editStateId, editCityId);
                }
            });
        <?php endif; ?>

        // Setup Quill Editors
        let quillToolbarOptions = [
            [{ 'font': [] }, { 'size': [] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'script': 'super' }, { 'script': 'sub' }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'indent': '-1' }, { 'indent': '+1' }],
            [{ 'align': [] }],
            ['link', 'clean']
        ];

        function initQuill(selector) {
            let el = document.querySelector(selector);
            if (typeof Quill !== 'undefined' && el) {
                let instance = Quill.find(el);
                if (!instance) {
                    instance = new Quill(selector, {
                        theme: 'snow',
                        modules: { toolbar: quillToolbarOptions }
                    });
                }
                return instance;
            }
            return null;
        }

        let addressQuill = initQuill('#address-editor');
        let bankQuill = initQuill('#bank-editor');
        let termsQuill = initQuill('#terms-editor');

        // Color sync helper: sync input color picker with text box
        $(document).on('input', 'input[type="color"]', function() {
            let val = $(this).val();
            let id = $(this).attr('id');
            $('.color-text-sync[data-target="#' + id + '"]').val(val);
        });

        $(document).on('input', '.color-text-sync', function() {
            let val = $(this).val();
            let target = $(this).data('target');
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                $(target).val(val);
            }
        });

        // Reusable function to bind image pickers
        function bindImageUploader(btnId, inputId, displayId, previewId, containerId, removeBtnId, removeHiddenId) {
            $('#' + btnId).on('click', function() {
                $('#' + inputId).trigger('click');
            });

            $('#' + inputId).on('change', function(e) {
                let file = e.target.files[0];
                if (file) {
                    let validExtensions = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'];
                    if (!validExtensions.includes(file.type)) {
                        showToast('Please select a valid image (JPEG, PNG, WEBP, ICO)', 'error');
                        $(this).val('');
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        showToast('Image size cannot exceed 5MB', 'error');
                        $(this).val('');
                        return;
                    }

                    $('#' + displayId).val(file.name);
                    $('#' + removeHiddenId).val('0');

                    let reader = new FileReader();
                    reader.onload = function(evt) {
                        $('#' + previewId).attr('src', evt.target.result);
                        $('#' + containerId).removeClass('d-none');
                        if (window.lucide) lucide.createIcons();
                    };
                    reader.readAsDataURL(file);
                }
            });

            $('#' + removeBtnId).on('click', function() {
                $('#' + inputId).val('');
                $('#' + displayId).val('');
                $('#' + previewId).attr('src', '');
                $('#' + containerId).addClass('d-none');
                $('#' + removeHiddenId).val('1');
            });
        }

        // Bind image uploaders
        bindImageUploader('selectHeaderImageBtn', 'headerImageInput', 'headerImageNameDisplay', 'headerPreview', 'headerPreviewContainer', 'removeHeaderBtn', 'remove_header_image');
        bindImageUploader('selectFooterImageBtn', 'footerImageInput', 'footerImageNameDisplay', 'footerPreview', 'footerPreviewContainer', 'removeFooterBtn', 'remove_footer_image');
        bindImageUploader('selectFaviconBtn', 'faviconInput', 'faviconNameDisplay', 'faviconPreview', 'faviconPreviewContainer', 'removeFaviconBtn', 'remove_favicon');
        bindImageUploader('selectAppLogoBtn', 'appLogoInput', 'appLogoNameDisplay', 'appLogoPreview', 'appLogoPreviewContainer', 'removeAppLogoBtn', 'remove_app_logo');
        bindImageUploader('selectLoginLogoBtn', 'loginLogoInput', 'loginLogoNameDisplay', 'loginLogoPreview', 'loginLogoPreviewContainer', 'removeLoginLogoBtn', 'remove_login_logo');

        // Strong password method
        $.validator.addMethod("strongPassword", function(value, element) {
            if (this.optional(element)) {
                return true;
            }
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,12}$/.test(value);
        }, "Password must contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and be between 8 and 12 characters.");

        // PAN Card Format validation (e.g. ABCDE1234F)
        $.validator.addMethod("panFormat", function(value, element) {
            if (this.optional(element) || value.trim() === '') {
                return true;
            }
            return /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i.test(value.trim());
        }, "Please enter a valid 10-digit PAN Card number (e.g. ABCDE1234F).");

        // GST Number Format validation (15-digits: e.g. 22AAAAA0000A1Z5)
        $.validator.addMethod("gstFormat", function(value, element) {
            if (this.optional(element) || value.trim() === '') {
                return true;
            }
            return /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i.test(value.trim());
        }, "Please enter a valid 15-character GST number (e.g. 22AAAAA0000A1Z5).");

        let currentCompanyId = '<?= (int)$company['id'] ?>';

        let $validator = $("#companyForm").validate({
            rules: {
                company_type_id: {
                    required: true
                },
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                person_name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                email: {
                    required: true,
                    email: true,
                    remote: {
                        url: SITE_URL + "admin/setting/company/store.php",
                        type: "GET",
                        data: {
                            action: 'check_unique',
                            field: 'email',
                            id: currentCompanyId,
                            value: function() {
                                return $('#email').val().trim();
                            }
                        }
                    }
                },
                password: {
                    required: <?= $isEdit ? 'false' : 'true' ?>,
                    minlength: 8,
                    maxlength: 12,
                    strongPassword: true,
                },
                mobile_no: {
                    required: true,
                    minlength: 10,
                    maxlength: 10,
                    number: true,
                },
                pan_card: {
                    panFormat: true
                },
                gst: {
                    gstFormat: true,
                    remote: {
                        url: SITE_URL + "admin/setting/company/store.php",
                        type: "GET",
                        data: {
                            action: 'check_unique',
                            field: 'gst',
                            id: currentCompanyId,
                            value: function() {
                                return $('#gst').val().trim();
                            }
                        }
                    }
                },
                country_id: {
                    required: true,
                },
                state_id: {
                    required: true,
                },
                city_id: {
                    required: true,
                },
                plan_id: {
                    required: true,
                }
            },
            messages: {
                company_type_id: {
                    required: "Please select company type"
                },
                name: {
                    required: "Please enter company name",
                    minlength: "Company name must be at least 2 characters",
                    maxlength: "Company name cannot exceed 100 characters"
                },
                person_name: {
                    required: "Please enter person name",
                    minlength: "Person name must be at least 2 characters",
                    maxlength: "Person name cannot exceed 100 characters"
                },
                email: {
                    required: "Please enter email",
                    email: "Please enter valid email",
                    remote: "Email is already registered. Please use another."
                },
                password: {
                    required: "Please enter password",
                    minlength: "Password must be at least 8 characters",
                    maxlength: "Password cannot exceed 12 characters",
                    strongPassword: "Password must contain at least 1 uppercase letter, 1 lowercase letter, and 1 number",
                },
                mobile_no: {
                    required: "Please enter mobile number",
                    minlength: "Mobile number must be at least 10 digits",
                    maxlength: "Mobile number cannot exceed 10 digits",
                    number: "Please enter valid mobile number",
                },
                pan_card: {
                    panFormat: "Please enter a valid 10-digit PAN Card number (e.g. ABCDE1234F)"
                },
                gst: {
                    gstFormat: "Please enter a valid 15-character GST number (e.g. 22AAAAA0000A1Z5)",
                    remote: "GST number is already registered. Please enter a unique GST."
                },
                country_id: {
                    required: "Please Select Country",
                },
                state_id: {
                    required: "Please Select State",
                },
                city_id: {
                    required: "Please Select City",
                },
                plan_id: {
                    required: "Please Select Plan",
                },
            },
            errorElement: "span",
            errorClass: "text-danger",
            ignore: ':hidden:not(#select-company-type, #select-single, #select-state, #select-city, #select-plan, [data-step="1"] input, [data-step="1"] select)',
            errorPlacement: function(error, element) {
                if (element.hasClass('form-select') && element[0].tomselect) {
                    error.insertAfter($(element[0].tomselect.wrapper));
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                // Populate Quill HTML into hidden inputs
                if (addressQuill) {
                    let html = addressQuill.root.innerHTML;
                    if (addressQuill.getText().trim().length === 0 && !html.includes('<img')) html = '';
                    $('#address').val(html);
                }
                if (bankQuill) {
                    let html = bankQuill.root.innerHTML;
                    if (bankQuill.getText().trim().length === 0 && !html.includes('<img')) html = '';
                    $('#bank_details').val(html);
                }
                if (termsQuill) {
                    let html = termsQuill.root.innerHTML;
                    if (termsQuill.getText().trim().length === 0 && !html.includes('<img')) html = '';
                    $('#terms_conditions').val(html);
                }

                let formData = new FormData(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");
                let originalText = "<?= $isEdit ? 'Update' : 'Submit' ?>";

                $.ajax({
                    url: SITE_URL + "admin/setting/company/store.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true);
                        $text.text("Saving...");
                        $loader.removeClass("d-none");
                    },
                    success: function(response) {
                        if (response.status === true) {
                            showToast(response.message, "success");
                            setTimeout(function() {
                                window.location.href = '<?= SITE_URL ?>company';
                            }, 1000);
                        } else {
                            showToast(response.message, "error");
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        showToast("Something went wrong. Please try again.", "error");
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                        $text.text(originalText);
                        $loader.addClass("d-none");
                    }
                });
                return false;
            }
        });

        // Step Wizard Navigation Logic (Edit Mode)
        <?php if ($isEdit): ?>
            let currentStep = 1;
            const totalSteps = 5;

            function goToStep(stepNumber) {
                if (stepNumber < 1 || stepNumber > totalSteps) return;

                // If moving ahead from step 1, validate step 1 inputs
                if (stepNumber > 1 && currentStep === 1) {
                    let step1Valid = true;
                    $('.wizard-step[data-step="1"]').find('input, select, textarea').each(function() {
                        if ($(this).is(':visible') || $(this).hasClass('form-select')) {
                            if (!$validator.element(this) && $validator.element(this) !== undefined) {
                                step1Valid = false;
                            }
                        }
                    });
                    if (!step1Valid) {
                        return;
                    }
                }

                currentStep = stepNumber;

                // Update active/d-none for wizard step containers
                $('.wizard-step').each(function() {
                    let step = parseInt($(this).data('step'));
                    if (step === currentStep) {
                        $(this).removeClass('d-none').addClass('active');
                    } else {
                        $(this).addClass('d-none').removeClass('active');
                    }
                });

                // Update step indicators
                $('.wizard-step-item').each(function() {
                    let step = parseInt($(this).data('step'));
                    $(this).removeClass('active completed');
                    if (step === currentStep) {
                        $(this).addClass('active');
                    } else if (step < currentStep) {
                        $(this).addClass('completed');
                    }
                });

                // Update buttons
                if (currentStep === 1) {
                    $('#stepPrevBtn').addClass('d-none');
                } else {
                    $('#stepPrevBtn').removeClass('d-none');
                }

                if (currentStep === totalSteps) {
                    $('#stepNextBtn').addClass('d-none');
                    $('#submitBtn').removeClass('d-none');
                } else {
                    $('#stepNextBtn').removeClass('d-none');
                    $('#submitBtn').addClass('d-none');
                }

                if (window.lucide) {
                    lucide.createIcons();
                }

                // Scroll to top of card smoothly
                $('html, body').animate({
                    scrollTop: $(".card").offset().top - 80
                }, 200);
            }

            $('#stepNextBtn').on('click', function() {
                goToStep(currentStep + 1);
            });

            $('#stepPrevBtn').on('click', function() {
                goToStep(currentStep - 1);
            });

            $('.wizard-step-item').on('click', function() {
                let targetStep = parseInt($(this).data('step'));
                goToStep(targetStep);
            });
        <?php endif; ?>

        // =====================================================
        // PLAN PAYMENT & INSTALLMENT SCHEDULE LOGIC
        // =====================================================
        function getSelectedPlanPrice() {
            let $opt = $('#select-plan option:selected');
            let price = parseFloat($opt.data('price')) || 0;
            return price;
        }

        var isEditMode = <?= $isEdit ? 'true' : 'false' ?>;

        function updatePlanPriceDisplay() {
            let price = getSelectedPlanPrice();
            let planId = $('#select-plan').val();

            // Edit mode ma payment container show nahi
            if (isEditMode) return;

            // If no plan selected OR plan price is 0, completely hide payment & installment details container
            if (planId && parseInt(planId) > 0 && price > 0) {
                $('#planPaymentContainer').removeClass('d-none');
                $('#displayPlanPrice').text('₹' + price.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#inputTotalPlanPrice').val(price);

                $('#installmentRadioWrapper').removeClass('d-none');
                generateInstallmentSchedule();
            } else {
                // Hide entire payment container if price is 0 or no plan selected
                $('#planPaymentContainer').addClass('d-none');
                $('#inputTotalPlanPrice').val(price || 0);
                $('#payTypeFull').prop('checked', true);
                $('.payment-full-fields').addClass('d-none');
                $('#installmentConfigBox').addClass('d-none');
            }
        }

        // Toggle Full vs Installment Fields
        $('input[name="payment_type"]').on('change', function() {
            let type = $(this).val();
            if (type === 'installment') {
                $('.payment-full-fields').addClass('d-none');
                $('#installmentConfigBox').removeClass('d-none');
                generateInstallmentSchedule();
            } else {
                $('.payment-full-fields').removeClass('d-none');
                $('#installmentConfigBox').addClass('d-none');
            }
            if (window.lucide) lucide.createIcons();
        });

        // Generate Installment Schedule Table Rows
        function generateInstallmentSchedule() {
            let totalPrice = getSelectedPlanPrice();
            let count = parseInt($('#installmentCount').val()) || 4;
            let freq = $('#installmentFrequency').val();
            let firstDateStr = $('#firstDueDate').val() || new Date().toISOString().split('T')[0];

            let baseDate = new Date(firstDateStr);
            let perInstallment = Math.floor((totalPrice / count) * 100) / 100;
            let remainder = Math.round((totalPrice - (perInstallment * count)) * 100) / 100;

            let html = '';
            for (let i = 1; i <= count; i++) {
                let dueDate = new Date(baseDate);
                if (i > 1) {
                    let monthsPerInstallment = freq === 'quarterly' ? 3 : (freq === 'half_yearly' ? 6 : 1);
                    let monthOffset = baseDate.getMonth() + ((i - 1) * monthsPerInstallment);
                    let targetYear = baseDate.getFullYear() + Math.floor(monthOffset / 12);
                    let targetMonth = monthOffset % 12;
                    let lastDayOfTargetMonth = new Date(targetYear, targetMonth + 1, 0).getDate();
                    dueDate = new Date(targetYear, targetMonth, Math.min(baseDate.getDate(), lastDayOfTargetMonth));
                }

                // Format YYYY-MM-DD
                let yyyy = dueDate.getFullYear();
                let mm = String(dueDate.getMonth() + 1).padStart(2, '0');
                let dd = String(dueDate.getDate()).padStart(2, '0');
                let formattedDate = `${yyyy}-${mm}-${dd}`;

                // Add remainder to the first installment
                let amount = (i === 1) ? (perInstallment + remainder).toFixed(2) : perInstallment.toFixed(2);

                html += `
                    <tr class="installment-row" data-index="${i}">
                        <td class="text-center fw-bold fs-12">${i}</td>
                        <td>
                            <input type="date" name="installments[${i}][due_date]" class="form-control form-control-sm inst-due-date" value="${formattedDate}" required>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="installments[${i}][amount]" class="form-control inst-amount" value="${amount}" min="1" required>
                            </div>
                        </td>
                        <td>
                            <select name="installments[${i}][status]" class="form-select form-select-sm inst-status">
                                <option value="pending" ${i > 1 ? 'selected' : ''}>Pending</option>
                                <option value="paid" ${i === 1 ? 'selected' : ''}>Paid (Down Payment)</option>
                            </select>
                        </td>
                        <td>
                        <input type="text" name="installments[${i}][payment_method]" class="form-control inst-method" value="Cash" readonly>
                        </td>
                    </tr>
                `;
            }

            $('#installmentScheduleBody').html(html);
            checkInstallmentTotals();
            if (window.lucide) lucide.createIcons();
        }

        function checkInstallmentTotals() {
            let totalExpected = getSelectedPlanPrice();
            let sum = 0;
            $('.inst-amount').each(function() {
                sum += parseFloat($(this).val()) || 0;
            });
            sum = Math.round(sum * 100) / 100;

            $('#installmentTotalSum').text('₹' + sum.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            let diff = Math.abs(sum - totalExpected);
            if (diff < 0.05) {
                $('#installmentTotalMatchBadge').removeClass('bg-danger bg-warning').addClass('bg-success').text('Matched');
            } else if (sum < totalExpected) {
                $('#installmentTotalMatchBadge').removeClass('bg-success').addClass('bg-warning').text('Under by ₹' + (totalExpected - sum).toFixed(2));
            } else {
                $('#installmentTotalMatchBadge').removeClass('bg-success').addClass('bg-danger').text('Exceeds by ₹' + (sum - totalExpected).toFixed(2));
            }
        }

        // Events for recalculating installments
        $(document).on('input', '.inst-amount', function() {
            checkInstallmentTotals();
        });

        $('#btnRegenerateSchedule, #installmentCount, #installmentFrequency, #firstDueDate').on('change click', function(e) {
            if (e.type === 'click' && $(this).attr('id') !== 'btnRegenerateSchedule') return;
            generateInstallmentSchedule();
        });

        // Plan change handler (native and TomSelect)
        $(document).on('change', '#select-plan', function() {
            updatePlanPriceDisplay();
        });

        // Hook into TomSelect instance if available
        let planElem = document.getElementById('select-plan');
        if (planElem && planElem.tomselect) {
            planElem.tomselect.on('change', function() {
                updatePlanPriceDisplay();
            });
        }

        // Check on initial page load
        setTimeout(function() {
            let pElem = document.getElementById('select-plan');
            if (pElem && pElem.tomselect) {
                pElem.tomselect.on('change', function() {
                    updatePlanPriceDisplay();
                });
            }
            updatePlanPriceDisplay();

            <?php if (!empty($existingPayment)): ?>
            // ---- Edit mode: existing payment pre-load ----
            var existPayType  = <?= json_encode($existingPayment['payment_type'] ?? 'full') ?>;
            var existInsts    = <?= json_encode(array_values($existingInstallments)) ?>;

            // 1. Pre-check correct radio
            if (existPayType === 'installment') {
                $('#payTypeInstallment').prop('checked', true);
                $('#payTypeFull').prop('checked', false);
                $('.payment-full-fields').addClass('d-none');
                $('#installmentConfigBox').removeClass('d-none');

                // 2. Load existing installment rows
                if (existInsts && existInsts.length > 0) {
                    var html = '';
                    $.each(existInsts, function(idx, inst) {
                        var i = parseInt(inst.installment_no);
                        var isPaid = (inst.status === 'paid');
                        html += '<tr class="installment-row" data-index="' + i + '">';
                        html += '<td class="text-center fw-bold fs-12">' + i + '</td>';
                        if (isPaid) {
                            // Paid row: show label only, no editable inputs
                            html += '<td><span class="text-muted">' + (inst.due_date || '-') + '</span><input type="hidden" name="installments[' + i + '][due_date]" value="' + (inst.due_date || '') + '"></td>';
                            html += '<td>';
                            html += '  <div class="input-group input-group-sm"><span class="input-group-text">₹</span>';
                            html += '  <input type="number" step="0.01" name="installments[' + i + '][amount]" class="form-control inst-amount" value="' + parseFloat(inst.amount).toFixed(2) + '" readonly></div>';
                            html += '</td>';
                            html += '<td><span class="badge bg-success">Paid</span><input type="hidden" name="installments[' + i + '][status]" value="paid"></td>';
                            html += '<td><span class="text-muted">' + (inst.payment_method || '-') + '</span><input type="hidden" name="installments[' + i + '][payment_method]" value="' + (inst.payment_method || 'Cash') + '"></td>';
                        } else {
                            // Pending row: editable
                            html += '<td><input type="date" name="installments[' + i + '][due_date]" class="form-control form-control-sm inst-due-date" value="' + (inst.due_date || '') + '" required></td>';
                            html += '<td><div class="input-group input-group-sm"><span class="input-group-text">₹</span><input type="number" step="0.01" name="installments[' + i + '][amount]" class="form-control inst-amount" value="' + parseFloat(inst.amount).toFixed(2) + '" min="1" required></div></td>';
                            html += '<td><select name="installments[' + i + '][status]" class="form-select form-select-sm inst-status"><option value="pending" selected>Pending</option><option value="paid">Paid</option></select></td>';
                            html += '<td><select name="installments[' + i + '][payment_method]" class="form-select form-select-sm inst-method">';
                            html += '<option value="Cash" ' + (inst.payment_method==='Cash'?'selected':'') + '>Cash</option>';
                            html += '<option value="UPI" '  + (inst.payment_method==='UPI'?'selected':'')  + '>UPI</option>';
                            html += '<option value="Bank Transfer" ' + (inst.payment_method==='Bank Transfer'?'selected':'') + '>Bank Transfer</option>';
                            html += '<option value="Cheque" ' + (inst.payment_method==='Cheque'?'selected':'') + '>Cheque</option>';
                            html += '</select></td>';
                        }
                        html += '</tr>';
                    });
                    $('#installmentScheduleBody').html(html);
                    checkInstallmentTotals();
                    if (window.lucide) lucide.createIcons();
                }
            } else {
                // Full payment - radio already default full, nothing extra needed
                $('#payTypeFull').prop('checked', true);
            }
            <?php endif; ?>

        }, 300);
    });
</script>

</body>
</html>