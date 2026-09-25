<?php
require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/../conn/dbqry.php';

// Check if dynamic favicon is already calculated by header.php or calculate fallback
if (!isset($headerFavicon)) {
  $defaultFavicon = SITE_URL . 'assets/image/crm_logo.png';
  $headerFavicon = $defaultFavicon;
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  $cssCompId = (int)($_SESSION['company_id'] ?? 0);
  if ($cssCompId > 0) {
    $compFavRow = db_row("SELECT favicon, header_image, app_logo FROM company WHERE id = $cssCompId LIMIT 1");
    if (!empty($compFavRow['favicon']) && file_exists(BASE_PATH . '/uploads/company/' . $compFavRow['favicon'])) {
      $headerFavicon = SITE_URL . 'uploads/company/' . $compFavRow['favicon'];
    } elseif (!empty($compFavRow['header_image']) && file_exists(BASE_PATH . '/uploads/company/' . $compFavRow['header_image'])) {
      $headerFavicon = SITE_URL . 'uploads/company/' . $compFavRow['header_image'];
    } elseif (!empty($compFavRow['app_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $compFavRow['app_logo'])) {
      $headerFavicon = SITE_URL . 'uploads/company/' . $compFavRow['app_logo'];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-sidebar-size="expanded" dir="ltr">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
  <meta charset="UTF-8" />
  <meta name="hosting-provider" content="Netlify">
  <meta name="netlify-deploy" content="https://netlify.new/?utm_campaign=ai-legible&amp;utm_source=meta&amp;utm_medium=referral&amp;utm_id=4be51903-81f3-4958-9ebf-ad2cac13c711">
  <link rel="icon" type="image/svg+xml" href="<?= $headerFavicon ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Armor CRM</title>
  <meta name="description"
    content="Armore CRM">
  <meta name="keywords" content="Armore CRM">
  <meta name="author" content="Armore">
  <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&amp;display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/index.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/data-tables.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/form-select.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/flatpickr.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/app.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/custom.css">
</head>