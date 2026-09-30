<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'product';
$canEdit   = hasPermission('product', 'updates');
$canDelete = hasPermission('product', 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "p.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['p.name', 'cat.name', 'sub.name', 'p.hsn_code'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'p.name',
        3 => 'p.type',
        4 => 'cat.name',
        5 => 'sub.name',
        6 => 'p.image',
        7 => 'p.status',
        8 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'p.name',
        2 => 'p.type',
        3 => 'cat.name',
        4 => 'sub.name',
        5 => 'p.image',
        6 => 'p.status',
        7 => null
    ];
}

$joins = "LEFT JOIN company c ON c.id = p.company_id 
          LEFT JOIN category cat ON cat.id = p.category_id 
          LEFT JOIN sub_category sub ON sub.id = p.sub_category_id 
          LEFT JOIN tax t ON t.id = p.tax_id";

$select = "p.*, c.name AS company_name, cat.name AS category_name, sub.name AS sub_category_name, t.name AS tax_name, t.value AS tax_value";

handle_datatable([
    'table'                => "$tbl p",
    'joins'                => $joins,
    'select'               => $select,
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'p.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $encId = encrypt_id($row['id']);

        // Image display
        if (!empty($row['image']) && file_exists(BASE_PATH . '/uploads/product/' . $row['image'])) {
            $imgUrl = SITE_URL . 'uploads/product/' . $row['image'];
            $imageHtml = '<a href="' . $imgUrl . '" target="_blank">
                <img src="' . $imgUrl . '" alt="' . htmlspecialchars($row['name']) . '" class="table-img-thumb">
            </a>';
        } else {
            $imageHtml = '<div class="table-img-placeholder bg-light rounded text-muted">
                <i data-lucide="image" class="fs-16"></i>
            </div>';
        }

        $types = [
            '1' => 'With Variant',
            '2' => 'Without Variant'
        ];
        $typeBadge = '<span class="badge bg-primary">' . ($types[$row['type']] ?? '-') . '</span>';

        $rowItem = [$srNo];
        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = $typeBadge;
        $rowItem[] = htmlspecialchars($row['category_name'] ?? '-');
        $rowItem[] = htmlspecialchars($row['sub_category_name'] ?? '-');
        $rowItem[] = $imageHtml;
        $rowItem[] = dt_status_switch($encId, $row['status'], $tbl, true);
        $rowItem[] = dt_action_dropdown($encId, $tbl, [
            'can_edit'     => $canEdit,
            'can_delete'   => $canDelete,
            'is_encrypted' => true,
            'edit_url'     => SITE_URL . 'product/edit/' . $encId
        ]);

        return $rowItem;
    }
]);
