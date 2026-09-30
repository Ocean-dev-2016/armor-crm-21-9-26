<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'category';
$canEdit   = hasPermission('category', 'updates');
$canDelete = hasPermission('category', 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// Company-wise condition for non-superadmin
$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "cat.company_id = " . (int)$_SESSION['company_id'];
}

// Columns for searching & ordering
$searchColumns = ['cat.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'cat.name',
        3 => 'cat.image',
        4 => 'cat.status',
        5 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'cat.name',
        2 => 'cat.image',
        3 => 'cat.status',
        4 => null
    ];
}

handle_datatable([
    'table'                => "$tbl cat",
    'joins'                => "LEFT JOIN company c ON c.id = cat.company_id",
    'select'               => "cat.*, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'cat.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        // Image display
        if (!empty($row['image'])) {
            $imgPath = SITE_URL . 'uploads/category/' . htmlspecialchars($row['image']);
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
            'edit_class' => 'category_edit'
        ]);

        return $rowItem;
    }
]);
