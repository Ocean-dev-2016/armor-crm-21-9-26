<?php

session_start();

require_once __DIR__ . '/conn/db.php';
session_destroy();

header("Location: " . SITE_URL . "login");
exit;