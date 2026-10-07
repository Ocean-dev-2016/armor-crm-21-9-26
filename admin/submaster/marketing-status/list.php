<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'marketing-status';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Marketing Status';
$tbl = 'marketing_status';
$fields = 'name:Name, color:Color, type:Type';
$module = 'marketing-status';
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

<!-- Marketing Status Modal -->
<?php
$modalId = 'MarketingStatusModal';
$formId = 'marketingStatusForm';
$includeCompanySelect = true;

$modalBodyContent = function() use ($pageNm) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label" for="color">Status Color</label>
            <input type="color" name="color" id="color" class="form-control" title="Choose color">
        </div>
    </div>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label" for="type">Status For (Type) <span class="text-danger">*</span></label>
            <select name="type" id="type" class="form-select">
                <option value="Lead">Lead</option>
                <option value="Follow Up">Follow Up</option>
            </select>
        </div>
    </div>
<?php };

include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/submaster/marketing-status/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, ['Name', 'Color', 'Type', 'Status', 'Action']);
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

        // Synchronize color input and text hex
        $('#color').on('input change', function() {
            $('#color_hex').val($(this).val());
        });
        $('#color_hex').on('input change', function() {
            let val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#color').val(val);
            }
        });

        let validationRules = {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            type: {
                required: true
            }
        };
        let validationMessages = {
            name: {
                required: "Please enter status name",
                minlength: "Name must be at least 2 characters",
                maxlength: "Name cannot exceed 100 characters"
            },
            type: {
                required: "Please select status for (Lead or Follow Up)"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select a company" };
        }

        initMasterModalCrud({
            modalId: '#MarketingStatusModal',
            formId: '#marketingStatusForm',
            storeUrl: SITE_URL + 'admin/submaster/marketing-status/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.marketing_status_edit',
            rules: validationRules,
            messages: validationMessages,
            onReset: function() {
                if (isSuperadmin) {
                    setCompanySelectValue('');
                }
                $('#color').val('#0e5a6c');
                $('#color_hex').val('#0e5a6c');
                $('#type').val('Lead');
            },
            onEditPopulate: function(rec) {
                if (isSuperadmin) {
                    setCompanySelectValue(rec.company_id);
                }
                if (rec.color) {
                    $('#color').val(rec.color);
                    $('#color_hex').val(rec.color);
                } else {
                    $('#color').val('#0e5a6c');
                    $('#color_hex').val('#0e5a6c');
                }
                if (rec.type) {
                    $('#type').val(rec.type);
                } else {
                    $('#type').val('Lead');
                }
            }
        });
    });
</script>
</body>
</html>
