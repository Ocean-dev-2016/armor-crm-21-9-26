<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Custom action: Update row order positions via datatable drag-and-drop
if (isset($_POST['action']) && $_POST['action'] === 'update_position') {
    header('Content-Type: application/json');
    if (!hasPermission('source-of-inquiry', 'updates')) {
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
            db_query("UPDATE source_of_inquiry SET order_by = $position, updated_at = NOW() WHERE id = $recId");
            $position++;
        }
    }

    echo json_encode([
        'status'  => true,
        'message' => 'Positions updated successfully.'
    ]);
    exit;
}

$masterConfig = [
    'tbl'       => 'source_of_inquiry',
    'pageNm'    => 'Source Of Inquiry',
    'moduleKey' => 'source-of-inquiry'
];

require_once BASE_PATH . '/component/master-store.php';