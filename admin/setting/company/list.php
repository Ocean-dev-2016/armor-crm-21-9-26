<?php
require_once __DIR__ . '/../../../conn/db.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('company', 'views');

$pageNm = 'Company';
$showAdd = hasPermission('company', 'adds');
$addType = 'redirect';
$addUrl  = SITE_URL . 'company/add';
$breadcrumbType = 'list';
$fields = 'name:Name';
$module = 'company';
$tbl = 'company';
include BASE_PATH . '/component/breadcrumb.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/setting/company/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Name</th>
                                <th>Person Name</th>
                                <th>Mobile No</th>
                                <th>Email</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>City</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Actions</th>
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