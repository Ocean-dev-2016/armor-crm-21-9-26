<?php
require_once __DIR__ . '/conn/db.php';

// Default Superadmin Credentials
$name       = 'Super Admin';
$username   = 'superadmin';
$email      = 'superadmin@gmail.com';
$password   = '12345678'; // Default password
$user_type  = 'superadmin';
$status     = 1;

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if superadmin already exists by username or email
$checkSql = "SELECT * FROM `users` WHERE `username` = '$username' OR `email` = '$email' OR `user_type` = 'superadmin' LIMIT 1";
$res = mysqli_query($conn, $checkSql);

header('Content-Type: text/html; charset=utf-8');
echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #f8fafc; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>";

if ($res && mysqli_num_rows($res) > 0) {
    $existing = mysqli_fetch_assoc($res);
    $userId = (int)$existing['id'];

    // Update existing superadmin password and credentials
    $updateSql = "UPDATE `users` SET 
                    `name` = '$name',
                    `username` = '$username',
                    `email` = '$email',
                    `password` = '$hashedPassword',
                    `user_type` = '$user_type',
                    `status` = $status,
                    `updated_at` = NOW()
                  WHERE `id` = $userId";

    if (mysqli_query($conn, $updateSql)) {
        echo "<h2 style='color: #0d9488; margin-top:0;'>✅ Superadmin Already Existed & Updated!</h2>";
        echo "<p>Superadmin account reset with the details below:</p>";
    } else {
        echo "<h2 style='color: #e11d48; margin-top:0;'>❌ Error updating user:</h2>";
        echo "<p>" . mysqli_error($conn) . "</p>";
        echo "</div>";
        exit;
    }
} else {
    // Insert new superadmin
    $insertSql = "INSERT INTO `users` 
                    (`company_id`, `company_plan_id`, `name`, `username`, `email`, `password`, `user_type`, `status`, `created_at`, `updated_at`) 
                  VALUES 
                    (0, 0, '$name', '$username', '$email', '$hashedPassword', '$user_type', $status, NOW(), NOW())";

    if (mysqli_query($conn, $insertSql)) {
        echo "<h2 style='color: #16a34a; margin-top:0;'>🎉 Superadmin Created Successfully!</h2>";
    } else {
        echo "<h2 style='color: #e11d48; margin-top:0;'>❌ Error creating user:</h2>";
        echo "<p>" . mysqli_error($conn) . "</p>";
        echo "</div>";
        exit;
    }
}

echo "<ul style='line-height: 1.8; color: #334155; font-size: 15px;'>
        <li><strong>Username:</strong> <code>$username</code></li>
        <li><strong>Email:</strong> <code>$email</code></li>
        <li><strong>Password:</strong> <code>$password</code></li>
        <li><strong>Role / Type:</strong> <code>$user_type</code></li>
      </ul>";
echo "<p style='margin-top: 20px;'><a href='login.php' style='display: inline-block; background-color: #2563eb; color: #fff; padding: 10px 18px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Go to Login</a></p>";
echo "</div>";
