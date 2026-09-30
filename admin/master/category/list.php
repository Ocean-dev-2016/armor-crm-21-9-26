<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('category', 'views');

$pageNm = 'Category';
$tbl = 'category';
$fields = 'name:Name, image: Image';
$showAdd = hasPermission('category', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}
?>

<!-- Category Modal -->
<?php
$modalId = 'CategoryModal';
$formId = 'categoryForm';
$includeCompanySelect = true;
$formEnctype = 'multipart/form-data';
$hasImageUpload = true;
$modalBodyContent = function() { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Select Image</label>
            <div class="input-group">
                <input type="text" class="form-control bg-light" id="imageFileNameDisplay" placeholder="No File Selected" readonly>
                <input type="file" name="image" id="categoryImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
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
$ajaxUrl = SITE_URL . 'admin/master/category/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, ['Name', 'Image', 'Status', 'Action']);
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).ready(function() {

        // Custom File Input Trigger
        $('#selectFileBtn').on('click', function() {
            $('#categoryImageInput').trigger('click');
        });

        $('#categoryImageInput').on('change', function() {
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

        let isSuperadmin = <?= $isSuperadmin ? 'true' : 'false' ?>;

        function setCompanySelectValue(val) {
            let el = $('#select-company')[0];
            if (el && el.tomselect) {
                el.tomselect.setValue(val ? String(val) : '');
            } else {
                $('#select-company').val(val || '').trigger('change');
            }
        }

        if (isSuperadmin && typeof TomSelect !== 'undefined' && $('#select-company').length) {
            new TomSelect('#select-company', {
                create: false,
                allowEmptyOption: true
            });
        }

        $('#removeImageBtn').on('click', function() {
            $('#categoryImageInput').val('');
            $('#remove_image').val('1');
            $('#imageFileNameDisplay').val('No File Selected');
            $('#imagePreview').attr('src', '');
            $('#imagePreviewContainer').addClass('d-none');
        });

        let validationRules = {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            }
        };
        let validationMessages = {
            name: {
                required: "Please enter category name",
                minlength: "Category name must be at least 2 characters",
                maxlength: "Category name cannot exceed 100 characters"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select a company" };
        }

        initMasterModalCrud({
            modalId: '#CategoryModal',
            formId: '#categoryForm',
            storeUrl: SITE_URL + 'admin/master/category/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.category_edit',
            rules: validationRules,
            messages: validationMessages,
            onReset: function() {
                $('#remove_image').val('0');
                if (isSuperadmin) {
                    setCompanySelectValue('');
                }
                $('#categoryImageInput').val('');
                $('#imageFileNameDisplay').val('No File Selected');
                $('#imagePreview').attr('src', '');
                $('#imagePreviewContainer').addClass('d-none');
            },
            onEditPopulate: function(rec) {
                $('#remove_image').val('0');
                if (isSuperadmin) {
                    setCompanySelectValue(rec.company_id);
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
                $('#categoryImageInput').val('');
            },
            onSuccess: function() {
                $('#categoryImageInput').val('');
                $('#imageFileNameDisplay').val('No File Selected');
                $('#imagePreviewContainer').addClass('d-none');
            }
        });
    });
</script>
</body>
</html>
