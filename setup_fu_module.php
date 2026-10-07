<?php
require_once __DIR__ . '/conn/db.php';
require_once __DIR__ . '/conn/dbqry.php';

$chk = db_row("SELECT id FROM module WHERE route = 'company-lead-followup' LIMIT 1");
if (!$chk) {
    // Parent ID is 27 (Setting) or 4 (Sales & Marketing). Module 53 (Company Lead) has parent_id = 27.
    $ins = db_query("INSERT INTO module (name, icon, route, parent_id, order_by, status) VALUES ('Company Lead Follow Up', 'calendar-clock', 'company-lead-followup', 27, 2, 1)");
    $newId = db_insert_id();
    echo "Inserted module ID: $newId\n";

    // Also update plan table panel_right so all active plans get access
    $plans = db_rows("SELECT id, panel_right FROM plan");
    foreach ($plans as $p) {
        $pId = (int)$p['id'];
        $rights = array_filter(array_map('trim', explode(',', $p['panel_right'] ?? '')));
        if (!in_array((string)$newId, $rights)) {
            $rights[] = (string)$newId;
            $newRights = implode(',', $rights);
            db_query("UPDATE plan SET panel_right = '$newRights' WHERE id = $pId");
        }
    }
    echo "Updated plans\n";

    // Also update role_permission for existing team roles if needed
    $roles = db_rows("SELECT DISTINCT role_id FROM role_permission");
    foreach ($roles as $r) {
        $roleId = (int)$r['role_id'];
        $rChk = db_row("SELECT id FROM role_permission WHERE role_id = $roleId AND module_id = $newId LIMIT 1");
        if (!$rChk) {
            db_query("INSERT INTO role_permission (role_id, module_id, views, adds, updates, deletes, status) VALUES ($roleId, $newId, 1, 1, 1, 1, 1)");
        }
    }
    echo "Updated role permissions\n";
} else {
    echo "Module already exists with ID: " . $chk['id'] . "\n";
}

unlink(__FILE__);
