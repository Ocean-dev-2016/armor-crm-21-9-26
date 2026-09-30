<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/../conn/dbqry.php';

function apiResponse($status, $message, $data = [])
{
    http_response_code($status);
    echo json_encode([
        'status'  => $status === 200,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}


$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'login') {
    if ($method !== 'POST') {
        apiResponse(405, 'Only POST method is allowed.');
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $login = trim($input['username'] ?? ($input['login'] ?? ($input['email'] ?? '')));
    $password = $input['password'] ?? '';

    if ($login === '' || $password === '') {
        apiResponse(400, 'Username/Email and password are required.');
    }

    $loginEscaped = db_escape($login);

    $sql = "SELECT id, name, username, email, password, user_type, company_id, role_id, company_plan_id, app_key, status FROM users 
        WHERE (username = '$loginEscaped' OR email = '$loginEscaped') LIMIT 1";

    $user = db_row($sql);

    if (!$user) {
        apiResponse(401, 'Invalid username/email');
    }

    if (isset($user['status']) && (int)$user['status'] !== 1) {
        apiResponse(403, 'Your account is inactive. Please contact administrator.');
    }

    if (!password_verify($password, $user['password'])) {
        apiResponse(401, 'Invalid password.');
    }

    // Generate Bearer / API token and update in database
    $token = bin2hex(random_bytes(32));
    $tokenEscaped = db_escape($token);
    $userId = (int)$user['id'];
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    db_query("UPDATE users SET app_key = '$tokenEscaped', app_key_expires_at = '$expiresAt', updated_at = NOW() WHERE id = $userId");

    unset($user['password']);
    unset($user['app_key']);

    apiResponse(200, 'Login successful.', [
        'token' => $token,
        'expires_at' => $expiresAt,
        'user'  => $user
    ]);
}

function getBearerToken()
{
    $headers = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    if (!empty($headers) && preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
        return trim($matches[1]);
    }

    return trim($_GET['token'] ?? ($_POST['token'] ?? ''));
}

function getAuthenticatedUser()
{
    $token = getBearerToken();
    if (empty($token)) {
        apiResponse(401, 'Authorization token is required.');
    }

    $tokenEscaped = db_escape($token);
    $user = db_row("SELECT id, name, username, email, user_type, company_id, role_id, company_plan_id, app_key_expires_at, status 
                    FROM users 
                    WHERE app_key = '$tokenEscaped' LIMIT 1");

    if (!$user) {
        apiResponse(401, 'Invalid or expired token.');
    }

    if (isset($user['status']) && (int)$user['status'] !== 1) {
        apiResponse(403, 'User account is inactive.');
    }

    if (!empty($user['app_key_expires_at']) && strtotime($user['app_key_expires_at']) < time()) {
        apiResponse(401, 'Token has expired. Please login again.');
    }

    return $user;
}

if ($action === 'get_role') {
    if ($method !== 'GET') {
        apiResponse(405, 'Only GET method is allowed.');
    }

    $authUser = getAuthenticatedUser();
    $roleId = (int)($authUser['role_id'] ?? 0);
    $companyId = (int)($authUser['company_id'] ?? 0);

    $roleData = null;

    if ($authUser['user_type'] === 'superadmin' || ($roleId === 0 && $companyId === 0)) {
        $roleData = [
            'id' => 0,
            'name' => 'Superadmin',
            'company_id' => 0,
            'parent_id' => 0,
            'user_type' => 'superadmin',
            'is_superadmin' => true,
            'permissions' => 'ALL'
        ];
    } else {
        $roleData = db_rows("SELECT company_id, name FROM roles WHERE company_id = $companyId");
    }

    apiResponse(200, 'Role retrieved successfully.', [
        'user_id' => (int)$authUser['id'],
        'username' => $authUser['username'],
        'role' => $roleData
    ]);
}

if ($action === 'create_company') {
    if ($method !== 'POST') {
        apiResponse(405, 'Only POST method is allowed.');
    }

    $authUser = getAuthenticatedUser();
    $userId = (int)($authUser['id'] ?? 0);

    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);
    $input = is_array($jsonInput) ? $jsonInput : $_POST;

    $company_type_id = (int)($input['company_type_id'] ?? 0);
    $name            = trim($input['name'] ?? '');
    $person_name     = trim($input['person_name'] ?? '');
    $mobile_no       = trim($input['mobile_no'] ?? '');
    $email           = strtolower(trim($input['email'] ?? ''));
    $password_raw    = trim($input['password'] ?? '');
    $country_id      = (int)($input['country_id'] ?? 0);
    $state_id        = (int)($input['state_id'] ?? 0);
    $city_id         = (int)($input['city_id'] ?? 0);
    $plan_id         = (int)($input['plan_id'] ?? 0);

    // Validations for required fields only
    if ($company_type_id <= 0) {
        apiResponse(400, 'Company type is required.');
    }
    if ($name === '') {
        apiResponse(400, 'Company name is required.');
    }
    if ($person_name === '') {
        apiResponse(400, 'Person name is required.');
    }
    if ($mobile_no === '') {
        apiResponse(400, 'Mobile no is required.');
    }
    if ($email === '') {
        apiResponse(400, 'Email is required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        apiResponse(400, 'Please enter a valid email address.');
    }
    if ($password_raw === '') {
        apiResponse(400, 'Password is required.');
    }
    if ($country_id <= 0) {
        apiResponse(400, 'Country is required.');
    }
    if ($state_id <= 0) {
        apiResponse(400, 'State is required.');
    }
    if ($city_id <= 0) {
        apiResponse(400, 'City is required.');
    }
    if ($plan_id <= 0) {
        apiResponse(400, 'Plan is required.');
    }

    // Unique checks
    $nameEsc = db_escape($name);
    $uniqueName = db_row("SELECT id FROM company WHERE name = '$nameEsc' AND status = 1 LIMIT 1");
    if (!empty($uniqueName)) {
        apiResponse(400, 'Company name already exists.');
    }

    $emailEsc = db_escape($email);
    $uniqueEmail = db_row("SELECT id FROM company WHERE email = '$emailEsc' AND status = 1 LIMIT 1");
    if (!empty($uniqueEmail)) {
        apiResponse(400, 'Email already exists. Please use a different email.');
    }

    // Check unique email in users table
    $uniqueUserEmail = db_row("SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
    if (!empty($uniqueUserEmail)) {
        apiResponse(400, 'User with this email already exists.');
    }

    // Escapes & Hash
    $personNameEsc  = db_escape($person_name);
    $mobileNoEsc    = db_escape($mobile_no);
    $hashedPassword = password_hash($password_raw, PASSWORD_BCRYPT);
    $ip             = $_SERVER['REMOTE_ADDR'] ?? '';

    // Insert only required fields into company table
    $sqlCompany = "INSERT INTO company (
        company_type_id, name, person_name, mobile_no, email, password, 
        country_id, state_id, city_id, plan_id, created_by, created_at, updated_at
    ) VALUES (
        '$company_type_id', '$nameEsc', '$personNameEsc', '$mobileNoEsc', '$emailEsc', '$hashedPassword',
        '$country_id', '$state_id', '$city_id', '$plan_id', '$userId', NOW(), NOW()
    )";

    $resCompany = db_query($sqlCompany);
    if (!$resCompany) {
        apiResponse(500, 'Failed to create company: ' . mysqli_error($conn));
    }

    $newCompanyId = db_insert_id($conn);

    // 1. Create Default Role for this company
    $roleCheck = db_row("SELECT id FROM roles WHERE company_id = $newCompanyId AND name = '$nameEsc' LIMIT 1");
    if (!empty($roleCheck)) {
        $roleId = (int)$roleCheck['id'];
    } else {
        db_query("INSERT INTO roles (company_id, name, created_by, created_at, updated_at) 
                  VALUES ('$newCompanyId', '$nameEsc', '$userId', NOW(), NOW())");
        $roleId = db_insert_id($conn);
    }

    // 2. Create Company Admin User in users table
    $sqlUser = "INSERT INTO users (
        role_id, company_id, company_plan_id, name, username, email, password, 
        mobile_no, country_id, state_id, city_id, user_type, ip_address, created_by, created_at, updated_at
    ) VALUES (
        '$roleId', '$newCompanyId', '$plan_id', '$personNameEsc', '$nameEsc', '$emailEsc', '$hashedPassword',
        '$mobileNoEsc', '$country_id', '$state_id', '$city_id', 'company_admin', '$ip', '$userId', NOW(), NOW()
    )";
    db_query($sqlUser);
    $newUserId = db_insert_id($conn);

    // 3. Assign Plan Permissions to Role
    if ($plan_id > 0 && !empty($newCompanyId) && !empty($roleId)) {
        $planRow = db_row("SELECT panel_right FROM plan WHERE id = " . (int)$plan_id . " LIMIT 1");
        if (!empty($planRow['panel_right'])) {
            $moduleIds = array_filter(array_map('trim', explode(',', $planRow['panel_right'])));
            foreach ($moduleIds as $mId) {
                $mId = (int)$mId;
                if ($mId <= 0) continue;

                $existPerm = db_row("SELECT id FROM role_permissions WHERE company_id = $newCompanyId AND role_id = $roleId AND module_id = $mId LIMIT 1");
                if (!empty($existPerm)) {
                    $permSql = "UPDATE role_permissions 
                                SET views = 1, adds = 1, updates = 1, deletes = 1, print = 1, excel = 1, created_by = $userId, updated_at = NOW() 
                                WHERE id = " . (int)$existPerm['id'];
                } else {
                    $permSql = "INSERT INTO role_permissions (company_id, role_id, module_id, views, adds, updates, deletes, print, excel, created_by, created_at, updated_at) 
                                VALUES ($newCompanyId, $roleId, $mId, 1, 1, 1, 1, 1, 1, $userId, NOW(), NOW())";
                }
                db_query($permSql);
            }
        }
    }

    apiResponse(200, 'Company created successfully.', [
        'company_id' => $newCompanyId,
        'user_id'    => $newUserId,
        'role_id'    => $roleId,
        'name'       => $name,
        'email'      => $email
    ]);
}


apiResponse(404, 'Invalid API action.');