<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$search = $_POST['search']['value'] ?? '';

$tbl = 'product';
$canEdit   = hasPermission('product', 'updates');
$canDelete = hasPermission('product', 'deletes');

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "p.company_id = " . (int)$_SESSION['company_id'];
}
$baseWhere = !empty($baseConditions) ? "WHERE " . implode(" AND ", $baseConditions) : "";

$totalRow = db_row("SELECT COUNT(*) AS total FROM $tbl p $baseWhere");
$recordsTotal = (int) ($totalRow['total'] ?? 0);

$conditions = $baseConditions;
if ($search !== '') {
    $search = db_escape($search);
    if ($isSuperadmin) {
        $conditions[] = "(p.name LIKE '%$search%' OR cat.name LIKE '%$search%' OR sub.name LIKE '%$search%' OR c.name LIKE '%$search%' OR p.hsn_code LIKE '%$search%')";
    } else {
        $conditions[] = "(p.name LIKE '%$search%' OR cat.name LIKE '%$search%' OR sub.name LIKE '%$search%' OR p.hsn_code LIKE '%$search%')";
    }
}
$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$joinSql = "FROM $tbl p 
            LEFT JOIN company c ON c.id = p.company_id 
            LEFT JOIN category cat ON cat.id = p.category_id 
            LEFT JOIN sub_category sub ON sub.id = p.sub_category_id 
            LEFT JOIN tax t ON t.id = p.tax_id";

$filteredRow = db_row("SELECT COUNT(*) AS total $joinSql $where");
$recordsFiltered = (int) ($filteredRow['total'] ?? 0);

if ($isSuperadmin) {
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

$orderColumn = 'p.id';
$orderDirection = 'DESC';
if (isset($_POST['order'][0]['column'])) {
    $columnIndex = (int) $_POST['order'][0]['column'];
    $direction = $_POST['order'][0]['dir'] ?? 'desc';
    if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
        $orderColumn = $columns[$columnIndex];
        $orderDirection = ($direction === 'asc') ? 'ASC' : 'DESC';
    }
}

$sql = "SELECT p.*, c.name AS company_name, cat.name AS category_name, sub.name AS sub_category_name, t.name AS tax_name, t.value AS tax_value
        $joinSql 
        $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";
$result = db_rows($sql);
$data = [];
$srNo = $start + 1;

foreach ($result as $row) {
    $actionItems = '';
    if ($canEdit) {
        $actionItems .= '<li>
            <a class="dropdown-item text-primary" href="' . SITE_URL . 'product/edit/' . encrypt_id($row['id']) . '">
               <i data-lucide="edit" class="fs-14"></i> Edit
            </a>
        </li>';
    }
    if ($canDelete) {
        $actionItems .= '<li>
            <a class="dropdown-item text-danger delete-record" href="javascript:void(0);" data-id="' . encrypt_id($row['id']) . '" data-tbl="' . $tbl . '">
               <i data-lucide="trash-2" class="fs-14"></i> Delete
            </a>
        </li>';
    }

    if (!empty($actionItems)) {
        $actionHtml = '
        <div class="dropdown">
            <button class="btn btn-icon btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i data-lucide="more-horizontal" class="fs-16"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                ' . $actionItems . '
            </ul>
        </div>';
    } else {
        $actionHtml = '<span class="text-muted">-</span>';
    }

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
        '1'    => 'With Variant',
        '2' => 'Without Variant'
    ];

    $typeBadge = '<span class="badge bg-primary">' . $types[$row['type']] . '</span>';

    $rowItem = [$srNo];
    if ($isSuperadmin) {
        $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
    }
    $rowItem[] = htmlspecialchars($row['name'] ?? '');
    $rowItem[] = $typeBadge;
    $rowItem[] = htmlspecialchars($row['category_name'] ?? '-');
    $rowItem[] = htmlspecialchars($row['sub_category_name'] ?? '-');
    $rowItem[] = $imageHtml;
    $rowItem[] = '<div class="form-check form-switch mb-0">
        <input class="form-check-input change_status" type="checkbox" role="switch" ' . ($row['status'] == 1 ? 'checked' : '') . ' data-id="' . encrypt_id($row['id']) . '" data-tbl="' . $tbl . '">
    </div>';
    $rowItem[] = $actionHtml;

    $data[] = $rowItem;
    $srNo++;
}

echo json_encode([
    "draw"            => $draw,
    "recordsTotal"    => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data"            => $data
]);
exit;
