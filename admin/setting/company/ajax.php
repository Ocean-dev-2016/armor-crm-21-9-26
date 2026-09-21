<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$search = $_POST['search']['value'] ?? '';

$tbl = 'company';
$canEdit   = hasPermission('company', 'updates');
$canDelete = hasPermission('company', 'deletes');

$totalRow = db_row("SELECT COUNT(*) AS total FROM $tbl");
$recordsTotal = (int) $totalRow['total'];
$where = "";

if ($search !== '') {
    $search = db_escape($search);
    $where = "WHERE (c.name LIKE '%$search%' 
                 OR c.person_name LIKE '%$search%' 
                 OR c.mobile_no LIKE '%$search%' 
                 OR c.email LIKE '%$search%' 
                 OR cr.name LIKE '%$search%' 
                 OR s.name LIKE '%$search%' 
                 OR ci.name LIKE '%$search%' 
                 OR p.name LIKE '%$search%')";
}

$filteredRow = db_row("SELECT COUNT(*) AS total FROM $tbl c 
                       LEFT JOIN country cr ON cr.id = c.country_id 
                       LEFT JOIN state s ON s.id = c.state_id 
                       LEFT JOIN city ci ON ci.id = c.city_id 
                       LEFT JOIN plan p ON p.id = c.plan_id 
                       $where");
$recordsFiltered = (int) $filteredRow['total'];
$columns = [
    0 => null,
    1 => 'c.name',
    2 => 'c.person_name',
    3 => 'c.mobile_no',
    4 => 'c.email',
    5 => 'cr.name',
    6 => 's.name',
    7 => 'ci.name',
    8 => 'p.name',
    9 => 'c.status',
    10 => null
];

$orderColumn = 'c.id';
$orderDirection = 'DESC';
if (isset($_POST['order'][0]['column'])) {
    $columnIndex = (int) $_POST['order'][0]['column'];
    $direction = $_POST['order'][0]['dir'] ?? 'desc';
    if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
        $orderColumn = $columns[$columnIndex];
        $orderDirection = ($direction === 'asc') ? 'ASC' : 'DESC';
    }
}

$sql = "SELECT c.*, cr.name as country_name, s.name as state_name, ci.name as city_name, p.name as plan_name FROM $tbl c 
        LEFT JOIN country cr ON cr.id = c.country_id 
        LEFT JOIN state s ON s.id = c.state_id 
        LEFT JOIN city ci ON ci.id = c.city_id 
        LEFT JOIN plan p ON p.id = c.plan_id $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";

$result = db_rows($sql);
$data = [];
$srNo = $start + 1;
foreach($result as $row){    
    $actionItems = '';
    if ($canEdit) {
        $actionItems .= '<li>
            <a class="dropdown-item text-primary" href="'.SITE_URL.'company/edit/' . encrypt_id($row['id']) . '">
               <i data-lucide="edit" class="fs-14"></i> Edit
            </a>
        </li>';
    }
    if ($canDelete) {
        $actionItems .= '<li>
            <a class="dropdown-item text-danger delete-record" href="javascript:void(0);" data-id="'.encrypt_id($row['id']).'" data-tbl="company">
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
        htmlspecialchars($row['name'] ?? ''),
        htmlspecialchars($row['person_name'] ?? ''),
        htmlspecialchars($row['mobile_no'] ?? ''),
        htmlspecialchars($row['email'] ?? ''),
        htmlspecialchars($row['country_name'] ?? ''),
        htmlspecialchars($row['state_name'] ?? ''),
        htmlspecialchars($row['city_name'] ?? ''),
        htmlspecialchars($row['plan_name'] ?? '-'),
        '<div class="form-check form-switch mb-3">
                  <input class="form-check-input change_status" type="checkbox" role="switch"  ' . ($row['status'] == 1 ? 'checked' : '') . ' data-id="' . encrypt_id($row['id']) . '" data-tbl="company">
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
