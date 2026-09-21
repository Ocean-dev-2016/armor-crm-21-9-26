<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$search = $_POST['search']['value'] ?? '';

$tbl = 'state';

$canEdit   = hasPermission('state', 'updates');
$canDelete = hasPermission('state', 'deletes');

$totalRow = db_row("SELECT COUNT(*) AS total FROM $tbl");
$recordsTotal = (int) $totalRow['total'];
$where = "";

if ($search !== '') {
    $search = db_escape($search);
    $where = "WHERE (s.name LIKE '%$search%' OR c.name LIKE '%$search%')";
}

$filteredRow = db_row("SELECT COUNT(*) AS total FROM $tbl s LEFT JOIN country AS c ON c.id = s.country_id $where");
$recordsFiltered = (int) $filteredRow['total'];
$columns = [
    0 => null,
    1 => 'c.name',
    2 => 's.name',
    3 => 's.status',
    4 => null
];

$orderColumn = 's.id';
$orderDirection = 'DESC';
if (isset($_POST['order'][0]['column'])) {
    $columnIndex = (int) $_POST['order'][0]['column'];
    $direction = $_POST['order'][0]['dir'] ?? 'desc';
    if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
        $orderColumn = $columns[$columnIndex];
        $orderDirection = ($direction === 'asc') ? 'ASC' : 'DESC';
    }
}

$sql = "SELECT s.id, s.name, s.country_id, s.status, c.name as country_name FROM $tbl s LEFT JOIN country AS c ON c.id = s.country_id
        $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";

$result = db_rows($sql);
$data = [];
$srNo = $start + 1;
foreach($result as $row){    
    $actionItems = '';
    if ($canEdit) {
        $actionItems .= '<li>
            <a class="dropdown-item text-primary state_edit" href="javascript:void(0);" data-id="'.encrypt_id($row['id']).'">
               <i data-lucide="edit" class="fs-14"></i> Edit
            </a>
        </li>';
    }
    if ($canDelete) {
        $actionItems .= '<li>
            <a class="dropdown-item text-danger delete-record" href="javascript:void(0);" data-id="'.encrypt_id($row['id']).'" data-tbl="'.$tbl.'">
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

    $data[] = [
        $srNo,
        htmlspecialchars($row['country_name'] ?? ''),
        htmlspecialchars($row['name'] ?? ''),
        '<div class="form-check form-switch mb-3">
                  <input class="form-check-input change_status" type="checkbox" role="switch"  ' . ($row['status'] == 1 ? 'checked' : '') . ' data-id="' . encrypt_id($row['id']) . '" data-tbl="'.$tbl.'">
                </div>',
        $actionHtml
    ];
    $srNo++;
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data'             => $data
]);
