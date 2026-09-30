<?php
/**
 * Database Migration / Sync Script for Armor CRM
 * 
 * Usage:
 * 1. Upload this file to the root of your project on the live server.
 * 2. Visit in browser: https://newcrm.oceanhub.co.in/db_sync.php
 * 3. After successful sync, you can delete this file or keep it for future migrations.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/conn/db.php';
require_once __DIR__ . '/conn/dbqry.php';

function column_exists($table, $column) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return ($res && mysqli_num_rows($res) > 0);
}

function table_exists($table) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    return ($res && mysqli_num_rows($res) > 0);
}

$logs = [];

// 1. Variant table: add order_by column
if (table_exists('variant')) {
    if (!column_exists('variant', 'order_by')) {
        $q = mysqli_query($conn, "ALTER TABLE `variant` ADD COLUMN `order_by` INT(11) NOT NULL DEFAULT 0 AFTER `status`");
        if ($q) {
            mysqli_query($conn, "UPDATE `variant` SET `order_by` = `id` WHERE `order_by` = 0");
            $logs[] = ['status' => 'success', 'msg' => "Added column 'order_by' to table 'variant' and populated default order."];
        } else {
            $logs[] = ['status' => 'error', 'msg' => "Failed to add column 'order_by' to table 'variant': " . mysqli_error($conn)];
        }
    } else {
        $logs[] = ['status' => 'info', 'msg' => "Table 'variant' already has 'order_by' column."];
    }
} else {
    $logs[] = ['status' => 'warning', 'msg' => "Table 'variant' not found in database."];
}

// 2. Source of inquiry table: add order_by column
if (table_exists('source_of_inquiry')) {
    if (!column_exists('source_of_inquiry', 'order_by')) {
        $q = mysqli_query($conn, "ALTER TABLE `source_of_inquiry` ADD COLUMN `order_by` INT(11) NOT NULL DEFAULT 0 AFTER `status`");
        if ($q) {
            mysqli_query($conn, "UPDATE `source_of_inquiry` SET `order_by` = `id` WHERE `order_by` = 0");
            $logs[] = ['status' => 'success', 'msg' => "Added column 'order_by' to table 'source_of_inquiry' and populated default order."];
        } else {
            $logs[] = ['status' => 'error', 'msg' => "Failed to add column 'order_by' to table 'source_of_inquiry': " . mysqli_error($conn)];
        }
    } else {
        $logs[] = ['status' => 'info', 'msg' => "Table 'source_of_inquiry' already has 'order_by' column."];
    }
} else {
    $logs[] = ['status' => 'warning', 'msg' => "Table 'source_of_inquiry' not found in database."];
}

// 3. Module table: ensure order_by exists and is set
if (table_exists('module')) {
    if (!column_exists('module', 'order_by')) {
        $q = mysqli_query($conn, "ALTER TABLE `module` ADD COLUMN `order_by` INT(11) NOT NULL DEFAULT 0 AFTER `route`");
        if ($q) {
            mysqli_query($conn, "UPDATE `module` SET `order_by` = `id` WHERE `order_by` = 0");
            $logs[] = ['status' => 'success', 'msg' => "Added column 'order_by' to table 'module' and populated default order."];
        } else {
            $logs[] = ['status' => 'error', 'msg' => "Failed to add column 'order_by' to table 'module': " . mysqli_error($conn)];
        }
    } else {
        $logs[] = ['status' => 'info', 'msg' => "Table 'module' already has 'order_by' column."];
    }
} else {
    $logs[] = ['status' => 'warning', 'msg' => "Table 'module' not found in database."];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Sync - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .log-item { border-left: 4px solid #ccc; border-radius: 4px; padding: 12px 16px; margin-bottom: 10px; background: #fff; }
        .log-success { border-color: #198754; background: #f0fdf4; }
        .log-error { border-color: #dc3545; background: #fef2f2; }
        .log-info { border-color: #0dcaf0; background: #f0f9ff; }
        .log-warning { border-color: #ffc107; background: #fffbeb; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card p-4">
                <div class="d-flex align-items-center mb-4">
                    <div>
                        <h4 class="mb-1 text-dark fw-bold">Database Sync Tool</h4>
                        <p class="text-muted mb-0 small">Environment: <strong><?= $isLocal ? 'Localhost' : 'Live Server' ?></strong> (DB: <?= htmlspecialchars(DB_DATABASE) ?>)</p>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-semibold text-secondary mb-3">Migration Logs:</h6>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item log-<?= $log['status'] ?>">
                            <div class="d-flex align-items-center">
                                <?php if ($log['status'] === 'success'): ?>
                                    <span class="badge bg-success me-2">SUCCESS</span>
                                <?php elseif ($log['status'] === 'error'): ?>
                                    <span class="badge bg-danger me-2">ERROR</span>
                                <?php elseif ($log['status'] === 'warning'): ?>
                                    <span class="badge bg-warning text-dark me-2">WARNING</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark me-2">INFO</span>
                                <?php endif; ?>
                                <span class="text-dark small fw-medium"><?= htmlspecialchars($log['msg']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-success d-flex align-items-center mb-0" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-circle-fill me-2" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    <div>
                        <strong>Database synchronization complete!</strong> You can now use your updated modules.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
