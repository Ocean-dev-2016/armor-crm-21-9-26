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
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$ajaxUrl = SITE_URL . 'admin/setting/team-person/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders[] = 'Name';
$tableHeaders[] = 'Email';
$tableHeaders[] = 'Mobile No';
$tableHeaders[] = 'Country';
$tableHeaders[] = 'State';
$tableHeaders[] = 'City';
$tableHeaders[] = 'Role';
$tableHeaders[] = 'Status';
$tableHeaders[] = 'Actions';
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>

</body>

</html>