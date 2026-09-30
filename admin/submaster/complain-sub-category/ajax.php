<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'complain_sub_category';
$moduleKey = 'complain-sub-category';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "csc.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['csc.name', 'cc.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'cc.name',
        3 => 'csc.name',
        4 => 'csc.status',
        5 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'cc.name',
        2 => 'csc.name',
        3 => 'csc.status',
        4 => null
    ];
}

handle_datatable([
    'table'                => "$tbl csc",
    'joins'                => "LEFT JOIN complain_category cc ON cc.id = csc.complain_category_id LEFT JOIN company c ON c.id = csc.company_id",
    'select'               => "csc.*, cc.name AS complain_category_name, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'csc.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $rowItem = [$srNo];
        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }
        $rowItem[] = htmlspecialchars($row['complain_category_name'] ?? '-');
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = dt_status_switch($row['id'], $row['status'], $tbl);
        $rowItem[] = dt_action_dropdown($row['id'], $tbl, [
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'edit_class' => 'complain_sub_category_edit'
        ]);

        return $rowItem;
    }
]);
