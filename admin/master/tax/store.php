<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'tax',
    'pageNm'       => 'Tax',
    'moduleKey'    => 'tax',
    'fields'       => [
        'value' => 'float'
    ],
    'uniqueFields' => ['name'],
    'beforeSave'   => function(&$dataValues, $id) {
        if (!isset($dataValues['value']) || $dataValues['value'] === '') {
            echo json_encode(['status' => false, 'message' => 'Value is required.']);
            return false;
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
