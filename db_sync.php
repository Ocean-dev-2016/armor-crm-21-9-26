
<?php
require_once __DIR__ . '/conn/db.php';
require_once __DIR__ . '/conn/dbqry.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Sync / Migration</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; padding: 30px; color: #1e293b; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 25px 30px; }
        h2 { margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        .log-item { padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; font-family: monospace; font-size: 13px; }
        .log-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .log-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .log-warn { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .log-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 500; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Database Schema Synchronization</h2>
<?php

function columnExists($table, $column) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($res && mysqli_num_rows($res) > 0);
}

function tableExists($table) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($res && mysqli_num_rows($res) > 0);
}

// 1. Check & Add order_by column to variant
if (tableExists('variant')) {
    if (!columnExists('variant', 'order_by')) {
        $q = mysqli_query($conn, "ALTER TABLE `variant` ADD COLUMN `order_by` INT(11) NOT NULL DEFAULT 0 AFTER `id`");
        if ($q) {
            echo '<div class="log-item log-success">✓ Added `order_by` column to `variant` table successfully.</div>';
        } else {
            echo '<div class="log-item log-error">✗ Failed to add `order_by` to `variant`: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        echo '<div class="log-item log-info">ℹ `variant`.`order_by` column already exists.</div>';
    }
}

// 2. Check & Add order_by column to source_of_inquiry
if (tableExists('source_of_inquiry')) {
    if (!columnExists('source_of_inquiry', 'order_by')) {
        $q = mysqli_query($conn, "ALTER TABLE `source_of_inquiry` ADD COLUMN `order_by` INT(11) NOT NULL DEFAULT 0 AFTER `id`");
        if ($q) {
            echo '<div class="log-item log-success">✓ Added `order_by` column to `source_of_inquiry` table successfully.</div>';
        } else {
            echo '<div class="log-item log-error">✗ Failed to add `order_by` to `source_of_inquiry`: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        echo '<div class="log-item log-info">ℹ `source_of_inquiry`.`order_by` column already exists.</div>';
    }
}

// 3. Check & Add login_logo, header_logo, favicon columns to users table for Superadmin branding
if (tableExists('users')) {
    $addedCols = [];
    if (!columnExists('users', 'login_logo')) {
        $q = mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `login_logo` VARCHAR(255) NULL AFTER `profile_img`");
        if ($q) $addedCols[] = 'login_logo';
    }
    if (!columnExists('users', 'header_logo')) {
        $q = mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `header_logo` VARCHAR(255) NULL AFTER `login_logo`");
        if ($q) $addedCols[] = 'header_logo';
    }
    if (!columnExists('users', 'favicon')) {
        $q = mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `favicon` VARCHAR(255) NULL AFTER `header_logo`");
        if ($q) $addedCols[] = 'favicon';
    }

    if (!empty($addedCols)) {
        echo '<div class="log-item log-success">✓ Added branding columns (`' . implode('`, `', $addedCols) . '`) to `users` table successfully.</div>';
    } else {
        echo '<div class="log-item log-info">ℹ `users` table branding columns (`login_logo`, `header_logo`, `favicon`) already exist.</div>';
    }
}

// Ensure uploads/system directory exists
$systemUploadDir = __DIR__ . '/uploads/system/';
if (!is_dir($systemUploadDir)) {
    if (@mkdir($systemUploadDir, 0777, true)) {
        echo '<div class="log-item log-success">✓ Created directory `uploads/system/` successfully.</div>';
    } else {
        echo '<div class="log-item log-warn">⚠ Please create `uploads/system/` directory with write permissions.</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ Directory `uploads/system/` already exists.</div>';
}

echo '<p class="mt-3 font-semibold text-green-700"><strong>Database synchronization completed successfully.</strong></p>';
?>
    <a href="<?= SITE_URL ?>" class="btn">Go to CRM Home</a>
</div>
</body>
</html>
