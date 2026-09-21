<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'state';
$pageNm = 'State';
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
if (!hasPermission('state', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' states.';
    echo json_encode($response);
    exit;
}
$name = trim($_POST['name'] ?? '');
$country_id = trim($_POST['country_id'] ?? '');

if ($name === '') {
    $response['message'] = $pageNm.' name is required.';
    echo json_encode($response);
    exit;
}
if ($country_id === '') {
    $response['message'] = 'Country is required.';
    echo json_encode($response);
    exit;
}

if ($id > 0) {
    $uniqueSql = "SELECT * FROM $tbl WHERE name = '" . $name . "' AND country_id = '$country_id' AND id != $id";
} else {
    $uniqueSql = "SELECT * FROM $tbl WHERE name = '" . $name . "' AND country_id = '$country_id' ";
}
$uniqueResult = db_row($uniqueSql);
if (!empty($uniqueResult)) {
    $response['message'] = 'This Country in State name is already exist.';
    echo json_encode($response);
    exit;
}

if ($id > 0) {    
    $sql = "UPDATE $tbl SET country_id = '$country_id', name = '".$name."', updated_at = NOW() WHERE id = ".$id;
    $msg = $pageNm . ' updated successfully.';
} 
else 
{
    $sql = "INSERT INTO $tbl (country_id, name, created_at, updated_at) VALUES ('$country_id', '". $name."', NOW(), NOW())";
    $msg = $pageNm . ' created successfully.';
}

$states = db_query($sql);
if ($states) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] =
        'Failed to create '.$pageNm;
}
echo json_encode($response);
exit;

