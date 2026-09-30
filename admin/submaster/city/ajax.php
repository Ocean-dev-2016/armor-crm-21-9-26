<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'city';
$canEdit   = hasPermission('city', 'updates');
$canDelete = hasPermission('city', 'deletes');

$columns = [
    0 => null,
    1 => 'c.name',
    2 => 'st.name',
    3 => 's.name',
    4 => 's.status',
    5 => null
];

handle_datatable([
    'table'                => "$tbl s",
    'joins'                => "LEFT JOIN country AS c ON c.id = s.country_id LEFT JOIN state AS st ON st.id = s.state_id",
    'select'               => "s.id, s.name, s.country_id, s.state_id, s.status, c.name as country_name, st.name as state_name",
    'search_columns'       => ['s.name', 'c.name', 'st.name'],
    'order_columns'        => $columns,
    'default_order_column' => 's.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete) {
        return [
            $srNo,
            htmlspecialchars($row['country_name'] ?? ''),
            htmlspecialchars($row['state_name'] ?? ''),
            htmlspecialchars($row['name'] ?? ''),
            dt_status_switch($row['id'], $row['status'], $tbl),
            dt_action_dropdown($row['id'], $tbl, [
                'can_edit'   => $canEdit,
                'can_delete' => $canDelete,
                'edit_class' => 'city_edit'
            ])
        ];
    }
]);
