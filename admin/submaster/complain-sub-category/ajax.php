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

$tbl = 'complain_sub_category';
$moduleKey = 'complain-sub-category';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "csc.company_id = " . (int)$_SESSION['company_id'];
}
$baseWhere = !empty($baseConditions) ? "WHERE " . implode(" AND ", $baseConditions) : "";

$totalRow = db_row("SELECT COUNT(*) AS total FROM $tbl csc $baseWhere");
$recordsTotal = (int) ($totalRow['total'] ?? 0);

$conditions = $baseConditions;
if ($search !== '') {
    $search = db_escape($search);
    if ($isSuperadmin) {
        $conditions[] = "(csc.name LIKE '%$search%' OR cc.name LIKE '%$search%' OR c.name LIKE '%$search%')";
    } else {
        $conditions[] = "(csc.name LIKE '%$search%' OR cc.name LIKE '%$search%')";
    }
}
$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$filteredRow = db_row("SELECT COUNT(*) AS total 
                       FROM $tbl csc 
                       LEFT JOIN complain_category cc ON cc.id = csc.complain_category_id 
                       LEFT JOIN company c ON c.id = csc.company_id 
                       $where");
$recordsFiltered = (int) ($filteredRow['total'] ?? 0);

if ($isSuperadmin) {
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'cc.name',
        3 => 'csc.name',
        4 => 'csc.status',
        5 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'cc.name',
        2 => 'csc.name',
        3 => 'csc.status',
        4 => null
    ];
}

$orderColumn = 'csc.id';
$orderDirection = 'DESC';
if (isset($_POST['order'][0]['column'])) {
    $columnIndex = (int) $_POST['order'][0]['column'];
    $direction = $_POST['order'][0]['dir'] ?? 'desc';
    if (isset($columns[$columnIndex]) && $columns[$columnIndex] !== null) {
        $orderColumn = $columns[$columnIndex];
        $orderDirection = ($direction === 'asc') ? 'ASC' : 'DESC';
    }
}

$sql = "SELECT csc.*, cc.name AS complain_category_name, c.name AS company_name 
        FROM $tbl csc 
        LEFT JOIN complain_category cc ON cc.id = csc.complain_category_id 
        LEFT JOIN company c ON c.id = csc.company_id 
        $where ORDER BY $orderColumn $orderDirection LIMIT $start, $length";
$result = db_rows($sql);
$data = [];
$srNo = $start + 1;

foreach ($result as $row) {
    $actionItems = '';
    if ($canEdit) {
        $actionItems .= '<li>
            <a class="dropdown-item text-primary complain_sub_category_edit" href="javascript:void(0);" data-id="' . encrypt_id($row['id']) . '">
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
    if ($isSuperadmin) {
        $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
    }
    $rowItem[] = htmlspecialchars($row['complain_category_name'] ?? '-');
    $rowItem[] = htmlspecialchars($row['name'] ?? '');
    $rowItem[] = '<div class="form-check form-switch mb-0">
        <input class="form-check-input change_status" type="checkbox" role="switch" ' . ($row['status'] == 1 ? 'checked' : '') . ' data-id="' . encrypt_id($row['id']) . '" data-tbl="' . $tbl . '">
    </div>';
    $rowItem[] = $actionHtml;

    $data[] = $rowItem;
    $srNo++;
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data'            => $data
]);
