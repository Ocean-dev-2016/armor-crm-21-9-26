<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Custom action: Fetch parent roles for a company
if (isset($_GET['action']) && $_GET['action'] == 'get_parent_company') {
    header('Content-Type: application/json');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid Company ID.']);
        exit;
    }

    $userSql = "SELECT id, name FROM roles WHERE company_id = $id AND status = 1";
    $userData = db_rows($userSql);

    $response = [
        'status'  => true,
        'data'    => [],
        'message' => !empty($userData) ? 'Company Data Retrieve.' : 'Company not found.'
    ];

    if (!empty($userData)) {
        foreach ($userData as $userRow) {
            $response['data'][] = ["id" => $userRow['id'], "name" => $userRow['name']];
        }
    }

    echo json_encode($response);
    exit;
}

// Custom action: Save role permissions
if (isset($_POST['action']) && $_POST['action'] == 'add_permission') {
    header('Content-Type: application/json');
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
        $permissions = $_POST['permissions'] ?? [];

        if (empty($permissions)) {
            echo json_encode([
                'status'  => false,
                'message' => 'Please select at least one permission.'
            ]);
            exit;
        }

        foreach ($permissions as $moduleId => $permissionIds) {
            $moduleId = (int)$moduleId;
            $views    = (int)($permissionIds['views'] ?? 0);
            $adds     = (int)($permissionIds['adds'] ?? 0);
            $updates  = (int)($permissionIds['updates'] ?? 0);
            $deletes  = (int)($permissionIds['deletes'] ?? 0);
            $print    = (int)($permissionIds['print'] ?? 0);
            $excel    = (int)($permissionIds['excel'] ?? 0);

            $perSql = "SELECT id FROM role_permissions WHERE company_id = $company_id AND role_id = $id AND module_id = $moduleId LIMIT 1";
            $perQry = db_row($perSql);
            if (!empty($perQry)) {
                $sql = "UPDATE role_permissions SET views = $views, adds = $adds, updates = $updates, deletes = $deletes, print = $print, excel = $excel, updated_at = NOW() WHERE id = " . $perQry['id'];
            } else {
                $sql = "INSERT INTO role_permissions (company_id, role_id, module_id, views, adds, updates, deletes, print, excel, created_at, updated_at) VALUES ($company_id, $id, $moduleId, $views, $adds, $updates, $deletes, $print, $excel, NOW(), NOW())";
            }
            db_query($sql);
        }

        echo json_encode([
            'status'  => true,
            'message' => 'Permissions saved successfully.'
        ]);
    } catch (Throwable $e) {
        echo json_encode([
            'status'  => false,
            'message' => 'Something went wrong.'
        ]);
    }
    exit;
}

$masterConfig = [
    'tbl'          => 'roles',
    'pageNm'       => 'Team Role',
    'moduleKey'    => 'teamrole',
    'hasCompany'   => true,
    'fields'       => [
        'parent_id' => 'int'
    ],
    'uniqueFields' => [],
    'beforeSave'   => function(&$dataValues, $id) {
        if (!isset($_POST['parent_id']) || trim((string)$_POST['parent_id']) === '') {
            echo json_encode(['status' => false, 'message' => 'Parent Company is required.']);
            return false;
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
