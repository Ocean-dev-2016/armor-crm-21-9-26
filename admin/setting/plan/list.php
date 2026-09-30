<?php
require_once __DIR__ . '/../../../conn/db.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('plan', 'views');

$pageNm = 'Plan';
$showAdd = hasPermission('plan', 'adds');
$addType = 'redirect';
$addUrl  = SITE_URL . 'plan/add';
$breadcrumbType = 'list';
$fields = 'name:Name';
$module = 'plan';
$tbl = 'plan';
include BASE_PATH . '/component/breadcrumb.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/setting/plan/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Name',
    'Price',
    'Plan Duration',
    'Max Team User',
    'Max Customer',
    'Max Inquiry',
    'Status',
    'Actions'
];
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>

</body>

</html>