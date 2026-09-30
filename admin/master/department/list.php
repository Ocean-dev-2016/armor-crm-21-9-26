<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('department', 'views');

$pageNm = 'Department';
$tbl = 'department';
$showAdd = hasPermission('department', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name, code: Code';
$module = ' department';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}
?>

<!-- Department Modal -->
<?php
$modalId = 'DepartmentModal';
$formId = 'departmentForm';
$includeCompanySelect = true;
$modalBodyContent = function() use ($pageNm) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" id="code" class="form-control" placeholder="Enter <?= $pageNm ?> Code">
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/master/department/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, ['Department Name', 'Code', 'Status', 'Action']);
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).ready(function() {

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

        // Open Add Modal
        $(document).on('click', '.open_modal', function() {
            $('#id').val('');
            $('#name').val('');
            $('#code').val('');
            if (isSuperadmin) {
                setCompanySelectValue('');
            }
            $('#DepartmentModalLabel').text('Add <?= $pageNm ?>');
            $('#departmentForm').validate().resetForm();
            $("#DepartmentModal").modal("show");
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
                required: "Please enter department name",
                minlength: "Department name must be at least 2 characters",
                maxlength: "Department name cannot exceed 100 characters"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select company" };
        }

        initMasterModalCrud({
            modalId: '#DepartmentModal',
            formId: '#departmentForm',
            storeUrl: SITE_URL + 'admin/master/department/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.department_edit',
            rules: validationRules,
            messages: validationMessages,
            onReset: function() {
                if (isSuperadmin) {
                    setCompanySelectValue('');
                }
            },
            onEditPopulate: function(rec) {
                if (isSuperadmin) {
                    setCompanySelectValue(rec.company_id);
                }
            }
        });
    });
</script>
</body>
</html>
