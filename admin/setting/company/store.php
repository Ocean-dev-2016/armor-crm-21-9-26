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

// Step 2 fields
$order_prefix = trim($_POST['order_prefix'] ?? '');
$order_title = trim($_POST['order_title'] ?? '');
$order_view_color = trim($_POST['order_view_color'] ?? '');

$quotation_prefix = trim($_POST['quotation_prefix'] ?? '');
$quotation_title = trim($_POST['quotation_title'] ?? '');
$quotation_view_color = trim($_POST['quotation_view_color'] ?? '');

$dispatch_prefix = trim($_POST['dispatch_prefix'] ?? '');
$dispatch_title = trim($_POST['dispatch_title'] ?? '');
$dispatch_view_color = trim($_POST['dispatch_view_color'] ?? '');

$packing_slip_prefix = trim($_POST['packing_slip_prefix'] ?? '');
$packing_slip_title = trim($_POST['packing_slip_title'] ?? '');
$packing_slip_view_color = trim($_POST['packing_slip_view_color'] ?? '');

// Step 3 fields
$is_stock_check = isset($_POST['is_stock_check']) ? (int)$_POST['is_stock_check'] : 0;
$stock_manage_by = trim($_POST['stock_manage_by'] ?? 'fifo');
if (!in_array($stock_manage_by, ['fifo', 'lifo'], true)) {
    $stock_manage_by = 'fifo';
}

// Step 5 fields
$bg_dark_color = trim($_POST['bg_dark_color'] ?? '');
$bg_light_color = trim($_POST['bg_light_color'] ?? '');
$customer_panel_login = isset($_POST['customer_panel_login']) ? (int)$_POST['customer_panel_login'] : 0;

// Upload directory
$uploadDir = BASE_PATH . '/uploads/company/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Helper function to handle image upload
function handleCompanyImageUpload($inputName, $prefixName, $uploadDir, &$response) {
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        $fileName      = $_FILES[$inputName]['name'];
        $fileSize      = $_FILES[$inputName]['size'];
        $fileNameCmps  = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'ico', 'svg'];
        if (!in_array($fileExtension, $allowedExtensions, true)) {
            $response['message'] = 'Only image files are allowed for ' . str_replace('_', ' ', $inputName) . '.';
            echo json_encode($response);
            exit;
        }

        if ($fileSize > 5 * 1024 * 1024) {
            $response['message'] = 'Image size must not exceed 5MB.';
            echo json_encode($response);
            exit;
        }

        $newFileName = upload_and_convert_to_webp($_FILES[$inputName], $uploadDir, $prefixName, 85);
        if ($newFileName) {
            return $newFileName;
        }
    }
    return null;
}

// Step 4 images
$headerImageName = handleCompanyImageUpload('header_image', 'header', $uploadDir, $response);
$footerImageName = handleCompanyImageUpload('footer_image', 'footer', $uploadDir, $response);
$faviconName     = handleCompanyImageUpload('favicon', 'favicon', $uploadDir, $response);
$appLogoName     = handleCompanyImageUpload('app_logo', 'applogo', $uploadDir, $response);

$removeHeader = (isset($_POST['remove_header_image']) && $_POST['remove_header_image'] == '1');
$removeFooter = (isset($_POST['remove_footer_image']) && $_POST['remove_footer_image'] == '1');
$removeFavicon = (isset($_POST['remove_favicon']) && $_POST['remove_favicon'] == '1');
$removeAppLogo = (isset($_POST['remove_app_logo']) && $_POST['remove_app_logo'] == '1');

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

$orderPrefixEsc = db_escape($order_prefix);
$orderTitleEsc = db_escape($order_title);
$orderColorEsc = db_escape($order_view_color);

$quotationPrefixEsc = db_escape($quotation_prefix);
$quotationTitleEsc = db_escape($quotation_title);
$quotationColorEsc = db_escape($quotation_view_color);

$dispatchPrefixEsc = db_escape($dispatch_prefix);
$dispatchTitleEsc = db_escape($dispatch_title);
$dispatchColorEsc = db_escape($dispatch_view_color);

$packingPrefixEsc = db_escape($packing_slip_prefix);
$packingTitleEsc = db_escape($packing_slip_title);
$packingColorEsc = db_escape($packing_slip_view_color);

$stockManageByEsc = db_escape($stock_manage_by);
$bgDarkColorEsc = db_escape($bg_dark_color);
$bgLightColorEsc = db_escape($bg_light_color);

