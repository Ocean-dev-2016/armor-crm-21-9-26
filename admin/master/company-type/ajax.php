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

$tbl = 'company_type';
$moduleKey = 'company-type';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');

$totalRow = db_row("SELECT COUNT(*) AS total FROM $tbl");
$recordsTotal = (int) ($totalRow['total'] ?? 0);

$conditions = [];
if ($search !== '') {
    $search = db_escape($search);
    $conditions[] = "name LIKE '%$search%'";
}
$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$filteredRow = db_row("SELECT COUNT(*) AS total FROM $tbl $where");
$recordsFiltered = (int) ($filteredRow['total'] ?? 0);


$columns = [
    0 => null,
    1 => 'name',
    2 => 'status',
    3 => null
];

$orderColumn = 'id';
$orderDirection = 'DESC';
if (isset($_POST['order'][0]['column'])) {
    $columnIndex = (int) $_POST['order'][0]['column'];
    $direction = $_POST['order'][0]['dir'] ?? 'desc';
    if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
        $orderColumn = $columns[$columnIndex];
        $orderDirection = ($direction === 'asc') ? 'ASC' : 'DESC';
    }
}

$sql = "SELECT * FROM $tbl $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";
$result = db_rows($sql);
$data = [];
$srNo = $start + 1;

foreach ($result as $row) {
    $actionItems = '';
    if ($canEdit) {
        $actionItems .= '<li>
            <a class="dropdown-item text-primary companytype_edit" href="javascript:void(0);" data-id="' . encrypt_id($row['id']) . '">
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

    $rowItem = [$srNo];
    $rowItem[] = htmlspecialchars($row['name'] ?? '');
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
