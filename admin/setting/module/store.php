<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Custom action: Fetch sub modules for ordering modal
if (isset($_GET['action']) && $_GET['action'] === 'get_sub_modules') {
    header('Content-Type: application/json');
    $parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
    if ($parent_id <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid parent ID.']);
        exit;
    }

    $subSql = "SELECT id, name, icon, route, order_by FROM module WHERE parent_id = $parent_id AND status = 1 ORDER BY order_by ASC, id ASC";
    $subResult = db_rows($subSql);
    echo json_encode([
        'status'  => true,
        'message' => 'Sub modules fetched successfully.',
        'data'    => $subResult ?: []
    ]);
    exit;
}

// Custom action: Update sub module order positions
if (isset($_POST['action']) && $_POST['action'] === 'update_position') {
    header('Content-Type: application/json');
    if (!hasPermission('module', 'updates')) {
        echo json_encode(['status' => false, 'message' => 'Permission denied.']);
        exit;
    }

    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];

    if ($parent_id <= 0 || empty($order)) {
        echo json_encode(['status' => false, 'message' => 'Invalid request parameters.']);
        exit;
    }

    $position = 1;
    foreach ($order as $moduleId) {
        $mId = (int)$moduleId;
        if ($mId > 0) {
            db_query("UPDATE module SET order_by = $position, updated_at = NOW() WHERE id = $mId AND parent_id = $parent_id");
            $position++;
        }
    }

    echo json_encode([
        'status'  => true,
        'message' => 'Positions updated successfully.'
    ]);
    exit;
}

$masterConfig = [
    'tbl'          => 'module',
    'pageNm'       => 'Module',
    'moduleKey'    => 'module',
    'hasCompany'   => false,
    'fields'       => [
        'parent_id' => 'int',
        'icon'      => 'string',
        'route'     => 'string'
    ],
    'uniqueFields' => [],
    'beforeSave'   => function(&$dataValues, $id) {
        $name = trim($_POST['name'] ?? '');
        $route = trim($_POST['route'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

        if ($route === '') {
            echo json_encode(['status' => false, 'message' => 'Module Route is required.']);
            return false;
        }

        if ($parentId === 0 && $icon === '') {
            echo json_encode(['status' => false, 'message' => 'Module icon is required.']);
            return false;
        }

        $dataValues['parent_id'] = $parentId;
        $dataValues['slug'] = generate_slug($name);
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
