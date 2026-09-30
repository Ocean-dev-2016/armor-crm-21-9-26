<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'       => 'visit_purpose',
    'pageNm'    => 'Visit Purpose',
    'moduleKey' => 'visit-purpose'
];

require_once BASE_PATH . '/component/master-store.php';