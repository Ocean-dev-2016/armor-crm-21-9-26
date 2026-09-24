<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'complain-sub-category';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Complain Sub Category';
$tbl = 'complain_sub_category';
$fields = 'complain_category_id:Complain Category,name:Name';
$module = 'complain-sub-category';
$showAdd = hasPermission($moduleKey, 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}

// Prefetch categories for non-superadmin (active company)
$categories = [];
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $categories = db_rows("SELECT id, name FROM complain_category WHERE company_id = $sessionCompanyId AND status = 1 ORDER BY name ASC");
}
?>

<!-- Complain Sub Category Modal -->
<div class="modal fade" id="ComplainSubCategoryModal" tabindex="-1" aria-labelledby="ComplainSubCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="ComplainSubCategoryModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="complainSubCategoryForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
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
                                <label class="form-label">Complain/Request Category</label>
                                <select name="complain_category_id" id="select-complain-category" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php if (!$isSuperadmin): ?>
                                        <?php foreach ($categories as $cat): ?>
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
                                <label class="form-label">Complain/Request Sub Category</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
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
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/submaster/complain-sub-category/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <?php if ($isSuperadmin): ?>
                                    <th>Company Name</th>
                                <?php endif; ?>
                                <th>Complain Category</th>
                                <th>Name</th>
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

        let isSuperadmin = <?= $isSuperadmin ? 'true' : 'false' ?>;
        let sessionCompanyId = '<?= $sessionCompanyId > 0 ? $sessionCompanyId : '' ?>';

        function getTomSelect(selector) {
            let el = $(selector)[0];
            if (!el) return null;
            if (el.tomselect) return el.tomselect;
            if (typeof TomSelect !== 'undefined') {
                return new TomSelect(el, {
                    create: false,
                    allowEmptyOption: true,
                    sortField: [{ field: '$order' }]
                });
            }
            return null;
        }

        let tsCompany = isSuperadmin ? getTomSelect('#select-company') : null;
        let tsComplainCategory = getTomSelect('#select-complain-category');

        function setSelectVal(selector, val) {
            let el = $(selector)[0];
            if (el && el.tomselect) {
                el.tomselect.setValue(val ? String(val) : '');
            } else {
                $(selector).val(val ? String(val) : '').trigger('change');
            }
        }

        // Function to load complain categories for a company
        function loadComplainCategories(companyId, selectedCatId = '') {
            let catTs = getTomSelect('#select-complain-category');
            if (catTs) {
                catTs.clear();
                catTs.clearOptions();
                catTs.addOption({ value: '', text: 'Select Category', $order: 1 });
                catTs.setValue('');
            } else {
                $('#select-complain-category').html('<option value="">Select Category</option>');
            }

            if (!companyId) return;

            $.ajax({
                url: SITE_URL + 'admin/submaster/complain-sub-category/store.php',
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
                            $('#select-complain-category').html(opts);
                            if (selectedCatId) $('#select-complain-category').val(selectedCatId).trigger('change');
                        }
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function() {
                    showToast('Failed to load categories.', 'error');
                }
            });
        }

        // When company changes in superadmin mode
        $(document).on('change', '#select-company', function() {
            let compId = $(this).val();
            loadComplainCategories(compId);
        });

        // Open Add Modal
        $(document).on('click', '.open_modal', function() {
            $('#id').val('');
            $('#name').val('');
            if (isSuperadmin) {
                setSelectVal('#select-company', '');
                let catTs = getTomSelect('#select-complain-category');
                if (catTs) {
                    catTs.clear();
                    catTs.clearOptions();
                    catTs.addOption({ value: '', text: 'Select Category', $order: 1 });
                    catTs.setValue('');
                } else {
                    $('#select-complain-category').html('<option value="">Select Category</option>');
                }
            } else {
                setSelectVal('#select-complain-category', '');
            }
            $('#ComplainSubCategoryModalLabel').text('Add <?= $pageNm ?>');
            $('#complainSubCategoryForm').validate().resetForm();
            $("#ComplainSubCategoryModal").modal("show");
        });

        // Form Validation
        let validationRules = {
            complain_category_id: {
                required: true
            },
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            }
        };
        let validationMessages = {
            complain_category_id: {
                required: "Please select complain/request category"
            },
            name: {
                required: "Please enter name",
                minlength: "Name must be at least 2 characters",
                maxlength: "Name cannot exceed 100 characters"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select a company" };
        }

        $("#complainSubCategoryForm").validate({
            rules: validationRules,
            messages: validationMessages,
            errorElement: "span",
            errorClass: "text-danger",
            submitHandler: function(form) {
                let $form = $(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");

                $.ajax({
                    url: SITE_URL + "admin/submaster/complain-sub-category/store.php",
                    type: "POST",
                    data: $form.serialize(),
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
                                setSelectVal('#select-company', '');
                                let catTs = getTomSelect('#select-complain-category');
                                if (catTs) {
                                    catTs.clear();
                                    catTs.clearOptions();
                                    catTs.addOption({ value: '', text: 'Select Category', $order: 1 });
                                    catTs.setValue('');
                                } else {
                                    $('#select-complain-category').html('<option value="">Select Category</option>');
                                }
                            } else {
                                setSelectVal('#select-complain-category', '');
                            }
                            form.reset();
                            $(form).validate().resetForm();
                            $("#ComplainSubCategoryModal").modal("hide");
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

        // Edit Complain Sub Category
        $(document).on('click', '.complain_sub_category_edit', function() {
            var id = $(this).data('id');
            $.ajax({
                url: SITE_URL + 'admin/submaster/complain-sub-category/store.php',
                type: 'GET',
                data: {
                    id: id,
                    action: 'edit'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true) {
                        let data = response.data;
                        $('#id').val(data.id);
                        $('#name').val(data.name);
                        $('#ComplainSubCategoryModalLabel').text('Edit <?= $pageNm ?>');

                        if (isSuperadmin) {
                            setSelectVal('#select-company', data.company_id);
                            loadComplainCategories(data.company_id, data.complain_category_id);
                        } else {
                            setSelectVal('#select-complain-category', data.complain_category_id);
                        }

                        $("#ComplainSubCategoryModal").modal("show");
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
