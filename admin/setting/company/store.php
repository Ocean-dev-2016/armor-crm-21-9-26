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

// Real-time unique check for jQuery Validation remote method
if (isset($_GET['action']) && $_GET['action'] === 'check_unique') {
    $field = trim($_GET['field'] ?? '');
    $value = trim($_GET['value'] ?? '');
    $excludeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!in_array($field, ['email', 'gst', 'name'], true) || $value === '') {
        echo json_encode(true);
        exit;
    }

    $fieldEsc = db_escape(strtolower($value));
    if ($field === 'gst') {
        $fieldEsc = db_escape(strtoupper($value));
    }

    $idCond = ($excludeId > 0) ? " AND id != $excludeId" : "";
    $row = db_row("SELECT id FROM $tbl WHERE $field = '$fieldEsc' AND status = 1 $idCond LIMIT 1");
    if (!empty($row)) {
        echo json_encode(false);
    } else {
        echo json_encode(true);
    }
    exit;
}

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
$name              = trim($_POST['name'] ?? '');
$company_type_id   = (int)($_POST['company_type_id'] ?? 0);
$prefix            = strtoupper(trim($_POST['prefix'] ?? ''));
$gst               = strtoupper(trim($_POST['gst'] ?? ''));
$indiamart_api_key = trim($_POST['indiamart_api_key'] ?? '');
$pan_card          = strtoupper(trim($_POST['pan_card'] ?? ''));
$address           = trim($_POST['address'] ?? '');
$bank_details      = trim($_POST['bank_details'] ?? '');
$terms_conditions  = trim($_POST['terms_conditions'] ?? '');
$person_name       = trim($_POST['person_name'] ?? '');
$mobile_no         = trim($_POST['mobile_no'] ?? '');
$email             = strtolower(trim($_POST['email'] ?? ''));
$country_id        = trim($_POST['country_id'] ?? '');
$state_id          = trim($_POST['state_id'] ?? '');
$city_id           = trim($_POST['city_id'] ?? '');
$plan_id           = trim($_POST['plan_id'] ?? '');

if ($company_type_id <= 0) {
    $response['message'] = 'Please select company type.';
    echo json_encode($response);
    exit;
}
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
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
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

// Format validation for Pan Card (10 alphanumeric characters: 5 letters, 4 digits, 1 letter)
if ($pan_card !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan_card)) {
    $response['message'] = 'Please enter a valid PAN Card number (e.g. ABCDE1234F).';
    echo json_encode($response);
    exit;
}

// Format validation for GST No (15 characters: 2 digits, 5 letters, 4 digits, 1 letter, 1 alphanumeric, 'Z', 1 alphanumeric)
if ($gst !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst)) {
    $response['message'] = 'Please enter a valid 15-digit GST number (e.g. 22AAAAA0000A1Z5).';
    echo json_encode($response);
    exit;
}

// Unique check: Company Name (only active records where status = 1)
$nameEsc = db_escape($name);
$idCondition = ($id > 0) ? " AND id != " . (int)$id : "";
$uniqueNameSql = "SELECT id FROM $tbl WHERE name = '$nameEsc' AND status = 1 $idCondition LIMIT 1";
if (!empty(db_row($uniqueNameSql))) {
    $response['message'] = 'Company name already exists.';
    echo json_encode($response);
    exit;
}

// Unique check: Email (only active records where status = 1)
$emailEsc = db_escape($email);
$uniqueEmailSql = "SELECT id FROM $tbl WHERE email = '$emailEsc' AND status = 1 $idCondition LIMIT 1";
if (!empty(db_row($uniqueEmailSql))) {
    $response['message'] = 'Email already exists. Please use a different email.';
    echo json_encode($response);
    exit;
}

// Unique check: GST (if provided, only active records where status = 1)
if ($gst !== '') {
    $gstEsc = db_escape($gst);
    $uniqueGstSql = "SELECT id FROM $tbl WHERE gst = '$gstEsc' AND status = 1 $idCondition LIMIT 1";
    if (!empty(db_row($uniqueGstSql))) {
        $response['message'] = 'GST number already exists. Please enter a unique GST number.';
        echo json_encode($response);
        exit;
    }
}

// Upload directory
$uploadDir = BASE_PATH . '/uploads/company/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Header Image
$headerImageName = null;
if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath   = $_FILES['header_image']['tmp_name'];
    $fileName      = $_FILES['header_image']['name'];
    $fileSize      = $_FILES['header_image']['size'];
    $fileNameCmps  = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileExtension, $allowedExtensions, true)) {
        $response['message'] = 'Only image files (jpg, jpeg, png, webp) are allowed for header image.';
        echo json_encode($response);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        $response['message'] = 'Header image size must not exceed 5MB.';
        echo json_encode($response);
        exit;
    }

    $newFileName = 'header_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
    $destPath = $uploadDir . $newFileName;
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $headerImageName = $newFileName;
    }
}

