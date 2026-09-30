<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'variant';
$canEdit   = hasPermission('variant', 'updates');
$canDelete = hasPermission('variant', 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "v.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['v.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'v.name',
        3 => 'v.status',
        4 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'v.name',
        2 => 'v.status',
        3 => null
    ];
}

handle_datatable([
    'table'                => "$tbl v",
    'joins'                => "LEFT JOIN company c ON c.id = v.company_id",
    'select'               => "v.*, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'v.order_by',
    'default_order_dir'    => 'ASC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $rowItem = [
            'DT_RowId'   => 'row_' . $row['id'],
            'DT_RowData' => ['id' => $row['id']],
            0            => dt_drag_sr_no($srNo)
        ];

        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = dt_status_switch($row['id'], $row['status'], $tbl);
        $rowItem[] = dt_action_dropdown($row['id'], $tbl, [
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'edit_class' => 'variant_edit'
        ]);

        return $rowItem;
    }
]);
