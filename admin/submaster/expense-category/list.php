<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'expense-category';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Expense Category';
$tbl = 'expense_category';
$fields = 'name:Category Name,image:Image';
$module = 'expense-category';
$showAdd = hasPermission($moduleKey, 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}
?>

<!-- Expense Category Modal -->
<div class="modal fade" id="ExpenseCategoryModal" tabindex="-1" aria-labelledby="ExpenseCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="ExpenseCategoryModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="expenseCategoryForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="remove_image" id="remove_image" value="0">
                    <div class="row g-3">
                        <?php if ($isSuperadmin): ?>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Company</label>
                                <select name="company_id" id="select-company" class="form-select company_id">
                                    <option value="">Select a Company</option>
                                    <?php foreach ($companies as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Select Image</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light" id="imageFileNameDisplay" placeholder="No File Selected" readonly>
                                    <input type="file" name="image" id="expenseCategoryImageInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="submitText">Submit</span>
                        <span id="submitLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm; ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/submaster/expense-category/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <?php if ($isSuperadmin): ?>
                                    <th>Company Name</th>
                                <?php endif; ?>
                                <th>Category Name</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).ready(function() {

        // Custom File Input Trigger
        $('#selectFileBtn').on('click', function() {
            $('#expenseCategoryImageInput').trigger('click');
        });

        $('#expenseCategoryImageInput').on('change', function() {
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
            $('#expenseCategoryImageInput').val('');
            $('#remove_image').val('1');
            $('#imageFileNameDisplay').val('No File Selected');
            $('#imagePreview').attr('src', '');
            $('#imagePreviewContainer').addClass('d-none');
        });

        // Open Add Modal
        $(document).on('click', '.open_modal', function() {
            $('#id').val('');
            $('#name').val('');
            $('#remove_image').val('0');
            if (isSuperadmin) {
                setCompanySelectValue('');
            }
            $('#expenseCategoryImageInput').val('');
            $('#imageFileNameDisplay').val('No File Selected');
            $('#imagePreview').attr('src', '');
            $('#imagePreviewContainer').addClass('d-none');
            $('#ExpenseCategoryModalLabel').text('Add <?= $pageNm ?>');
            $('#expenseCategoryForm').validate().resetForm();
            $("#ExpenseCategoryModal").modal("show");
        });

        // Validate Form
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

        $("#expenseCategoryForm").validate({
            rules: validationRules,
            messages: validationMessages,
            errorElement: "span",
            errorClass: "text-danger",
            submitHandler: function(form) {
                let formData = new FormData(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");

                $.ajax({
                    url: SITE_URL + "admin/submaster/expense-category/store.php",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true);
                        $text.text("Saving...");
                        $loader.removeClass("d-none");
                    },
                    success: function(response) {
                        if (response.status === true) {
                            showToast(response.message, "success");
                            $('#id').val('');
                            $('#name').val('');
                            if (isSuperadmin) {
                                setCompanySelectValue('');
                            }
                            $('#expenseCategoryImageInput').val('');
                            $('#imageFileNameDisplay').val('No File Selected');
                            $('#imagePreviewContainer').addClass('d-none');
                            form.reset();
                            $(form).validate().resetForm();
                            $("#ExpenseCategoryModal").modal("hide");
                            $(".data-table").DataTable().ajax.reload(null, false);
                        } else {
                            showToast(response.message, "error");
                        }
                    },
                    error: function() {
                        showToast("Something went wrong. Please try again.", "error");
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                        $text.text("Submit");
                        $loader.addClass("d-none");
                    }
                });
            }
        });

        // Edit Expense Category
        $(document).on('click', '.expense_category_edit', function() {
            var id = $(this).data('id');
            $.ajax({
                url: SITE_URL + 'admin/submaster/expense-category/store.php',
                type: 'GET',
                data: {
                    id: id,
                    action: 'edit'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true) {
                        $('#id').val(response.data.id);
                        $('#name').val(response.data.name);
                        $('#remove_image').val('0');
                        $('#ExpenseCategoryModalLabel').text('Edit <?= $pageNm ?>');

                        if (isSuperadmin) {
                            setCompanySelectValue(response.data.company_id);
                        }

                        if (response.data.image && response.data.image_url) {
                            $('#imageFileNameDisplay').val(response.data.image);
                            $('#imagePreview').attr('src', response.data.image_url);
                            $('#imagePreviewContainer').removeClass('d-none');
                        } else {
                            $('#imageFileNameDisplay').val('No File Selected');
                            $('#imagePreview').attr('src', '');
                            $('#imagePreviewContainer').addClass('d-none');
                        }
                        $('#expenseCategoryImageInput').val('');

                        $("#ExpenseCategoryModal").modal("show");
                        if (window.lucide) lucide.createIcons();
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    showToast('Something went wrong. Please try again.', 'error');
                }
            });
        });
    });
</script>
</body>
</html>
