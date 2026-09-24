<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'module';
$pageNm = 'Module';
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

if (isset($_GET['action']) && $_GET['action'] === 'get_sub_modules') {
    $parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
    if ($parent_id <= 0) {
        $response['message'] = 'Invalid parent ID.';
        echo json_encode($response);
        exit;
    }

    $subSql = "SELECT id, name, icon, route, order_by FROM $tbl WHERE parent_id = $parent_id AND status = 1 ORDER BY order_by ASC, id ASC";
    $subResult = db_rows($subSql);
    $response['status'] = true;
    $response['message'] = 'Sub modules fetched successfully.';
    $response['data'] = $subResult ?: [];
    echo json_encode($response);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'update_position') {
    if (!hasPermission('module', 'updates')) {
        $response['message'] = 'Permission denied.';
        echo json_encode($response);
        exit;
    }

    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];

    if ($parent_id <= 0 || empty($order)) {
        $response['message'] = 'Invalid request parameters.';
        echo json_encode($response);
        exit;
    }

    $position = 1;
    foreach ($order as $moduleId) {
        $mId = (int)$moduleId;
        if ($mId > 0) {
            db_query("UPDATE $tbl SET order_by = $position, updated_at = NOW() WHERE id = $mId AND parent_id = $parent_id");
            $position++;
        }
    }

    $response['status'] = true;
    $response['message'] = 'Positions updated successfully.';
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
if (!hasPermission('module', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' modules.';
    echo json_encode($response);
    exit;
}
$name = trim($_POST['name'] ?? '');
$icon = trim($_POST['icon'] ?? null);
$route = trim($_POST['route'] ?? '');
$slug = generate_slug($name);
$parent_id = trim($_POST['parent_id'] ?? 0);

if ($name === '') {
    $response['message'] = $pageNm.' name is required.';
    echo json_encode($response);
    exit;
}

if ($route === '') {
    $response['message'] = $pageNm.' Route is required.';
    echo json_encode($response);
    exit;
}

if ($parent_id == 0 && $icon === '') {
    $response['message'] = $pageNm.' icon is required.';
    echo json_encode($response);
    exit;
}

if ($id > 0) {    
    $sql = "UPDATE $tbl SET parent_id = '$parent_id', name = '".$name."', slug = '".$slug."', icon = '".$icon."', route = '".$route."', updated_at = NOW() WHERE id = ".$id;
    $msg = $pageNm . ' updated successfully.';
} 
else 
{
    $sql = "INSERT INTO $tbl (parent_id, name, slug, icon, route, created_at, updated_at) VALUES ('$parent_id', '". $name."', '". $slug."',  '". $icon."', '". $route."',  NOW(), NOW())";
    $msg = $pageNm . ' created successfully.';
}

$modules = db_query($sql);
if ($modules) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] =
        'Failed to create '.$pageNm;
}
echo json_encode($response);
exit;
