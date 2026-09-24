<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'sub_category';
$pageNm = 'Sub Category';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// Action: Fetch categories by company ID (for dependent dropdown)
if (isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    $company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : $sessionCompanyId;
    if ($company_id <= 0) {
        $response['message'] = 'Invalid company ID.';
        echo json_encode($response);
        exit;
    }

    $cats = db_rows("SELECT id, name FROM category WHERE company_id = $company_id AND status = 1 ORDER BY name ASC");
    $response['status'] = true;
    $response['message'] = 'Categories retrieved.';
    $response['data'] = $cats ?: [];
    echo json_encode($response);
    exit;
}

// Action: Fetch single record for Edit
if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;

    if ($id <= 0) {
        $response['message'] = 'Invalid record ID.';
        echo json_encode($response);
        exit;
    }

    $editSql = "SELECT * FROM $tbl WHERE id = $id LIMIT 1";
    $editResult = db_row($editSql);
    if ($editResult) {
        $response['status'] = true;
        $response['message'] = 'Record fetched successfully.';
        if (!empty($editResult['image'])) {
            $editResult['image_url'] = SITE_URL . 'uploads/subcategory/' . $editResult['image'];
        } else {
            $editResult['image_url'] = '';
        }

        // Include categories list for this record's company
        $recCompanyId = (int)$editResult['company_id'];
        $cats = db_rows("SELECT id, name FROM category WHERE company_id = $recCompanyId AND status = 1 ORDER BY name ASC");
        $response['categories'] = $cats ?: [];
        $response['data'] = $editResult;
    } else {
        $response['message'] = 'Failed to fetch record.';
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission('sub-category', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' sub category.';
    echo json_encode($response);
    exit;
}

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

$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$name        = trim($_POST['name'] ?? '');

if ($category_id <= 0) {
    $response['message'] = 'Please select a category.';
    echo json_encode($response);
    exit;
}

if ($name === '') {
    $response['message'] = $pageNm . ' name is required.';
    echo json_encode($response);
    exit;
}

// Check unique name per category in this company
$nameEsc = db_escape($name);
if ($id > 0) {
    $uniqueSql = "SELECT id FROM $tbl WHERE category_id = $category_id AND name = '$nameEsc' AND id != $id LIMIT 1";
} else {
    $uniqueSql = "SELECT id FROM $tbl WHERE category_id = $category_id AND name = '$nameEsc' LIMIT 1";
}
$uniqueResult = db_row($uniqueSql);
if (!empty($uniqueResult)) {
    $response['message'] = 'Sub category name already exists in this category.';
    echo json_encode($response);
    exit;
}

// Handle Image Upload
$imageName = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName    = $_FILES['image']['name'];
    $fileSize    = $_FILES['image']['size'];
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
    $uploadFileDir = BASE_PATH . '/uploads/subcategory/';
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
        $oldRow = db_row("SELECT image FROM $tbl WHERE id = $id LIMIT 1");
        if (!empty($oldRow['image'])) {
            $oldFilePath = BASE_PATH . '/uploads/subcategory/' . $oldRow['image'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $imageUpdateSql = ", image = '$imageName'";
    } elseif ($remove_image) {
        $oldRow = db_row("SELECT image FROM $tbl WHERE id = $id LIMIT 1");
        if (!empty($oldRow['image'])) {
            $oldFilePath = BASE_PATH . '/uploads/subcategory/' . $oldRow['image'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $imageUpdateSql = ", image = NULL";
    }

    $sql = "UPDATE $tbl SET 
                category_id = $category_id,
                company_id = $company_id,
                name = '$nameEsc', 
                updated_by = $userId,
                updated_at = NOW() 
                $imageUpdateSql 
            WHERE id = $id";
    $msg = $pageNm . ' updated successfully.';
} else {
    $imgVal = ($imageName !== null) ? "'$imageName'" : "NULL";
    $sql = "INSERT INTO $tbl (company_id, category_id, name, image, status, created_by, created_at, updated_at) 
            VALUES ($company_id, $category_id, '$nameEsc', $imgVal, 1, $userId, NOW(), NOW())";
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
