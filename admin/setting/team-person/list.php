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
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/setting/team-person/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Company Name</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Mobile No</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>City</th>
                                <th>Role</th>
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