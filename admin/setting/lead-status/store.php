<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'lead_status',
    'pageNm'       => 'Lead Status',
    'moduleKey'    => 'lead-status',
    'hasCompany'   => false,
    'fields'       => [
        'color' => 'string'
    ],
    'uniqueFields' => ['name'],
    'beforeSave'   => function(&$dataValues, $id) {
        $color = trim($_POST['color'] ?? '');
        if ($color === '') {
            $dataValues['color'] = '#0e5a6c';
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
