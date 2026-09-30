<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'category',
    'pageNm'       => 'Category',
    'moduleKey'    => 'category',
    'hasImage'     => true,
    'uploadFolder' => 'category'
];

require_once BASE_PATH . '/component/master-store.php';
