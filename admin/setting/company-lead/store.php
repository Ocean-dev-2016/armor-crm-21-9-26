<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');

$tbl = 'company_lead';
$pageNm = 'Company Lead';
$moduleKey = 'company-lead';

$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

// Handle AJAX fetching of company-specific dropdowns (Assigned To and Source of Inquiry)
if (isset($_GET['action']) && $_GET['action'] === 'get_company_dropdowns') {
    $reqCompanyId = (int)($_GET['company_id'] ?? 0);
    if (!$isSuperadmin) {
        $reqCompanyId = $sessionCompanyId;
    }

    $soiList = [];
    $teamList = [];
    $inquiryStatusList = [];
    $followupStatusList = [];

    if ($reqCompanyId > 0) {
        $soiRows = db_rows("SELECT id, name FROM source_of_inquiry WHERE status = 1 AND (company_id = $reqCompanyId OR company_id = 0) ORDER BY order_by ASC, name ASC");
        foreach ($soiRows as $s) {
            $soiList[] = ['id' => (int)$s['id'], 'name' => $s['name']];
        }

        $teamRows = db_rows("SELECT id, name FROM users WHERE status = 1 AND company_id = $reqCompanyId ORDER BY name ASC");
        foreach ($teamRows as $t) {
            $teamList[] = ['id' => (int)$t['id'], 'name' => $t['name']];
        }

        // Marketing Status: Lead type
        $leadStatusRows = db_rows("SELECT id, name, color FROM marketing_status WHERE status = 1 AND type = 'Lead' AND (company_id = $reqCompanyId OR company_id = 0) ORDER BY order_by ASC, name ASC");
        foreach ($leadStatusRows as $ls) {
            $inquiryStatusList[] = ['id' => (int)$ls['id'], 'name' => $ls['name'], 'color' => $ls['color']];
        }

        // // Marketing Status: Follow Up type
        // $fuStatusRows = db_rows("SELECT id, name, color FROM marketing_status WHERE status = 1 AND type = 'Follow Up' AND (company_id = $reqCompanyId OR company_id = 0) ORDER BY order_by ASC, name ASC");
        // foreach ($fuStatusRows as $fs) {
        //     $followupStatusList[] = ['id' => (int)$fs['id'], 'name' => $fs['name'], 'color' => $fs['color']];
        // }
    }

    echo json_encode([
        'status' => true,
        'sources_of_inquiry' => $soiList,
        'team_persons' => $teamList,
        'inquiry_statuses' => $inquiryStatusList
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$userId = getCurrentUserId();
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

if ($userId <= 0) {
    $response['message'] = 'Unauthorized access.';
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

// Session or posted company_id
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
$inquiryNo          = trim($_POST['inquiry_no'] ?? '');
$inquiryDateRaw     = trim($_POST['inquiry_date'] ?? '');
$customerName       = trim($_POST['customer_name'] ?? '');
$contactPerson      = trim($_POST['contact_person'] ?? '');
$mobileNo           = trim($_POST['mobile_no'] ?? '');
$whatsappNo         = trim($_POST['whatsapp_no'] ?? '');
$email              = trim($_POST['email'] ?? '');
$website            = trim($_POST['website'] ?? '');
$inquiryStatusInput = trim($_POST['inquiry_status'] ?? '');
$sourceOfInquiryId  = (int)($_POST['source_of_inquiry_id'] ?? 0);
$address            = trim($_POST['address'] ?? '');
$pincode            = trim($_POST['pincode'] ?? '');
$countryId          = (int)($_POST['country_id'] ?? 0);
$stateId            = (int)($_POST['state_id'] ?? 0);
$cityId             = (int)($_POST['city_id'] ?? 0);
$area               = trim($_POST['area'] ?? '');
$assignedTo         = (int)($_POST['assigned_to'] ?? 0);
$requirementDetails = trim($_POST['requirement_details'] ?? '');
$followupDateRaw    = trim($_POST['followup_date'] ?? '');
$followupDetails    = trim($_POST['followup_details'] ?? '');

// Resolve inquiry status ID and Name
$inquiryStatusId = 0;
$inquiryStatusName = '';
if (!empty($inquiryStatusInput)) {
    if (is_numeric($inquiryStatusInput)) {
        $inquiryStatusId = (int)$inquiryStatusInput;
        $stRow = db_row("SELECT id, name FROM marketing_status WHERE id = $inquiryStatusId LIMIT 1");
        if ($stRow) {
            $inquiryStatusName = $stRow['name'];
        }
    } else {
        $stRow = db_row("SELECT id, name FROM marketing_status WHERE name = '" . db_escape($inquiryStatusInput) . "' AND type = 'Lead' LIMIT 1");
        if ($stRow) {
            $inquiryStatusId = (int)$stRow['id'];
            $inquiryStatusName = $stRow['name'];
        } else {
            $inquiryStatusName = $inquiryStatusInput;
        }
    }
}

// Fallback to default "new" status ID if empty
if ($inquiryStatusId <= 0) {
    $defStRow = db_row("SELECT id, name FROM marketing_status WHERE type = 'Lead' AND status = 1 ORDER BY order_by ASC, id ASC LIMIT 1");
    if ($defStRow) {
        $inquiryStatusId = (int)$defStRow['id'];
        $inquiryStatusName = $defStRow['name'];
    }
}

// Value to store in DB: store status ID
$inquiryStatus = (string)$inquiryStatusId;

// Validations
if ($customerName === '') {
    $response['message'] = 'Customer Name is required.';
    echo json_encode($response);
    exit;
}
if ($mobileNo === '') {
    $response['message'] = 'Mobile No. is required.';
    echo json_encode($response);
    exit;
}
if ($assignedTo <= 0) {
    $response['message'] = 'Assigned To is required.';
    echo json_encode($response);
    exit;
}
if ($sourceOfInquiryId <= 0) {
    $response['message'] = 'Source of Inquiry is required.';
    echo json_encode($response);
    exit;
}

// Format dates
$inquiryDate = date('Y-m-d');
if (!empty($inquiryDateRaw)) {
    $fmt = formatDate($inquiryDateRaw, 'Y-m-d');
    if ($fmt) {
        $inquiryDate = $fmt;
    } else {
        $ts = strtotime($inquiryDateRaw);
        if ($ts !== false) {
            $inquiryDate = date('Y-m-d', $ts);
        }
    }
}

$followupDate = null;
if (!empty($followupDateRaw)) {
    $fmt = formatDate($followupDateRaw, 'Y-m-d');
    if ($fmt) {
        $followupDate = $fmt;
    } else {
        $ts = strtotime($followupDateRaw);
        if ($ts !== false) {
            $followupDate = date('Y-m-d', $ts);
        }
    }
}

// Auto-generate inquiry number if empty
if (empty($inquiryNo)) {
    $randNum = mt_rand(1000, 9999);
    $inquiryNo = 'INQ-' . $randNum;
}

// Handle Attachment Upload
$attachmentFileName = null;
$uploadDir = BASE_PATH . '/uploads/lead/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['attachment']['tmp_name'];
    $originalName = $_FILES['attachment']['name'];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'zip'];
    if (in_array($fileExtension, $allowedExtensions, true)) {
        $newFileName = 'lead_' . time() . '_' . mt_rand(100, 999) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $attachmentFileName = $newFileName;
        }
    } else {
        $response['message'] = 'Invalid attachment format. Allowed formats: jpg, png, pdf, doc, docx, xls, xlsx, csv, zip';
        echo json_encode($response);
        exit;
    }
}

// Build SQL fields
$inqNoSql     = db_escape($inquiryNo);
$inqDateSql   = db_escape($inquiryDate);
$cNameSql     = db_escape($customerName);
$cpNameSql    = ($contactPerson !== '') ? "'" . db_escape($contactPerson) . "'" : "NULL";
$mNoSql       = db_escape($mobileNo);
$waNoSql      = ($whatsappNo !== '') ? "'" . db_escape($whatsappNo) . "'" : "NULL";
$emailSql     = ($email !== '') ? "'" . db_escape($email) . "'" : "NULL";
$websiteSql   = ($website !== '') ? "'" . db_escape($website) . "'" : "NULL";
$inqStatusSql = db_escape($inquiryStatus);
$soiSql       = ($sourceOfInquiryId > 0) ? (int)$sourceOfInquiryId : "NULL";
$addrSql      = ($address !== '') ? "'" . db_escape($address) . "'" : "NULL";
$pinSql       = ($pincode !== '') ? "'" . db_escape($pincode) . "'" : "NULL";
$countrySql   = ($countryId > 0) ? (int)$countryId : "NULL";
$stateSql     = ($stateId > 0) ? (int)$stateId : "NULL";
$citySql      = ($cityId > 0) ? (int)$cityId : "NULL";
$areaSql      = ($area !== '') ? "'" . db_escape($area) . "'" : "NULL";
$assignSql    = (int)$assignedTo;
$reqSql       = ($requirementDetails !== '') ? "'" . db_escape($requirementDetails) . "'" : "NULL";
$fuDateSql    = !empty($followupDate) ? "'" . db_escape($followupDate) . "'" : "NULL";
$fuDetSql     = ($followupDetails !== '') ? "'" . db_escape($followupDetails) . "'" : "NULL";

if ($isEdit) {
    // Check record exists
    $existing = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$existing) {
        $response['message'] = 'Lead not found.';
        echo json_encode($response);
        exit;
    }

    $attachmentSql = "";
    if ($attachmentFileName !== null) {
        // remove old file if exists
        if (!empty($existing['attachment']) && file_exists($uploadDir . $existing['attachment'])) {
            @unlink($uploadDir . $existing['attachment']);
        }
        $attachmentSql = ", `attachment` = '" . db_escape($attachmentFileName) . "'";
    }

    $companyUpdateSql = ($companyId > 0) ? "`company_id` = $companyId," : "";

    $updateSql = "UPDATE `$tbl` SET 
        $companyUpdateSql
        `inquiry_no` = '$inqNoSql',
        `inquiry_date` = '$inqDateSql',
        `customer_name` = '$cNameSql',
        `contact_person` = $cpNameSql,
        `mobile_no` = '$mNoSql',
        `whatsapp_no` = $waNoSql,
        `email` = $emailSql,
        `website` = $websiteSql,
        `inquiry_status` = '$inqStatusSql',
        `source_of_inquiry_id` = $soiSql,
        `address` = $addrSql,
        `pincode` = $pinSql,
        `country_id` = $countrySql,
        `state_id` = $stateSql,
        `city_id` = $citySql,
        `area` = $areaSql,
        `assigned_to` = $assignSql,
        `requirement_details` = $reqSql,
        `followup_date` = $fuDateSql,
        `followup_details` = $fuDetSql,
        `updated_by` = $userId,
        `updated_at` = NOW()
        $attachmentSql
        WHERE `id` = $id";

    $res = db_query($updateSql);
    if ($res) {
        // If followup_date or followup_details is not blank, create a follow-up record
        if (!empty($followupDate) || !empty($followupDetails)) {
            $prevDate = (!empty($existing['followup_date']) && $existing['followup_date'] !== '0000-00-00') ? date('Y-m-d', strtotime($existing['followup_date'])) : '';
            $prevDet  = trim($existing['followup_details'] ?? '');
            $currDate = !empty($followupDate) ? $followupDate : '';
            $currDet  = $followupDetails;

            // Only insert a new follow-up if either date or details has changed or no followup exists yet
            if ($prevDate !== $currDate || $prevDet !== $currDet) {
                $fuDtSql = !empty($followupDate) ? ($followupDate . ' ' . date('H:i:s')) : date('Y-m-d H:i:s');
                $escFuRemarks = db_escape($followupDetails);
                $escInqSt = db_escape($inquiryStatus);
                $targetCompId = ($companyId > 0) ? $companyId : (int)($existing['company_id'] ?? 0);

                db_query("INSERT INTO `company_lead_followups` (
                    `company_id`, `lead_id`, `followup_date`, `remarks`, `followup_status`, `inquiry_status`, `created_by`, `created_at`, `updated_at`
                ) VALUES (
                    $targetCompId, $id, '$fuDtSql', '$escFuRemarks', 'Pending', '$escInqSt', $userId, NOW(), NOW()
                )");
            }
        }

        $response['status'] = true;
        $response['message'] = 'Company Lead updated successfully.';
    } else {
        $response['message'] = 'Failed to update Company Lead.';
    }
} else {
    $attachVal = ($attachmentFileName !== null) ? "'" . db_escape($attachmentFileName) . "'" : "NULL";

    $insertSql = "INSERT INTO `$tbl` (
        `company_id`, `inquiry_no`, `inquiry_date`, `customer_name`, `contact_person`,
        `mobile_no`, `whatsapp_no`, `email`, `website`,
        `inquiry_status`, `source_of_inquiry_id`,
        `address`, `pincode`, `country_id`, `state_id`, `city_id`, `area`,
        `assigned_to`, `attachment`, `requirement_details`,
        `followup_date`, `followup_details`, `status`, `created_by`, `created_at`, `updated_at`
    ) VALUES (
        $companyId, '$inqNoSql', '$inqDateSql', '$cNameSql', $cpNameSql,
        '$mNoSql', $waNoSql, $emailSql, $websiteSql,
        '$inqStatusSql', $soiSql,
        $addrSql, $pinSql, $countrySql, $stateSql, $citySql, $areaSql,
        $assignSql, $attachVal, $reqSql,
        $fuDateSql, $fuDetSql, 1, $userId, NOW(), NOW()
    )";

    $res = db_query($insertSql);
    if ($res) {
        $newLeadId = db_insert_id();

        // If followup_date or followup_details is not blank, create initial follow-up record
        if (!empty($followupDate) || !empty($followupDetails)) {
            $fuDtSql = !empty($followupDate) ? ($followupDate . ' ' . date('H:i:s')) : date('Y-m-d H:i:s');
            $escFuRemarks = db_escape($followupDetails);
            $escInqSt = db_escape($inquiryStatus);

            db_query("INSERT INTO `company_lead_followups` (
                `company_id`, `lead_id`, `followup_date`, `remarks`, `followup_status`, `inquiry_status`, `created_by`, `created_at`, `updated_at`
            ) VALUES (
                $companyId, $newLeadId, '$fuDtSql', '$escFuRemarks', 'Pending', '$escInqSt', $userId, NOW(), NOW()
            )");
        }

        $response['status'] = true;
        $response['message'] = 'Company Lead created successfully.';
    } else {
        $response['message'] = 'Failed to create Company Lead.';
    }
}

echo json_encode($response);
exit;
