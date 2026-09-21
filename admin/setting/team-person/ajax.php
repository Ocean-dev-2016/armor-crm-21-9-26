<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
$draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$search = $_POST['search']['value'] ?? '';

$tbl = 'users';
$canEdit   = hasPermission('team-person', 'updates');
$canDelete = hasPermission('team-person', 'deletes');

$baseConditions = ["u.user_type = 'company_admin'"];
if (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "u.company_id = " . (int)$_SESSION['company_id'];
}
$baseWhere = "WHERE " . implode(" AND ", $baseConditions);

$totalRow = db_row("SELECT COUNT(*) AS total FROM $tbl u $baseWhere");
$recordsTotal = (int) ($totalRow['total'] ?? 0);
$where = $baseWhere;

if ($search !== '') {
    $search = db_escape($search);
    $where .= " AND (u.name LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%' OR u.mobile_no LIKE '%$search%' OR c.name LIKE '%$search%' OR r.name LIKE '%$search%')";
}

$filteredRow = db_row("SELECT COUNT(*) AS total FROM $tbl u 
    LEFT JOIN company c ON c.id = u.company_id 
    LEFT JOIN roles r ON r.id = u.role_id 
    $where");
$recordsFiltered = (int) ($filteredRow['total'] ?? 0);
$columns = [
    0 => null,
    1 => 'c.name',
    2 => 'u.name',
    3 => 'u.email',
    4 => 'u.mobile_no',
    5 => 'cr.name',
    6 => 's.name',
    7 => 'ci.name',
    8 => 'r.name',
    9 => 'u.status',
    10 => null
];

$orderColumn = 'u.id';
$orderDirection = 'DESC';
if (isset($_POST['order'][0]['column'])) {
    $columnIndex = (int) $_POST['order'][0]['column'];
    $direction = $_POST['order'][0]['dir'] ?? 'desc';
    if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
        $orderColumn = $columns[$columnIndex];
        $orderDirection = ($direction === 'asc') ? 'ASC' : 'DESC';
    }
}

$sql = "SELECT u.*, c.name as company_name, r.name as role_name, cr.name as country_name, s.name as state_name, ci.name as city_name 
        FROM $tbl u 
        LEFT JOIN company c ON c.id = u.company_id 
        LEFT JOIN roles r ON r.id = u.role_id 
        LEFT JOIN country cr ON cr.id = u.country_id 
        LEFT JOIN state s ON s.id = u.state_id 
        LEFT JOIN city ci ON ci.id = u.city_id 
        $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";
$result = db_rows($sql);

$data = [];
$srNo = $start + 1;
foreach($result as $row){    
    $actionItems = '';
    if ($canEdit) {
        $actionItems .= '<li>
            <a class="dropdown-item text-primary" href="'.SITE_URL.'team-person/edit/' . encrypt_id($row['id']) . '">
                <i data-lucide="edit" class="fs-14"></i> Edit
            </a>
        </li>';
    }
    if ($canDelete) {
        $actionItems .= '<li>
            <a class="dropdown-item text-danger delete-record" href="javascript:void(0);" data-id="'.encrypt_id($row['id']).'" data-tbl="users">
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
        htmlspecialchars($row['company_name'] ?? '-'),
        htmlspecialchars($row['name'] ?? ''),
        htmlspecialchars($row['email'] ?? ''),
        htmlspecialchars($row['mobile_no'] ?? ''),
        htmlspecialchars($row['country_name'] ?? '-'),
        htmlspecialchars($row['state_name'] ?? '-'),
        htmlspecialchars($row['city_name'] ?? '-'),
        htmlspecialchars($row['role_name'] ?? '-'),
        '<div class="form-check form-switch mb-3">
                  <input class="form-check-input change_status" type="checkbox" role="switch"  ' . ($row['status'] == 1 ? 'checked' : '') . ' data-id="' . encrypt_id($row['id']) . '" data-tbl="users">
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
