<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'module';
$canEdit   = hasPermission('module', 'updates');
$canDelete = hasPermission('module', 'deletes');

$columns = [
    0 => null,
    1 => 'name',
    2 => null
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
            dt_action_dropdown($row['id'], $tbl, [
                'can_edit'   => $canEdit,
                'can_delete' => $canDelete,
                'edit_class' => 'module_edit'
            ])
        ];
    }
]);
