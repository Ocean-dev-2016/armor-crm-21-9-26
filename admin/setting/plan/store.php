<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'plan';
$pageNm = 'Plan';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}
$id          = trim($_POST['id'] ?? '');
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission('plan', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' plans.';
    echo json_encode($response);
    exit;
}
$name          = trim($_POST['name'] ?? '');
$price         = trim($_POST['price'] ?? '');
$to_date      =  ($_POST['to_date'] ?? 0);
$from_date      =  ($_POST['from_date'] ?? 0);
$maxTeamUser   = (int) ($_POST['max_team_user'] ?? 0);
$maxCustomer   = (int) ($_POST['max_customer'] ?? 0);
$maxInquiry    = (int) ($_POST['max_inquiry'] ?? 0);
$panelRight     = trim($_POST['panel_right'] ?? '');
$appRight       = trim($_POST['app_right'] ?? '');
if ($name === '') {
    $response['message'] = $pageNm.' name is required.';
    echo json_encode($response);
    exit;
}
if ($price === '' || !is_numeric($price)) {
    $response['message'] = 'Valid '.$pageNm.' price is required.';
    echo json_encode($response);
    exit;
}
if ($to_date === '') {
    $response['message'] = 'Please select To Date.';
    echo json_encode($response);
    exit;
}
if ($from_date === '') {
    $response['message'] = 'Please select From Date.';
    echo json_encode($response);
    exit;
}
if ($panelRight === '') {
    $response['message'] = 'Please select at least one Panel Right module.';
    echo json_encode($response);
    exit;
}

// Validate Panel Right module IDs
$panelIdArray = array_unique(array_filter(array_map('intval', explode(',', $panelRight))));
if (empty($panelIdArray)) {
    $response['message'] = 'Please select valid Panel Right modules.';
    echo json_encode($response);
    exit;
}
$panelIdsString = implode(',', $panelIdArray);
$checkPanelSql = "SELECT id FROM module WHERE id IN ($panelIdsString) AND status = 1";
$checkPanelResult = db_rows($checkPanelSql);
$validPanelIds = [];
if ($checkPanelResult) {
    foreach ($checkPanelResult as $value) {
        $validPanelIds[] = (int) $value['id'];
    }
}
if (empty($validPanelIds)) {
    $response['message'] = 'Selected Panel Right modules are invalid.';
    echo json_encode($response);
    exit;
}
$panelRightString = implode(',', $validPanelIds);

// Validate Application Right module IDs (if provided)
$appRightString = '';
if ($appRight !== '') {
    $appIdArray = array_unique(array_filter(array_map('intval', explode(',', $appRight))));
    if (!empty($appIdArray)) {
        $appIdsString = implode(',', $appIdArray);
        $checkAppSql = "SELECT id FROM module WHERE id IN ($appIdsString) AND status = 1";
        $checkAppResult = db_rows($checkAppSql);
        $validAppIds = [];
        if ($checkAppResult) {
            foreach ($checkAppResult as $value) {
                $validAppIds[] = (int) $value['id'];
            }
        }
        $appRightString = implode(',', $validAppIds);
    }
}

$to = new DateTime($to_date);
$from = new DateTime($from_date);

$days = $to->diff($from)->days;

$to_date = formatDate($to_date, 'Y-m-d');
$from_date = formatDate($from_date, 'Y-m-d');

// $toDate = DateTime::createFromFormat('d-m-Y', $to_date);
// $fromDate = DateTime::createFromFormat('d-m-Y', $from_date);
// $to_date = $toDate->format('Y-m-d');
// $from_date = $fromDate->format('Y-m-d');

if ($id > 0) {    
    $sql = "UPDATE $tbl SET 
    name = '" . $name . "',
    price = '" . $price . "',
    to_date = '" . $to_date . "',
    from_date = '" . $from_date . "',
    days = " . (int) $days . ",
    max_team_user = " . (int) $maxTeamUser . ",
    max_customer = " . (int) $maxCustomer . ",
    max_inquiry = " . (int) $maxInquiry . ",
    panel_right = '" . $panelRightString . "',
    app_right = '" . $appRightString . "',
    updated_at = NOW()
    WHERE id = " . (int) $id;
    $msg = $pageNm . ' updated successfully.';
} else {
    $sql = "INSERT INTO $tbl
(
    name,
    price,
    max_team_user,
    max_customer,
    max_inquiry,
    panel_right,
    app_right,
    to_date,
    from_date,
    days,
    created_at,
    updated_at
)
VALUES
(
    '" . $name . "',
    '" . $price . "',
    " . $maxTeamUser . ",
    " . $maxCustomer . ",
    " . $maxInquiry . ",
    '" . $panelRightString . "',
    '" . $appRightString . "',
    '" . $to_date . "',
    '" . $from_date . "',
    " . $days . ",
    NOW(),
    NOW()
)";
    $msg = $pageNm . ' created successfully.';
}

$modules = db_query($sql);
$price = (float) $price;
if ($modules) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] =
        'Failed to create '.$pageNm;
}
echo json_encode($response);
exit;
