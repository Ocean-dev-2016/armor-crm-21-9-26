<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('product', 'views');

$pageNm = 'Product';
$tbl = 'product';
$showAdd = hasPermission('product', 'adds');
$breadcrumbType = 'list';
$addType = 'redirect';
$addUrl = SITE_URL . 'product/add';
$fields = 'type:Type,category_id:Category,sub_category_id:Sub Category,name: Name, tax_id: Tax, sales_unit_id:Unit, 
            customer_unit_id:Customer, display_unit: Display Unit, hsn_code: Hsn Code, image:Image';
$module = ' product';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/master/product/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, [
    'Name',
    'Type',
    'Category',
    'Sub Category',
    'Image',
    'Status',
    'Action'
]);
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>
</body>
</html>
