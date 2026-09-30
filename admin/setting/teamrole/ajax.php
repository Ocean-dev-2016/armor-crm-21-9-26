<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'roles';
$canEdit   = hasPermission('teamrole', 'updates');
$canDelete = hasPermission('teamrole', 'deletes');

$baseConditions = [];
if (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "r.company_id = " . (int)$_SESSION['company_id'] . " AND r.parent_id != 0";
}

$columns = [
    0 => null,
    1 => 'c.name',
    2 => 'pr.name',
    3 => 'r.name',
    4 => 'r.status',
    5 => null
];

$joins = "LEFT JOIN company AS c ON c.id = r.company_id 
          LEFT JOIN roles AS pr ON pr.id = r.parent_id 
          LEFT JOIN users AS u ON u.id = r.created_by";

$select = "r.id, r.name, r.parent_id, r.created_by, r.status, c.name as company_name, pr.name as parent_role_name, u.username as created_by_username";

handle_datatable([
    'table'                => "$tbl r",
    'joins'                => $joins,
    'select'               => $select,
    'base_conditions'      => $baseConditions,
    'search_columns'       => ['r.name', 'c.name', 'u.username', 'pr.name'],
    'order_columns'        => $columns,
    'default_order_column' => 'r.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete) {
        $parentDisplay = ((int)($row['parent_id'] ?? 0) === 0) 
            ? ($row['created_by_username'] ?? '') 
            : ($row['parent_role_name'] ?? '');

        $encId = encrypt_id($row['id']);
        $settingItem = '<li>
            <a class="dropdown-item text-info" href="' . SITE_URL . 'teamrole/setting/' . $encId . '">
               <i data-lucide="settings" class="fs-14"></i> Setting
            </a>
        </li>';

        return [
            $srNo,
            htmlspecialchars($row['company_name'] ?? ''),
            htmlspecialchars($parentDisplay),
            htmlspecialchars($row['name'] ?? ''),
            dt_status_switch($encId, $row['status'], $tbl, true),
            dt_action_dropdown($encId, $tbl, [
                'can_edit'     => $canEdit,
                'can_delete'   => $canDelete,
                'edit_class'   => 'team_role_edit',
                'is_encrypted' => true,
                'extra_items'  => $settingItem
            ])
        ];
    }
]);
