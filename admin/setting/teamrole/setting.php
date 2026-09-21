<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
$pageNm = 'Team Role';
$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: " . SITE_URL . "teamrole");
    exit;
}
include BASE_PATH . '/include/header.php';
$userSql = "SELECT r.*, c.plan_id as company_plan_id FROM roles r LEFT JOIN company c ON c.id = r.company_id WHERE r.id = " . $id;
$res = db_row($userSql);
$company_id = $res['company_id'] ?? 0;
$role_id = $id;
$modName = array();
if (!empty($res)) {
    $planSql = "SELECT * FROM plan WHERE id = " . $res['company_plan_id'];
    $res = db_row($planSql);
    $panel_right = ($res['panel_right'] != null) ? $res['panel_right'] : '';
    $modulesql = "SELECT * FROM module WHERE id IN (" . $panel_right . ")";
    $modName = db_rows($modulesql);
}

$parentIds = [];

foreach ($modName as $module) {
    $parentId = (int) $module['parent_id'];
    if ($parentId > 0) {
        $parentIds[] = $parentId;
    }
}

$parentIds = array_unique($parentIds);
if (!empty($parentIds)) {
    $parentIdString = implode(',', $parentIds);
    $parentSql = "
        SELECT *
        FROM module
        WHERE id IN ($parentIdString)
        AND status = 1
    ";
    $parentModules = db_rows($parentSql);
    $modName = array_merge($parentModules, $modName);
}

// Get existing permissions
$permissionSql = "
    SELECT
        module_id,
        views,
        adds,
        updates,
        deletes
    FROM role_permissions
    WHERE company_id = " . (int)$company_id . "
    AND role_id = " . (int)$role_id;

$permissionRows = db_rows($permissionSql);

// Make module-wise permission array
$existingPermissions = [];

foreach ($permissionRows as $permission) {

    $moduleId = (int)$permission['module_id'];

    $existingPermissions[$moduleId] = [
        'views'   => (int)$permission['views'],
        'adds'    => (int)$permission['adds'],
        'updates' => (int)$permission['updates'],
        'deletes' => (int)$permission['deletes'],
    ];
}

