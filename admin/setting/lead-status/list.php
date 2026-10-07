<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$pageNm = 'Lead Status';
$tbl = 'lead_status';
$moduleKey = 'lead-status';

$showAdd = $isSuperadmin || hasPermission($moduleKey, 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name, color:Color';
$module = 'lead-status';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Lead Status Modal -->
<?php
$modalId = 'LeadStatusModal';
$formId = 'leadStatusForm';
$modalBodyContent = function() use ($pageNm) { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label" for="color">Status Color</label>
            <input type="color" name="color" id="color" class="form-control" value="#0e5a6c" title="Choose color" >
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/setting/lead-status/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Name',
    'Color',
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
        // Color input change handling

        initMasterModalCrud({
            modalId: '#LeadStatusModal',
            formId: '#leadStatusForm',
            storeUrl: SITE_URL + 'admin/setting/lead-status/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.lead_status_edit',
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                color: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "Please enter lead status name",
                    minlength: "Status name must be at least 2 characters",
                    maxlength: "Status name cannot exceed 100 characters"
                },
                color: {
                    required: "Please select status color"
                }
            },
            onReset: function() {
                $('#color').val('#0e5a6c');
                $('#colorHexText').val('#0E5A6C');
            },
            onEditPopulate: function(rec) {
                let color = rec.color || '#0e5a6c';
                $('#color').val(color);
                $('#colorHexText').val(color.toUpperCase());
            }
        });
    });
</script>
</body>
</html>
