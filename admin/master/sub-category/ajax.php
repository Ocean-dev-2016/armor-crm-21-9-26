<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'sub_category';
$canEdit   = hasPermission('sub-category', 'updates');
$canDelete = hasPermission('sub-category', 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "sc.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['sc.name', 'cat.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'cat.name',
        3 => 'sc.name',
        4 => 'sc.image',
        5 => 'sc.status',
        6 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'cat.name',
        2 => 'sc.name',
        3 => 'sc.image',
        4 => 'sc.status',
        5 => null
    ];
}

handle_datatable([
    'table'                => "$tbl sc",
    'joins'                => "LEFT JOIN category cat ON cat.id = sc.category_id LEFT JOIN company c ON c.id = sc.company_id",
    'select'               => "sc.*, cat.name AS category_name, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'sc.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $imageHtml = '-';
        if (!empty($row['image'])) {
            $imgPath = SITE_URL . 'uploads/subcategory/' . htmlspecialchars($row['image']);
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
        $rowItem[] = htmlspecialchars($row['category_name'] ?? '-');
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = $imageHtml;
        $rowItem[] = dt_status_switch($row['id'], $row['status'], $tbl);
        $rowItem[] = dt_action_dropdown($row['id'], $tbl, [
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'edit_class' => 'sub_category_edit'
        ]);

        return $rowItem;
    }
]);
