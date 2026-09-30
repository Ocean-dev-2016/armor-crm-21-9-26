<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'company';
$canEdit   = hasPermission('company', 'updates');
$canDelete = hasPermission('company', 'deletes');

$columns = [
    0 => null,
    1 => 'c.name',
    2 => 'c.person_name',
    3 => 'c.mobile_no',
    4 => 'c.email',
    5 => 'cr.name',
    6 => 's.name',
    7 => 'ci.name',
    8 => 'p.name',
    9 => 'c.status',
    10 => null
];

$joins = "LEFT JOIN country cr ON cr.id = c.country_id 
          LEFT JOIN state s ON s.id = c.state_id 
          LEFT JOIN city ci ON ci.id = c.city_id 
          LEFT JOIN plan p ON p.id = c.plan_id";

$select = "c.*, cr.name as country_name, s.name as state_name, ci.name as city_name, p.name as plan_name";

handle_datatable([
    'table'                => "$tbl c",
    'joins'                => $joins,
    'select'               => $select,
    'search_columns'       => ['c.name', 'c.person_name', 'c.mobile_no', 'c.email', 'cr.name', 's.name', 'ci.name', 'p.name'],
    'order_columns'        => $columns,
    'default_order_column' => 'c.id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete) {
        $encId = encrypt_id($row['id']);

        return [
            $srNo,
            htmlspecialchars($row['name'] ?? ''),
            htmlspecialchars($row['person_name'] ?? ''),
            htmlspecialchars($row['mobile_no'] ?? ''),
            htmlspecialchars($row['email'] ?? ''),
            htmlspecialchars($row['country_name'] ?? ''),
            htmlspecialchars($row['state_name'] ?? ''),
            htmlspecialchars($row['city_name'] ?? ''),
            htmlspecialchars($row['plan_name'] ?? '-'),
            dt_status_switch($encId, $row['status'], $tbl, true),
            dt_action_dropdown($encId, $tbl, [
                'can_edit'     => $canEdit,
                'can_delete'   => $canDelete,
                'is_encrypted' => true,
                'edit_url'     => SITE_URL . 'company/edit/' . $encId
            ])
        ];
    }
]);
