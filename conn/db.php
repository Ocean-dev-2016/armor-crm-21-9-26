<?php

//session_start();
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

// Application
define('APP_NAME', 'Armor CRM');
define('BASE_PATH', dirname(__DIR__));

// Check if running on localhost / local environment
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($httpHost, ['localhost', '127.0.0.1', '::1']) || strpos($httpHost, 'localhost:') === 0;

if ($isLocal) {
    define('SITE_URL', 'http://localhost/armor/');

    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_DATABASE', 'armor_crm_live');
} else {
    define('SITE_URL', 'https://newcrm.oceanhub.co.in/');

    define('DB_HOST', 'localhost');
    define('DB_USER', 'jrosvllq_newcrm_26_09');      
    define('DB_PASS', '9s4sreIC!zV4mfh_');  
    define('DB_DATABASE', 'jrosvllq_newcrm_26_09');   
}

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
