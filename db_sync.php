
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

// 4. Check & Add login_logo & lead_id columns to company table
if (tableExists('company')) {
    if (!columnExists('company', 'login_logo')) {
        $q = mysqli_query($conn, "ALTER TABLE `company` ADD COLUMN `login_logo` VARCHAR(255) NULL AFTER `app_logo`");
        if ($q) {
            echo '<div class="log-item log-success">✓ Added `login_logo` column to `company` table successfully.</div>';
        } else {
            echo '<div class="log-item log-error">✗ Failed to add `login_logo` to `company`: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        echo '<div class="log-item log-info">ℹ `company`.`login_logo` column already exists.</div>';
    }

    if (!columnExists('company', 'lead_id')) {
        $q = mysqli_query($conn, "ALTER TABLE `company` ADD COLUMN `lead_id` INT(11) NULL DEFAULT NULL AFTER `id`");
        if ($q) {
            echo '<div class="log-item log-success">✓ Added `lead_id` column to `company` table successfully.</div>';
        } else {
            echo '<div class="log-item log-error">✗ Failed to add `lead_id` to `company`: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        echo '<div class="log-item log-info">ℹ `company`.`lead_id` column already exists.</div>';
    }
}

// 5. Check & Create company_subscription_plan table
if (!tableExists('company_subscription_plan')) {
    $createSubTableSql = "CREATE TABLE `company_subscription_plan` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `plan_id` INT(11) NOT NULL DEFAULT 0,
        `plan_from` DATE NULL DEFAULT NULL,
        `plan_to` DATE NULL DEFAULT NULL,
        `extra_detail` TEXT NULL DEFAULT NULL,
        `plan_expiry_date` DATE NULL DEFAULT NULL,
        `subscription_status` VARCHAR(50) NULL DEFAULT 'active',
        `platform` VARCHAR(50) NULL DEFAULT NULL,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_company` (`company_id`),
        KEY `idx_plan` (`plan_id`),
        KEY `idx_expiry` (`plan_expiry_date`),
        KEY `idx_status` (`subscription_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createSubTableSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `company_subscription_plan` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `company_subscription_plan` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `company_subscription_plan` table already exists.</div>';
}

// 6. Check & Rename / Create `lead` table for Superadmin
if (tableExists('saas_leads') && !tableExists('lead')) {
    $ren = mysqli_query($conn, "RENAME TABLE `saas_leads` TO `lead`");
    if ($ren) {
        echo '<div class="log-item log-success">✓ Renamed table `saas_leads` to `lead` successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to rename `saas_leads` to `lead`: ' . mysqli_error($conn) . '</div>';
    }
}

if (!tableExists('lead')) {
    $createLeadSql = "CREATE TABLE IF NOT EXISTS `lead` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `lead_number` VARCHAR(50) NULL DEFAULT NULL,
        `business_name` VARCHAR(255) NOT NULL,
        `contact_name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NULL DEFAULT NULL,
        `mobile_no` VARCHAR(25) NOT NULL,
        `city` VARCHAR(100) NULL DEFAULT NULL,
        `country_id` INT(11) NULL DEFAULT NULL,
        `state_id` INT(11) NULL DEFAULT NULL,
        `city_id` INT(11) NULL DEFAULT NULL,
        `address` TEXT NULL DEFAULT NULL,
        `team_size` VARCHAR(50) NULL DEFAULT '1-10',
        `interested_plan_id` INT(11) NULL DEFAULT NULL,
        `lead_source` VARCHAR(100) NULL DEFAULT 'Direct',
        `lead_stage` ENUM('new', 'contacted', 'demo_scheduled', 'trial_active', 'negotiation', 'converted', 'lost') NOT NULL DEFAULT 'new',
        `demo_date` DATETIME NULL DEFAULT NULL,
        `converted_company_id` INT(11) NULL DEFAULT NULL,
        `requirements` TEXT NULL DEFAULT NULL,
        `notes` TEXT NULL DEFAULT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_stage` (`lead_stage`),
        KEY `idx_plan` (`interested_plan_id`),
        KEY `idx_converted` (`converted_company_id`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createLeadSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `lead` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `lead` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `lead` table exists.</div>';
}

// 6b. Add country_id, state_id, city_id, address to lead if not present
if (tableExists('lead')) {
    if (!columnExists('lead', 'country_id')) {
        mysqli_query($conn, "ALTER TABLE `lead` ADD COLUMN `country_id` INT(11) NULL DEFAULT NULL AFTER `city`");
    }
    if (!columnExists('lead', 'state_id')) {
        mysqli_query($conn, "ALTER TABLE `lead` ADD COLUMN `state_id` INT(11) NULL DEFAULT NULL AFTER `country_id`");
    }
    if (!columnExists('lead', 'city_id')) {
        mysqli_query($conn, "ALTER TABLE `lead` ADD COLUMN `city_id` INT(11) NULL DEFAULT NULL AFTER `state_id`");
    }
    if (!columnExists('lead', 'address')) {
        mysqli_query($conn, "ALTER TABLE `lead` ADD COLUMN `address` TEXT NULL DEFAULT NULL AFTER `city_id`");
    }
}

// 7. Check & Rename / Create `lead_followups` table for Superadmin Lead Follow-ups & Reminders
if (tableExists('saas_lead_followups') && !tableExists('lead_followups')) {
    $renF = mysqli_query($conn, "RENAME TABLE `saas_lead_followups` TO `lead_followups`");
    if ($renF) {
        echo '<div class="log-item log-success">✓ Renamed table `saas_lead_followups` to `lead_followups` successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to rename `saas_lead_followups` to `lead_followups`: ' . mysqli_error($conn) . '</div>';
    }
}

if (!tableExists('lead_followups')) {
    $createFollowupsSql = "CREATE TABLE IF NOT EXISTS `lead_followups` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `lead_id` INT(11) NOT NULL,
        `followup_type` ENUM('call', 'whatsapp', 'email', 'meeting', 'demo') NOT NULL DEFAULT 'call',
        `followup_date` DATETIME NOT NULL,
        `stage` VARCHAR(50) NOT NULL DEFAULT 'contacted',
        `remarks` TEXT NOT NULL,
        `reminder_status` ENUM('pending', 'completed', 'missed') NOT NULL DEFAULT 'pending',
        `created_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_lead` (`lead_id`),
        KEY `idx_followup_date` (`followup_date`),
        KEY `idx_reminder_status` (`reminder_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createFollowupsSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `lead_followups` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `lead_followups` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `lead_followups` table exists.</div>';
}
// 7b. Check & Create `lead_followup_type` table & default entries
if (!tableExists('lead_followup_type')) {
    $createLeadFuTypeSql = "CREATE TABLE IF NOT EXISTS `lead_followup_type` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createLeadFuTypeSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `lead_followup_type` table successfully.</div>';
        mysqli_query($conn, "INSERT INTO `lead_followup_type` (`id`, `name`, `status`) VALUES
            (1, 'Phone Call', 1),
            (2, 'WhatsApp', 1),
            (3, 'Live Demo', 1),
            (4, 'Meeting', 1),
            (5, 'Note / Email', 1)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)");
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `lead_followup_type` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `lead_followup_type` table exists.</div>';
    // Ensure default rows exist
    $checkRows = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `lead_followup_type`");
    $cntRow = mysqli_fetch_assoc($checkRows);
    if (($cntRow['cnt'] ?? 0) == 0) {
        mysqli_query($conn, "INSERT INTO `lead_followup_type` (`id`, `name`, `status`) VALUES
            (1, 'Phone Call', 1),
            (2, 'WhatsApp', 1),
            (3, 'Live Demo', 1),
            (4, 'Meeting', 1),
            (5, 'Note / Email', 1)");
        echo '<div class="log-item log-success">✓ Seeded default types in `lead_followup_type`.</div>';
    }
}

// 8. Create `company_lead` table for Company Leads
if (!tableExists('company_lead')) {
    $createCompanyLeadSql = "CREATE TABLE IF NOT EXISTS `company_lead` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `inquiry_no` VARCHAR(50) NOT NULL,
        `inquiry_date` DATE NOT NULL,
        `customer_name` VARCHAR(255) NOT NULL,
        `contact_person` VARCHAR(150) NULL DEFAULT NULL,
        `mobile_no` VARCHAR(25) NOT NULL,
        `whatsapp_no` VARCHAR(25) NULL DEFAULT NULL,
        `email` VARCHAR(150) NULL DEFAULT NULL,
        `website` VARCHAR(255) NULL DEFAULT NULL,
        `inquiry_status` VARCHAR(50) NOT NULL DEFAULT 'New Lead',
        `followup_status` VARCHAR(50) NOT NULL DEFAULT 'Open',
        `source_of_inquiry_id` INT(11) NULL DEFAULT NULL,
        `address` TEXT NULL DEFAULT NULL,
        `pincode` VARCHAR(20) NULL DEFAULT NULL,
        `country_id` INT(11) NULL DEFAULT NULL,
        `state_id` INT(11) NULL DEFAULT NULL,
        `city_id` INT(11) NULL DEFAULT NULL,
        `area` VARCHAR(150) NULL DEFAULT NULL,
        `assigned_to` INT(11) NOT NULL,
        `attachment` VARCHAR(255) NULL DEFAULT NULL,
        `requirement_details` TEXT NULL DEFAULT NULL,
        `followup_date` DATE NULL DEFAULT NULL,
        `followup_details` TEXT NULL DEFAULT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_cl_company` (`company_id`),
        KEY `idx_cl_assigned` (`assigned_to`),
        KEY `idx_cl_source` (`source_of_inquiry_id`),
        KEY `idx_cl_inquiry_date` (`inquiry_date`),
        KEY `idx_cl_inquiry_status` (`inquiry_status`),
        KEY `idx_cl_followup_status` (`followup_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createCompanyLeadSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `company_lead` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `company_lead` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `company_lead` table already exists.</div>';
}

// 9. Register Company Lead Module under Setting (parent_id = 27)
$checkMod = mysqli_query($conn, "SELECT id FROM module WHERE route = 'company-lead' OR (parent_id = 27 AND name = 'Company Lead') LIMIT 1");
if ($checkMod && mysqli_num_rows($checkMod) == 0) {
    // Determine next order_by for parent_id = 27
    $maxOrdRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(order_by) as mo FROM module WHERE parent_id = 27"));
    $nextOrder = ((int)($maxOrdRow['mo'] ?? 0)) + 1;
    $insMod = mysqli_query($conn, "INSERT INTO module (parent_id, name, icon, route, order_by, status, created_at, updated_at) 
        VALUES (27, 'Company Lead', 'contact', 'company-lead', $nextOrder, 1, NOW(), NOW())");
    if ($insMod) {
        $newModId = mysqli_insert_id($conn);
        echo '<div class="log-item log-success">✓ Registered `Company Lead` module (ID: ' . $newModId . ') under Setting successfully.</div>';

        // Auto-add new module ID to all plans' panel_right
        $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
        while ($pRow = mysqli_fetch_assoc($plansRes)) {
            $pId = (int)$pRow['id'];
            $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
            if (!in_array((string)$newModId, $curRights, true)) {
                $curRights[] = (string)$newModId;
                $newRightsStr = implode(',', $curRights);
                mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
            }
        }

        // Auto-grant full permissions to all roles for this new module
        $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
        while ($rRow = mysqli_fetch_assoc($rolesRes)) {
            $rId = (int)$rRow['id'];
            $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $newModId");
            if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
                mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $newModId, 1, 1, 1, 1)");
            }
        }
    } else {
        echo '<div class="log-item log-error">✗ Failed to register `Company Lead` module: ' . mysqli_error($conn) . '</div>';
    }
} else {
    $existingMod = mysqli_fetch_assoc($checkMod);
    $existingModId = (int)$existingMod['id'];
    echo '<div class="log-item log-info">ℹ `Company Lead` module already registered (ID: ' . $existingModId . ').</div>';
    
    // Ensure it exists in plans and roles
    $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
    while ($pRow = mysqli_fetch_assoc($plansRes)) {
        $pId = (int)$pRow['id'];
        $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
        if (!in_array((string)$existingModId, $curRights, true)) {
            $curRights[] = (string)$existingModId;
            $newRightsStr = implode(',', $curRights);
            mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
        }
    }
    $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
    while ($rRow = mysqli_fetch_assoc($rolesRes)) {
        $rId = (int)$rRow['id'];
        $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $existingModId");
        if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
            mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $existingModId, 1, 1, 1, 1)");
        }
    }
}

// 10. Create `marketing_status` table for Sub Master
if (!tableExists('marketing_status')) {
    $createMktStatusSql = "CREATE TABLE IF NOT EXISTS `marketing_status` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `name` VARCHAR(150) NOT NULL,
        `color` VARCHAR(25) NOT NULL DEFAULT '#0e5a6c',
        `type` VARCHAR(50) NOT NULL DEFAULT 'Lead',
        `order_by` INT(11) NOT NULL DEFAULT 0,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_ms_company` (`company_id`),
        KEY `idx_ms_type` (`type`),
        KEY `idx_ms_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createMktStatusSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `marketing_status` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `marketing_status` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `marketing_status` table already exists.</div>';
}

// 11. Register Marketing Status Module under Sub Master (parent_id = 13)
$checkMktMod = mysqli_query($conn, "SELECT id FROM module WHERE route = 'marketing-status' OR (parent_id = 13 AND name = 'Marketing Status') LIMIT 1");
if ($checkMktMod && mysqli_num_rows($checkMktMod) == 0) {
    $maxOrdRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(order_by) as mo FROM module WHERE parent_id = 13"));
    $nextOrder = ((int)($maxOrdRow['mo'] ?? 0)) + 1;
    $insMod = mysqli_query($conn, "INSERT INTO module (parent_id, name, icon, route, order_by, status, created_at, updated_at) 
        VALUES (13, 'Marketing Status', 'tag', 'marketing-status', $nextOrder, 1, NOW(), NOW())");
    if ($insMod) {
        $newModId = mysqli_insert_id($conn);
        echo '<div class="log-item log-success">✓ Registered `Marketing Status` module (ID: ' . $newModId . ') under Sub Master successfully.</div>';

        // Auto-add new module ID to all plans' panel_right
        $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
        while ($pRow = mysqli_fetch_assoc($plansRes)) {
            $pId = (int)$pRow['id'];
            $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
            if (!in_array((string)$newModId, $curRights, true)) {
                $curRights[] = (string)$newModId;
                $newRightsStr = implode(',', $curRights);
                mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
            }
        }

        // Auto-grant full permissions to all roles for this new module
        $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
        while ($rRow = mysqli_fetch_assoc($rolesRes)) {
            $rId = (int)$rRow['id'];
            $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $newModId");
            if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
                mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $newModId, 1, 1, 1, 1)");
            }
        }
    } else {
        echo '<div class="log-item log-error">✗ Failed to register `Marketing Status` module: ' . mysqli_error($conn) . '</div>';
    }
} else {
    $existingMod = mysqli_fetch_assoc($checkMktMod);
    $existingModId = (int)$existingMod['id'];
    echo '<div class="log-item log-info">ℹ `Marketing Status` module already registered (ID: ' . $existingModId . ').</div>';
    
    // Ensure it exists in plans and roles
    $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
    while ($pRow = mysqli_fetch_assoc($plansRes)) {
        $pId = (int)$pRow['id'];
        $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
        if (!in_array((string)$existingModId, $curRights, true)) {
            $curRights[] = (string)$existingModId;
            $newRightsStr = implode(',', $curRights);
            mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
        }
    }
    $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
    while ($rRow = mysqli_fetch_assoc($rolesRes)) {
        $rId = (int)$rRow['id'];
        $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $existingModId");
        if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
            mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $existingModId, 1, 1, 1, 1)");
        }
    }
}

// Ensure uploads/lead directory exists
$leadUploadDir = __DIR__ . '/uploads/lead/';
if (!is_dir($leadUploadDir)) {
    @mkdir($leadUploadDir, 0777, true);
}

// 12. Create `company_lead_followups` table
if (!tableExists('company_lead_followups')) {
    $createClFuSql = "CREATE TABLE IF NOT EXISTS `company_lead_followups` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `lead_id` INT(11) NOT NULL,
        `followup_date` DATETIME NOT NULL,
        `reason_id` INT(11) NULL DEFAULT NULL,
        `followup_type_id` INT(11) NULL DEFAULT NULL,
        `remarks` TEXT NOT NULL,
        `followup_status` VARCHAR(50) NOT NULL DEFAULT 'Open',
        `inquiry_status` VARCHAR(50) NOT NULL DEFAULT 'New Lead',
        `created_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_clf_lead` (`lead_id`),
        KEY `idx_clf_company` (`company_id`),
        KEY `idx_clf_fu_date` (`followup_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createClFuSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `company_lead_followups` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `company_lead_followups` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `company_lead_followups` table already exists.</div>';
}

// 13. Create `customer_type` table for Master
if (!tableExists('customer_type')) {
    $createCustTypeSql = "CREATE TABLE IF NOT EXISTS `customer_type` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `name` VARCHAR(150) NOT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_ct_company` (`company_id`),
        KEY `idx_ct_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createCustTypeSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `customer_type` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `customer_type` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `customer_type` table already exists.</div>';
}

// 14. Register Customer Type Module under Master (parent_id = 10)
$checkCustTypeMod = mysqli_query($conn, "SELECT id FROM module WHERE route = 'customer-type' OR (parent_id = 10 AND name = 'Customer Type') LIMIT 1");
if ($checkCustTypeMod && mysqli_num_rows($checkCustTypeMod) == 0) {
    $maxOrdRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(order_by) as mo FROM module WHERE parent_id = 10"));
    $nextOrder = ((int)($maxOrdRow['mo'] ?? 0)) + 1;
    $insMod = mysqli_query($conn, "INSERT INTO module (parent_id, name, icon, route, order_by, status, created_at, updated_at) 
        VALUES (10, 'Customer Type', 'user-check', 'customer-type', $nextOrder, 1, NOW(), NOW())");
    if ($insMod) {
        $newModId = mysqli_insert_id($conn);
        echo '<div class="log-item log-success">✓ Registered `Customer Type` module (ID: ' . $newModId . ') under Master successfully.</div>';

        // Auto-add new module ID to all plans' panel_right
        $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
        while ($pRow = mysqli_fetch_assoc($plansRes)) {
            $pId = (int)$pRow['id'];
            $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
            if (!in_array((string)$newModId, $curRights, true)) {
                $curRights[] = (string)$newModId;
                $newRightsStr = implode(',', $curRights);
                mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
            }
        }

        // Auto-grant full permissions to all roles for this new module
        $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
        while ($rRow = mysqli_fetch_assoc($rolesRes)) {
            $rId = (int)$rRow['id'];
            $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $newModId");
            if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
                mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $newModId, 1, 1, 1, 1)");
            }
        }
    } else {
        echo '<div class="log-item log-error">✗ Failed to register `Customer Type` module: ' . mysqli_error($conn) . '</div>';
    }
} else {
    $existingMod = mysqli_fetch_assoc($checkCustTypeMod);
    $existingModId = (int)$existingMod['id'];
    echo '<div class="log-item log-info">ℹ `Customer Type` module already registered (ID: ' . $existingModId . ').</div>';
    
    // Ensure it exists in plans and roles
    $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
    while ($pRow = mysqli_fetch_assoc($plansRes)) {
        $pId = (int)$pRow['id'];
        $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
        if (!in_array((string)$existingModId, $curRights, true)) {
            $curRights[] = (string)$existingModId;
            $newRightsStr = implode(',', $curRights);
            mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
        }
    }
    $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
    while ($rRow = mysqli_fetch_assoc($rolesRes)) {
        $rId = (int)$rRow['id'];
        $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $existingModId");
        if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
            mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $existingModId, 1, 1, 1, 1)");
        }
    }
}

// 15. Create `customer` table for Sales & Marketing
if (!tableExists('customer')) {
    $createCustomerSql = "CREATE TABLE IF NOT EXISTS `customer` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `customer_type_id` INT(11) NOT NULL DEFAULT 0,
        `name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NULL DEFAULT NULL,
        `mobile_no` VARCHAR(25) NOT NULL,
        `address` TEXT NULL DEFAULT NULL,
        `country_id` INT(11) NULL DEFAULT NULL,
        `state_id` INT(11) NULL DEFAULT NULL,
        `city_id` INT(11) NULL DEFAULT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_cust_company` (`company_id`),
        KEY `idx_cust_type` (`customer_type_id`),
        KEY `idx_cust_country` (`country_id`),
        KEY `idx_cust_state` (`state_id`),
        KEY `idx_cust_city` (`city_id`),
        KEY `idx_cust_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createCustomerSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `customer` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `customer` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `customer` table already exists.</div>';
}

// 15b. Ensure `company_lead_id` and all extended fields exist in `customer` table
if (tableExists('customer')) {
    $custNewCols = [
        'client_code'         => "VARCHAR(50) NULL DEFAULT NULL AFTER `company_lead_id`",
        'contact_person'      => "VARCHAR(150) NULL DEFAULT NULL AFTER `name`",
        'password'            => "VARCHAR(255) NULL DEFAULT NULL AFTER `contact_person`",
        'gst_no'              => "VARCHAR(25) NULL DEFAULT NULL AFTER `password`",
        'whatsapp_no'         => "VARCHAR(25) NULL DEFAULT NULL AFTER `mobile_no`",
        'birth_date'          => "DATE NULL DEFAULT NULL AFTER `email`",
        'area'                => "VARCHAR(150) NULL DEFAULT NULL AFTER `city_id`",
        'pincode'             => "VARCHAR(15) NULL DEFAULT NULL AFTER `area`",
        'price_list'          => "VARCHAR(100) NULL DEFAULT NULL AFTER `pincode`",
        'shipping_address'    => "TEXT NULL DEFAULT NULL AFTER `address`",
        'billing_address'     => "TEXT NULL DEFAULT NULL AFTER `shipping_address`",
        'latitude'            => "VARCHAR(50) NULL DEFAULT NULL AFTER `billing_address`",
        'longitude'           => "VARCHAR(50) NULL DEFAULT NULL AFTER `latitude`"
    ];

    if (!columnExists('customer', 'company_lead_id')) {
        $q = mysqli_query($conn, "ALTER TABLE `customer` ADD COLUMN `company_lead_id` INT(11) NULL DEFAULT NULL AFTER `company_id`, ADD KEY `idx_cust_lead` (`company_lead_id`)");
        if ($q) {
            echo '<div class="log-item log-success">✓ Added `company_lead_id` column to `customer` table successfully.</div>';
        } else {
            echo '<div class="log-item log-error">✗ Failed to add `company_lead_id` to `customer`: ' . mysqli_error($conn) . '</div>';
        }
    }

    foreach ($custNewCols as $colName => $colDef) {
        if (!columnExists('customer', $colName)) {
            $q = mysqli_query($conn, "ALTER TABLE `customer` ADD COLUMN `$colName` $colDef");
            if ($q) {
                echo '<div class="log-item log-success">✓ Added `' . $colName . '` column to `customer` table successfully.</div>';
            } else {
                echo '<div class="log-item log-error">✗ Failed to add `' . $colName . '` to `customer`: ' . mysqli_error($conn) . '</div>';
            }
        }
    }
}

// 16. Register Customer Module under Sales & Marketing (parent_id = 4)
$checkCustMod = mysqli_query($conn, "SELECT id FROM module WHERE route = 'customer' OR (parent_id = 4 AND name = 'Customer') LIMIT 1");
if ($checkCustMod && mysqli_num_rows($checkCustMod) == 0) {
    $maxOrdRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(order_by) as mo FROM module WHERE parent_id = 4"));
    $nextOrder = ((int)($maxOrdRow['mo'] ?? 0)) + 1;
    $insMod = mysqli_query($conn, "INSERT INTO module (parent_id, name, icon, route, order_by, status, created_at, updated_at) 
        VALUES (4, 'Customer', 'users', 'customer', $nextOrder, 1, NOW(), NOW())");
    if ($insMod) {
        $newModId = mysqli_insert_id($conn);
        echo '<div class="log-item log-success">✓ Registered `Customer` module (ID: ' . $newModId . ') under Sales & Marketing successfully.</div>';

        // Auto-add new module ID to all plans' panel_right
        $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
        while ($pRow = mysqli_fetch_assoc($plansRes)) {
            $pId = (int)$pRow['id'];
            $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
            if (!in_array((string)$newModId, $curRights, true)) {
                $curRights[] = (string)$newModId;
                $newRightsStr = implode(',', $curRights);
                mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
            }
        }

        // Auto-grant full permissions to all roles for this new module
        $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
        while ($rRow = mysqli_fetch_assoc($rolesRes)) {
            $rId = (int)$rRow['id'];
            $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $newModId");
            if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
                mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $newModId, 1, 1, 1, 1)");
            }
        }
    } else {
        echo '<div class="log-item log-error">✗ Failed to register `Customer` module: ' . mysqli_error($conn) . '</div>';
    }
} else {
    $existingMod = mysqli_fetch_assoc($checkCustMod);
    $existingModId = (int)$existingMod['id'];
    echo '<div class="log-item log-info">ℹ `Customer` module already registered (ID: ' . $existingModId . ').</div>';
    
    // Ensure it exists in plans and roles
    $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
    while ($pRow = mysqli_fetch_assoc($plansRes)) {
        $pId = (int)$pRow['id'];
        $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
        if (!in_array((string)$existingModId, $curRights, true)) {
            $curRights[] = (string)$existingModId;
            $newRightsStr = implode(',', $curRights);
            mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
        }
    }
    $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
    while ($rRow = mysqli_fetch_assoc($rolesRes)) {
        $rId = (int)$rRow['id'];
        $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $existingModId");
        if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
            mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $existingModId, 1, 1, 1, 1)");
        }
    }
}

// 17. Create customer_followups table
if (!tableExists('customer_followups')) {
    $createCustFuSql = "CREATE TABLE IF NOT EXISTS `customer_followups` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `customer_id` INT(11) NOT NULL,
        `followup_date` DATETIME NOT NULL,
        `reason_id` INT(11) NULL DEFAULT NULL,
        `followup_type_id` INT(11) NULL DEFAULT NULL,
        `remarks` TEXT NULL DEFAULT NULL,
        `response` TEXT NULL DEFAULT NULL,
        `followup_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_cf_company` (`company_id`),
        KEY `idx_cf_customer` (`customer_id`),
        KEY `idx_cf_date` (`followup_date`),
        KEY `idx_cf_status` (`followup_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createCustFuSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `customer_followups` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `customer_followups` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `customer_followups` table already exists.</div>';
}

// 18. Register Customer Follow Up Module under Sales & Marketing (parent_id = 4)
$checkCustFuMod = mysqli_query($conn, "SELECT id FROM module WHERE route = 'customer-followup' OR (parent_id = 4 AND name = 'Customer Follow Up') LIMIT 1");
if ($checkCustFuMod && mysqli_num_rows($checkCustFuMod) == 0) {
    $maxOrdRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(order_by) as mo FROM module WHERE parent_id = 4"));
    $nextOrder = ((int)($maxOrdRow['mo'] ?? 0)) + 1;
    $insMod = mysqli_query($conn, "INSERT INTO module (parent_id, name, icon, route, order_by, status, created_at, updated_at) 
        VALUES (4, 'Customer Follow Up', 'calendar-clock', 'customer-followup', $nextOrder, 1, NOW(), NOW())");
    if ($insMod) {
        $newModId = mysqli_insert_id($conn);
        echo '<div class="log-item log-success">✓ Registered `Customer Follow Up` module (ID: ' . $newModId . ') under Sales & Marketing successfully.</div>';

        // Auto-add new module ID to all plans' panel_right
        $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
        while ($pRow = mysqli_fetch_assoc($plansRes)) {
            $pId = (int)$pRow['id'];
            $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
            if (!in_array((string)$newModId, $curRights, true)) {
                $curRights[] = (string)$newModId;
                $newRightsStr = implode(',', $curRights);
                mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
            }
        }

        // Auto-grant full permissions to all roles for this new module
        $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
        while ($rRow = mysqli_fetch_assoc($rolesRes)) {
            $rId = (int)$rRow['id'];
            $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $newModId");
            if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
                mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $newModId, 1, 1, 1, 1)");
            }
        }
    } else {
        echo '<div class="log-item log-error">✗ Failed to register `Customer Follow Up` module: ' . mysqli_error($conn) . '</div>';
    }
} else {
    $existingMod = mysqli_fetch_assoc($checkCustFuMod);
    $existingModId = (int)$existingMod['id'];
    echo '<div class="log-item log-info">ℹ `Customer Follow Up` module already registered (ID: ' . $existingModId . ').</div>';
    
    // Ensure it exists in plans and roles
    $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
    while ($pRow = mysqli_fetch_assoc($plansRes)) {
        $pId = (int)$pRow['id'];
        $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
        if (!in_array((string)$existingModId, $curRights, true)) {
            $curRights[] = (string)$existingModId;
            $newRightsStr = implode(',', $curRights);
            mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
        }
    }
    $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
    while ($rRow = mysqli_fetch_assoc($rolesRes)) {
        $rId = (int)$rRow['id'];
        $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $existingModId");
        if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
            mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $existingModId, 1, 1, 1, 1)");
        }
    }
}

// 19. Create `customer_followup_reason` table
if (!tableExists('customer_followup_reason')) {
    $createCustFuReasonSql = "CREATE TABLE IF NOT EXISTS `customer_followup_reason` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT(11) NOT NULL DEFAULT 0,
        `name` VARCHAR(150) NOT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT(11) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_cfr_company` (`company_id`),
        KEY `idx_cfr_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $q = mysqli_query($conn, $createCustFuReasonSql);
    if ($q) {
        echo '<div class="log-item log-success">✓ Created `customer_followup_reason` table successfully.</div>';
    } else {
        echo '<div class="log-item log-error">✗ Failed to create `customer_followup_reason` table: ' . mysqli_error($conn) . '</div>';
    }
} else {
    echo '<div class="log-item log-info">ℹ `customer_followup_reason` table already exists.</div>';
}

// 20. Register `Customer Followup Reason` Module under Master (parent_id = 10)
$checkCustFuReasonMod = mysqli_query($conn, "SELECT id FROM module WHERE route = 'customer-followup-reason' OR (parent_id = 10 AND name = 'Customer Followup Reason') LIMIT 1");
if ($checkCustFuReasonMod && mysqli_num_rows($checkCustFuReasonMod) == 0) {
    $maxOrdRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(order_by) as mo FROM module WHERE parent_id = 10"));
    $nextOrder = ((int)($maxOrdRow['mo'] ?? 0)) + 1;
    $insMod = mysqli_query($conn, "INSERT INTO module (parent_id, name, icon, route, order_by, status, created_at, updated_at) 
        VALUES (10, 'Customer Followup Reason', 'help-circle', 'customer-followup-reason', $nextOrder, 1, NOW(), NOW())");
    if ($insMod) {
        $newModId = mysqli_insert_id($conn);
        echo '<div class="log-item log-success">✓ Registered `Customer Followup Reason` module (ID: ' . $newModId . ') under Master successfully.</div>';

        // Auto-add new module ID to all plans' panel_right
        $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
        while ($pRow = mysqli_fetch_assoc($plansRes)) {
            $pId = (int)$pRow['id'];
            $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
            if (!in_array((string)$newModId, $curRights, true)) {
                $curRights[] = (string)$newModId;
                $newRightsStr = implode(',', $curRights);
                mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
            }
        }

        // Auto-grant full permissions to all roles for this new module
        $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
        while ($rRow = mysqli_fetch_assoc($rolesRes)) {
            $rId = (int)$rRow['id'];
            $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $newModId");
            if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
                mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $newModId, 1, 1, 1, 1)");
            }
        }
    } else {
        echo '<div class="log-item log-error">✗ Failed to register `Customer Followup Reason` module: ' . mysqli_error($conn) . '</div>';
    }
} else {
    $existingMod = mysqli_fetch_assoc($checkCustFuReasonMod);
    $existingModId = (int)$existingMod['id'];
    echo '<div class="log-item log-info">ℹ `Customer Followup Reason` module already registered (ID: ' . $existingModId . ').</div>';
    
    // Ensure it exists in plans and roles
    $plansRes = mysqli_query($conn, "SELECT id, panel_right FROM plan");
    while ($pRow = mysqli_fetch_assoc($plansRes)) {
        $pId = (int)$pRow['id'];
        $curRights = array_filter(array_map('trim', explode(',', $pRow['panel_right'] ?? '')));
        if (!in_array((string)$existingModId, $curRights, true)) {
            $curRights[] = (string)$existingModId;
            $newRightsStr = implode(',', $curRights);
            mysqli_query($conn, "UPDATE plan SET panel_right = '" . mysqli_real_escape_string($conn, $newRightsStr) . "' WHERE id = $pId");
        }
    }
    $rolesRes = mysqli_query($conn, "SELECT id FROM roles");
    while ($rRow = mysqli_fetch_assoc($rolesRes)) {
        $rId = (int)$rRow['id'];
        $chkPerm = mysqli_query($conn, "SELECT id FROM role_permissions WHERE role_id = $rId AND module_id = $existingModId");
        if ($chkPerm && mysqli_num_rows($chkPerm) == 0) {
            mysqli_query($conn, "INSERT INTO role_permissions (role_id, module_id, views, adds, updates, deletes) VALUES ($rId, $existingModId, 1, 1, 1, 1)");
        }
    }
}

// Check & Add assigned_to column to customer table
if (tableExists('customer')) {
    if (!columnExists('customer', 'assigned_to')) {
        $q = mysqli_query($conn, "ALTER TABLE `customer` ADD COLUMN `assigned_to` INT(11) NULL DEFAULT 0 AFTER `company_lead_id`");
        if ($q) {
            echo '<div class="log-item log-success">✓ Added `assigned_to` column to `customer` table successfully.</div>';
        } else {
            echo '<div class="log-item log-error">✗ Failed to add `assigned_to` to `customer`: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        echo '<div class="log-item log-info">ℹ `customer`.`assigned_to` column already exists.</div>';
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
