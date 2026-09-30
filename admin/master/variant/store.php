<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// Custom action: Update variant order positions
if (isset($_POST['action']) && $_POST['action'] === 'update_position') {
    header('Content-Type: application/json');
    if (!hasPermission('variant', 'updates')) {
        echo json_encode(['status' => false, 'message' => 'Permission denied.']);
        exit;
    }

    $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];

    if (empty($order)) {
        echo json_encode(['status' => false, 'message' => 'Invalid request parameters.']);
        exit;
    }

    $position = 1;
    foreach ($order as $variantId) {
        $vId = (int)$variantId;
        if ($vId > 0) {
            db_query("UPDATE variant SET order_by = $position, updated_at = NOW() WHERE id = $vId");
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
    'tbl'       => 'variant',
    'pageNm'    => 'Variant',
    'moduleKey' => 'variant'
];

require_once BASE_PATH . '/component/master-store.php';
