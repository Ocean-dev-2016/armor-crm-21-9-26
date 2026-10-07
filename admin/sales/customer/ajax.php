<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'customer';
$canEdit   = hasPermission('customer', 'updates');
$canDelete = hasPermission('customer', 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

if (isset($_GET['action']) && $_GET['action'] === 'get_company_types') {
    header('Content-Type: application/json');
    $compId = (int)($_GET['company_id'] ?? 0);
    $cond = "status = 1";
    if ($compId > 0) {
        $cond .= " AND (company_id = $compId OR company_id = 0)";
    }
    $rows = db_rows("SELECT id, name FROM customer_type WHERE $cond ORDER BY name ASC");
    echo json_encode(['status' => true, 'data' => $rows]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_company_users') {
    header('Content-Type: application/json');
    $compId = (int)($_GET['company_id'] ?? 0);
    $cond = "status = 1 AND user_type = 'user'";
    if ($compId > 0) {
        $cond .= " AND company_id = $compId";
    }
    $rows = db_rows("SELECT id, name, user_type FROM users WHERE $cond ORDER BY name ASC");
    echo json_encode(['status' => true, 'data' => $rows]);
    exit;
}

$userId = getCurrentUserId();
$userType = $_SESSION['user_type'] ?? '';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "cust.company_id = " . (int)$_SESSION['company_id'];
}

// If logged in as standard user (team person), show only customers assigned to this user or created by them
if ($userType === 'user' && $userId > 0) {
    $baseConditions[] = "(cust.assigned_to = $userId OR cust.created_by = $userId)";
}

$searchColumns = ['cust.client_code', 'cust.name', 'cust.contact_person', 'cust.email', 'cust.mobile_no', 'cust.gst_no', 'ct.name', 'ci.name', 's.name', 'u.name'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 'ct.name',
        3 => 'cust.name',
        4 => 'cust.mobile_no',
        5 => 'cust.email',
        6 => 'ci.name',
        7 => 's.name',
        8 => 'u.name',
        9 => 'cust.status',
        10 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 'ct.name',
        2 => 'cust.name',
        3 => 'cust.mobile_no',
        4 => 'cust.email',
        5 => 'ci.name',
        6 => 's.name',
        7 => 'u.name',
        8 => 'cust.status',
        9 => null
    ];
}

$joins = "LEFT JOIN company c ON c.id = cust.company_id 
          LEFT JOIN customer_type ct ON ct.id = cust.customer_type_id 
          LEFT JOIN city ci ON ci.id = cust.city_id 
          LEFT JOIN state s ON s.id = cust.state_id 
          LEFT JOIN country co ON co.id = cust.country_id
          LEFT JOIN users u ON u.id = cust.assigned_to";

$select = "cust.*, c.name AS company_name, ct.name AS customer_type_name, ci.name AS city_name, s.name AS state_name, co.name AS country_name, u.name AS assigned_to_name";

handle_datatable([
    'table'                => "$tbl cust",
    'joins'                => $joins,
    'select'               => $select,
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 'cust.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $encId = encrypt_id($row['id']);

        $rowItem = [$srNo];
        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }
        $rowItem[] = htmlspecialchars($row['customer_type_name'] ?? '-');
        $rowItem[] = htmlspecialchars($row['name'] ?? '');
        $rowItem[] = htmlspecialchars($row['mobile_no'] ?? '-');
        $rowItem[] = htmlspecialchars($row['email'] ?? '-');
        $rowItem[] = htmlspecialchars($row['city_name'] ?? '-');
        $rowItem[] = htmlspecialchars($row['state_name'] ?? '-');
        $rowItem[] = htmlspecialchars($row['assigned_to_name'] ?? '-');
        $leadId = (int)($row['company_lead_id'] ?? 0);
        $encLeadId = $leadId > 0 ? encrypt_id($leadId) : '';

        $extraActionItems = [
            '<li><a class="dropdown-item text-success btn-customer-add-fu" href="javascript:void(0);" ' .
                'data-customer-id="' . (int)$row['id'] . '" ' .
                'data-company-id="' . (int)$row['company_id'] . '" ' .
                'data-name="' . htmlspecialchars($row['name'] ?? '', ENT_QUOTES) . '" ' .
                'data-company-name="' . htmlspecialchars($row['company_name'] ?? '', ENT_QUOTES) . '" ' .
                'data-mobile="' . htmlspecialchars($row['mobile_no'] ?? '', ENT_QUOTES) . '" ' .
                'data-email="' . htmlspecialchars($row['email'] ?? '', ENT_QUOTES) . '">' .
                '<i data-lucide="calendar-plus" class="fs-14"></i> Add Follow-up</a></li>',
            '<li><a class="dropdown-item text-info" href="' . SITE_URL . 'customer-followup?customer_id=' . $encId . '">' .
                '<i data-lucide="history" class="fs-14"></i> Follow-up History</a></li>'
        ];

        if ($leadId > 0) {
            $extraActionItems[] = '<li><a class="dropdown-item text-warning" href="' . SITE_URL . 'customer/lead-history/' . $encId . '">' .
                '<i data-lucide="clipboard-list" class="fs-14"></i> Lead History</a></li>';
        }

        $rowItem[] = dt_status_switch($encId, $row['status'], $tbl, true);
        $rowItem[] = dt_action_dropdown($encId, $tbl, [
            'can_edit'     => $canEdit,
            'can_delete'   => $canDelete,
            'is_encrypted' => true,
            'edit_url'     => SITE_URL . 'customer/edit/' . $encId,
            'extra_items'  => $extraActionItems
        ]);

        return $rowItem;
    }
]);
