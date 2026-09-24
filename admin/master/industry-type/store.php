<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'industry_type';
$pageNm = 'Industry Type';
$moduleKey = 'industry-type';

$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// Action: Fetch single record for Edit
if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;

    if ($id <= 0) {
        $response['message'] = 'Invalid record ID.';
        echo json_encode($response);
        exit;
    }

    $editSql = "SELECT * FROM $tbl WHERE id = $id LIMIT 1";
    $editResult = db_row($editSql);
    if ($editResult) {
        $response['status'] = true;
        $response['message'] = 'Record fetched successfully.';
        $response['data'] = $editResult;
    } else {
        $response['message'] = 'Failed to fetch record.';
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission($moduleKey, $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' ' . strtolower($pageNm) . '.';
    echo json_encode($response);
    exit;
}

if ($isSuperadmin) {
    $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
    if ($company_id <= 0) {
        $response['message'] = 'Please select a company.';
        echo json_encode($response);
        exit;
    }
} else {
    $company_id = $sessionCompanyId;
}

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    $response['message'] = $pageNm . ' name is required.';
    echo json_encode($response);
    exit;
}

$nameEsc = db_escape($name);
$companyWhere = ($company_id > 0) ? " AND company_id = $company_id" : "";

// Check unique name per company
if ($id > 0) {
    $uniqueSql = "SELECT id FROM $tbl WHERE name = '$nameEsc' AND id != $id $companyWhere LIMIT 1";
} else {
    $uniqueSql = "SELECT id FROM $tbl WHERE name = '$nameEsc' $companyWhere LIMIT 1";
}
$uniqueResult = db_row($uniqueSql);
if (!empty($uniqueResult)) {
    $response['message'] = $pageNm . ' name already exists.';
    echo json_encode($response);
    exit;
}

if ($id > 0) {
    $sql = "UPDATE $tbl SET 
                name = '$nameEsc', 
                company_id = $company_id,
                updated_by = $userId,
                updated_at = NOW() 
            WHERE id = $id";
    $msg = $pageNm . ' updated successfully.';
} else {
    $sql = "INSERT INTO $tbl (company_id, name, status, created_by, created_at, updated_at) 
            VALUES ($company_id, '$nameEsc', 1, $userId, NOW(), NOW())";
    $msg = $pageNm . ' created successfully.';
}

$res = db_query($sql);
if ($res) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] = 'Failed to save ' . $pageNm;
}

echo json_encode($response);
exit;
