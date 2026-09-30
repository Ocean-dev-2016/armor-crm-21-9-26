<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');
$tbl = 'product';
$pageNm = 'Product';
$response = [
    'status' => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// Action: Fetch categories by company ID
if (isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    $company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : $sessionCompanyId;
    if ($company_id <= 0 && !$isSuperadmin) {
        $company_id = $sessionCompanyId;
    }
    $where = ($company_id > 0) ? "WHERE company_id = $company_id AND status = 1" : "WHERE status = 1";
    $cats = db_rows("SELECT id, name FROM category $where ORDER BY name ASC");
    $taxes = db_rows("SELECT id, name, value FROM tax $where ORDER BY name ASC");
    $units = db_rows("SELECT id, name FROM unit $where ORDER BY name ASC");

    $response['status'] = true;
    $response['data'] = [
        'categories' => $cats ?: [],
        'taxes'      => $taxes ?: [],
        'units'      => $units ?: []
    ];
    echo json_encode($response);
    exit;
}

// Action: Fetch subcategories by category ID
if (isset($_GET['action']) && $_GET['action'] === 'get_subcategories') {
    $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    if ($category_id <= 0) {
        $response['status'] = true;
        $response['data'] = [];
        echo json_encode($response);
        exit;
    }

    $subcats = db_rows("SELECT id, name FROM sub_category WHERE category_id = $category_id AND status = 1 ORDER BY name ASC");
    $response['status'] = true;
    $response['data'] = $subcats ?: [];
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
if (!hasPermission('product', $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' product.';
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

$type             = isset($_POST['type']) ? (int)$_POST['type'] : 0;
$category_id      = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$sub_category_id  = isset($_POST['sub_category_id']) ? (int)$_POST['sub_category_id'] : 0;
$name             = trim($_POST['name'] ?? '');
$tax_id           = isset($_POST['tax_id']) ? (int)$_POST['tax_id'] : 0;
$sales_unit_id    = isset($_POST['sales_unit_id']) ? (int)$_POST['sales_unit_id'] : 0;
$customer_unit_id = isset($_POST['customer_unit_id']) ? (int)$_POST['customer_unit_id'] : 0;
$display_unit     = trim($_POST['display_unit'] ?? '');
$hsn_code         = trim($_POST['hsn_code'] ?? '');
$description      = trim($_POST['description'] ?? '');

if ($type <= 0) {
    $response['message'] = 'Please select a Variant.';
    echo json_encode($response);
    exit;
}

if ($category_id <= 0) {
    $response['message'] = 'Please select a category.';
    echo json_encode($response);
    exit;
}

if ($sub_category_id <= 0) {
    $response['message'] = 'Please select a sub category.';
    echo json_encode($response);
    exit;
}

if ($name === '') {
    $response['message'] = 'Product name is required.';
    echo json_encode($response);
    exit;
}

if ($tax_id <= 0) {
    $response['message'] = 'Please select GST (Tax).';
    echo json_encode($response);
    exit;
}

// Uniqueness validation per company
$nameEsc = db_escape($name);
$companyWhere = ($company_id > 0) ? " AND company_id = $company_id" : "";
if ($id > 0) {
    $uniqueSql = "SELECT id FROM $tbl WHERE name = '$nameEsc' AND id != $id $companyWhere LIMIT 1";
} else {
    $uniqueSql = "SELECT id FROM $tbl WHERE name = '$nameEsc' $companyWhere LIMIT 1";
}
$uniqueResult = db_row($uniqueSql);
if (!empty($uniqueResult)) {
    $response['message'] = 'Product name already exists in this company.';
    echo json_encode($response);
    exit;
}

// Handle Image Upload
$imageName = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileName      = $_FILES['image']['name'];
    $fileSize      = $_FILES['image']['size'];
    $fileNameCmps  = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileExtension, $allowedfileExtensions, true)) {
        $response['message'] = 'Only image files (jpg, jpeg, png, webp) are allowed.';
        echo json_encode($response);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        $response['message'] = 'Image size must not exceed 5MB.';
        echo json_encode($response);
        exit;
    }

    $uploadDir = BASE_PATH . '/uploads/product/';
    $newFileName = upload_and_convert_to_webp($_FILES['image'], $uploadDir, 'prod', 85);

    if ($newFileName) {
        $imageName = $newFileName;

        // If editing and old image exists, remove old image
        if ($id > 0) {
            $old = db_row("SELECT image FROM $tbl WHERE id = $id LIMIT 1");
            if (!empty($old['image']) && file_exists($uploadDir . $old['image'])) {
                @unlink($uploadDir . $old['image']);
            }
        }
    } else {
        $response['message'] = 'Error uploading image. Please check directory permissions.';
        echo json_encode($response);
        exit;
    }
}

// Handle image removal if user requested removal during edit
$removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';
if ($removeImage && $id > 0 && empty($imageName)) {
    $old = db_row("SELECT image FROM $tbl WHERE id = $id LIMIT 1");
    if (!empty($old['image'])) {
        $uploadDir = BASE_PATH . '/uploads/product/';
        if (file_exists($uploadDir . $old['image'])) {
            @unlink($uploadDir . $old['image']);
        }
    }
    $imageUpdateClause = ", image = NULL";
} elseif ($imageName !== null) {
    $imageUpdateClause = ", image = '$imageName'";
} else {
    $imageUpdateClause = "";
}

$displayUnitEsc  = db_escape($display_unit);
$hsnCodeEsc      = db_escape($hsn_code);
$descriptionEsc  = db_escape($description);

if ($id > 0) {
    $sql = "UPDATE $tbl SET 
                company_id = $company_id,
                type = $type,
                category_id = $category_id,
                sub_category_id = $sub_category_id,
                name = '$nameEsc',
                tax_id = $tax_id,
                sales_unit_id = $sales_unit_id,
                customer_unit_id = $customer_unit_id,
                display_unit = '$displayUnitEsc',
                hsn_code = '$hsnCodeEsc',
                description = '$descriptionEsc',
                updated_by = $userId,
                updated_at = NOW()
                $imageUpdateClause
            WHERE id = $id";
    $msg = $pageNm . ' updated successfully.';
} else {
    $imgVal = ($imageName !== null) ? "'$imageName'" : "NULL";
    $sql = "INSERT INTO $tbl (
                company_id, type, category_id, sub_category_id, name, 
                tax_id, sales_unit_id, customer_unit_id, display_unit, hsn_code, 
                image, description, status, created_by, created_at, updated_at
            ) VALUES (
                $company_id, $type, $category_id, $sub_category_id, '$nameEsc',
                $tax_id, $sales_unit_id, $customer_unit_id, '$displayUnitEsc', '$hsnCodeEsc',
                $imgVal, '$descriptionEsc', 1, $userId, NOW(), NOW()
            )";
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
