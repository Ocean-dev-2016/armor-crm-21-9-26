<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'followup_reason';
$moduleKey = 'followup-reason';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "fr.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['fr.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'fr.name',
        3 => 'fr.status',
        4 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'fr.name',
        2 => 'fr.status',
        3 => null
    ];
}

handle_datatable([
    'table'                => "$tbl fr",
    'joins'                => "LEFT JOIN company c ON c.id = fr.company_id",
    'select'               => "fr.*, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'fr.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $rowItem = [$srNo];
        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = dt_status_switch($row['id'], $row['status'], $tbl);
        $rowItem[] = dt_action_dropdown($row['id'], $tbl, [
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'edit_class' => 'followupreason_edit'
        ]);

        return $rowItem;
    }
]);
