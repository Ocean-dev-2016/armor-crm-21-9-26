<?php
require_once __DIR__ . '/../../conn/db.php';
require_once __DIR__ . '/../../conn/dbqry.php';
require_once __DIR__ . '/../../conn/helper.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = getCurrentUserId();
if ($userId <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Please login to perform this action.'
    ]);
    exit;
}

$user = db_row("SELECT * FROM users WHERE id = $userId LIMIT 1");
if (!$user) {
    echo json_encode([
        'status' => false,
        'message' => 'User not found.'
    ]);
    exit;
}

$action = trim($_POST['action'] ?? '');

// 1. UPDATE LOGO ACTION
if ($action === 'update_logo') {
    if (!isset($_FILES['logo_image']) || $_FILES['logo_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'status' => false,
            'message' => 'Please choose a valid image file.'
        ]);
        exit;
    }

    $fileTmpPath   = $_FILES['logo_image']['tmp_name'];
    $fileName      = $_FILES['logo_image']['name'];
    $fileSize      = $_FILES['logo_image']['size'];
    $fileNameCmps  = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileExtension, $allowedExtensions, true)) {
        echo json_encode([
            'status' => false,
            'message' => 'Only image files (jpg, jpeg, png, webp) are allowed.'
        ]);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        echo json_encode([
            'status' => false,
            'message' => 'Image size must not exceed 5MB.'
        ]);
        exit;
    }

    $uploadDir = BASE_PATH . '/uploads/profile/';
    $newFileName = upload_and_convert_to_webp($_FILES['logo_image'], $uploadDir, 'profile', 85);

    if ($newFileName) {
        if (!empty($user['profile_img']) && file_exists($uploadDir . $user['profile_img'])) {
            @unlink($uploadDir . $user['profile_img']);
        }

        db_query("UPDATE users SET profile_img = '$newFileName', updated_at = NOW() WHERE id = $userId");

        echo json_encode([
            'status' => true,
            'message' => 'Profile image updated successfully.'
        ]);
        exit;
    }

    echo json_encode([
        'status' => false,
        'message' => 'Failed to upload image. Please try again.'
    ]);
    exit;
}

// 1.1 UPDATE BRANDING (Login Logo, Header Logo, Favicon)
// If Superadmin -> updates users table (system branding)
// If Company User -> updates company table (company branding)
if ($action === 'update_branding' || $action === 'update_superadmin_branding') {
    $isSuper = ($user['user_type'] ?? '') === 'superadmin';
    $companyId = (int)($user['company_id'] ?? 0);

    if (!$isSuper && $companyId <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'No associated company found for this user.'
        ]);
        exit;
    }

    $uploadDir = $isSuper ? (BASE_PATH . '/uploads/system/') : (BASE_PATH . '/uploads/company/');
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'ico'];
    $updates = [];

    // Current values
    if ($isSuper) {
        $curLoginLogo = $user['login_logo'] ?? '';
        $curHeaderLogo = $user['header_logo'] ?? '';
        $curFavicon = $user['favicon'] ?? '';
    } else {
        $compRow = db_row("SELECT login_logo, header_image, favicon FROM company WHERE id = $companyId LIMIT 1");
        $curLoginLogo = $compRow['login_logo'] ?? '';
        $curHeaderLogo = $compRow['header_image'] ?? '';
        $curFavicon = $compRow['favicon'] ?? '';
    }

    // Helper for processing branding file upload
    $processUpload = function($fileKey, $targetCol, $prefix, $currentVal, $removeKey) use ($uploadDir, $allowedExtensions, &$updates) {
        $shouldRemove = isset($_POST[$removeKey]) && $_POST[$removeKey] == '1';

        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $origName = $_FILES[$fileKey]['name'];
            $fileSize = $_FILES[$fileKey]['size'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                return "Invalid file type for $prefix. Allowed: JPG, PNG, WEBP, ICO.";
            }
            if ($fileSize > 5 * 1024 * 1024) {
                return "File size for $prefix cannot exceed 5MB.";
            }

            $newFileName = upload_and_convert_to_webp($_FILES[$fileKey], $uploadDir, $prefix, 85);
            if ($newFileName) {
                // Delete old file if present
                if (!empty($currentVal) && file_exists($uploadDir . $currentVal)) {
                    @unlink($uploadDir . $currentVal);
                }
                $updates[$targetCol] = $newFileName;
            } else {
                return "Failed to save uploaded file for $prefix.";
            }
        } elseif ($shouldRemove) {
            if (!empty($currentVal) && file_exists($uploadDir . $currentVal)) {
                @unlink($uploadDir . $currentVal);
            }
            $updates[$targetCol] = null;
        }
        return true;
    };

    // 1. Login Logo
    $err = $processUpload('login_logo', 'login_logo', 'login_logo', $curLoginLogo, 'remove_login_logo');
    if ($err !== true) {
        echo json_encode(['status' => false, 'message' => $err]);
        exit;
    }

    // 2. Header Logo (header_logo for superadmin in users table, header_image for company in company table)
    $headerCol = $isSuper ? 'header_logo' : 'header_image';
    $err = $processUpload('header_logo', $headerCol, 'header', $curHeaderLogo, 'remove_header_logo');
    if ($err !== true) {
        echo json_encode(['status' => false, 'message' => $err]);
        exit;
    }

    // 3. Favicon
    $err = $processUpload('favicon', 'favicon', 'favicon', $curFavicon, 'remove_favicon');
    if ($err !== true) {
        echo json_encode(['status' => false, 'message' => $err]);
        exit;
    }

    if (!empty($updates)) {
        $setParts = [];
        foreach ($updates as $col => $val) {
            if ($val === null) {
                $setParts[] = "$col = NULL";
            } else {
                $escaped = db_escape($val);
                $setParts[] = "$col = '$escaped'";
            }
        }
        $setParts[] = "updated_at = NOW()";
        $setSql = implode(', ', $setParts);

        if ($isSuper) {
            db_query("UPDATE users SET $setSql WHERE id = $userId");
        } else {
            db_query("UPDATE company SET $setSql WHERE id = $companyId");
        }

        echo json_encode([
            'status' => true,
            'message' => 'Branding settings updated successfully.'
        ]);
        exit;
    } else {
        echo json_encode([
            'status' => true,
            'message' => 'No changes made.'
        ]);
        exit;
    }
}

