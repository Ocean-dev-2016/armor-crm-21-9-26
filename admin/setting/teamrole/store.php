<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'roles';
$pageNm = 'Team Role';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];
$user_id = getCurrentUserId();
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

if(isset($_GET['action']) && $_GET['action'] == 'get_parent_company')
{
    $id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        $response['message'] = 'Invalid Company ID.';
        echo json_encode($response);
        exit;
    }
    
    // Get roles for this company (parent and sub roles)
    $userSql = "SELECT id, name FROM $tbl WHERE company_id = " . $id . " AND status = 1";
    $userData = db_rows($userSql);

    $response['status'] = true;
    $response['data'] = [];
    if (!empty($userData)) {
        foreach($userData as $userRow) {
            $response['data'][] = ["id" => $userRow['id'], "name" => $userRow['name']];
        }
        $response['message'] = 'Company Data Retrieve.';
    } else {
        $response['message'] = 'Company not found.';
    }
    echo json_encode($response);
    exit;
}

if(isset($_POST['action']) && $_POST['action'] == 'add_permission')
{
    try {
        $id = isset($_POST['id'])
            ? (int) $_POST['id']
            : 0;
        $company_id = isset($_POST['company_id'])
            ? (int) $_POST['company_id']
            : 0;
        $permissions = $_POST['permissions'] ?? [];
        if (empty($permissions)) {
            echo json_encode([
                'status' => false,
                'message' => 'Please select at least one permission.'
            ]);
            exit;
        }
        foreach ($permissions as $moduleId => $permissionIds) {
            $moduleId = (int) $moduleId;
            $views = $permissionIds['views'];
            $adds = $permissionIds['adds'];
            $updates = $permissionIds['updates'];
            $deletes = $permissionIds['deletes'];
            $perSql = "SELECT * FROM role_permissions WHERE company_id = '$company_id' AND role_id = $id AND module_id = $moduleId LIMIT 1";
            $perQry = db_row($perSql);
            if(isset($perQry) && !empty($perQry))
                {
                    $sql = "UPDATE role_permissions SET views = '$views', adds = '$adds', updates = '$updates', deletes = '$deletes', updated_at = NOW() WHERE id = " . $perQry['id'];
                }
                else
                {
                    $sql = "INSERT INTO role_permissions (company_id, role_id, module_id, views, adds, updates, deletes, created_at, updated_at) VALUES ('$company_id', '$id', '$moduleId', '$views', '$adds', '$updates', '$deletes', NOW(), NOW())";
                }
            $countrys = db_query($sql);
        }
        echo json_encode([
            'status' => true,
            'message' => 'Permissions saved successfully.'
        ]);
    } catch (Throwable $e) {
        echo json_encode([
            'status' => false,
            'message' => 'Something went wrong.'
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}
$id = trim($_POST['id'] ?? '');
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission('teamrole', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' team roles.';
    echo json_encode($response);
    exit;
}
$company_id = trim($_POST['company_id'] ?? '');
$parent_id = trim($_POST['parent_id'] ?? '');
$name = trim($_POST['name'] ?? '');

if ($name === '') {
    $response['message'] = $pageNm.' name is required.';
    echo json_encode($response);
    exit;
}
if ($company_id === '') {
    $response['message'] = 'Company is required.';
    echo json_encode($response);
    exit;
}
if ($parent_id === '') {
    $response['message'] = 'Parent Company is required.';
    echo json_encode($response);
    exit;
}

if ($id > 0) {    
   
    $sql = "UPDATE $tbl SET company_id = '$company_id', parent_id = '$parent_id', name = '".$name."', updated_by='".$user_id."', updated_at = NOW() WHERE id = ".$id;
    $msg = $pageNm . ' updated successfully.';
} 
else 
{
    $sql = "INSERT INTO $tbl (company_id, parent_id, name, created_by, created_at, updated_at) VALUES ('$company_id', '$parent_id', '". $name."', '".$user_id."', NOW(), NOW())";
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
