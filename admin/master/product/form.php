<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$pageNm = 'Product';
$tbl = 'product';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isEdit = $id > 0;

$product = [
    'id'               => 0,
    'company_id'       => '',
    'type'             => '',
    'category_id'      => '',
    'sub_category_id'  => '',
    'name'             => '',
    'tax_id'           => '',
    'sales_unit_id'    => '',
    'customer_unit_id' => '',
    'display_unit'     => '',
    'hsn_code'         => '',
    'image'            => '',
    'description'      => '',
];

if ($isEdit) {
    $productData = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$productData) {
        die($pageNm . ' not found.');
    }
    $product = $productData;
}

include BASE_PATH . '/include/header.php';

$requiredAction = $isEdit ? 'updates' : 'adds';
checkPermissionOrDeny('product', $requiredAction);

$breadcrumbType = 'form';
$parentUrl = SITE_URL . 'product';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

$selectedCompanyId = $isSuperadmin ? (int)($product['company_id'] ?: 0) : $sessionCompanyId;

// Static Array for Select Type
$types = [
    '1'    => 'With Variant',
    '2' => 'Without Variant'
];

// Static Array for Display Unit
$displayUnits = [
    'NOS' => 'NOS'
];

// Companies for Superadmin
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}

// Master Dropdowns scoped by company
$companyWhere = ($selectedCompanyId > 0) ? "WHERE company_id = $selectedCompanyId AND status = 1" : ($isSuperadmin ? "WHERE status = 1" : "WHERE company_id = $sessionCompanyId AND status = 1");

$categories = db_rows("SELECT id, name FROM category $companyWhere ORDER BY name ASC");
$taxes      = db_rows("SELECT id, name, value FROM tax $companyWhere ORDER BY name ASC");
$units      = db_rows("SELECT id, name FROM unit $companyWhere ORDER BY name ASC");

