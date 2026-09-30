<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('tax', 'views');

$pageNm = 'Tax';
$tbl = 'tax';
$showAdd = hasPermission('tax', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name,value:Value';
$module = 'tax';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}
?>

<!-- Tax Modal -->
<?php
$modalId = 'TaxModal';
$formId = 'taxForm';
$includeCompanySelect = true;
$modalBodyContent = function() use ($pageNm) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Value <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="value" id="value" class="form-control" placeholder="Enter Value (e.g. 18.00)">
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/master/tax/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, [
    'Tax Name',
    'Value (%)',
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
            $('#value').val('');
            if (isSuperadmin) {
                setCompanySelectValue('');
            }
            $('#TaxModalLabel').text('Add <?= $pageNm ?>');
            $('#taxForm').validate().resetForm();
            $("#TaxModal").modal("show");
        });

        let validationRules = {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            value: {
                required: true,
                number: true,
                min: 0
            }
        };
        let validationMessages = {
            name: {
                required: "Please enter tax name",
                minlength: "Tax name must be at least 2 characters",
                maxlength: "Tax name cannot exceed 100 characters"
            },
            value: {
                required: "Please enter tax value",
                number: "Please enter a valid numeric value",
                min: "Tax value cannot be negative"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select company" };
        }

        initMasterModalCrud({
            modalId: '#TaxModal',
            formId: '#taxForm',
            storeUrl: SITE_URL + 'admin/master/tax/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.tax_edit',
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
