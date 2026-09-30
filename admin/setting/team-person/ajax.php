<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'users';
$canEdit   = hasPermission('team-person', 'updates');
$canDelete = hasPermission('team-person', 'deletes');

$baseConditions = ["u.user_type = 'company_admin'"];
if (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "u.company_id = " . (int)$_SESSION['company_id'];
}

$columns = [
    0 => null,
    1 => 'c.name',
    2 => 'u.name',
    3 => 'u.email',
    4 => 'u.mobile_no',
    5 => 'cr.name',
    6 => 's.name',
    7 => 'ci.name',
    8 => 'r.name',
    9 => 'u.status',
    10 => null
];

$joins = "LEFT JOIN company c ON c.id = u.company_id 
          LEFT JOIN roles r ON r.id = u.role_id 
          LEFT JOIN country cr ON cr.id = u.country_id 
          LEFT JOIN state s ON s.id = u.state_id 
          LEFT JOIN city ci ON ci.id = u.city_id";

$select = "u.*, c.name as company_name, r.name as role_name, cr.name as country_name, s.name as state_name, ci.name as city_name";

handle_datatable([
    'table'                => "$tbl u",
    'joins'                => $joins,
    'select'               => $select,
    'base_conditions'      => $baseConditions,
    'search_columns'       => ['u.name', 'u.username', 'u.email', 'u.mobile_no', 'c.name', 'r.name'],
    'order_columns'        => $columns,
    'default_order_column' => 'u.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete) {
        $encId = encrypt_id($row['id']);

        return [
            $srNo,
            htmlspecialchars($row['company_name'] ?? '-'),
            htmlspecialchars($row['name'] ?? ''),
            htmlspecialchars($row['email'] ?? ''),
            htmlspecialchars($row['mobile_no'] ?? ''),
            htmlspecialchars($row['country_name'] ?? '-'),
            htmlspecialchars($row['state_name'] ?? '-'),
            htmlspecialchars($row['city_name'] ?? '-'),
            htmlspecialchars($row['role_name'] ?? '-'),
            dt_status_switch($encId, $row['status'], $tbl, true),
            dt_action_dropdown($encId, $tbl, [
                'can_edit'     => $canEdit,
                'can_delete'   => $canDelete,
                'is_encrypted' => true,
                'edit_url'     => SITE_URL . 'team-person/edit/' . $encId
            ])
        ];
    }
]);
