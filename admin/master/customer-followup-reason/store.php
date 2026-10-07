<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'       => 'customer_followup_reason',
    'pageNm'    => 'Customer Followup Reason',
    'moduleKey' => 'customer-followup-reason'
];

require_once BASE_PATH . '/component/master-store.php';
