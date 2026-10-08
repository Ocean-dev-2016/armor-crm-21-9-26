<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Custom action: Update row order positions via datatable drag-and-drop
if (isset($_POST['action']) && $_POST['action'] === 'update_position') {
    header('Content-Type: application/json');
    $isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
    if (!$isSuperadmin && !hasPermission('lead-status', 'updates')) {
        echo json_encode(['status' => false, 'message' => 'Permission denied.']);
        exit;
    }

    $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];

    if (empty($order)) {
        echo json_encode(['status' => false, 'message' => 'Invalid request parameters.']);
        exit;
    }

    $position = 1;
    foreach ($order as $id) {
        $recId = (int)$id;
        if ($recId > 0) {
            db_query("UPDATE lead_status SET order_by = $position, updated_at = NOW() WHERE id = $recId");
            $position++;
        }
    }

    echo json_encode([
        'status'  => true,
        'message' => 'Order updated successfully.'
    ]);
    exit;
}

$masterConfig = [
    'tbl'          => 'lead_status',
    'pageNm'       => 'Lead Status',
    'moduleKey'    => 'lead-status',
    'hasCompany'   => false,
    'fields'       => [
        'color' => 'string'
    ],
    'uniqueFields' => ['name'],
    'beforeSave'   => function(&$dataValues, $id) {
        $color = trim($_POST['color'] ?? '');
        if ($color === '') {
            $dataValues['color'] = '#0e5a6c';
        }
        if ($id <= 0) {
            // Assign next order_by on creation
            $maxRow = db_row("SELECT MAX(order_by) as mo FROM lead_status");
            $dataValues['order_by'] = ((int)($maxRow['mo'] ?? 0)) + 1;
        }
        return true;
    }
];

require_once BASE_PATH . '/component/master-store.php';
