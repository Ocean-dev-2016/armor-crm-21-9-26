<?php
require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/../conn/dbqry.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// 1. Resolve Header Company Logo & Favicon if not already computed
if (!isset($headerCompanyLogo) || !isset($headerFavicon)) {
  $defaultCompanyLogo = SITE_URL . 'assets/image/crm_logo.png';
  $superBranding = db_row("SELECT header_logo, favicon FROM users WHERE user_type = 'superadmin' AND (header_logo IS NOT NULL OR favicon IS NOT NULL) ORDER BY id ASC LIMIT 1");
  if (!empty($superBranding['header_logo']) && file_exists(BASE_PATH . '/uploads/system/' . $superBranding['header_logo'])) {
    $defaultCompanyLogo = SITE_URL . 'uploads/system/' . $superBranding['header_logo'];
  }
  
  $defaultFavicon = $defaultCompanyLogo;
  if (!empty($superBranding['favicon']) && file_exists(BASE_PATH . '/uploads/system/' . $superBranding['favicon'])) {
    $defaultFavicon = SITE_URL . 'uploads/system/' . $superBranding['favicon'];
  }

  $calcLogo = $defaultCompanyLogo;
  $calcFavicon = $defaultFavicon;

  $cssCompId = (int)($_SESSION['company_id'] ?? 0);
  if ($cssCompId > 0) {
    $compFavRow = db_row("SELECT favicon, header_image, app_logo FROM company WHERE id = $cssCompId LIMIT 1");
    if (!empty($compFavRow['header_image']) && file_exists(BASE_PATH . '/uploads/company/' . $compFavRow['header_image'])) {
      $calcLogo = SITE_URL . 'uploads/company/' . $compFavRow['header_image'];
    } elseif (!empty($compFavRow['app_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $compFavRow['app_logo'])) {
      $calcLogo = SITE_URL . 'uploads/company/' . $compFavRow['app_logo'];
    } else {
      $calcLogo = $defaultCompanyLogo;
    }

    if (!empty($compFavRow['favicon']) && file_exists(BASE_PATH . '/uploads/company/' . $compFavRow['favicon'])) {
      $calcFavicon = SITE_URL . 'uploads/company/' . $compFavRow['favicon'];
    } elseif (!empty($compFavRow['app_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $compFavRow['app_logo'])) {
      $calcFavicon = SITE_URL . 'uploads/company/' . $compFavRow['app_logo'];
    } elseif ($calcLogo !== $defaultCompanyLogo) {
      $calcFavicon = $calcLogo;
    } else {
      $calcFavicon = $defaultFavicon;
    }
  }

  if (!isset($headerCompanyLogo)) {
    $headerCompanyLogo = $calcLogo;
  }
  if (!isset($headerFavicon)) {
    $headerFavicon = $calcFavicon;
  }
}

// 2. Server-side Logo Dominant Color Extraction (Cached in session for instant zero-lag rendering)
if (!isset($themeBrandColor) && !empty($headerCompanyLogo)) {
  $logoCacheKey = 'brand_color_' . md5($headerCompanyLogo);
  if (isset($_SESSION[$logoCacheKey]) && is_array($_SESSION[$logoCacheKey])) {
    $themeBrandColor = $_SESSION[$logoCacheKey];
  } else {
    // Determine local disk path
    $localImagePath = null;
    $parsedPath = parse_url($headerCompanyLogo, PHP_URL_PATH);
    if (!empty($parsedPath)) {
      $relative = ltrim($parsedPath, '/');
      // Strip base folder if needed
      if (strpos($relative, 'armor/') === 0) {
        $relative = substr($relative, 6);
      }
      $candidatePath = BASE_PATH . '/' . $relative;
      if (file_exists($candidatePath)) {
        $localImagePath = $candidatePath;
      }
    }

    if ($localImagePath && file_exists($localImagePath)) {
      $imgInfo = @getimagesize($localImagePath);
      $mime = $imgInfo['mime'] ?? '';
      $srcImg = null;
      if ($mime === 'image/webp' || str_ends_with(strtolower($localImagePath), '.webp')) {
        $srcImg = @imagecreatefromwebp($localImagePath);
      } elseif ($mime === 'image/png' || str_ends_with(strtolower($localImagePath), '.png')) {
        $srcImg = @imagecreatefrompng($localImagePath);
      } elseif ($mime === 'image/jpeg' || str_ends_with(strtolower($localImagePath), '.jpg') || str_ends_with(strtolower($localImagePath), '.jpeg')) {
        $srcImg = @imagecreatefromjpeg($localImagePath);
      }

      if ($srcImg) {
        $thumbW = 48;
        $thumbH = 48;
        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $srcImg, 0, 0, 0, 0, $thumbW, $thumbH, imagesx($srcImg), imagesy($srcImg));
        imagedestroy($srcImg);

        $colorBuckets = [];
        $maxBucketCount = 0;
        $dominant = null;

        for ($x = 0; $x < $thumbW; $x++) {
          for ($y = 0; $y < $thumbH; $y++) {
            $rgba = imagecolorat($thumb, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;
            if ($alpha > 60) continue; // Skip mostly transparent

            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // Skip near-white and near-black
            if ($r > 235 && $g > 235 && $b > 235) continue;
            if ($r < 25 && $g < 25 && $b < 25) continue;

            $qR = (int)round($r / 16) * 16;
            $qG = (int)round($g / 16) * 16;
            $qB = (int)round($b / 16) * 16;
            $bKey = "{$qR},{$qG},{$qB}";

            $colorBuckets[$bKey] = ($colorBuckets[$bKey] ?? 0) + 1;
            if ($colorBuckets[$bKey] > $maxBucketCount) {
              $maxBucketCount = $colorBuckets[$bKey];
              $dominant = ['r' => $qR, 'g' => $qG, 'b' => $qB];
            }
          }
        }
        imagedestroy($thumb);

        if ($dominant) {
          $dominant['hex'] = sprintf('#%02x%02x%02x', $dominant['r'], $dominant['g'], $dominant['b']);
          $dominant['rgb'] = "{$dominant['r']}, {$dominant['g']}, {$dominant['b']}";
          $themeBrandColor = $dominant;
          $_SESSION[$logoCacheKey] = $themeBrandColor;
        }
      }
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

  <!-- Immediate Client-Side Theme Preload Script to Prevent Any Refresh Flash -->
  <script>
    (function() {
      try {
        // 1. Instantly apply dark / light mode before page starts rendering
        var savedTheme = localStorage.getItem('theme');
        var smartTheme = localStorage.getItem('smartTheme');
        var effectiveTheme = savedTheme || 'light';
        if (smartTheme === 'time') {
          var hr = new Date().getHours();
          effectiveTheme = (hr >= 6 && hr < 18) ? 'light' : 'dark';
        }
        if (effectiveTheme === 'dark') {
          document.documentElement.setAttribute('data-bs-theme', 'dark');
        } else {
          document.documentElement.setAttribute('data-bs-theme', 'light');
        }

        var savedSidebarTheme = localStorage.getItem('sidebarTheme');
        if (savedSidebarTheme && savedSidebarTheme !== 'default') {
          document.documentElement.setAttribute('data-sidebar-theme', savedSidebarTheme);
        }
        var savedHeaderTheme = localStorage.getItem('headerTheme');
        if (savedHeaderTheme && savedHeaderTheme !== 'default') {
          document.documentElement.setAttribute('data-header-theme', savedHeaderTheme);
        }
        var savedPreset = localStorage.getItem('themePreset');
        if (savedPreset && savedPreset !== 'default') {
          document.documentElement.setAttribute('data-theme-preset', savedPreset);
        }

        // 2. Instantly apply logo dominant theme colors
        var lastTheme = localStorage.getItem('armor_last_logo_color');
        if (lastTheme) {
          var parsed = JSON.parse(lastTheme);
          if (parsed && parsed.hex && parsed.rgb) {
            var hex = parsed.hex;
            var rgb = parsed.rgb;
            var rgbStr = rgb.r + ', ' + rgb.g + ', ' + rgb.b;
            var hoverHex = 'rgb(' + Math.round(rgb.r * 0.85) + ', ' + Math.round(rgb.g * 0.85) + ', ' + Math.round(rgb.b * 0.85) + ')';
            var activeHex = 'rgb(' + Math.round(rgb.r * 0.75) + ', ' + Math.round(rgb.g * 0.75) + ', ' + Math.round(rgb.b * 0.75) + ')';
            var css = ':root,[data-bs-theme=light]{--bs-primary:' + hex + ' !important;--bs-primary-rgb:' + rgbStr + ' !important;--bs-link-color:' + hex + ' !important;--bs-link-hover-color:' + hoverHex + ' !important;--bs-primary-bg-subtle:rgba(' + rgbStr + ',0.12) !important;--bs-primary-border-subtle:rgba(' + rgbStr + ',0.35) !important;--bs-focus-ring-color:rgba(' + rgbStr + ',0.25) !important;}[data-bs-theme=dark]{--bs-primary:' + hex + ' !important;--bs-primary-rgb:' + rgbStr + ' !important;--bs-link-color:' + hex + ' !important;--bs-primary-bg-subtle:rgba(' + rgbStr + ',0.2) !important;--bs-primary-border-subtle:rgba(' + rgbStr + ',0.45) !important;}.btn-primary{--bs-btn-bg:' + hex + ' !important;--bs-btn-border-color:' + hex + ' !important;--bs-btn-hover-bg:' + hoverHex + ' !important;--bs-btn-hover-border-color:' + hoverHex + ' !important;--bs-btn-active-bg:' + activeHex + ' !important;--bs-btn-active-border-color:' + activeHex + ' !important;--bs-btn-disabled-bg:' + hex + ' !important;--bs-btn-disabled-border-color:' + hex + ' !important;}.btn-outline-primary{--bs-btn-color:' + hex + ' !important;--bs-btn-border-color:' + hex + ' !important;--bs-btn-hover-bg:' + hex + ' !important;--bs-btn-hover-border-color:' + hex + ' !important;--bs-btn-active-bg:' + hex + ' !important;--bs-btn-active-border-color:' + hex + ' !important;}.text-primary{color:' + hex + ' !important;}.bg-primary{background-color:rgba(' + rgbStr + ',var(--bs-bg-opacity,1)) !important;}.border-primary{border-color:' + hex + ' !important;}.horizontal-menu .nav-item .nav-link.active,.horizontal-menu .nav-item .nav-link[aria-expanded=true]{color:#fff !important;background-color:' + hex + ' !important;}.horizontal-menu .nav-item .nav-link.active .nav-text,.horizontal-menu .nav-item .nav-link[aria-expanded=true] .nav-text{color:#fff !important;}.horizontal-menu .nav-item .nav-link.active i,.horizontal-menu .nav-item .nav-link.active [data-lucide],.horizontal-menu .nav-item .nav-link[aria-expanded=true] i,.horizontal-menu .nav-item .nav-link[aria-expanded=true] [data-lucide]{color:#fff !important;}.form-check-input:checked{background-color:' + hex + ' !important;border-color:' + hex + ' !important;}.page-item.active .page-link{background-color:' + hex + ' !important;border-color:' + hex + ' !important;}.module-card.selected-highlight{background-color:' + hex + ' !important;border-color:' + hex + ' !important;}';
            var s = document.createElement('style');
            s.id = 'dynamic-logo-theme-vars';
            s.textContent = css;
            document.head.appendChild(s);
          }
        }
      } catch (err) {}
    })();
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&amp;display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/index.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/data-tables.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/form-select.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/flatpickr.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/app.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/custom.css">
<?php if (!empty($themeBrandColor['hex'])): 
  $bHex = $themeBrandColor['hex'];
  $bRgb = $themeBrandColor['rgb'];
  $r = $themeBrandColor['r'];
  $g = $themeBrandColor['g'];
  $b = $themeBrandColor['b'];
?>
  <!-- Dynamic Theme Color based on Header Logo (Server-Rendered for 0ms delay) -->
  <style id="dynamic-logo-theme-vars">
    :root,
    [data-bs-theme=light] {
      --bs-primary: <?= $bHex ?> !important;
      --bs-primary-rgb: <?= $bRgb ?> !important;
      --bs-link-color: <?= $bHex ?> !important;
      --bs-link-hover-color: rgb(<?= round($r * 0.85) ?>, <?= round($g * 0.85) ?>, <?= round($b * 0.85) ?>) !important;
      --bs-primary-bg-subtle: rgba(<?= $bRgb ?>, 0.12) !important;
      --bs-primary-border-subtle: rgba(<?= $bRgb ?>, 0.35) !important;
      --bs-focus-ring-color: rgba(<?= $bRgb ?>, 0.25) !important;
    }
    [data-bs-theme=dark] {
      --bs-primary: <?= $bHex ?> !important;
      --bs-primary-rgb: <?= $bRgb ?> !important;
      --bs-link-color: <?= $bHex ?> !important;
      --bs-primary-bg-subtle: rgba(<?= $bRgb ?>, 0.2) !important;
      --bs-primary-border-subtle: rgba(<?= $bRgb ?>, 0.45) !important;
    }
    .btn-primary {
      --bs-btn-bg: <?= $bHex ?> !important;
      --bs-btn-border-color: <?= $bHex ?> !important;
      --bs-btn-hover-bg: rgb(<?= round($r * 0.85) ?>, <?= round($g * 0.85) ?>, <?= round($b * 0.85) ?>) !important;
      --bs-btn-hover-border-color: rgb(<?= round($r * 0.8) ?>, <?= round($g * 0.8) ?>, <?= round($b * 0.8) ?>) !important;
      --bs-btn-active-bg: rgb(<?= round($r * 0.75) ?>, <?= round($g * 0.75) ?>, <?= round($b * 0.75) ?>) !important;
      --bs-btn-disabled-bg: <?= $bHex ?> !important;
      --bs-btn-disabled-border-color: <?= $bHex ?> !important;
    }
    .btn-outline-primary {
      --bs-btn-color: <?= $bHex ?> !important;
      --bs-btn-border-color: <?= $bHex ?> !important;
      --bs-btn-hover-bg: <?= $bHex ?> !important;
      --bs-btn-hover-border-color: <?= $bHex ?> !important;
      --bs-btn-active-bg: <?= $bHex ?> !important;
      --bs-btn-active-border-color: <?= $bHex ?> !important;
    }
    .text-primary {
      color: <?= $bHex ?> !important;
    }
    .bg-primary {
      background-color: <?= $bHex ?> !important;
    }
    .border-primary {
      border-color: <?= $bHex ?> !important;
    }
    .horizontal-menu .nav-item .nav-link.active,
    .horizontal-menu .nav-item .nav-link[aria-expanded=true] {
      color: #fff !important;
      background-color: <?= $bHex ?> !important;
    }
    .horizontal-menu .nav-item .nav-link.active .nav-text,
    .horizontal-menu .nav-item .nav-link[aria-expanded=true] .nav-text {
      color: #fff !important;
    }
    .horizontal-menu .nav-item .nav-link.active i,
    .horizontal-menu .nav-item .nav-link.active [data-lucide],
    .horizontal-menu .nav-item .nav-link[aria-expanded=true] i,
    .horizontal-menu .nav-item .nav-link[aria-expanded=true] [data-lucide] {
      color: #fff !important;
    }
    .form-check-input:checked {
      background-color: <?= $bHex ?> !important;
      border-color: <?= $bHex ?> !important;
    }
    .form-check-input:focus {
      border-color: <?= $bHex ?> !important;
      box-shadow: 0 0 0 0.25rem rgba(<?= $bRgb ?>, 0.25) !important;
    }
    .page-item.active .page-link {
      background-color: <?= $bHex ?> !important;
      border-color: <?= $bHex ?> !important;
    }
    .module-card.selected-highlight {
      background-color: <?= $bHex ?> !important;
      border-color: <?= $bHex ?> !important;
      box-shadow: 0 3px 8px rgba(<?= $bRgb ?>, 0.4) !important;
    }
  </style>
<?php endif; ?>
</head>