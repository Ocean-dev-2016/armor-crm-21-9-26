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
<?php
$modalId = 'ComplainSubCategoryModal';
$formId = 'complainSubCategoryForm';
$includeCompanySelect = true;
$hideNameField = true;
$modalBodyContent = function() use ($pageNm, $isSuperadmin, $categories) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Complain Category</label>
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
            <label class="form-label">Complain Sub Category</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/submaster/complain-sub-category/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, [
    'Complain Category',
    'Name',
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

        initMasterModalCrud({
            modalId: '#ComplainSubCategoryModal',
            formId: '#complainSubCategoryForm',
            storeUrl: SITE_URL + 'admin/submaster/complain-sub-category/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.complain_sub_category_edit',
            rules: validationRules,
            messages: validationMessages,
            onReset: function() {
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
            },
            onEditPopulate: function(data) {
                if (isSuperadmin) {
                    setSelectVal('#select-company', data.company_id);
                    loadComplainCategories(data.company_id, data.complain_category_id);
                } else {
                    setSelectVal('#select-complain-category', data.complain_category_id);
                }
            }
        });
    });
</script>
</body>
</html>
