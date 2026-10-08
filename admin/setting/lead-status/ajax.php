<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'lead_status';
$moduleKey = 'lead-status';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$canEdit   = $isSuperadmin || hasPermission($moduleKey, 'updates');
$canDelete = $isSuperadmin || hasPermission($moduleKey, 'deletes');

$columns = [
    0 => null,
    1 => 'name',
    2 => 'color',
    3 => 'status',
    4 => null
];

handle_datatable([
    'table'                => $tbl,
    'search_columns'       => ['name', 'color'],
    'order_columns'        => $columns,
    'default_order_column' => 'order_by',
    'default_order_dir'    => 'ASC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete) {
        $colorCode = !empty($row['color']) ? htmlspecialchars($row['color']) : '#0e5a6c';
        
        $colorBadge = '<div class="d-flex align-items-center gap-2">
            <span class="rounded-circle d-inline-block border" style="width: 18px; height: 18px; background-color: ' . $colorCode . ';"></span>
            <span class="font-monospace fs-12">' . $colorCode . '</span>
        </div>';

        return [
            'DT_RowId'   => 'row_' . $row['id'],
            'DT_RowData' => ['id' => $row['id']],
            0            => dt_drag_sr_no($srNo),
            1            => htmlspecialchars($row['name'] ?? ''),
            2            => $colorBadge,
            3            => dt_status_switch($row['id'], $row['status'], $tbl),
            4            => dt_action_dropdown($row['id'], $tbl, [
                'can_edit'   => $canEdit,
                'can_delete' => $canDelete,
                'edit_class' => 'lead_status_edit'
            ])
        ];
    }
]);
