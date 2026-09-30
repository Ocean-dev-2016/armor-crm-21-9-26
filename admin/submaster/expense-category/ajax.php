<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'expense_category';
$moduleKey = 'expense-category';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "ec.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['ec.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'ec.name',
        3 => 'ec.image',
        4 => 'ec.status',
        5 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'ec.name',
        2 => 'ec.image',
        3 => 'ec.status',
        4 => null
    ];
}

handle_datatable([
    'table'                => "$tbl ec",
    'joins'                => "LEFT JOIN company c ON c.id = ec.company_id",
    'select'               => "ec.*, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'ec.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $imageHtml = '-';
        if (!empty($row['image'])) {
            $imgPath = SITE_URL . 'uploads/expense_category/' . htmlspecialchars($row['image']);
            $imageHtml = '<a href="' . $imgPath . '" target="_blank">
                <img src="' . $imgPath . '" alt="' . htmlspecialchars($row['name']) . '" class="table-img-thumb">
            </a>';
        } else {
            $imageHtml = '<span class="avatar avatar-sm rounded bg-light text-muted table-img-placeholder">
                <i data-lucide="image" class="fs-18"></i>
            </span>';
        }

        $rowItem = [$srNo];
        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = $imageHtml;
        $rowItem[] = dt_status_switch($row['id'], $row['status'], $tbl);
        $rowItem[] = dt_action_dropdown($row['id'], $tbl, [
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'edit_class' => 'expense_category_edit'
        ]);

        return $rowItem;
    }
]);
