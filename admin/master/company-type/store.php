<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'        => 'company_type',
    'pageNm'     => 'Company Type',
    'moduleKey'  => 'company-type',
    'hasCompany' => false
];

require_once BASE_PATH . '/component/master-store.php';