// Handle Footer Image
$footerImageName = null;
if (isset($_FILES['footer_image']) && $_FILES['footer_image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath   = $_FILES['footer_image']['tmp_name'];
    $fileName      = $_FILES['footer_image']['name'];
    $fileSize      = $_FILES['footer_image']['size'];
    $fileNameCmps  = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileExtension, $allowedExtensions, true)) {
        $response['message'] = 'Only image files (jpg, jpeg, png, webp) are allowed for footer image.';
        echo json_encode($response);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        $response['message'] = 'Footer image size must not exceed 5MB.';
        echo json_encode($response);
        exit;
    }

    $newFileName = 'footer_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
    $destPath = $uploadDir . $newFileName;
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $footerImageName = $newFileName;
    }
}

$removeHeader = (isset($_POST['remove_header_image']) && $_POST['remove_header_image'] == '1');
$removeFooter = (isset($_POST['remove_footer_image']) && $_POST['remove_footer_image'] == '1');

$prefixEsc = db_escape($prefix);
$gstEsc = db_escape($gst);
$indiamartEsc = db_escape($indiamart_api_key);
$panCardEsc = db_escape($pan_card);
$addressEsc = db_escape($address);
$bankDetailsEsc = db_escape($bank_details);
$termsConditionsEsc = db_escape($terms_conditions);
$personNameEsc = db_escape($person_name);
$mobileNoEsc = db_escape($mobile_no);
$emailEsc = db_escape($email);
$countryIdEsc = db_escape($country_id);
$stateIdEsc = db_escape($state_id);
$cityIdEsc = db_escape($city_id);
$planIdEsc = db_escape($plan_id);

$ip = getClientIp();
if ($id > 0) {
    $existing = db_row("SELECT header_image, footer_image FROM $tbl WHERE id = " . (int)$id . " LIMIT 1");

    $headerImageSql = "";
    if ($headerImageName !== null) {
        if (!empty($existing['header_image']) && file_exists($uploadDir . $existing['header_image'])) {
            unlink($uploadDir . $existing['header_image']);
        }
        $headerImageSql = ", header_image = '$headerImageName'";
    } elseif ($removeHeader) {
        if (!empty($existing['header_image']) && file_exists($uploadDir . $existing['header_image'])) {
            unlink($uploadDir . $existing['header_image']);
        }
        $headerImageSql = ", header_image = NULL";
    }

    $footerImageSql = "";
    if ($footerImageName !== null) {
        if (!empty($existing['footer_image']) && file_exists($uploadDir . $existing['footer_image'])) {
            unlink($uploadDir . $existing['footer_image']);
        }
        $footerImageSql = ", footer_image = '$footerImageName'";
    } elseif ($removeFooter) {
        if (!empty($existing['footer_image']) && file_exists($uploadDir . $existing['footer_image'])) {
            unlink($uploadDir . $existing['footer_image']);
        }
        $footerImageSql = ", footer_image = NULL";
    }

    $sql = "UPDATE $tbl SET 
                company_type_id = '$company_type_id',
                name = '$nameEsc',
                prefix = '$prefixEsc',
                gst = '$gstEsc',
                indiamart_api_key = '$indiamartEsc',
                pan_card = '$panCardEsc',
                address = '$addressEsc',
                bank_details = '$bankDetailsEsc',
                terms_conditions = '$termsConditionsEsc',
                person_name = '$personNameEsc',
                mobile_no = '$mobileNoEsc', 
                email = '$emailEsc',
                country_id = '$countryIdEsc',
                state_id = '$stateIdEsc',
                city_id = '$cityIdEsc',
                plan_id = '$planIdEsc'
                $headerImageSql
                $footerImageSql,
                updated_by = '$userId',
                updated_at = NOW() 
            WHERE id = " . (int) $id;

    $msg = $pageNm . ' updated successfully.';
    $modules = db_query($sql);
    $company_id = $id;
} else {
    $password = trim(password_hash($_POST['password'], PASSWORD_BCRYPT) ?? '');
    $headerVal = $headerImageName !== null ? "'$headerImageName'" : "NULL";
    $footerVal = $footerImageName !== null ? "'$footerImageName'" : "NULL";

    $sql = "INSERT INTO $tbl (
                company_type_id, name, prefix, gst, indiamart_api_key, pan_card, 
                header_image, footer_image, address, bank_details, terms_conditions,
                person_name, mobile_no, email, password, country_id, state_id, city_id, plan_id, 
                created_by, created_at, updated_at
            ) VALUES (
                '$company_type_id', '$nameEsc', '$prefixEsc', '$gstEsc', '$indiamartEsc', '$panCardEsc',
                $headerVal, $footerVal, '$addressEsc', '$bankDetailsEsc', '$termsConditionsEsc',
                '$personNameEsc', '$mobileNoEsc', '$emailEsc', '$password', '$countryIdEsc', '$stateIdEsc', '$cityIdEsc', '$planIdEsc',
                '$userId', NOW(), NOW()
            )";
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
