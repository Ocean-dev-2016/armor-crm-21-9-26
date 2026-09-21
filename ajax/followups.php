<?php

include '../conn/db.php';

$draw   = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$start  = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;

$search = $_POST['search']['value'] ?? '';

$totalQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM followups"
);

$totalRow = mysqli_fetch_assoc($totalQuery);

$recordsTotal = (int) $totalRow['total'];

$where = "";

if ($search !== '') {

    $search = mysqli_real_escape_string($conn, $search);

    $where = "WHERE
        company_id LIKE '%$search%'
        OR inquiry_id LIKE '%$search%'
        OR details LIKE '%$search%'
        OR through LIKE '%$search%'
        OR date_time LIKE '%$search%'";
}

$filteredQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM followups
     $where"
);

$filteredRow = mysqli_fetch_assoc($filteredQuery);

$recordsFiltered = (int) $filteredRow['total'];

$columns = [
    0 => null,
    1 => 'company_id',
    2 => 'inquiry_id',
    3 => 'date_time',
    4 => 'details',
    5 => 'through',
    6 => null
];

$orderColumn = 'id';
$orderDirection = 'DESC';

if (isset($_POST['order'][0]['column'])) {

    $columnIndex = (int) $_POST['order'][0]['column'];

    $direction = $_POST['order'][0]['dir'] ?? 'desc';

    if (
        isset($columns[$columnIndex]) &&
        $columns[$columnIndex] !== null
    ) {

        $orderColumn = $columns[$columnIndex];

        $orderDirection = ($direction === 'asc')
            ? 'ASC'
            : 'DESC';
    }
}

$sql = "
    SELECT
        id,
        company_id,
        inquiry_id,
        date_time,
        details,
        through
    FROM followups
    $where
    ORDER BY $orderColumn $orderDirection
    LIMIT $start, $length
";

$result = mysqli_query($conn, $sql);

$data = [];

$srNo = $start + 1;

while ($row = mysqli_fetch_assoc($result)) {

    $data[] = [
        $srNo,
        htmlspecialchars($row['company_id'] ?? ''),
        htmlspecialchars($row['inquiry_id'] ?? ''),
        !empty($row['date_time'])
            ? date(
                'd-m-Y h:i A',
                strtotime($row['date_time'])
            )
            : '-',
        htmlspecialchars($row['details'] ?? '-'),
        htmlspecialchars($row['through'] ?? '-'),
        '
        <div class="dropdown">
            <button
                class="btn btn-icon btn-sm btn-light"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">
                <i data-lucide="more-horizontal"
                   class="fs-16"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item"
                       href="followup-edit.php?id=' . (int)$row['id'] . '">
                        Edit
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-danger"
                       href="followup-delete.php?id=' . (int)$row['id'] . '"
                       onclick="return confirm(\'Are you sure you want to delete this followup?\')">
                        Delete
                    </a>
                </li>
            </ul>
        </div>
        '
    ];
    $srNo++;
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data'             => $data
]);
