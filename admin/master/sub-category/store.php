<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Custom action: Fetch categories by company ID (for dependent dropdown)
if (isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    header('Content-Type: application/json');
    $sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
    $company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : $sessionCompanyId;
    if ($company_id <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid company ID.']);
        exit;
    }

    $cats = db_rows("SELECT id, name FROM category WHERE company_id = $company_id AND status = 1 ORDER BY name ASC");
    echo json_encode(['status' => true, 'message' => 'Categories retrieved.', 'data' => $cats ?: []]);
    exit;
}

$masterConfig = [
    'tbl'          => 'sub_category',
    'pageNm'       => 'Sub Category',
    'moduleKey'    => 'sub-category',
    'fields'       => [
        'category_id' => 'int'
    ],
    'uniqueFields' => ['category_id', 'name'],
    'hasImage'     => true,
    'uploadFolder' => 'subcategory',
    'beforeSave'   => function(&$dataValues, $id) {
        if (empty($dataValues['category_id']) || (int)$dataValues['category_id'] <= 0) {
            echo json_encode(['status' => false, 'message' => 'Please select a category.']);
            return false;
        }
        return true;
    },
    'onEditData'   => function(&$editResult, $id) {
        $recCompanyId = (int)($editResult['company_id'] ?? 0);
        $cats = db_rows("SELECT id, name FROM category WHERE company_id = $recCompanyId AND status = 1 ORDER BY name ASC");
        return ['categories' => $cats ?: []];
    }
];

require_once BASE_PATH . '/component/master-store.php';
