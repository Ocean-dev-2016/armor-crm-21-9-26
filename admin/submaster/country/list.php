<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('country', 'views');

$pageNm = 'Country';
$tbl = 'country';
$sql = "SELECT * FROM $tbl WHERE status = 1";
$country = db_rows($sql);
$showAdd = hasPermission('country', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name';
$module = 'country';
$tbl = 'country';
include BASE_PATH . '/component/breadcrumb.php';
?>

<?php
$modalId = 'CountryModal';
$formId = 'countryForm';
$modalBodyContent = function() use ($pageNm) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Short Name <span class="text-danger">*</span></label>
            <input type="text" name="short_name" id="short_name" class="form-control" placeholder="Enter <?= $pageNm ?> Short Name">
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/submaster/country/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Name',
    'Short Name',
    'Status',
    'Action'
];
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).ready(function() {
        initMasterModalCrud({
            modalId: '#CountryModal',
            formId: '#countryForm',
            storeUrl: 'admin/submaster/country/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.country_edit',
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                short_name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                }
            },
            messages: {
                name: {
                    required: "Please enter country name",
                    minlength: "Country name must be at least 3 characters",
                    maxlength: "Country name cannot exceed 100 characters"
                },
                short_name: {
                    required: "Please enter country short name",
                    minlength: "Country short name must be at least 2 characters",
                    maxlength: "Country short name cannot exceed 100 characters"
                }
            }
        });
    });
</script>
</body>

</html>