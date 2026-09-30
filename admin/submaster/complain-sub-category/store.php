<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Custom action: Fetch complain categories by company ID
if (isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    header('Content-Type: application/json');
    $comp_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
    if ($comp_id <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid company ID', 'data' => []]);
        exit;
    }
    $catSql = "SELECT id, name FROM complain_category WHERE company_id = $comp_id AND status = 1 ORDER BY name ASC";
    $cats = db_rows($catSql);
    echo json_encode(['status' => true, 'data' => $cats ?: []]);
    exit;
}

$masterConfig = [
    'tbl'          => 'complain_sub_category',
    'pageNm'       => 'Complain Sub Category',
    'moduleKey'    => 'complain-sub-category',
    'fields'       => [
        'complain_category_id' => 'int'
    ],
    'uniqueFields' => ['complain_category_id', 'name'],
    'beforeSave'   => function(&$dataValues, $id) {
        if (empty($dataValues['complain_category_id']) || (int)$dataValues['complain_category_id'] <= 0) {
            echo json_encode(['status' => false, 'message' => 'Please select complain/request category.']);
            return false;
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
