<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Custom action: Fetch transport_by options by company ID
if (isset($_GET['action']) && $_GET['action'] === 'get_transport_by') {
    header('Content-Type: application/json');
    $comp_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
    if ($comp_id <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid company ID', 'data' => []]);
        exit;
    }
    $tbSql = "SELECT id, name FROM transport_by WHERE company_id = $comp_id AND status = 1 ORDER BY name ASC";
    $list = db_rows($tbSql);
    echo json_encode(['status' => true, 'data' => $list ?: []]);
    exit;
}

$masterConfig = [
    'tbl'          => 'transporter_detail',
    'pageNm'       => 'Transporter Detail',
    'moduleKey'    => 'transporter-detail',
    'fields'       => [
        'transport_by_id' => 'int'
    ],
    'uniqueFields' => ['transport_by_id', 'name'],
    'beforeSave'   => function(&$dataValues, $id) {
        if (empty($dataValues['transport_by_id']) || (int)$dataValues['transport_by_id'] <= 0) {
            echo json_encode(['status' => false, 'message' => 'Please select transport by.']);
            return false;
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
