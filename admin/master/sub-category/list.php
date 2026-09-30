<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('sub-category', 'views');

$pageNm = 'Sub Category';
$tbl = 'sub_category';
$fields = 'name:Name,category_id:Category,image:Image';
$module = 'sub-category'; 
$showAdd = hasPermission('sub-category', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}

// For company login, prefetch categories of this company
$companyCategories = [];
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $companyCategories = db_rows("SELECT id, name FROM category WHERE company_id = $sessionCompanyId AND status = 1 ORDER BY name ASC");
}
?>

<!-- Sub Category Modal -->
<?php
$modalId = 'SubCategoryModal';
$formId = 'subCategoryForm';
$includeCompanySelect = true;
$formEnctype = 'multipart/form-data';
$hasImageUpload = true;
$hideNameField = true;
$modalBodyContent = function() use ($pageNm, $isSuperadmin, $companyCategories) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" id="select-category" class="form-select category_id">
                <option value="">Select Category</option>
                <?php if (!$isSuperadmin): ?>
                    <?php foreach ($companyCategories as $cat): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
        </div>
    </div>

    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Select Image</label>
            <div class="input-group">
                <input type="text" class="form-control bg-light" id="imageFileNameDisplay" placeholder="No File Selected" readonly>
                <input type="file" name="image" id="subCategoryImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                <button class="btn btn-select-file" type="button" id="selectFileBtn">
                    SELECT FILE
                </button>
            </div>
            <div id="imagePreviewContainer" class="image-preview-wrapper d-none">
                <img id="imagePreview" src="" alt="Preview" class="image-preview-thumb">
                <button type="button" class="btn btn-sm btn-danger btn-remove-preview" id="removeImageBtn" title="Remove selected image">
                    <i data-lucide="x" class="fs-12"></i>
                </button>
            </div>
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/master/sub-category/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, [
    'Category Name',
    'Name',
    'Image',
    'Status',
    'Action'
]);
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).ready(function() {

        let isSuperadmin = <?= $isSuperadmin ? 'true' : 'false' ?>;
        let sessionCompanyId = '<?= $sessionCompanyId > 0 ? $sessionCompanyId : '' ?>';

        function getTomSelect(selector, placeholder = '') {
            let el = $(selector)[0];
            if (!el) return null;
            if (el.tomselect) return el.tomselect;
            return new TomSelect(el, {
                create: false,
                allowEmptyOption: true,
                sortField: [{ field: '$order' }]
            });
        }

        let tsCompany = isSuperadmin ? getTomSelect('#select-company', 'Select a Company') : null;
        let tsCategory = getTomSelect('#select-category', 'Select Category');

        function setSelectVal(selector, val) {
            let el = $(selector)[0];
            if (el && el.tomselect) {
                el.tomselect.setValue(val ? String(val) : '');
            } else {
                $(selector).val(val ? String(val) : '').trigger('change');
            }
        }

        function loadCategoriesByCompany(companyId, selectedCatId = '') {
            let catTs = getTomSelect('#select-category');
            if (catTs) {
                catTs.clear();
                catTs.clearOptions();
                catTs.addOption({ value: '', text: 'Select Category', $order: 1 });
                catTs.setValue('');
            } else {
                $('#select-category').html('<option value="">Select Category</option>');
            }

            if (!companyId) return;

            $.ajax({
                url: SITE_URL + 'admin/master/sub-category/store.php',
                type: 'GET',
                data: {
                    action: 'get_categories',
                    company_id: companyId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true) {
                        if (catTs) {
                            catTs.clear();
                            catTs.clearOptions();
                            catTs.addOption({ value: '', text: 'Select Category', $order: 1 });
                            let orderIdx = 2;
                            $.each(response.data, function(key, val) {
                                catTs.addOption({
                                    value: String(val.id),
                                    text: val.name,
                                    $order: orderIdx++
                                });
                            });
                            if (selectedCatId) {
                                catTs.setValue(String(selectedCatId));
                            } else {
                                catTs.setValue('');
                            }
                            catTs.refreshOptions(false);
                        } else {
                            let opts = '<option value="">Select Category</option>';
                            $.each(response.data, function(key, val) {
                                let sel = (String(selectedCatId) === String(val.id)) ? 'selected' : '';
                                opts += '<option value="' + val.id + '" ' + sel + '>' + val.name + '</option>';
                            });
                            $('#select-category').html(opts);
                            if (selectedCatId) $('#select-category').val(selectedCatId).trigger('change');
                        }
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    showToast('Failed to load categories.', 'error');
                }
            });
        }

        // When company is changed in superadmin mode
        $(document).on('change', '#select-company', function() {
            let compId = $(this).val();
            loadCategoriesByCompany(compId);
        });

        // Custom File Input Trigger
        $('#selectFileBtn').on('click', function() {
            $('#subCategoryImageInput').trigger('click');
        });

        $('#subCategoryImageInput').on('change', function() {
            let file = this.files[0];
            if (file) {
                $('#imageFileNameDisplay').val(file.name);
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').attr('src', e.target.result);
                    $('#imagePreviewContainer').removeClass('d-none');
                    if (window.lucide) lucide.createIcons();
                }
                reader.readAsDataURL(file);
            } else {
                $('#imageFileNameDisplay').val('No File Selected');
                $('#imagePreviewContainer').addClass('d-none');
            }
        });

        $('#removeImageBtn').on('click', function() {
            $('#subCategoryImageInput').val('');
            $('#remove_image').val('1');
            $('#imageFileNameDisplay').val('No File Selected');
            $('#imagePreview').attr('src', '');
            $('#imagePreviewContainer').addClass('d-none');
        });

        let validationRules = {
            category_id: {
                required: true
            },
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            }
        };
        let validationMessages = {
            category_id: {
                required: "Please select category"
            },
            name: {
                required: "Please enter sub category name",
                minlength: "Sub category name must be at least 2 characters",
                maxlength: "Sub category name cannot exceed 100 characters"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select company" };
        }

        initMasterModalCrud({
            modalId: '#SubCategoryModal',
            formId: '#subCategoryForm',
            storeUrl: SITE_URL + 'admin/master/sub-category/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.sub_category_edit',
            rules: validationRules,
            messages: validationMessages,
            onReset: function() {
                $('#remove_image').val('0');
                if (isSuperadmin) {
                    setSelectVal('#select-company', '');
                    let catTs = getTomSelect('#select-category');
                    if (catTs) {
                        catTs.clear();
                        catTs.clearOptions();
                        catTs.addOption({ value: '', text: 'Select Category', $order: 1 });
                        catTs.setValue('');
                    } else {
                        $('#select-category').html('<option value="">Select Category</option>');
                    }
                } else if (sessionCompanyId) {
                    loadCategoriesByCompany(sessionCompanyId);
                } else {
                    setSelectVal('#select-category', '');
                }
                $('#subCategoryImageInput').val('');
                $('#imageFileNameDisplay').val('No File Selected');
                $('#imagePreview').attr('src', '');
                $('#imagePreviewContainer').addClass('d-none');
            },
            onEditPopulate: function(rec) {
                $('#remove_image').val('0');
                if (isSuperadmin) {
                    setSelectVal('#select-company', rec.company_id);
                    loadCategoriesByCompany(rec.company_id, rec.category_id);
                } else {
                    let targetCompanyId = rec.company_id || sessionCompanyId;
                    loadCategoriesByCompany(targetCompanyId, rec.category_id);
                }

                if (rec.image && rec.image_url) {
                    $('#imageFileNameDisplay').val(rec.image);
                    $('#imagePreview').attr('src', rec.image_url);
                    $('#imagePreviewContainer').removeClass('d-none');
                } else {
                    $('#imageFileNameDisplay').val('No File Selected');
                    $('#imagePreview').attr('src', '');
                    $('#imagePreviewContainer').addClass('d-none');
                }
                $('#subCategoryImageInput').val('');
            },
            onSuccess: function() {
                $('#subCategoryImageInput').val('');
                $('#imageFileNameDisplay').val('No File Selected');
                $('#imagePreviewContainer').addClass('d-none');
            }
        });
    });
</script>
</body>
</html>