$subCategories = [];
if (!empty($product['category_id'])) {
    $catId = (int)$product['category_id'];
    $subCategories = db_rows("SELECT id, name FROM sub_category WHERE category_id = $catId AND status = 1 ORDER BY name ASC");
}
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" id="id" value="<?= (int)$product['id'] ?>">
                    <?php endif; ?>
                    <input type="hidden" name="remove_image" id="remove_image" value="0">

                    <div class="row g-3">
                        <?php if ($isSuperadmin): ?>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Company</label>
                                <select name="company_id" id="select-company" class="form-select company_id">
                                    <option value="">Select a Company</option>
                                    <?php foreach ($companies as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ((int)$product['company_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Select Type </label>
                                <select name="type" id="select-type" class="form-select">
                                    <option value="">Select Type</option>
                                    <?php foreach ($types as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($product['type'] == $key) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Category </label>
                                <select name="category_id" id="select-category" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ((int)$product['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Sub Category </label>
                                <select name="sub_category_id" id="select-sub-category" class="form-select">
                                    <option value="">Select Sub Category</option>
                                    <?php foreach ($subCategories as $sub): ?>
                                        <option value="<?= $sub['id'] ?>" <?= ((int)$product['sub_category_id'] === (int)$sub['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sub['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Name </label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter Product Name" value="<?= htmlspecialchars($product['name']) ?>">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">GST </label>
                                <select name="tax_id" id="select-tax" class="form-select">
                                    <option value="">Select GST</option>
                                    <?php foreach ($taxes as $tax): ?>
                                        <?php 
                                            $valText = (float)$tax['value'] == (int)$tax['value'] ? (int)$tax['value'] : (float)$tax['value'];
                                            $label = htmlspecialchars($tax['name']) . ' (' . $valText . '%)';
                                        ?>
                                        <option value="<?= $tax['id'] ?>" <?= ((int)$product['tax_id'] === (int)$tax['id']) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Sales Order Unit</label>
                                <select name="sales_unit_id" id="select-sales-unit" class="form-select">
                                    <option value="">Select Sales Order Unit</option>
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= ((int)$product['sales_unit_id'] === (int)$u['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Customer Order Unit</label>
                                <select name="customer_unit_id" id="select-customer-unit" class="form-select">
                                    <option value="">Select Customer Order Unit</option>
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= ((int)$product['customer_unit_id'] === (int)$u['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Dispaly Unit</label>
                                <select name="display_unit" id="select-display-unit" class="form-select">
                                    <option value="">Select Unit</option>
                                    <?php foreach ($displayUnits as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= ($product['display_unit'] === $val) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lbl) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">HSN Code</label>
                                <input type="text" name="hsn_code" id="hsn_code" class="form-control" placeholder="Enter HSN Code" value="<?= htmlspecialchars($product['hsn_code']) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label">Select Image</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light" id="imageFileNameDisplay" 
                                        value="<?= !empty($product['image']) ? htmlspecialchars($product['image']) : '' ?>" 
                                        placeholder="No File Selected" readonly>
                                    <input type="file" name="image" id="productImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                    <button class="btn btn-select-file" type="button" id="selectFileBtn">
                                        SELECT FILE
                                    </button>
                                </div>
                                <?php 
                                    $hasExistingImage = !empty($product['image']) && file_exists(BASE_PATH . '/uploads/product/' . $product['image']);
                                    $existingImageUrl = $hasExistingImage ? SITE_URL . 'uploads/product/' . $product['image'] : '';
                                ?>
                                <div id="imagePreviewContainer" class="image-preview-wrapper <?= $hasExistingImage ? '' : 'd-none' ?>">
                                    <img id="imagePreview" src="<?= $existingImageUrl ?>" alt="Preview" class="image-preview-thumb">
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeImageBtn" title="Remove image">
                                        <i data-lucide="x" class="fs-12"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-2">
                                <label class="form-label">Description</label>
                                <div id="snow-editor" class="editor-container">
                                    <?= $product['description'] ?>
                                </div>
                                <input type="hidden" name="description" id="description" value="<?= htmlspecialchars($product['description']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit"
                            class="btn btn-primary"
                            id="submitBtn">
                            <span id="submitText"> <?= $isEdit ? 'Update' : 'Submit' ?></span>
                            <span id="submitLoader"
                                class="spinner-border spinner-border-sm d-none">
                            </span>
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
        let isSuperadmin = <?= $isSuperadmin ? 'true' : 'false' ?>;

        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }

        // Initialize Quill Editor
        let quill = null;
        if (typeof Quill !== 'undefined' && $('#snow-editor').length) {
            quill = Quill.find(document.getElementById('snow-editor'));
            if (!quill) {
                quill = new Quill('#snow-editor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'font': [] }, { 'size': [] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'script': 'super' }, { 'script': 'sub' }],
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'indent': '-1' }, { 'indent': '+1' }],
                            [{ 'direction': 'rtl' }],
                            [{ 'align': [] }],
                            ['link', 'image', 'video'],
                            ['clean']
                        ]
                    }
                });
            }
        }

        // Initialize TomSelect on dropdowns
        let tsInstances = {};
        function initTomSelect(selector) {
            let el = $(selector)[0];
            if (el && typeof TomSelect !== 'undefined' && !el.tomselect) {
                let ph = el.getAttribute('placeholder') || $(el).find('option[value=""]').first().text() || undefined;
                tsInstances[selector] = new TomSelect(el, {
                    create: false,
                    allowEmptyOption: false,
                    placeholder: ph
                });
            }
        }

        if (isSuperadmin) initTomSelect('#select-company');
        initTomSelect('#select-type');
        initTomSelect('#select-category');
        initTomSelect('#select-sub-category');
        initTomSelect('#select-tax');
        initTomSelect('#select-sales-unit');
        initTomSelect('#select-customer-unit');
        initTomSelect('#select-display-unit');

        // File upload custom trigger
        $('#selectFileBtn').on('click', function() {
            $('#productImageInput').trigger('click');
        });

        $('#productImageInput').on('change', function(e) {
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

                $('#imageFileNameDisplay').val(file.name);
                $('#remove_image').val('0');

                let reader = new FileReader();
                reader.onload = function(evt) {
                    $('#imagePreview').attr('src', evt.target.result);
                    $('#imagePreviewContainer').removeClass('d-none');
                    if (window.lucide) lucide.createIcons();
                };
                reader.readAsDataURL(file);
            }
        });

        $('#removeImageBtn').on('click', function() {
            $('#productImageInput').val('');
            $('#imageFileNameDisplay').val('');
            $('#imagePreview').attr('src', '');
            $('#imagePreviewContainer').addClass('d-none');
            $('#remove_image').val('1');
        });

        // Dynamic Subcategories loading based on Category selection
        $('#select-category').on('change', function() {
            let categoryId = $(this).val();
            let subSelect = $('#select-sub-category')[0];

            if (subSelect && subSelect.tomselect) {
                let ts = subSelect.tomselect;
                ts.settings.placeholder = 'Select Sub Category';
                ts.clear(true);
                ts.clearOptions();
                ts.inputState();
                ts.refreshOptions(false);
            } else {
                $('#select-sub-category').html('<option value="">Select Sub Category</option>');
            }

            if (!categoryId) return;

            $.ajax({
                url: SITE_URL + 'admin/master/product/store.php',
                type: 'GET',
                data: {
                    action: 'get_subcategories',
                    category_id: categoryId
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status && res.data) {
                        if (subSelect && subSelect.tomselect) {
                            let ts = subSelect.tomselect;
                            ts.clear(true);
                            ts.clearOptions();
                            $.each(res.data, function(idx, item) {
                                ts.addOption({ value: String(item.id), text: item.name });
                            });
                            ts.settings.placeholder = 'Select Sub Category';
                            ts.inputState();
                            ts.refreshOptions(false);
                        } else {
                            let options = '<option value="">Select Sub Category</option>';
                            $.each(res.data, function(idx, item) {
                                options += '<option value="' + item.id + '">' + item.name + '</option>';
                            });
                            $('#select-sub-category').html(options);
                        }
                    }
                }
            });
        });

        // Helper function to update TomSelect dropdown with placeholder
        function updateDropdownOptions(el, placeholderText, items, labelFormatter) {
            if (el && el.tomselect) {
                let ts = el.tomselect;
                ts.settings.placeholder = placeholderText;
                ts.clear(true);
                ts.clearOptions();
                if (items && items.length) {
                    $.each(items, function(i, item) {
                        let text = labelFormatter ? labelFormatter(item) : item.name;
                        ts.addOption({ value: String(item.id), text: text });
                    });
                }
                ts.inputState();
                ts.refreshOptions(false);
            } else {
                let options = '<option value="">' + placeholderText + '</option>';
                if (items && items.length) {
                    $.each(items, function(i, item) {
                        let text = labelFormatter ? labelFormatter(item) : item.name;
                        options += '<option value="' + item.id + '">' + text + '</option>';
                    });
                }
                $(el).html(options);
            }
        }

        // When Superadmin changes company, refresh categories, taxes, and units
        if (isSuperadmin) {
            $('#select-company').on('change', function() {
                let compId = $(this).val();
                
                let catEl = $('#select-category')[0];
                let taxEl = $('#select-tax')[0];
                let salesEl = $('#select-sales-unit')[0];
                let custEl = $('#select-customer-unit')[0];
                let subEl = $('#select-sub-category')[0];

                updateDropdownOptions(catEl, 'Select Category', []);
                updateDropdownOptions(subEl, 'Select Sub Category', []);
                updateDropdownOptions(taxEl, 'Select GST', []);
                updateDropdownOptions(salesEl, 'Select Sales Order Unit', []);
                updateDropdownOptions(custEl, 'Select Customer Order Unit', []);

                if (!compId) return;

                $.ajax({
                    url: SITE_URL + 'admin/master/product/store.php',
                    type: 'GET',
                    data: {
                        action: 'get_categories',
                        company_id: compId
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status && res.data) {
                            // Update Categories
                            updateDropdownOptions(catEl, 'Select Category', res.data.categories);

                            // Update Taxes
                            updateDropdownOptions(taxEl, 'Select GST', res.data.taxes, function(t) {
                                let v = parseFloat(t.value);
                                return t.name + ' (' + v + '%)';
                            });

                            // Update Sales Order Unit
                            updateDropdownOptions(salesEl, 'Select Sales Order Unit', res.data.units);

                            // Update Customer Order Unit
                            updateDropdownOptions(custEl, 'Select Customer Order Unit', res.data.units);
                        }
                    }
                });
            });
        }

        // jQuery Validation Rules
        let validationRules = {
            type: { required: true },
            category_id: { required: true },
            sub_category_id: { required: true },
            name: { required: true, minlength: 2, maxlength: 255 },
            tax_id: { required: true }
        };

        let validationMessages = {
            type: { required: "Please select type" },
            category_id: { required: "Please select category" },
            sub_category_id: { required: "Please select sub category" },
            name: { 
                required: "Please enter product name", 
                minlength: "Product name must be at least 2 characters",
                maxlength: "Product name cannot exceed 255 characters"
            },
            tax_id: { required: "Please select GST" }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select company" };
        }

        $("#productForm").validate({
            rules: validationRules,
            messages: validationMessages,
            errorElement: "span",
            errorClass: "text-danger",
            ignore: ':hidden:not(#description, #productImageInput)',
            errorPlacement: function(error, element) {
                if (element.hasClass('form-select') && element[0].tomselect) {
                    error.insertAfter($(element[0].tomselect.wrapper));
                } else if (element.attr('name') === 'image') {
                    error.insertAfter(element.closest('.input-group'));
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                // Populate description from Quill
                if (quill) {
                    let html = quill.root.innerHTML;
                    if (quill.getText().trim().length === 0 && !html.includes('<img')) {
                        html = '';
                    }
                    $('#description').val(html);
                }

                let formData = new FormData(form);
                let $btns = $("#submitBtn");
                let $loaders = $("#submitLoader, .submit-loader");
                let $texts = $("#submitText, .submitText");
                let originalText = "<?= $isEdit ? 'Update' : 'Submit' ?>";

                $.ajax({
                    url: SITE_URL + "admin/master/product/store.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",
                    beforeSend: function() {
                        $btns.prop("disabled", true);
                        $texts.text("Saving...");
                        $loaders.removeClass("d-none");
                    },
                    success: function(response) {
                        if (response.status === true) {
                            showToast(response.message, "success");
                            setTimeout(function() {
                                window.location.href = SITE_URL + 'product';
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
                        $btns.prop("disabled", false);
                        $texts.text(originalText);
                        $loaders.addClass("d-none");
                    }
                });
                return false;
            }
        });
    });
</script>
</body>
</html>