$breadcrumbType = 'form';
$isEdit = false;
$parentUrl = SITE_URL . 'teamrole';
$customName = 'Add Permission';
include BASE_PATH . '/component/breadcrumb.php';
?>
<form id="teamRoleForm">
    <input type="hidden" name="id" value="<?= $role_id ?>">
    <input type="hidden" name="company_id" value="<?= $company_id ?>">
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-bordered permission-table mb-0">
                        <thead>
                            <tr>
                                <th class="submenu-name">NAME</th>
                                <th>VIEW</th>
                                <th>ADD</th>
                                <th>UPDATE</th>
                                <th>DELETE</th>
                                <th>CHECK ALL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $mainModules = [];
                            $subModules = [];
                            foreach ($modName as $module) {
                                $moduleId = (int) $module['id'];
                                $parentId = (int) $module['parent_id'];
                                if ($parentId === 0) {
                                    $mainModules[$moduleId] = $module;
                                } else {
                                    $subModules[$parentId][] = $module;
                                }
                            }

                            foreach ($mainModules as $mainModule):
                                $mainModuleId = (int) $mainModule['id'];
                                $children = $subModules[$mainModuleId] ?? [];

                                if (!empty($children)):
                            ?>
                                    <tr class="module-row">
                                        <td colspan="6">
                                            <i data-lucide="folder" class="me-2"></i>
                                            <?= htmlspecialchars($mainModule['name']) ?>
                                        </td>
                                    </tr>

                                    <?php foreach ($children as $subModule): ?>
                                        <?php
                                        $moduleId = (int) $subModule['id'];
                                        $permission = $existingPermissions[$moduleId] ?? [
                                            'views' => 0,
                                            'adds' => 0,
                                            'updates' => 0,
                                            'deletes' => 0
                                        ];
                                        $checkAll =
                                        $permission['views'] == 1 &&
                                        $permission['adds'] == 1 &&
                                        $permission['updates'] == 1 &&
                                        $permission['deletes'] == 1;
                                        ?>

                                        <tr class="permission-row">
                                            <!-- NAME -->
                                            <td class="submenu-title">
                                                <span class="submenu-circle"></span>
                                                <?= htmlspecialchars($subModule['name']) ?>
                                            </td>

                                            <!-- VIEW -->
                                            <td class="text-center">
                                                <div class="form-check permission-checkbox mb-2">
                                                     <input type="hidden" name="permissions[<?= $moduleId ?>][views]" value="0">
                                                    <input
                                                        class="form-check-input permission-input"
                                                        type="checkbox"
                                                        name="permissions[<?= $moduleId ?>][views]"
                                                        value="1"
                                                        data-module-id="<?= $moduleId ?>" 
                                                        <?= $permission['views'] == 1 ? 'checked' : '' ?>>
                                                </div>
                                            </td>

                                            <!-- ADD -->
                                            <td class="text-center">
                                                <div class="form-check permission-checkbox mb-2">
                                                     <input type="hidden" name="permissions[<?= $moduleId ?>][adds]" value="0">
                                                    <input
                                                        class="form-check-input permission-input"
                                                        type="checkbox"
                                                        name="permissions[<?= $moduleId ?>][adds]"                                                        
                                                        value="1"
                                                        data-module-id="<?= $moduleId ?>" 
                                                        <?= $permission['adds'] == 1 ? 'checked' : '' ?>>
                                                </div>
                                            </td>

                                            <!-- UPDATE -->
                                            <td class="text-center">
                                                <div class="form-check permission-checkbox mb-2">
                                                     <input type="hidden" name="permissions[<?= $moduleId ?>][updates]" value="0">
                                                    <input
                                                        class="form-check-input permission-input"
                                                        type="checkbox"
                                                        name="permissions[<?= $moduleId ?>][updates]"
                                                        value="1"
                                                        data-module-id="<?= $moduleId ?>" 
                                                        <?= $permission['updates'] == 1 ? 'checked' : '' ?>>
                                                </div>
                                            </td>

                                            <!-- DELETE -->
                                            <td class="text-center">

                                                <div class="form-check permission-checkbox mb-2">
                                                     <input type="hidden" name="permissions[<?= $moduleId ?>][deletes]" value="0">
                                                    <input
                                                        class="form-check-input permission-input"
                                                        type="checkbox"
                                                        name="permissions[<?= $moduleId ?>][deletes]"
                                                        value="1"
                                                        data-module-id="<?= $moduleId ?>" 
                                                        <?= $permission['deletes'] == 1 ? 'checked' : '' ?>>
                                                </div>
                                            </td>


                                            <!-- CHECK ALL -->
                                            <td class="text-center">
                                                <div class="form-check permission-checkbox mb-2">
                                                    <input
                                                        class="form-check-input check-all"
                                                        type="checkbox"
                                                        data-module-id="<?= $moduleId ?>"
                                                        <?= $checkAll ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php
                                else:
                                    $moduleId = $mainModuleId;
                                    $permission = $existingPermissions[$moduleId] ?? [
                                        'views' => 0,
                                        'adds' => 0,
                                        'updates' => 0,
                                        'deletes' => 0
                                    ];
                                    $checkAll =
                                    $permission['views'] == 1 &&
                                    $permission['adds'] == 1 &&
                                    $permission['updates'] == 1 &&
                                    $permission['deletes'] == 1;
                                ?>

                                    <tr class="permission-row">
                                        <td class="submenu-title">
                                            <span class="submenu-circle"></span>
                                            <i data-lucide="folder" class="me-2"></i>
                                            <?= htmlspecialchars($mainModule['name']) ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check permission-checkbox mb-2">
                                                <input type="hidden" name="permissions[<?= $moduleId ?>][views]" value="0">
                                                <input
                                                    class="form-check-input permission-input"
                                                    type="checkbox"
                                                    name="permissions[<?= $moduleId ?>][views]"
                                                    value="1"
                                                    data-module-id="<?= $moduleId ?>" 
                                                    <?= $permission['views'] == 1 ? 'checked' : '' ?>>
                                            </div>
                                        </td>

                                        <!-- ADD -->
                                        <td class="text-center">
                                            <div class="form-check permission-checkbox mb-2">
                                                <input type="hidden" name="permissions[<?= $moduleId ?>][adds]" value="0">
                                                <input
                                                    class="form-check-input permission-input"
                                                    type="checkbox"
                                                    name="permissions[<?= $moduleId ?>][adds]"
                                                    value="1"
                                                    data-module-id="<?= $moduleId ?>" 
                                                    <?= $permission['adds'] == 1 ? 'checked' : '' ?>>
                                            </div>
                                        </td>

                                        <!-- UPDATE -->
                                        <td class="text-center">
                                            <div class="form-check permission-checkbox mb-2">
                                                <input type="hidden" name="permissions[<?= $moduleId ?>][updates]" value="0">
                                                <input
                                                    class="form-check-input permission-input"
                                                    type="checkbox"
                                                    name="permissions[<?= $moduleId ?>][updates]"
                                                    value="1"
                                                    data-module-id="<?= $moduleId ?>" 
                                                    <?= $permission['updates'] == 1 ? 'checked' : '' ?>>
                                            </div>
                                        </td>

                                        <!-- DELETE -->
                                        <td class="text-center">
                                            <div class="form-check permission-checkbox mb-2">
                                                <input type="hidden" name="permissions[<?= $moduleId ?>][deletes]" value="0">
                                                <input
                                                    class="form-check-input permission-input"
                                                    type="checkbox"
                                                    name="permissions[<?= $moduleId ?>][deletes]"
                                                    value="1"
                                                    data-module-id="<?= $moduleId ?>" 
                                                    <?= $permission['deletes'] == 1 ? 'checked' : '' ?>>
                                            </div>
                                        </td>

                                        <!-- CHECK ALL -->
                                        <td class="text-center">
                                            <div class="form-check permission-checkbox mb-2">
                                                <input
                                                    class="form-check-input check-all"
                                                    type="checkbox"
                                                    data-module-id="<?= $moduleId ?>"
                                                    <?= $checkAll ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-end">
                    <button type="submit"
                        class="btn btn-primary"
                        id="saveTeamRole">
                        <i data-lucide="save" class="me-1"></i>
                        Save Permissions
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    $(document).on('submit', '#teamRoleForm', function(e) {

        e.preventDefault();

        const form = this;
        const submitBtn = $('#saveTeamRole');

        // FormData automatically collects checkbox arrays
        const formData = new FormData(form);
        formData.append('action', 'add_permission');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: SITE_URL + 'admin/setting/teamrole/store.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',

            success: function(response) {

                if (response.status === true) {

                    showToast(
                        response.message,
                        'success'
                    );

                    setTimeout(function() {
                        window.location.href = SITE_URL + 'teamrole';
                    }, 1000);

                } else {

                    showToast(
                        response.message,
                        'error'
                    );
                }
            },

            error: function(xhr) {

                console.log(xhr.responseText);

                showToast(
                    'Something went wrong. Please try again.',
                    'error'
                );
            },

            complete: function() {
                submitBtn.prop('disabled', false);
            }
        });

    });
    // CHECK ALL → Check/Uncheck all permissions
    $(document).on('change', '.check-all', function() {

        const moduleId = $(this).data('module-id');
        const isChecked = $(this).is(':checked');

        $('.permission-input[data-module-id="' + moduleId + '"]')
            .prop('checked', isChecked);

    });


    // Individual permission → Update CHECK ALL
    $(document).on('change', '.permission-input', function() {

        const moduleId = $(this).data('module-id');

        const total = $('.permission-input[data-module-id="' + moduleId + '"]').length;

        const checked = $('.permission-input[data-module-id="' + moduleId + '"]:checked').length;

        $('.check-all[data-module-id="' + moduleId + '"]')
            .prop('checked', total === checked);

    });
</script>
</body>

</html>