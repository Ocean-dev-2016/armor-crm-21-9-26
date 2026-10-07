<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'lead_source_of_inquiry',
    'pageNm'       => 'Lead Source Of Inquiry',
    'moduleKey'    => 'lead-source-of-inquiry',
    'hasCompany'   => false,
    'fields'       => [],
    'uniqueFields' => ['name']
];

require_once BASE_PATH . '/component/master-store.php';
