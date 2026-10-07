<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');

$tbl = 'lead';
$pageNm = 'Lead';

$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$userId = getCurrentUserId();
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

if ($userId <= 0 || !$isSuperadmin) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

$id                 = trim($_POST['id'] ?? '');
$leadNumber         = trim($_POST['lead_number'] ?? '');
$businessName       = trim($_POST['business_name'] ?? '');
$contactName        = trim($_POST['contact_name'] ?? '');
$mobileNo           = trim($_POST['mobile_no'] ?? '');
$whatsappNo         = trim($_POST['whatsapp_no'] ?? '');
$email              = trim($_POST['email'] ?? '');
$website            = trim($_POST['website'] ?? '');
$countryId          = (int)($_POST['country_id'] ?? 0);
$stateId            = (int)($_POST['state_id'] ?? 0);
$cityId             = (int)($_POST['city_id'] ?? 0);
$pincode            = trim($_POST['pincode'] ?? '');
$address            = trim($_POST['address'] ?? '');
$teamSize           = trim($_POST['team_size'] ?? '1');
$interestedPlanId   = (int)($_POST['interested_plan_id'] ?? 0);
$leadSource         = trim($_POST['lead_source'] ?? '');
$leadStage          = trim($_POST['lead_stage'] ?? '');
$assignTo           = (int)($_POST['assign_to'] ?? 0);
$demoDateRaw        = trim($_POST['demo_date'] ?? '');
$requirements       = trim($_POST['requirements'] ?? '');
$notes              = trim($_POST['notes'] ?? '');

if ($businessName === '') {
    $response['message'] = 'Business Name is required.';
    echo json_encode($response);
    exit;
}
if ($contactName === '') {
    $response['message'] = 'Contact Person is required.';
    echo json_encode($response);
    exit;
}
if ($mobileNo === '') {
    $response['message'] = 'Mobile No. is required.';
    echo json_encode($response);
    exit;
}

$demoDate = null;
if (!empty($demoDateRaw)) {
    $demoDate = formatDate($demoDateRaw, 'Y-m-d');
    if (!$demoDate) {
        $ts = strtotime($demoDateRaw);
        if ($ts !== false) {
            $demoDate = date('Y-m-d', $ts);
        }
    }
}

$bNameSql       = db_escape($businessName);
$cNameSql       = db_escape($contactName);
$mNoSql          = db_escape($mobileNo);
$waNoSql         = ($whatsappNo !== '') ? "'" . db_escape($whatsappNo) . "'" : "NULL";
$emailSql        = ($email !== '') ? "'" . db_escape($email) . "'" : "NULL";
$websiteSql      = ($website !== '') ? "'" . db_escape($website) . "'" : "NULL";
$countrySql      = ($countryId > 0) ? (int)$countryId : "NULL";
$stateSql        = ($stateId > 0) ? (int)$stateId : "NULL";
$citySql         = ($cityId > 0) ? (int)$cityId : "NULL";
$pincodeSql      = ($pincode !== '') ? "'" . db_escape($pincode) . "'" : "NULL";
$addrSql         = ($address !== '') ? "'" . db_escape($address) . "'" : "NULL";
$tSizeSql        = db_escape($teamSize);
$planIdSql       = ($interestedPlanId > 0) ? (int)$interestedPlanId : "NULL";
$lSourceSql      = db_escape($leadSource);
$lStageSql       = db_escape($leadStage);
$assignToSql     = ($assignTo > 0) ? (int)$assignTo : "NULL";
$demoDateSql     = !empty($demoDate) ? "'" . db_escape($demoDate) . "'" : "NULL";
$reqSql          = ($requirements !== '') ? "'" . db_escape($requirements) . "'" : "NULL";
$notesSql        = ($notes !== '') ? "'" . db_escape($notes) . "'" : "NULL";

if ((int)$id > 0) {
    $sql = "UPDATE `$tbl` SET 
        `business_name`      = '$bNameSql',
        `contact_name`       = '$cNameSql',
        `mobile_no`          = '$mNoSql',
        `whatsapp_no`        = $waNoSql,
        `email`              = $emailSql,
        `website`            = $websiteSql,
        `country_id`         = $countrySql,
        `state_id`           = $stateSql,
        `city_id`            = $citySql,
        `pincode`            = $pincodeSql,
        `address`            = $addrSql,
        `team_size`          = '$tSizeSql',
        `interested_plan_id` = $planIdSql,
        `lead_source`        = '$lSourceSql',
        `lead_stage`         = '$lStageSql',
        `assign_to`          = $assignToSql,
        `demo_date`          = $demoDateSql,
        `requirements`       = $reqSql,
        `notes`              = $notesSql,
        `updated_by`         = $userId,
        `updated_at`         = NOW()
    WHERE `id` = " . (int)$id;

    $updated = db_query($sql);
    if ($updated) {
        $response['status']  = true;
        $response['message'] = $pageNm . ' updated successfully.';
    } else {
        $response['message'] = 'Failed to update ' . $pageNm . '.';
    }
} else {
    if (empty($leadNumber)) {
        $leadNumber = 'OI-' . strtoupper(substr(uniqid(), -6));
    }
    $leadNumSql = db_escape($leadNumber);

    $sql = "INSERT INTO `$tbl` (
        `lead_number`,
        `business_name`,
        `contact_name`,
        `mobile_no`,
        `whatsapp_no`,
        `email`,
        `website`,
        `country_id`,
        `state_id`,
        `city_id`,
        `pincode`,
        `address`,
        `team_size`,
        `interested_plan_id`,
        `lead_source`,
        `lead_stage`,
        `assign_to`,
        `demo_date`,
        `requirements`,
        `notes`,
        `status`,
        `created_by`,
        `created_at`,
        `updated_at`
    ) VALUES (
        '$leadNumSql',
        '$bNameSql',
        '$cNameSql',
        '$mNoSql',
        $waNoSql,
        $emailSql,
        $websiteSql,
        $countrySql,
        $stateSql,
        $citySql,
        $pincodeSql,
        $addrSql,
        '$tSizeSql',
        $planIdSql,
        '$lSourceSql',
        '$lStageSql',
        $assignToSql,
        $demoDateSql,
        $reqSql,
        $notesSql,
        1,
        $userId,
        NOW(),
        NOW()
    )";

    $inserted = db_query($sql);
    if ($inserted) {
        $response['status']  = true;
        $response['message'] = $pageNm . ' created successfully.';
    } else {
        $response['message'] = 'Failed to create ' . $pageNm . '.';
    }
}

echo json_encode($response);
exit;
