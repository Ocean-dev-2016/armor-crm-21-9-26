<?php

//session_start();
error_reporting(E_ALL);

// Application
define('APP_NAME', 'Armor CRM');

// Website URL
define('SITE_URL', 'https://newcrm.oceanhub.co.in/');
//define('FOLDER_URL', 'http://localhost/armor/admin/');
define('BASE_PATH', dirname(__DIR__));

define('DB_HOST', 'localhost');
define('DB_USER', 'jrosvllq_newcrm_26_09');
define('DB_PASS', '9s4sreIC!zV4mfh_');
define('DB_DATABASE', 'jrosvllq_newcrm_26_09');

$conn = mysqli_connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_DATABASE
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
