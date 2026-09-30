<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'company-type';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Company Type';
$tbl = 'company_type';
$showAdd = hasPermission($moduleKey, 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name';
$module = ' company-type';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Company Type Modal -->
<?php
$modalId = 'CompanyTypeModal';
$formId = 'companyTypeForm';
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/master/company-type/ajax.php';
$tableHeaders = ['Sr No.', 'Name', 'Status', 'Action'];
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).ready(function() {
        initMasterModalCrud({
            modalId: '#CompanyTypeModal',
            formId: '#companyTypeForm',
            storeUrl: SITE_URL + 'admin/master/company-type/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.companytype_edit',
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                }
            },
            messages: {
                name: {
                    required: "Please enter <?= strtolower($pageNm) ?> name",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name cannot exceed 100 characters"
                }
            }
        });
    });
</script>
</body>
</html>
