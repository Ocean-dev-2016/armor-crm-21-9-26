<?php
require_once __DIR__ . '/conn/db.php';
$res = mysqli_query($conn, "SELECT id, name, parent_id, route FROM module ORDER BY id ASC");
while ($r = mysqli_fetch_assoc($res)) {
    echo "ID: {$r['id']} | Parent: {$r['parent_id']} | Name: {$r['name']} | Route: {$r['route']}\n";
}
unlink(__FILE__);
