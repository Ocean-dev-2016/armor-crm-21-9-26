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

    $companyId = (int)($user['company_id'] ?? 0);

    // if ($companyId > 0) {
    //     // Upload to uploads/company/
    //     $uploadDir = BASE_PATH . '/uploads/company/';
    //     if (!is_dir($uploadDir)) {
    //         mkdir($uploadDir, 0777, true);
    //     }

    //     $newFileName = 'header_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
    //     $destPath = $uploadDir . $newFileName;

    //     if (move_uploaded_file($fileTmpPath, $destPath)) {
    //         // Delete old file if exists
    //         $comp = db_row("SELECT header_image FROM company WHERE id = $companyId LIMIT 1");
    //         if (!empty($comp['header_image']) && file_exists($uploadDir . $comp['header_image'])) {
    //             @unlink($uploadDir . $comp['header_image']);
    //         }

    //         db_query("UPDATE company SET header_image = '$newFileName', updated_at = NOW() WHERE id = $companyId");

    //         echo json_encode([
    //             'status' => true,
    //             'message' => 'Company logo updated successfully.'
    //         ]);
    //         exit;
    //     }
    // } else {
        // Superadmin or user without company: update user profile_img
        $uploadDir = BASE_PATH . '/uploads/profile/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newFileName = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
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
    //}

    echo json_encode([
        'status' => false,
        'message' => 'Failed to upload image. Please try again.'
    ]);
    exit;
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
