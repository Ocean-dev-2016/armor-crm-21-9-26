<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'company';
$pageNm = 'Company';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}
$id          = trim($_POST['id'] ?? '');
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission('company', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' companies.';
    echo json_encode($response);
    exit;
}
$name          = trim($_POST['name'] ?? '');
$person_name   = trim($_POST['person_name'] ?? '');
$mobile_no     = trim($_POST['mobile_no'] ?? '');
$email         = trim($_POST['email'] ?? '');
$country_id    = trim($_POST['country_id'] ?? '');
$state_id      = trim($_POST['state_id'] ?? '');
$city_id       = trim($_POST['city_id'] ?? '');
$plan_id       = trim($_POST['plan_id'] ?? '');

if ($name === '') {
    $response['message'] = $pageNm . ' name is required.';
    echo json_encode($response);
    exit;
}
if ($person_name === '') {
    $response['message'] = 'Person name is required.';
    echo json_encode($response);
    exit;
}
if ($mobile_no === '') {
    $response['message'] = 'Mobile no is required.';
    echo json_encode($response);
    exit;
}
if ($email === '') {
    $response['message'] = 'Email is required.';
    echo json_encode($response);
    exit;
}
if ($country_id === '') {
    $response['message'] = 'Country is required.';
    echo json_encode($response);
    exit;
}
if ($state_id === '') {
    $response['message'] = 'State is required.';
    echo json_encode($response);
    exit;
}
if ($city_id === '') {
    $response['message'] = 'City is required.';
    echo json_encode($response);
    exit;
}
if ($plan_id === '') {
    $response['message'] = 'Plan is required.';
    echo json_encode($response);
    exit;
}

if ($id > 0) {
    $uniqueSql = "SELECT * FROM $tbl WHERE name = '" . $name . "' AND id != $id";
} else {
    $uniqueSql = "SELECT * FROM $tbl WHERE name = '" . $name . "' ";
}
$uniqueResult = db_row($uniqueSql);
if (!empty($uniqueResult)) {
    $response['message'] = 'Company name is already exist.';
    echo json_encode($response);
    exit;
}

$ip = getClientIp();
if ($id > 0) {
    $sql = "UPDATE $tbl SET name = '" . $name . "', person_name = '" . $person_name . "', mobile_no = '" . $mobile_no . "', 
            email = '" . $email . "', country_id = '" . $country_id . "', state_id = '" . $state_id . "',
            city_id = '" . $city_id . "', plan_id = '" . $plan_id . "', updated_by = '" . $userId . "', updated_at = NOW() WHERE id = " . (int) $id;
    $msg = $pageNm . ' updated successfully.';
    $modules = db_query($sql);
    $company_id = $id;
} else {
    $password = trim(password_hash($_POST['password'], PASSWORD_BCRYPT) ?? '');
    $sql = "INSERT INTO $tbl(name, person_name, mobile_no, email, password, country_id, state_id, city_id, plan_id, created_by, created_at, updated_at) 
            VALUES ('" . $name . "', '" . $person_name . "', '" . $mobile_no . "', '" . $email . "', '" . $password . "', 
            '" . $country_id . "', '" . $state_id . "', '" . $city_id . "', '" . $plan_id . "', '" . $userId . "', NOW(), NOW())";
    $msg = $pageNm . ' created successfully.';
    $modules = db_query($sql);
    $company_id = db_insert_id($conn);
}

$role_name = $name;
$sql_country = "SELECT * FROM roles WHERE company_id = $company_id AND name = '" . $role_name . "' ";
$rolesData = db_row($sql_country);

//roles create  
if (isset($rolesData) && !empty($rolesData)) {
    $role_id = $rolesData['id'];
} else {
    $comSql = "INSERT INTO roles (company_id, name, created_by, created_at, updated_at) 
                    VALUES ('" . $company_id . "', '" . $role_name . "', '" . $userId . "', NOW(), NOW())";
    $roles = db_query($comSql);
    $role_id = db_insert_id($conn);
}

// user create
$sql_users = "SELECT * FROM users WHERE company_id = $company_id AND role_id = $role_id";
$userData = db_row($sql_users);

if (isset($userData) && !empty($userData)) {
    $sql = "UPDATE users SET name = '" . $person_name . "', username = '" . $name . "', email = '".$email."', mobile_no = '" . $mobile_no . "', country_id = '" . $country_id . "', state_id = '" . $state_id . "', city_id = '" . $city_id . "', company_plan_id = '" . $plan_id . "', ip_address = '" . $ip . "', updated_at = NOW() WHERE id = " . (int) $userData['id'];
    $modules = db_query($sql);
} else {
    $sql = "INSERT INTO users(role_id, company_id, company_plan_id, name, username, email, password, mobile_no, country_id, state_id, city_id, user_type, ip_address, created_by, created_at, updated_at) 
                        VALUES ('" . $role_id . "', '" . $company_id . "', '" . $plan_id . "', '" . $person_name . "', '" . $name . "', '" . $email . "', '" . $password . "', '" . $mobile_no . "', '" . $country_id . "', '" . $state_id . "', '" . $city_id . "', 'company_admin', '".$ip."', '".$userId."', NOW(), NOW())";
    $res = db_query($sql);
}

// Add all permissions for the company's plan panel_right modules
if ($plan_id > 0 && !empty($company_id) && !empty($role_id)) {
    $planRow = db_row("SELECT panel_right FROM plan WHERE id = " . (int)$plan_id . " LIMIT 1");
    if (!empty($planRow['panel_right'])) {
        $moduleIds = array_filter(array_map('trim', explode(',', $planRow['panel_right'])));
        foreach ($moduleIds as $mId) {
            $mId = (int)$mId;
            if ($mId <= 0) continue;

            $existPerm = db_row("SELECT id FROM role_permissions WHERE company_id = $company_id AND role_id = $role_id AND module_id = $mId LIMIT 1");
            if (!empty($existPerm)) {
                $permSql = "UPDATE role_permissions 
                            SET views = 1, adds = 1, updates = 1, deletes = 1, updated_by = " . $userId . ", updated_at = NOW() 
                            WHERE id = " . (int)$existPerm['id'];
            } else {
                $permSql = "INSERT INTO role_permissions (company_id, role_id, module_id, views, adds, updates, deletes, created_by, created_at, updated_at) 
                            VALUES ($company_id, $role_id, $mId, 1, 1, 1, 1, $userId, NOW(), NOW())";
            }
            db_query($permSql);
        }
    }
}

if ($modules) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] =
        'Failed to create ' . $pageNm;
}
echo json_encode($response);
exit;