$ip = getClientIp();
if ($id > 0) {
    $existing = db_row("SELECT header_image, footer_image, favicon, app_logo FROM $tbl WHERE id = " . (int)$id . " LIMIT 1");

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

    $faviconSql = "";
    if ($faviconName !== null) {
        if (!empty($existing['favicon']) && file_exists($uploadDir . $existing['favicon'])) {
            unlink($uploadDir . $existing['favicon']);
        }
        $faviconSql = ", favicon = '$faviconName'";
    } elseif ($removeFavicon) {
        if (!empty($existing['favicon']) && file_exists($uploadDir . $existing['favicon'])) {
            unlink($uploadDir . $existing['favicon']);
        }
        $faviconSql = ", favicon = NULL";
    }

    $appLogoSql = "";
    if ($appLogoName !== null) {
        if (!empty($existing['app_logo']) && file_exists($uploadDir . $existing['app_logo'])) {
            unlink($uploadDir . $existing['app_logo']);
        }
        $appLogoSql = ", app_logo = '$appLogoName'";
    } elseif ($removeAppLogo) {
        if (!empty($existing['app_logo']) && file_exists($uploadDir . $existing['app_logo'])) {
            unlink($uploadDir . $existing['app_logo']);
        }
        $appLogoSql = ", app_logo = NULL";
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
                plan_id = '$planIdEsc',
                order_prefix = '$orderPrefixEsc',
                order_title = '$orderTitleEsc',
                order_view_color = '$orderColorEsc',
                quotation_prefix = '$quotationPrefixEsc',
                quotation_title = '$quotationTitleEsc',
                quotation_view_color = '$quotationColorEsc',
                dispatch_prefix = '$dispatchPrefixEsc',
                dispatch_title = '$dispatchTitleEsc',
                dispatch_view_color = '$dispatchColorEsc',
                packing_slip_prefix = '$packingPrefixEsc',
                packing_slip_title = '$packingTitleEsc',
                packing_slip_view_color = '$packingColorEsc',
                is_stock_check = '$is_stock_check',
                stock_manage_by = '$stockManageByEsc',
                bg_dark_color = '$bgDarkColorEsc',
                bg_light_color = '$bgLightColorEsc',
                customer_panel_login = '$customer_panel_login'
                $headerImageSql
                $footerImageSql
                $faviconSql
                $appLogoSql,
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
    $faviconVal = $faviconName !== null ? "'$faviconName'" : "NULL";
    $appLogoVal = $appLogoName !== null ? "'$appLogoName'" : "NULL";

    $sql = "INSERT INTO $tbl (
                company_type_id, name, prefix, gst, indiamart_api_key, pan_card, 
                header_image, footer_image, favicon, app_logo, address, bank_details, terms_conditions,
                person_name, mobile_no, email, password, country_id, state_id, city_id, plan_id,
                order_prefix, order_title, order_view_color,
                quotation_prefix, quotation_title, quotation_view_color,
                dispatch_prefix, dispatch_title, dispatch_view_color,
                packing_slip_prefix, packing_slip_title, packing_slip_view_color,
                is_stock_check, stock_manage_by,
                bg_dark_color, bg_light_color, customer_panel_login,
                created_by, created_at, updated_at
            ) VALUES (
                '$company_type_id', '$nameEsc', '$prefixEsc', '$gstEsc', '$indiamartEsc', '$panCardEsc',
                $headerVal, $footerVal, $faviconVal, $appLogoVal, '$addressEsc', '$bankDetailsEsc', '$termsConditionsEsc',
                '$personNameEsc', '$mobileNoEsc', '$emailEsc', '$password', '$countryIdEsc', '$stateIdEsc', '$cityIdEsc', '$planIdEsc',
                '$orderPrefixEsc', '$orderTitleEsc', '$orderColorEsc',
                '$quotationPrefixEsc', '$quotationTitleEsc', '$quotationColorEsc',
                '$dispatchPrefixEsc', '$dispatchTitleEsc', '$dispatchColorEsc',
                '$packingPrefixEsc', '$packingTitleEsc', '$packingColorEsc',
                '$is_stock_check', '$stockManageByEsc',
                '$bgDarkColorEsc', '$bgLightColorEsc', '$customer_panel_login',
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
                            SET views = 1, adds = 1, updates = 1, deletes = 1, print = 1, excel= 1, created_by = " . $userId . ", updated_at = NOW() 
                            WHERE id = " . (int)$existPerm['id'];
            } else {
                $permSql = "INSERT INTO role_permissions (company_id, role_id, module_id, views, adds, updates, deletes, print, excel, created_by, created_at, updated_at) 
                            VALUES ($company_id, $role_id, $mId, 1, 1, 1, 1, 1, 1, $userId, NOW(), NOW())";
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
