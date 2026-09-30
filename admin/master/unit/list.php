<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('unit', 'views');

$pageNm = 'Unit';
$tbl = 'unit';
$showAdd = hasPermission('unit', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name';
$module = 'unit';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}
?>

<!-- Unit Modal -->
<?php
$modalId = 'UnitModal';
$formId = 'unitForm';
$includeCompanySelect = true;
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/master/unit/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, [
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
            if (isSuperadmin) {
                setCompanySelectValue('');
            }
            $('#UnitModalLabel').text('Add <?= $pageNm ?>');
            $('#unitForm').validate().resetForm();
            $("#UnitModal").modal("show");
        });

        let validationRules = {
            name: {
                required: true,
                minlength: 1,
                maxlength: 100
            }
        };
        let validationMessages = {
            name: {
                required: "Please enter unit name",
                minlength: "Unit name must be at least 1 character",
                maxlength: "Unit name cannot exceed 100 characters"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select company" };
        }

        initMasterModalCrud({
            modalId: '#UnitModal',
            formId: '#unitForm',
            storeUrl: SITE_URL + 'admin/master/unit/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.unit_edit',
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
