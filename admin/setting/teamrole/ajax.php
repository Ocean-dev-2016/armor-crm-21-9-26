<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$search = $_POST['search']['value'] ?? '';
$tbl = 'roles';
$canEdit   = hasPermission('teamrole', 'updates');
$canDelete = hasPermission('teamrole', 'deletes');

$baseConditions = [];
if (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "r.company_id = " . (int)$_SESSION['company_id']." AND r.parent_id != 0";
}

$baseWhere = !empty($baseConditions) ? "WHERE " . implode(" AND ", $baseConditions) : "";
$totalRow = db_row("SELECT COUNT(*) AS total FROM $tbl r $baseWhere");
$recordsTotal = (int) ($totalRow['total'] ?? 0);

$conditions = $baseConditions;
if ($search !== '') {
    $search = db_escape($search);
    $conditions[] = "(r.name LIKE '%$search%' OR c.name LIKE '%$search%' OR u.username LIKE '%$search%' OR pr.name LIKE '%$search%')";
}
$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
$filteredRow = db_row("SELECT COUNT(*) AS total FROM $tbl r LEFT JOIN company AS c ON c.id = r.company_id LEFT JOIN $tbl AS pr ON pr.id = r.parent_id LEFT JOIN users AS u ON u.id = r.created_by $where");
$recordsFiltered = (int) ($filteredRow['total'] ?? 0);
$columns = [
    0 => null,
    1 => 'c.name',
    2 => 'pr.name',
    3 => 'r.name',
    4 => 'r.status',
    5 => null
];

$orderColumn = 'r.id';
$orderDirection = 'DESC';
if (isset($_POST['order'][0]['column'])) {
    $columnIndex = (int) $_POST['order'][0]['column'];
    $direction = $_POST['order'][0]['dir'] ?? 'desc';
    if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
        $orderColumn = $columns[$columnIndex];
        $orderDirection = ($direction === 'asc') ? 'ASC' : 'DESC';
    }
}

$sql = "SELECT r.id, r.name, r.parent_id, r.created_by, r.status, c.name as company_name, pr.name as parent_role_name, u.username as created_by_username 
        FROM $tbl r 
        LEFT JOIN company AS c ON c.id = r.company_id 
        LEFT JOIN $tbl AS pr ON pr.id = r.parent_id 
        LEFT JOIN users AS u ON u.id = r.created_by 
        $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";
$result = db_rows($sql);
$data = [];
$srNo = $start + 1;
foreach($result as $row){    
    $parentDisplay = ((int)($row['parent_id'] ?? 0) === 0) 
        ? ($row['created_by_username'] ?? '') 
        : ($row['parent_role_name'] ?? '');
    $actionItems = '';
    if ($canEdit) {
        $actionItems .= '<li>
            <a class="dropdown-item text-primary team_role_edit" href="javascript:void(0);" data-id="'.encrypt_id($row['id']).'">
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
    $actionItems .= '<li>
        <a class="dropdown-item text-info" href="'.SITE_URL.'teamrole/setting/' . encrypt_id($row['id']) . '">
           <i data-lucide="settings" class="fs-14"></i> Setting
        </a>
    </li>';
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
        htmlspecialchars($row['company_name'] ?? ''),
        htmlspecialchars($parentDisplay),
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
