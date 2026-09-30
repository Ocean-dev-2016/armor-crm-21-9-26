<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'        => 'country',
    'pageNm'     => 'Country',
    'moduleKey'  => 'country',
    'hasCompany' => false,
    'fields'     => [
        'short_name' => 'string'
    ]
];

require_once BASE_PATH . '/component/master-store.php';
