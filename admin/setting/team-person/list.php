<?php
require_once __DIR__ . '/../../../conn/db.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('team-person', 'views');

$pageNm = 'Team Person';
$showAdd = hasPermission('team-person', 'adds');
$addType = 'redirect';
$addUrl  = SITE_URL . 'team-person/add';
$breadcrumbType = 'list';
$fields = 'name:Name';
$module = 'team-person';
$tbl = 'users';
include BASE_PATH . '/component/breadcrumb.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/setting/team-person/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Company Name',
    'Name',
    'Email',
    'Mobile No',
    'Country',
    'State',
    'City',
    'Role',
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