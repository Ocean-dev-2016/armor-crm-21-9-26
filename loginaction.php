<?php

session_start();
require_once __DIR__ . '/conn/db.php';
require_once __DIR__ . '/conn/dbqry.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}
$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login)) {
    echo json_encode([
        'status' => false,
        'message' => 'Please enter your email or username.'
    ]);
    exit;
}
if (empty($password)) {
    echo json_encode([
        'status' => false,
        'message' => 'Please enter your password.'
    ]);
    exit;
}

$login = mysqli_real_escape_string($conn, $login);
$sql = "SELECT * FROM users WHERE email = '$login' OR username = '$login' LIMIT 1";
$result = db_row($sql);

if (!$result) {
    error_log(mysqli_error($conn));
    echo json_encode([
        'status' => false,
        'message' => 'This User not found. Please try again.'
    ]);
    exit;
}

if (empty($result)) {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid username'
    ]);
    exit;
}
$user = $result;
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid password.'
    ]);
    exit;
}
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['company_id'] = $user['company_id'];
$_SESSION['plan_id'] = $user['company_plan_id'] ?? 0;
$_SESSION['user_type'] = $user['user_type'];
$_SESSION['name'] = $user['name'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];

// For non-superadmin users: load allowed module IDs from their plan's panel_right
if ($user['user_type'] !== 'superadmin') {
    $plan_id = (int)($user['company_plan_id'] ?? 0);
    $allowed_modules = [];
    if ($plan_id > 0) {
        $plan_sql = "SELECT panel_right FROM plan WHERE id = $plan_id AND status = 1 LIMIT 1";
        $plan_row = db_row($plan_sql);
        if ($plan_row && !empty($plan_row['panel_right'])) {
            $allowed_modules = array_map('intval', array_filter(array_map('trim', explode(',', $plan_row['panel_right']))));
        }
    }
    $_SESSION['allowed_modules'] = $allowed_modules;
} else {
    // Superadmin sees everything — no restriction
    $_SESSION['allowed_modules'] = null;
}

// $password = trim(password_hash('12345678', PASSWORD_BCRYPT) ?? '');
// $sql = "INSERT into users (name, username, email, password, user_type, status) VALUES 
// ('superadmin', 'superadmin', 'superadmin@gmail.com', '$password', 'superadmin', '1')";
// db_query($sql);

$redirect = SITE_URL;
// switch ($user['user_type']) {

//     case 'superadmin':

//         $redirect = SITE_URL . 'admin/dashboard';

//         break;


//     case 'company_admin':

//         $redirect = SITE_URL . 'company/dashboard';

//         break;


//     case 'employee':

//         $redirect = SITE_URL . 'employee/dashboard';

//         break;


//     case 'customer':

//         $redirect = SITE_URL . 'customer/dashboard';

//         break;


//     default:

//         echo json_encode([
//             'status' => false,
//             'message' => 'Invalid user type.'
//         ]);

//         exit;
// }

echo json_encode([
    'status' => true,
    'message' => 'Login successful.',
    'redirect' => $redirect
]);

exit;
