<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

// Note: Header Content-Type is set conditionally per action
$tbl = 'category';
$pageNm = 'Category';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

// Edit action (fetch single record)
if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    header('Content-Type: application/json');
    $id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;

    if ($id <= 0) {
        $response['message'] = 'Invalid record ID.';
        echo json_encode($response);
        exit;
    }

    $editSql = "SELECT * FROM $tbl WHERE id = $id";
    $editResult = db_row($editSql);
    if ($editResult) {
        $response['status'] = true;
        $response['message'] = 'Record fetched successfully.';
        if (!empty($editResult['image'])) {
            $editResult['image_url'] = SITE_URL . 'uploads/category/' . $editResult['image'];
        } else {
            $editResult['image_url'] = '';
        }
        $response['data'] = $editResult;
    } else {
        $response['message'] = 'Failed to fetch record.';
    }

    echo json_encode($response);
    exit;
}



header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission('category', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' category.';
    echo json_encode($response);
    exit;
}

$name = trim($_POST['name'] ?? '');
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

if ($isSuperadmin) {
    $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
    if ($company_id <= 0) {
        $response['message'] = 'Please select a company.';
        echo json_encode($response);
        exit;
    }
} else {
    $company_id = $sessionCompanyId;
}

if ($name === '') {
    $response['message'] = $pageNm . ' name is required.';
    echo json_encode($response);
    exit;
}

// Check unique name per company
$nameEsc = db_escape($name);
$companyWhere = ($company_id > 0) ? " AND company_id = $company_id" : "";
if ($id > 0) {
    $uniqueSql = "SELECT id FROM $tbl WHERE name = '$nameEsc' AND id != $id $companyWhere LIMIT 1";
} else {
    $uniqueSql = "SELECT id FROM $tbl WHERE name = '$nameEsc' $companyWhere LIMIT 1";
}
$uniqueResult = db_row($uniqueSql);
if (!empty($uniqueResult)) {
    $response['message'] = 'Category name already exists.';
    echo json_encode($response);
    exit;
}

// Handle Image Upload
$imageName = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName    = $_FILES['image']['name'];
    $fileSize    = $_FILES['image']['size'];
    $fileType    = $_FILES['image']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileExtension, $allowedfileExtensions)) {
        $response['message'] = 'Only image files (jpg, jpeg, png, webp) are allowed.';
        echo json_encode($response);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
        $response['message'] = 'Image size should not exceed 5MB.';
        echo json_encode($response);
        exit;
    }

    $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
    $uploadFileDir = BASE_PATH . '/uploads/category/';
    if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0777, true);
    }
    $dest_path = $uploadFileDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        $imageName = $newFileName;
    } else {
        $response['message'] = 'There was an error moving the uploaded file.';
        echo json_encode($response);
        exit;
    }
}

$remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';

if ($id > 0) {
    $imageUpdateSql = "";
    if ($imageName !== null) {
        // remove old image if exists
        $oldRow = db_row("SELECT image FROM $tbl WHERE id = $id LIMIT 1");
        if (!empty($oldRow['image'])) {
            $oldFilePath = BASE_PATH . '/uploads/category/' . $oldRow['image'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $imageUpdateSql = ", image = '$imageName'";
    } elseif ($remove_image) {
        $oldRow = db_row("SELECT image FROM $tbl WHERE id = $id LIMIT 1");
        if (!empty($oldRow['image'])) {
            $oldFilePath = BASE_PATH . '/uploads/category/' . $oldRow['image'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $imageUpdateSql = ", image = NULL";
    }

    $sql = "UPDATE $tbl SET 
                name = '$nameEsc', 
                company_id = $company_id,
                updated_by = $userId,
                updated_at = NOW() 
                $imageUpdateSql 
            WHERE id = $id";
    $msg = $pageNm . ' updated successfully.';
} else {
    $imgVal = ($imageName !== null) ? "'$imageName'" : "NULL";
    $sql = "INSERT INTO $tbl (company_id, name, image, status, created_by, created_at, updated_at) 
            VALUES ($company_id, '$nameEsc', $imgVal, 1, $userId, NOW(), NOW())";
    $msg = $pageNm . ' created successfully.';
}

$res = db_query($sql);
if ($res) {
    $response['status'] = true;
    $response['message'] = $msg;
} else {
    $response['message'] = 'Failed to save ' . $pageNm;
}

echo json_encode($response);
exit;
