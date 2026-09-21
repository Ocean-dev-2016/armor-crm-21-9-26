<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'country';
$pageNm = 'Country';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $id  = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;

    if ($id <= 0) {
        $response['message'] = 'Invalid record ID.';
        echo json_encode($response);
        exit;
    }

    $editSql = "SELECT * FROM $tbl WHERE id = $id";
    $editResult = db_row($editSql);
    if ($editResult) {
        $response['status'] = true;
        $response['message'] = 'Record Edit successfully.';
        $response['data'] = $editResult;
    } else {
        $response['message'] = 'Failed to delete record.';
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}
$id = trim($_POST['id'] ?? '');
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission('country', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' countries.';
    echo json_encode($response);
    exit;
}
$name = trim($_POST['name'] ?? '');
$short_name = trim($_POST['short_name'] ?? '');

if ($name === '') {
    $response['message'] = $pageNm . ' name is required.';
    echo json_encode($response);
    exit;
}
if ($short_name === '') {
    $response['message'] = $pageNm . ' short name is required.';
    echo json_encode($response);
    exit;
}
if ($id > 0) {
    $uniqueSql = "SELECT * FROM $tbl WHERE name = '" . $name . "' AND id != $id";
} else {
    $uniqueSql = "SELECT * FROM $tbl WHERE name = '" . $name . "' ";
}
$uniqueResult = db_row($uniqueSql);
if (!empty($uniqueResult)) {
    $response['message'] = 'Country name is already exist.';
    echo json_encode($response);
    exit;
}

if ($id > 0) {
    $sql = "UPDATE $tbl SET name = '" . $name . "', short_name = '" . $short_name . "', updated_at = NOW() WHERE id = " . $id;
    $msg = $pageNm . ' updated successfully.';
} else {
    $sql = "INSERT INTO $tbl (name, short_name, created_at, updated_at) VALUES ('" . $name . "', '" . $short_name . "', NOW(), NOW())";
    $msg = $pageNm . ' created successfully.';
}

$countrys = db_query($sql);
if ($countrys) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] =
        'Failed to create ' . $pageNm;
}
echo json_encode($response);
exit;
