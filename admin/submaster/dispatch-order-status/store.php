<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'       => 'dispatch_order_status',
    'pageNm'    => 'Dispatch Order Status',
    'moduleKey' => 'dispatch-order-status'
];

require_once BASE_PATH . '/component/master-store.php';