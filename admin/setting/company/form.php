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
                                            <option value="<?= $p['id'] ?>" <?= ((int)$p['id'] === (int)$company['plan_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['name']) ?>
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

                        <!-- Row 3: Indiamart API Key, Country, State, City -->
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
    });
</script>
</body>
</html>