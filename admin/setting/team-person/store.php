<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'users';
$pageNm = 'Team Person';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();

// Fetch roles, existing company users, and plan limit info for a selected company
if (isset($_GET['action']) && $_GET['action'] === 'get_company_data') {
    $company_id = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
    if ($company_id <= 0) {
        $response['message'] = 'Invalid company ID.';
        echo json_encode($response);
        exit;
    }

    // 1. Roles belonging to this company
    $roles = db_rows("SELECT id, name FROM roles WHERE company_id = $company_id AND status = 1 ORDER BY name ASC");

    // 2. Users of this company (for Parent User selection)
    $parentUsers = db_rows("SELECT id, name, username FROM users WHERE company_id = $company_id AND status = 1 ORDER BY name ASC");

    // 3. Plan max_team_user and current team persons count
    $planInfo = db_row("SELECT c.id, c.plan_id, p.name AS plan_name, p.max_team_user 
                        FROM company c 
                        LEFT JOIN plan p ON p.id = c.plan_id 
                        WHERE c.id = $company_id LIMIT 1");
    $maxTeamUser = isset($planInfo['max_team_user']) ? (int) $planInfo['max_team_user'] : 0;
    $countRow = db_row("SELECT COUNT(*) AS total FROM users WHERE company_id = $company_id AND user_type = 'company_admin'");
    $currentCount = (int) ($countRow['total'] ?? 0);

    $response['status'] = true;
    $response['message'] = 'Data retrieved successfully.';
    $response['roles'] = $roles ?: [];
    $response['parent_users'] = $parentUsers ?: [];
    $response['plan_info'] = [
        'plan_name'     => $planInfo['plan_name'] ?? 'N/A',
        'max_team_user' => $maxTeamUser,
        'current_count' => $currentCount,
        'can_add'       => ($maxTeamUser <= 0 || $currentCount < $maxTeamUser)
    ];
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$id          = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission('team-person', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' team persons.';
    echo json_encode($response);
    exit;
}

$company_id  = isset($_POST['company_id']) ? (int) $_POST['company_id'] : 0;
$parent_user = isset($_POST['parent_user']) ? (int) $_POST['parent_user'] : 0;
$role_id     = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;

$name        = trim($_POST['name'] ?? '');
$username    = trim($_POST['username'] ?? '');
$email       = trim($_POST['email'] ?? '');
$mobile_no   = trim($_POST['mobile_no'] ?? '');
$password    = $_POST['password'] ?? '';
$address     = trim($_POST['address'] ?? '');

$country_id  = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;
$state_id    = isset($_POST['state_id']) ? (int) $_POST['state_id'] : 0;
$city_id     = isset($_POST['city_id']) ? (int) $_POST['city_id'] : 0;

if ($company_id <= 0) {
    $response['message'] = 'Please select a company.';
    echo json_encode($response);
    exit;
}
if ($name === '') {
    $response['message'] = 'Name is required.';
    echo json_encode($response);
    exit;
}
if ($username === '') {
    $response['message'] = 'Username is required.';
    echo json_encode($response);
    exit;
}
if ($email === '') {
    $response['message'] = 'Email is required.';
    echo json_encode($response);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}
if ($mobile_no === '') {
    $response['message'] = 'Mobile number is required.';
    echo json_encode($response);
    exit;
}
if ($id === 0 && empty($password)) {
    $response['message'] = 'Password is required.';
    echo json_encode($response);
    exit;
}
if ($role_id <= 0) {
    $response['message'] = 'Please select a role.';
    echo json_encode($response);
    exit;
}
if ($country_id <= 0) {
    $response['message'] = 'Please select a country.';
    echo json_encode($response);
    exit;
}
if ($state_id <= 0) {
    $response['message'] = 'Please select a state.';
    echo json_encode($response);
    exit;
}
if ($city_id <= 0) {
    $response['message'] = 'Please select a city.';
    echo json_encode($response);
    exit;
}

// Escape strings
$name_esc     = db_escape($name);
$username_esc = db_escape($username);
$email_esc    = db_escape($email);
$mobile_esc   = db_escape($mobile_no);
$address_esc  = db_escape($address);

// Unique Username check
$userCheckSql = ($id > 0)
    ? "SELECT id FROM $tbl WHERE username = '$username_esc' AND id != $id LIMIT 1"
    : "SELECT id FROM $tbl WHERE username = '$username_esc' LIMIT 1";
if (!empty(db_row($userCheckSql))) {
    $response['message'] = 'Username is already taken. Please choose another.';
    echo json_encode($response);
    exit;
}

// Unique Email check
$emailCheckSql = ($id > 0)
    ? "SELECT id FROM $tbl WHERE email = '$email_esc' AND id != $id LIMIT 1"
    : "SELECT id FROM $tbl WHERE email = '$email_esc' LIMIT 1";
if (!empty(db_row($emailCheckSql))) {
    $response['message'] = 'Email is already registered. Please choose another.';
    echo json_encode($response);
    exit;
}

// Get company's plan_id and plan details
$companyRow = db_row("SELECT c.plan_id, p.name AS plan_name, p.max_team_user 
                      FROM company c 
                      LEFT JOIN plan p ON p.id = c.plan_id 
                      WHERE c.id = $company_id LIMIT 1");
$company_plan_id = (int) ($companyRow['plan_id'] ?? 0);
$maxTeamUser     = isset($companyRow['max_team_user']) ? (int) $companyRow['max_team_user'] : 0;
$planName        = $companyRow['plan_name'] ?? 'Company Plan';

// Check max_team_user limit for new team persons (or when transferring between companies)
if ($id === 0) {
    if ($maxTeamUser > 0) {
        $countRow = db_row("SELECT COUNT(*) AS total FROM $tbl WHERE company_id = $company_id AND user_type = 'company_admin'");
        $currentCount = (int) ($countRow['total'] ?? 0);
        if ($currentCount >= $maxTeamUser) {
            $response['message'] = "Cannot create Team Person. The selected company plan ($planName) allows a maximum of $maxTeamUser team person(s), and all $maxTeamUser slot(s) are already filled.";
            echo json_encode($response);
            exit;
        }
    }
} else {
    // If editing and changing company, verify limit on the target company
    $existingUser = db_row("SELECT company_id FROM $tbl WHERE id = $id LIMIT 1");
    if ($existingUser && (int)$existingUser['company_id'] !== $company_id) {
        if ($maxTeamUser > 0) {
            $countRow = db_row("SELECT COUNT(*) AS total FROM $tbl WHERE company_id = $company_id AND user_type = 'company_admin'");
            $currentCount = (int) ($countRow['total'] ?? 0);
            if ($currentCount >= $maxTeamUser) {
                $response['message'] = "Cannot transfer Team Person. The selected company plan ($planName) allows a maximum of $maxTeamUser team person(s), and all $maxTeamUser slot(s) are already filled.";
                echo json_encode($response);
                exit;
            }
        }
    }
}

$ip = getClientIp();

if ($id > 0) {
    $updatePasswordSql = "";
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $updatePasswordSql = ", password = '$hashed'";
    }

    $sql = "UPDATE $tbl SET 
                role_id = $role_id,
                company_id = $company_id,
                company_plan_id = $company_plan_id,
                parent_user = $parent_user,
                name = '$name_esc',
                username = '$username_esc',
                email = '$email_esc',
                mobile_no = '$mobile_esc',
                country_id = $country_id,
                state_id = $state_id,
                city_id = $city_id,
                address = '$address_esc',
                ip_address = '$ip',
                updated_at = NOW()
                $updatePasswordSql 
            WHERE id = $id";

    $res = db_query($sql);
    $msg = $pageNm . ' updated successfully.';
} else {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $sql = "INSERT INTO $tbl(role_id, company_id, company_plan_id, parent_user, name, username, email, password, mobile_no, country_id, state_id, city_id, address, user_type, ip_address, status, created_by, created_at, updated_at) 
            VALUES ($role_id, $company_id, $company_plan_id, $parent_user, '$name_esc', '$username_esc', '$email_esc', '$hashed', '$mobile_esc', $country_id, $state_id, $city_id, '$address_esc', 'company_admin', '$ip', 1, '$userId', NOW(), NOW())";

    $res = db_query($sql);
    $msg = $pageNm . ' created successfully.';
}

if ($res) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] = 'Failed to save ' . $pageNm;
}

echo json_encode($response);
exit;
