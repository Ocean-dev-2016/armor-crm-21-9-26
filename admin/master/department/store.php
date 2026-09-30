<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'department',
    'pageNm'       => 'Department',
    'moduleKey'    => 'department',
    'fields'       => [
        'code' => 'string'
    ],
    'uniqueFields' => ['name'],
    'beforeSave'   => function(&$dataValues, $id) {
        if (!empty($dataValues['code'])) {
            $codeEsc = $dataValues['code'];
            $companyId = (int)($dataValues['company_id'] ?? 0);
            $companyWhere = ($companyId > 0) ? " AND company_id = $companyId" : "";
            $idWhere = ($id > 0) ? " AND id != $id" : "";
            $chk = db_row("SELECT id FROM department WHERE code = '$codeEsc' $idWhere $companyWhere LIMIT 1");
            if (!empty($chk)) {
                echo json_encode(['status' => false, 'message' => 'Department code already exists.']);
                return false;
            }
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
