<?php
require_once __DIR__ . '/../../../conn/db.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('company', 'views');

$pageNm = 'Company';
$showAdd = hasPermission('company', 'adds');
$addType = 'redirect';
$addUrl  = SITE_URL . 'company/add';
$breadcrumbType = 'list';
$fields = 'name:Name, person_name:Person Name, mobile_no:Mobile No, email:Email, header_image:Header Image, app_logo:App Logo, favicon:Favicon';
$module = 'company';
$tbl = 'company';
include BASE_PATH . '/component/breadcrumb.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/setting/company/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Name',
    'Person Name',
    'Mobile No',
    'Email',
    'Country',
    'State',
    'City',
    'Plan',
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