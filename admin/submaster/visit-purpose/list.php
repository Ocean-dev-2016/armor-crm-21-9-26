<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'visit-purpose';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Visit Purpose';
$tbl = 'visit_purpose';
$fields = 'name:Name';
$module = 'visit-purpose';
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

<!-- Visit Purpose Modal -->
<?php
$modalId = 'VisitPurposeModal';
$formId = 'visitPurposeForm';
$includeCompanySelect = true;
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/submaster/visit-purpose/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, ['Name', 'Status', 'Action']);
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

        let validationRules = {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            }
        };
        let validationMessages = {
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
            modalId: '#VisitPurposeModal',
            formId: '#visitPurposeForm',
            storeUrl: SITE_URL + 'admin/submaster/visit-purpose/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.visit_purpose_edit',
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