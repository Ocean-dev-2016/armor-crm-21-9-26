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
    $company = $companyData;
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
    /* 5-column layout for Edit screen on desktop */
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
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="companyForm" enctype="multipart/form-data">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= (int)$company['id'] ?>" id="id">
                    <?php endif; ?>
                    <?php 
                        // Row 1: If Add => 6 items (col-lg-2). If Edit => 5 items (col-lg-fifth)
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

                    <!-- Row 5: Header Image & Footer Image -->
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
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                            <span id="submitText"><?= $isEdit ? 'Update' : 'Submit' ?></span>
                            <span id="submitLoader" class="spinner-border spinner-border-sm d-none"></span>
                        </button>
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
                create: false,
                allowEmptyOption: true,
                placeholder: 'Select Company Type'
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

        // File upload custom trigger - Header Image
        $('#selectHeaderImageBtn').on('click', function() {
            $('#headerImageInput').trigger('click');
        });

        $('#headerImageInput').on('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let validExtensions = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!validExtensions.includes(file.type)) {
                    showToast('Please select a valid image (JPEG, PNG, WEBP)', 'error');
                    $(this).val('');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Image size cannot exceed 5MB', 'error');
                    $(this).val('');
                    return;
                }

                $('#headerImageNameDisplay').val(file.name);
                $('#remove_header_image').val('0');

                let reader = new FileReader();
                reader.onload = function(evt) {
                    $('#headerPreview').attr('src', evt.target.result);
                    $('#headerPreviewContainer').removeClass('d-none');
                    if (window.lucide) lucide.createIcons();
                };
                reader.readAsDataURL(file);
            }
        });

        $('#removeHeaderBtn').on('click', function() {
            $('#headerImageInput').val('');
            $('#headerImageNameDisplay').val('');
            $('#headerPreview').attr('src', '');
            $('#headerPreviewContainer').addClass('d-none');
            $('#remove_header_image').val('1');
        });

        // File upload custom trigger - Footer Image
        $('#selectFooterImageBtn').on('click', function() {
            $('#footerImageInput').trigger('click');
        });

        $('#footerImageInput').on('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let validExtensions = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!validExtensions.includes(file.type)) {
                    showToast('Please select a valid image (JPEG, PNG, WEBP)', 'error');
                    $(this).val('');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Image size cannot exceed 5MB', 'error');
                    $(this).val('');
                    return;
                }

                $('#footerImageNameDisplay').val(file.name);
                $('#remove_footer_image').val('0');

                let reader = new FileReader();
                reader.onload = function(evt) {
                    $('#footerPreview').attr('src', evt.target.result);
                    $('#footerPreviewContainer').removeClass('d-none');
                    if (window.lucide) lucide.createIcons();
                };
                reader.readAsDataURL(file);
            }
        });

        $('#removeFooterBtn').on('click', function() {
            $('#footerImageInput').val('');
            $('#footerImageNameDisplay').val('');
            $('#footerPreview').attr('src', '');
            $('#footerPreviewContainer').addClass('d-none');
            $('#remove_footer_image').val('1');
        });

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

        $("#companyForm").validate({
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
            ignore: ':hidden:not(#select-company-type, #select-single, #select-state, #select-city, #select-plan)',
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
    });
</script>
</body>
</html>