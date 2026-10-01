<?php
require_once __DIR__ . '/conn/db.php';
require_once __DIR__ . '/conn/dbqry.php';

header('Content-Type: application/json');

$login = trim($_POST['login'] ?? ($_GET['login'] ?? ''));

if (empty($login)) {
    echo json_encode([
        'status' => false,
        'logo'   => null
    ]);
    exit;
}

$loginEsc = mysqli_real_escape_string($conn, $login);

// 1. Check if user exists by email or username
$user = db_row("SELECT id, user_type, company_id FROM users WHERE email = '$loginEsc' OR username = '$loginEsc' LIMIT 1");

$logoUrl = null;
$companyName = null;

if (!empty($user)) {
    $userType = $user['user_type'] ?? '';
    $companyId = (int)($user['company_id'] ?? 0);

    if ($userType === 'superadmin') {
        // Superadmin Branding
        $superadmin = db_row("SELECT login_logo, header_logo FROM users WHERE user_type = 'superadmin' AND (login_logo IS NOT NULL OR header_logo IS NOT NULL) ORDER BY id ASC LIMIT 1");
        if (!empty($superadmin['login_logo']) && file_exists(BASE_PATH . '/uploads/system/' . $superadmin['login_logo'])) {
            $logoUrl = SITE_URL . 'uploads/system/' . $superadmin['login_logo'];
        } elseif (!empty($superadmin['header_logo']) && file_exists(BASE_PATH . '/uploads/system/' . $superadmin['header_logo'])) {
            $logoUrl = SITE_URL . 'uploads/system/' . $superadmin['header_logo'];
        } else {
            $logoUrl = SITE_URL . 'assets/image/crm_logo.png';
        }
        $companyName = 'Superadmin';
    } elseif ($companyId > 0) {
        // Company Branding: check login_logo > app_logo > header_image > Superadmin/System default
        $comp = db_row("SELECT id, name, login_logo, app_logo, header_image FROM company WHERE id = $companyId LIMIT 1");
        if (!empty($comp)) {
            $companyName = $comp['name'] ?? '';
            if (!empty($comp['login_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $comp['login_logo'])) {
                $logoUrl = SITE_URL . 'uploads/company/' . $comp['login_logo'];
            } elseif (!empty($comp['app_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $comp['app_logo'])) {
                $logoUrl = SITE_URL . 'uploads/company/' . $comp['app_logo'];
            } elseif (!empty($comp['header_image']) && file_exists(BASE_PATH . '/uploads/company/' . $comp['header_image'])) {
                $logoUrl = SITE_URL . 'uploads/company/' . $comp['header_image'];
            } else {
                // Fallback to Superadmin login logo or default CRM logo
                $superadmin = db_row("SELECT login_logo, header_logo FROM users WHERE user_type = 'superadmin' AND (login_logo IS NOT NULL OR header_logo IS NOT NULL) ORDER BY id ASC LIMIT 1");
                if (!empty($superadmin['login_logo']) && file_exists(BASE_PATH . '/uploads/system/' . $superadmin['login_logo'])) {
                    $logoUrl = SITE_URL . 'uploads/system/' . $superadmin['login_logo'];
                } elseif (!empty($superadmin['header_logo']) && file_exists(BASE_PATH . '/uploads/system/' . $superadmin['header_logo'])) {
                    $logoUrl = SITE_URL . 'uploads/system/' . $superadmin['header_logo'];
                } else {
                    $logoUrl = SITE_URL . 'assets/image/crm_logo.png';
                }
            }
        }
    }
}

if (!empty($logoUrl)) {
    echo json_encode([
        'status'       => true,
        'logo'         => $logoUrl,
        'company_name' => $companyName
    ]);
} else {
    echo json_encode([
        'status' => false,
        'logo'   => null
    ]);
}
exit;
