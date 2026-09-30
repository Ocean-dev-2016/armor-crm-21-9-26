<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'company_type';
$moduleKey = 'company-type';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');

$columns = [
    0 => null,
    1 => 'name',
    2 => 'status',
    3 => null
];

handle_datatable([
    'table'                => $tbl,
    'search_columns'       => ['name'],
    'order_columns'        => $columns,
    'default_order_column' => 'id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete) {
        return [
            $srNo,
            htmlspecialchars($row['name'] ?? ''),
            dt_status_switch($row['id'], $row['status'], $tbl),
            dt_action_dropdown($row['id'], $tbl, [
                'can_edit'   => $canEdit,
                'can_delete' => $canDelete,
                'edit_class' => 'companytype_edit'
            ])
        ];
    }
]);
