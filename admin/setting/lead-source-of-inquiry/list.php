<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

// Only Superadmin can access
if (!$isSuperadmin) {
    echo "<div class='container py-5 text-center'><h3 class='text-danger'>Access Denied</h3><p>Only Superadmin can access this setting.</p><a href='" . SITE_URL . "' class='btn btn-primary'>Back to Home</a></div>";
    include BASE_PATH . '/include/footer.php';
    exit;
}

$pageNm = 'Lead Source Of Inquiry';
$tbl = 'lead_source_of_inquiry';
$moduleKey = 'lead-source-of-inquiry';

$showAdd = true;
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name';
$module = 'lead-source-of-inquiry';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Lead Source Of Inquiry Modal -->
<?php
$modalId = 'LeadSourceOfInquiryModal';
$formId = 'leadSourceOfInquiryForm';
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/setting/lead-source-of-inquiry/ajax.php';
$tableHeaders = [
    'Sr No.',
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
            modalId: '#LeadSourceOfInquiryModal',
            formId: '#leadSourceOfInquiryForm',
            storeUrl: SITE_URL + 'admin/setting/lead-source-of-inquiry/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.lead_source_of_inquiry_edit',
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                }
            },
            messages: {
                name: {
                    required: "Please enter name",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name cannot exceed 100 characters"
                }
            }
        });
    });
</script>
</body>
</html>
