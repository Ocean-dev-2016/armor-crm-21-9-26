<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'       => 'unit',
    'pageNm'    => 'Unit',
    'moduleKey' => 'unit'
];

require_once BASE_PATH . '/component/master-store.php';
