<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'lead_followup_type',
    'pageNm'       => 'Lead Followup Type',
    'moduleKey'    => 'lead-followup-type',
    'hasCompany'   => false,
    'fields'       => [],
    'uniqueFields' => ['name']
];

require_once BASE_PATH . '/component/master-store.php';
