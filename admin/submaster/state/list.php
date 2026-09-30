<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('state', 'views');

$pageNm = 'State';
$tbl = 'state';
$sql = "SELECT * FROM country WHERE status = 1";
$country = db_rows($sql);
$showAdd = hasPermission('state', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'country_id:Country, name:Name';
$module = 'state';
$tbl = 'state';
include BASE_PATH . '/component/breadcrumb.php';
?>

<?php
$modalId = 'StateModal';
$formId = 'stateForm';
$hideNameField = true;
$modalBodyContent = function() use ($country, $pageNm) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Country</label>
            <select name="country_id" id="select-single" class="form-select country_id">
                <option value="">Select a Country</option>
                <?php foreach ($country as $val) { ?>
                    <option value="<?= $val['id'] ?>">
                        <?= htmlspecialchars($val['name']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/submaster/state/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Country',
    'Name',
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
            modalId: '#StateModal',
            formId: '#stateForm',
            storeUrl: 'admin/submaster/state/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.state_edit',
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                country_id: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "Please enter state name",
                    minlength: "State name must be at least 3 characters",
                    maxlength: "State name cannot exceed 100 characters"
                },
                country_id: {
                    required: "Please Select Country"
                }
            }
        });
    });
</script>
</body>

</html>