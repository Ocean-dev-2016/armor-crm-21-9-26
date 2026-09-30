<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'state',
    'pageNm'       => 'State',
    'moduleKey'    => 'state',
    'hasCompany'   => false,
    'fields'       => [
        'country_id' => 'int'
    ],
    'uniqueFields' => ['country_id', 'name'],
    'beforeSave'   => function(&$dataValues, $id) {
        if (empty($dataValues['country_id']) || (int)$dataValues['country_id'] <= 0) {
            echo json_encode(['status' => false, 'message' => 'Country is required.']);
            return false;
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
