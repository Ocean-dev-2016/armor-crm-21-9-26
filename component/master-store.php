<?php
/**
 * Common Master Modal Store Handler
 * File: component/master-store.php
 *
 * Usage in master store.php:
 * require_once __DIR__ . '/../../../conn/db.php';
 * require_once __DIR__ . '/../../../conn/dbqry.php';
 * require_once __DIR__ . '/../../../conn/helper.php';
 *
 * $masterConfig = [
 *     'tbl'       => 'brand',
 *     'pageNm'    => 'Brand',
 *     'moduleKey' => 'brand',       // optional, defaults to $tbl
 *     'hasCompany'=> true,          // optional, false for company-type
 *     'nameField' => 'name',        // optional, default 'name'
 *     'fields'    => [              // optional additional fields: ['col' => 'int'|'float'|'string']
 *         'code' => 'string'
 *     ],
 *     'uniqueFields' => ['name'],   // optional unique fields per company
 *     'hasImage'  => false,         // optional, true if image upload
 *     'uploadFolder' => 'category', // optional folder under uploads/
 *     'onEditData'   => function(&$data, $id) {}, // optional
 *     'beforeSave'   => function(&$dataValues, $id) {} // optional
 * ];
 * require_once BASE_PATH . '/component/master-store.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../conn/db.php';
    require_once __DIR__ . '/../conn/dbqry.php';
    require_once __DIR__ . '/../conn/helper.php';
}

header('Content-Type: application/json');

$tbl          = $masterConfig['tbl'] ?? ($tbl ?? '');
$pageNm       = $masterConfig['pageNm'] ?? ($pageNm ?? ucfirst($tbl));
$moduleKey    = $masterConfig['moduleKey'] ?? ($moduleKey ?? str_replace('_', '-', $tbl));
$hasCompany   = isset($masterConfig['hasCompany']) ? (bool)$masterConfig['hasCompany'] : (isset($hasCompany) ? (bool)$hasCompany : true);
$nameField    = $masterConfig['nameField'] ?? ($nameField ?? 'name');
$nameLabel    = $masterConfig['nameLabel'] ?? ($nameLabel ?? ($pageNm . ' name'));
$fields       = $masterConfig['fields'] ?? ($fields ?? []);
$uniqueFields = $masterConfig['uniqueFields'] ?? ($uniqueFields ?? [$nameField]);
$hasImage     = $masterConfig['hasImage'] ?? ($hasImage ?? false);
$imageField   = $masterConfig['imageField'] ?? ($imageField ?? 'image');
$uploadFolder = $masterConfig['uploadFolder'] ?? ($uploadFolder ?? $tbl);
$onEditData   = $masterConfig['onEditData'] ?? ($onEditData ?? null);
$beforeSave   = $masterConfig['beforeSave'] ?? ($beforeSave ?? null);

$response = [
    'status'  => false,
    'message' => 'Something went wrong.'
];

$userId = getCurrentUserId();
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';

// ----------------------------------------------------
// 1. Action: Fetch single record for Edit
// ----------------------------------------------------
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

        if ($hasImage) {
            if (!empty($editResult[$imageField])) {
                $editResult['image_url'] = SITE_URL . 'uploads/' . trim($uploadFolder, '/') . '/' . $editResult[$imageField];
            } else {
                $editResult['image_url'] = '';
            }
        }

        // Custom hook to augment edit data if defined
        if (is_callable($onEditData)) {
            $extra = $onEditData($editResult, $id);
            if (is_array($extra)) {
                $response = array_merge($response, $extra);
            }
        }

        $response['data'] = $editResult;
    } else {
        $response['message'] = 'Failed to fetch record.';
    }

    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 2. Validate Request Method
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 3. Permission Check
// ----------------------------------------------------
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$requiredAction = ($id > 0) ? 'updates' : 'adds';
if (!hasPermission($moduleKey, $requiredAction)) {
    $response['message'] = 'Permission denied. You do not have permission to ' . (($id > 0) ? 'edit' : 'create') . ' ' . strtolower($pageNm) . '.';
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 4. Company ID Handling
// ----------------------------------------------------
$company_id = 0;
if ($hasCompany) {
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
}

// ----------------------------------------------------
// 5. Name Validation
// ----------------------------------------------------
$name = trim($_POST[$nameField] ?? '');
if ($name === '') {
    $response['message'] = $nameLabel . ' is required.';
    echo json_encode($response);
    exit;
}
$nameEsc = db_escape($name);

// ----------------------------------------------------
// 6. Additional Fields & Validations
// ----------------------------------------------------
$dataValues = [];
if ($hasCompany) {
    $dataValues['company_id'] = $company_id;
}
$dataValues[$nameField] = $nameEsc;

foreach ($fields as $col => $type) {
    if (isset($_POST[$col])) {
        $val = trim((string)$_POST[$col]);
        if ($type === 'int') {
            $dataValues[$col] = (int)$val;
        } elseif ($type === 'float') {
            if ($val === '' || !is_numeric($val) || (float)$val < 0) {
                $response['message'] = ucfirst(str_replace('_', ' ', $col)) . ' must be a valid positive number.';
                echo json_encode($response);
                exit;
            }
            $dataValues[$col] = (float)$val;
        } else {
            $dataValues[$col] = db_escape($val);
        }
    }
}

// ----------------------------------------------------
// 7. Uniqueness Validation
// ----------------------------------------------------
$companyWhere = ($hasCompany && $company_id > 0) ? " AND company_id = $company_id" : "";
$uniqueIdWhere = ($id > 0) ? " AND id != $id" : "";

$uniqueConditions = [];
foreach ($uniqueFields as $uField) {
    if (isset($dataValues[$uField])) {
        $uVal = $dataValues[$uField];
        if (is_numeric($uVal)) {
            $uniqueConditions[] = "$uField = $uVal";
        } else {
            $uniqueConditions[] = "$uField = '$uVal'";
        }
    }
}

if (!empty($uniqueConditions)) {
    $uniqueWhereSql = implode(" AND ", $uniqueConditions);
    $uniqueSql = "SELECT id FROM $tbl WHERE $uniqueWhereSql $uniqueIdWhere $companyWhere LIMIT 1";
    $uniqueResult = db_row($uniqueSql);
    if (!empty($uniqueResult)) {
        $response['message'] = $pageNm . ' already exists.';
        echo json_encode($response);
        exit;
    }
}

// ----------------------------------------------------
// 8. Image Upload Handling (if enabled)
// ----------------------------------------------------
$imageName = null;
if ($hasImage) {
    if (isset($_FILES[$imageField]) && $_FILES[$imageField]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES[$imageField]['tmp_name'];
        $fileName    = $_FILES[$imageField]['name'];
        $fileSize    = $_FILES[$imageField]['size'];
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
        $uploadFileDir = BASE_PATH . '/uploads/' . trim($uploadFolder, '/') . '/';
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
        if ($imageName !== null) {
            $oldRow = db_row("SELECT $imageField FROM $tbl WHERE id = $id LIMIT 1");
            if (!empty($oldRow[$imageField])) {
                $oldFilePath = BASE_PATH . '/uploads/' . trim($uploadFolder, '/') . '/' . $oldRow[$imageField];
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }
            $dataValues[$imageField] = "'$imageName'";
        } elseif ($remove_image) {
            $oldRow = db_row("SELECT $imageField FROM $tbl WHERE id = $id LIMIT 1");
            if (!empty($oldRow[$imageField])) {
                $oldFilePath = BASE_PATH . '/uploads/' . trim($uploadFolder, '/') . '/' . $oldRow[$imageField];
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }
            $dataValues[$imageField] = "NULL";
        }
    } else {
        if ($imageName !== null) {
            $dataValues[$imageField] = "'$imageName'";
        } else {
            $dataValues[$imageField] = "NULL";
        }
    }
}

// ----------------------------------------------------
// 9. Custom Before Save Hook
// ----------------------------------------------------
if (is_callable($beforeSave)) {
    $hookRes = $beforeSave($dataValues, $id);
    if ($hookRes === false) {
        if ($response['message'] === 'Something went wrong.') {
            $response['message'] = 'Validation failed.';
        }
        echo json_encode($response);
        exit;
    }
}

// ----------------------------------------------------
// 10. Execute INSERT or UPDATE
// ----------------------------------------------------
if ($id > 0) {
    $updateParts = [];
    foreach ($dataValues as $col => $val) {
        if ($val === "NULL") {
            $updateParts[] = "$col = NULL";
        } elseif (is_numeric($val)) {
            $updateParts[] = "$col = $val";
        } elseif (str_starts_with((string)$val, "'") && str_ends_with((string)$val, "'")) {
            $updateParts[] = "$col = $val";
        } else {
            $updateParts[] = "$col = '$val'";
        }
    }
    $updateParts[] = "updated_by = $userId";
    $updateParts[] = "updated_at = NOW()";

    $setClause = implode(", ", $updateParts);
    $sql = "UPDATE $tbl SET $setClause WHERE id = $id";
    $msg = $pageNm . ' updated successfully.';
} else {
    $cols = [];
    $vals = [];

    foreach ($dataValues as $col => $val) {
        $cols[] = $col;
        if ($val === "NULL") {
            $vals[] = "NULL";
        } elseif (is_numeric($val)) {
            $vals[] = $val;
        } elseif (str_starts_with((string)$val, "'") && str_ends_with((string)$val, "'")) {
            $vals[] = $val;
        } else {
            $vals[] = "'$val'";
        }
    }

    $cols[] = "status";
    $vals[] = "1";

    $cols[] = "created_by";
    $vals[] = "$userId";

    $cols[] = "updated_by";
    $vals[] = "$userId";

    $cols[] = "created_at";
    $vals[] = "NOW()";

    $cols[] = "updated_at";
    $vals[] = "NOW()";

    $colsClause = implode(", ", $cols);
    $valsClause = implode(", ", $vals);
    $sql = "INSERT INTO $tbl ($colsClause) VALUES ($valsClause)";
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
