<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'       => 'zone',
    'pageNm'    => 'Zone',
    'moduleKey' => 'zone'
];

require_once BASE_PATH . '/component/master-store.php';
