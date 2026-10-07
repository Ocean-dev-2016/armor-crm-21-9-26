<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');

$tbl = 'customer';
$pageNm = 'Customer';
$moduleKey = 'customer';

$response = [
    'status'  => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

if ($userId <= 0) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$isEdit = $id > 0;

$requiredAction = $isEdit ? 'updates' : 'adds';
if (!hasPermission($moduleKey, $requiredAction)) {
    $response['message'] = 'You do not have permission to perform this action.';
    echo json_encode($response);
    exit;
}

// Company ID handling
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$companyId = $sessionCompanyId;
if ($isSuperadmin && isset($_POST['company_id']) && (int)$_POST['company_id'] > 0) {
    $companyId = (int)$_POST['company_id'];
}

if ($companyId <= 0 && !$isSuperadmin) {
    $response['message'] = 'Active Company is required. Please login with a company.';
    echo json_encode($response);
    exit;
}

// Input values
$clientCode       = trim($_POST['client_code'] ?? '');
$customerTypeId   = (int)($_POST['customer_type_id'] ?? 0);
$gstNo            = strtoupper(trim($_POST['gst_no'] ?? ''));
$name             = trim($_POST['name'] ?? '');
$passwordPlain    = trim($_POST['password'] ?? '');
$contactPerson    = trim($_POST['contact_person'] ?? '');
$mobileNo         = trim($_POST['mobile_no'] ?? '');
$whatsappNo       = trim($_POST['whatsapp_no'] ?? '');
$email            = trim($_POST['email'] ?? '');
$birthDateRaw     = trim($_POST['birth_date'] ?? '');
$countryId        = (int)($_POST['country_id'] ?? 0);
$stateId          = (int)($_POST['state_id'] ?? 0);
$cityId           = (int)($_POST['city_id'] ?? 0);
$area             = trim($_POST['area'] ?? '');
$pincode          = trim($_POST['pincode'] ?? '');
$priceList        = trim($_POST['price_list'] ?? '');
$address          = trim($_POST['address'] ?? '');
$shippingAddress  = trim($_POST['shipping_address'] ?? '');
$billingAddress   = trim($_POST['billing_address'] ?? '');
$latitude         = trim($_POST['latitude'] ?? '');
$longitude        = trim($_POST['longitude'] ?? '');
// Auto-generate client code if empty
if ($clientCode === '') {
    $maxCustId = (int)(db_row("SELECT MAX(id) as mid FROM `$tbl`")['mid'] ?? 0) + 1;
    $clientCode = 'CC-' . str_pad($maxCustId, 3, '0', STR_PAD_LEFT);
}

// Format birth date
$birthDateSql = "NULL";
if (!empty($birthDateRaw)) {
    $parsedBd = DateTime::createFromFormat('d-m-Y', $birthDateRaw);
    if (!$parsedBd) {
        $parsedBd = DateTime::createFromFormat('Y-m-d', $birthDateRaw);
    }
    if ($parsedBd) {
        $birthDateSql = "'" . db_escape($parsedBd->format('Y-m-d')) . "'";
    }
}

// Validations
if ($customerTypeId <= 0) {
    $response['message'] = 'Please select customer type.';
    echo json_encode($response);
    exit;
}

if ($name === '') {
    $response['message'] = 'Customer/Company name is required.';
    echo json_encode($response);
    exit;
}

if ($contactPerson === '') {
    $response['message'] = 'Contact Person Name is required.';
    echo json_encode($response);
    exit;
}

if ($mobileNo === '') {
    $response['message'] = 'Mobile number is required.';
    echo json_encode($response);
    exit;
}

if ($address === '') {
    $response['message'] = 'Address is required.';
    echo json_encode($response);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

// Duplicate mobile check within same company
$dupCond = "mobile_no = '" . db_escape($mobileNo) . "'";
if ($companyId > 0) {
    $dupCond .= " AND company_id = $companyId";
}
if ($isEdit) {
    $dupCond .= " AND id != $id";
}
$dupCheck = db_row("SELECT id FROM `$tbl` WHERE $dupCond LIMIT 1");
if ($dupCheck) {
    $response['message'] = 'Customer with this mobile number already exists.';
    echo json_encode($response);
    exit;
}

// Build escaped values
$clientCodeEsc   = db_escape($clientCode);
$nameEsc         = db_escape($name);
$contactPersonEsc= db_escape($contactPerson);
$gstNoSql        = ($gstNo !== '') ? "'" . db_escape($gstNo) . "'" : "NULL";
$mNoEsc          = db_escape($mobileNo);
$waNoSql         = ($whatsappNo !== '') ? "'" . db_escape($whatsappNo) . "'" : "NULL";
$emailSql        = ($email !== '') ? "'" . db_escape($email) . "'" : "NULL";
$countrySql      = ($countryId > 0) ? (int)$countryId : "NULL";
$stateSql        = ($stateId > 0) ? (int)$stateId : "NULL";
$citySql         = ($cityId > 0) ? (int)$cityId : "NULL";
$areaSql         = ($area !== '') ? "'" . db_escape($area) . "'" : "NULL";
$pincodeSql      = ($pincode !== '') ? "'" . db_escape($pincode) . "'" : "NULL";
$priceListSql    = ($priceList !== '') ? "'" . db_escape($priceList) . "'" : "NULL";
$addrSql         = "'" . db_escape($address) . "'";
$shippingAddrSql = ($shippingAddress !== '') ? "'" . db_escape($shippingAddress) . "'" : "NULL";
$billingAddrSql  = ($billingAddress !== '') ? "'" . db_escape($billingAddress) . "'" : "NULL";
$latSql          = ($latitude !== '') ? "'" . db_escape($latitude) . "'" : "NULL";
$longSql         = ($longitude !== '') ? "'" . db_escape($longitude) . "'" : "NULL";

$companyLeadId = (int)($_POST['company_lead_id'] ?? 0);
$leadIdSql = ($companyLeadId > 0) ? (int)$companyLeadId : "NULL";

$assignedTo = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : 0;
// Default to logged-in user if not explicitly specified
if ($assignedTo <= 0 && !$isEdit) {
    $assignedTo = $userId;
}
$assignedToSql = ($assignedTo > 0) ? (int)$assignedTo : "NULL";

if ($isEdit) {
    $existing = db_row("SELECT id, password FROM `$tbl` WHERE id = $id LIMIT 1");
    if (!$existing) {
        $response['message'] = $pageNm . ' not found.';
        echo json_encode($response);
        exit;
    }

    $companyUpdateSql = ($companyId > 0) ? "`company_id` = $companyId," : "";
    $leadUpdateSql = ($companyLeadId > 0) ? "`company_lead_id` = $companyLeadId," : "";

    $passwordSql = "";
    if ($passwordPlain !== '') {
        $hashed = password_hash($passwordPlain, PASSWORD_BCRYPT);
        $passwordSql = "`password` = '" . db_escape($hashed) . "',";
    }

    $assignedUpdateSql = ($assignedTo > 0) ? "`assigned_to` = $assignedToSql," : "";

    $sql = "UPDATE `$tbl` SET 
                $companyUpdateSql
                $leadUpdateSql
                $assignedUpdateSql
                `client_code`      = '$clientCodeEsc',
                `customer_type_id` = $customerTypeId,
                `gst_no`           = $gstNoSql,
                `name`             = '$nameEsc',
                $passwordSql
                `contact_person`   = '$contactPersonEsc',
                `email`            = $emailSql,
                `mobile_no`        = '$mNoEsc',
                `whatsapp_no`      = $waNoSql,
                `birth_date`       = $birthDateSql,
                `country_id`       = $countrySql,
                `state_id`         = $stateSql,
                `city_id`          = $citySql,
                `area`             = $areaSql,
                `pincode`          = $pincodeSql,
                `price_list`       = $priceListSql,
                `address`          = $addrSql,
                `shipping_address` = $shippingAddrSql,
                `billing_address`  = $billingAddrSql,
                `latitude`         = $latSql,
                `longitude`        = $longSql,
                `updated_by`       = $userId,
                `updated_at`       = NOW()
            WHERE `id` = $id";
    $msg = $pageNm . ' updated successfully.';
} else {
    $hashedPassword = ($passwordPlain !== '') ? "'" . db_escape(password_hash($passwordPlain, PASSWORD_BCRYPT)) . "'" : "NULL";

    $sql = "INSERT INTO `$tbl` (
                `company_id`, `company_lead_id`, `assigned_to`, `client_code`, `customer_type_id`, `gst_no`,
                `name`, `password`, `contact_person`, `email`, `mobile_no`, `whatsapp_no`,
                `birth_date`, `country_id`, `state_id`, `city_id`, `area`, `pincode`, `price_list`,
                `address`, `shipping_address`, `billing_address`, `latitude`, `longitude`,
                `created_by`, `created_at`, `updated_at`
            ) VALUES (
                $companyId, $leadIdSql, $assignedToSql, '$clientCodeEsc', $customerTypeId, $gstNoSql,
                '$nameEsc', $hashedPassword, '$contactPersonEsc', $emailSql, '$mNoEsc', $waNoSql,
                $birthDateSql, $countrySql, $stateSql, $citySql, $areaSql, $pincodeSql, $priceListSql,
                $addrSql, $shippingAddrSql, $billingAddrSql, $latSql, $longSql,
                $userId, NOW(), NOW()
            )";
    $msg = $pageNm . ' added successfully.';
}

$res = db_query($sql);
if ($res) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] = 'Failed to save ' . strtolower($pageNm) . '.';
}

echo json_encode($response);
exit;

