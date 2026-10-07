<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
require_once __DIR__ . '/../../../component/datatable_ajax.php';

$tbl = 'marketing_status';
$moduleKey = 'marketing-status';
$canEdit   = hasPermission($moduleKey, 'updates');
$canDelete = hasPermission($moduleKey, 'deletes');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

$baseConditions = [];
if (!$isSuperadmin && isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) {
    $baseConditions[] = "t.company_id = " . (int)$_SESSION['company_id'];
}

$searchColumns = ['t.name', 't.type'];
if ($isSuperadmin) {
    $searchColumns[] = 'c.name';
    $columns = [
        0 => null,
        1 => 'c.name',
        2 => 't.name',
        3 => 't.color',
        4 => 't.type',
        5 => 't.status',
        6 => null
    ];
} else {
    $columns = [
        0 => null,
        1 => 't.name',
        2 => 't.color',
        3 => 't.type',
        4 => 't.status',
        5 => null
    ];
}

handle_datatable([
    'table'                => "$tbl t",
    'joins'                => "LEFT JOIN company c ON c.id = t.company_id",
    'select'               => "t.*, c.name AS company_name",
    'base_conditions'      => $baseConditions,
    'search_columns'       => $searchColumns,
    'order_columns'        => $columns,
    'default_order_column' => 't.order_by',
    'default_order_dir'    => 'ASC',
    'row_callback'         => function ($row, $srNo) use ($tbl, $canEdit, $canDelete, $isSuperadmin) {
        $rowItem = [
            'DT_RowId'   => 'row_' . $row['id'],
            'DT_RowData' => ['id' => $row['id']],
            0            => dt_drag_sr_no($srNo)
        ];

        if ($isSuperadmin) {
            $rowItem[] = htmlspecialchars($row['company_name'] ?? '-');
        }

        $rowItem[] = htmlspecialchars($row['name'] ?? '');

        // Color badge/swatch
        $colorHex = !empty($row['color']) ? htmlspecialchars($row['color']) : '#0e5a6c';
        $rowItem[] = '<div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle d-inline-block border" style="width: 18px; height: 18px; background-color: ' . $colorHex . ';"></span>
                        <code class="fs-12">' . $colorHex . '</code>
                      </div>';

        // Type badge (Lead vs Follow Up)
        $badgeClass = ($row['type'] === 'Follow Up') ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info';
        $rowItem[] = '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row['type'] ?? 'Lead') . '</span>';

        $rowItem[] = dt_status_switch($row['id'], $row['status'], $tbl);

        $rowItem[] = dt_action_dropdown($row['id'], $tbl, [
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'edit_class' => 'marketing_status_edit'
        ]);

        return $rowItem;
    }
]);
