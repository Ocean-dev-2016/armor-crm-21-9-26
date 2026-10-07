<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$masterConfig = [
    'tbl'          => 'marketing_status',
    'pageNm'       => 'Marketing Status',
    'moduleKey'    => 'marketing-status',
    'hasCompany'   => true,
    'fields'       => [
        'color' => 'string',
        'type'  => 'string'
    ],
    'uniqueFields' => ['name', 'type'],
    'beforeSave'   => function(&$dataValues, $id) {
        $color = trim($_POST['color'] ?? '');
        if ($color === '') {
            $dataValues['color'] = '#0e5a6c';
        }
        $type = trim($_POST['type'] ?? 'Lead');
        if (!in_array($type, ['Lead', 'Follow Up'], true)) {
            $type = 'Lead';
        }
        $dataValues['type'] = $type;
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
