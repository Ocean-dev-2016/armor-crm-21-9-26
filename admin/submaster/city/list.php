<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('city', 'views');

$pageNm = 'City';
$tbl = 'city';
$sql = "SELECT * FROM country WHERE status = 1";
$country = db_rows($sql);
$showAdd = hasPermission('city', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'country_id:Country, state_id: State, name:Name';
$module = 'city';
$tbl = 'city';
include BASE_PATH . '/component/breadcrumb.php';
?>

<?php
$modalId = 'CityModal';
$formId = 'cityForm';
$hideNameField = true;
$modalBodyContent = function() use ($country, $pageNm) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Country</label>
            <select name="country_id" id="select-single" class="form-select country_base_state">
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
            <label class="form-label">State</label>
            <select name="state_id" id="select-state" class="form-select state_base_city">
                <option value="">Select a State</option>
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
$ajaxUrl = SITE_URL . 'admin/submaster/city/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Country',
    'State',
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
            modalId: '#CityModal',
            formId: '#cityForm',
            storeUrl: 'admin/submaster/city/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.city_edit',
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                country_id: {
                    required: true
                },
                state_id: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "Please enter city name",
                    minlength: "City name must be at least 3 characters",
                    maxlength: "City name cannot exceed 100 characters"
                },
                country_id: {
                    required: "Please Select Country"
                },
                state_id: {
                    required: "Please Select State"
                }
            },
            onReset: function($form, $modal) {
                let stateSelect = $('#select-state')[0];
                if (stateSelect && stateSelect.tomselect) {
                    stateSelect.tomselect.clearOptions();
                    stateSelect.tomselect.addOption({ value: '', text: 'Select a State' });
                    stateSelect.tomselect.setValue('');
                } else {
                    $('.state_base_city').html('<option value="">Select a State</option>');
                }
            },
            onEditPopulate: function(rec, $form, $modal) {
                loadStatesByCountry(rec.country_id, rec.state_id);
            }
        });
    });
</script>
</body>

</html>