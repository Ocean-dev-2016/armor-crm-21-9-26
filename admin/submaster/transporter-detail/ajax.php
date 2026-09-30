<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'transporter_detail';
$moduleKey = 'transporter-detail';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "td.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['td.name', 'tb.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'tb.name',
        3 => 'td.name',
        4 => 'td.status',
        5 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'tb.name',
        2 => 'td.name',
        3 => 'td.status',
        4 => null
    ];
}

handle_datatable([
    'table'                => "$tbl td",
    'joins'                => "LEFT JOIN transport_by tb ON tb.id = td.transport_by_id LEFT JOIN company c ON c.id = td.company_id",
    'select'               => "td.*, tb.name AS transport_by_name, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'td.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $rowItem = [$srNo];
        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }
        $rowItem[] = htmlspecialchars($row['transport_by_name'] ?? '-');
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = dt_status_switch($row['id'], $row['status'], $tbl);
        $rowItem[] = dt_action_dropdown($row['id'], $tbl, [
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'edit_class' => 'transporter_detail_edit'
        ]);

        return $rowItem;
    }
]);
