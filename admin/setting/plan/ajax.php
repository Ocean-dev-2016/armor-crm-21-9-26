<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'plan';
$canEdit   = hasPermission('plan', 'updates');
$canDelete = hasPermission('plan', 'deletes');

$columns = [
    0 => null,
    1 => 'name',
    2 => 'price',
    3 => 'duration',
    4 => 'max_team_user',
    5 => 'max_customer',
    6 => 'max_inquiry',
    7 => null
];

handle_datatable([
    'table'                => $tbl,
    'search_columns'       => ['name'],
    'order_columns'        => $columns,
    'default_order_column' => 'id',
    'default_order_dir'    => 'DESC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete) {
        $encId = encrypt_id($row['id']);

        return [
            $srNo,
            htmlspecialchars($row['name'] ?? ''),
            htmlspecialchars($row['price'] ?? ''),
            htmlspecialchars(($row['days'] ?? '') . ' Days'),
            htmlspecialchars($row['max_team_user'] ?? '-'),
            htmlspecialchars($row['max_customer'] ?? '-'),
            htmlspecialchars($row['max_inquiry'] ?? '-'),
            dt_status_switch($encId, $row['status'], $tbl, true),
            dt_action_dropdown($encId, $tbl, [
                'can_edit'     => $canEdit,
                'can_delete'   => $canDelete,
                'is_encrypted' => true,
                'edit_url'     => SITE_URL . 'plan/edit/' . $encId
            ])
        ];
    }
]);
