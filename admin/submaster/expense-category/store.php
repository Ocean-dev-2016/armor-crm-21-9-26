<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'expense_category',
    'pageNm'       => 'Expense Category',
    'moduleKey'    => 'expense-category',
    'hasImage'     => true,
    'uploadFolder' => 'expense_category'
];

require_once BASE_PATH . '/component/master-store.php';
