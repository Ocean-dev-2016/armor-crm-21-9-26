<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'       => 'terms_condition',
    'pageNm'    => 'Terms & Condition',
    'moduleKey' => 'terms-condition',
    'fields'    => [
        'terms_condition' => 'string'
    ]
];

require_once BASE_PATH . '/component/master-store.php';
