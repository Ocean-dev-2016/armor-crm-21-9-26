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

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm; ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/master/product/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <?php if ($isSuperadmin): ?>
                                    <th>Company Name</th>
                                <?php endif; ?>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include BASE_PATH . '/include/footer.php';
?>
</body>
</html>