// 2. UPDATE ACCOUNT SETTINGS (Profile Name & Passwords)
if ($action === 'update_account') {
    $profileName = trim($_POST['profile_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($profileName)) {
        echo json_encode([
            'status' => false,
            'message' => 'Profile name is required.'
        ]);
        exit;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => false,
            'message' => 'Please enter a valid email address.'
        ]);
        exit;
    }

    // Check email uniqueness in users table
    $emailEsc = db_escape($email);
    $existingEmail = db_row("SELECT id FROM users WHERE email = '$emailEsc' AND id != $userId LIMIT 1");
    if ($existingEmail) {
        echo json_encode([
            'status' => false,
            'message' => 'Email is already registered. Please use another.'
        ]);
        exit;
    }

    $profileNameEsc = db_escape($profileName);
    $passwordUpdateSql = "";

    // If new password is provided, validate old password and strong password format
    if (!empty($newPassword)) {
        if (empty($oldPassword)) {
            echo json_encode([
                'status' => false,
                'message' => 'Please enter your current old password.'
            ]);
            exit;
        }

        if (!password_verify($oldPassword, $user['password'])) {
            echo json_encode([
                'status' => false,
                'message' => 'Old password does not match.'
            ]);
            exit;
        }

        if (strlen($newPassword) < 8 || strlen($newPassword) > 12) {
            echo json_encode([
                'status' => false,
                'message' => 'Password must be between 8 and 12 characters.'
            ]);
            exit;
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,12}$/', $newPassword)) {
            echo json_encode([
                'status' => false,
                'message' => 'Password must contain at least 1 uppercase letter, 1 lowercase letter, and 1 number.'
            ]);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode([
                'status' => false,
                'message' => 'New password and confirm password do not match.'
            ]);
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $passwordUpdateSql = ", password = '$hash'";
    }

    $updateSql = "UPDATE users SET name = '$profileNameEsc', email = '$emailEsc' $passwordUpdateSql, updated_at = NOW() WHERE id = $userId";
    $res = db_query($updateSql);

    if ($res) {
        $_SESSION['name'] = $profileName;
        echo json_encode([
            'status' => true,
            'message' => 'Profile updated successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Failed to update profile.'
        ]);
    }
    exit;
}

echo json_encode([
    'status' => false,
    'message' => 'Invalid action.'
]);
exit;
