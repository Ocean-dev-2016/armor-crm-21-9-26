<?php

define('ID_ENCRYPT_KEY', 'your-strong-secret-key-here');

function encrypt_id($id)
{
    $key = hash('sha256', ID_ENCRYPT_KEY, true);

    $iv = random_bytes(16);

    $encrypted = openssl_encrypt(
        (string)$id,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return rtrim(
        strtr(
            base64_encode($iv . $encrypted),
            '+/',
            '-_'
        ),
        '='
    );
}

function decrypt_id($encryptedId)
{
    $key = hash('sha256', ID_ENCRYPT_KEY, true);

    $data = base64_decode(
        strtr($encryptedId, '-_', '+/')
    );

    if ($data === false || strlen($data) < 17) {
        return false;
    }

    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);

    $id = openssl_decrypt(
        $encrypted,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($id === false || !ctype_digit($id)) {
        return false;
    }

    return (int)$id;
}

function generate_slug($text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function formatDate($date, $toFormat = 'Y-m-d')
{
    if (empty($date)) {
        return null;
    }

    // Detect current format
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
        $dateObj = DateTime::createFromFormat('d-m-Y', $date);
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    } else {
        return null;
    }

    if (!$dateObj) {
        return null;
    }

    return $dateObj->format($toFormat);
}

function getClientIp()
{
    return $_SERVER['REMOTE_ADDR'] ?? '';
}



/**
 * Get logged-in user ID
 */
function getCurrentUserId(): int
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Load all role permissions for the currently logged-in user from role_permissions table
 */
function getUserRolePermissions(): array
{
    static $permissions = null;
    if ($permissions !== null) {
        return $permissions;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $permissions = [];
    $isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
    if ($isSuperadmin) {
        return $permissions; // empty array, superadmin bypasses restrictions
    }

    $companyId = (int)($_SESSION['company_id'] ?? 0);
    $roleId    = (int)($_SESSION['role_id'] ?? 0);

    if ($companyId > 0 && $roleId > 0) {
        $rows = db_rows("SELECT module_id, views, adds, updates, deletes, print, excel 
                         FROM role_permissions 
                         WHERE company_id = $companyId AND role_id = $roleId");
        if ($rows) {
            foreach ($rows as $r) {
                $permissions[(int)$r['module_id']] = [
                    'views'   => (int)$r['views'],
                    'adds'    => (int)$r['adds'],
                    'updates' => (int)$r['updates'],
                    'deletes' => (int)$r['deletes'],
                    'print' => (int)$r['print'],
                    'excel' => (int)$r['excel'],
                ];
            }
        }
    }

    return $permissions;
}

/**
 * Check if the current user has permission for a specific module and action.
 * Superadmin always returns true.
 * For other users, checks both their plan's allowed modules AND role_permissions table.
 *
 * @param int|string $module Module ID or Module route string
 * @param string $action 'views', 'adds', 'updates', or 'deletes'
 * @return bool
 */
function hasPermission($module, string $action = 'views'): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Superadmin has full access to everything
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin') {
        return true;
    }

    // 2. Resolve module ID
    $moduleId = 0;
    if (is_numeric($module)) {
        $moduleId = (int)$module;
    } else {
        $cleanRoute = trim((string)$module, '/');
        $modRow = db_row("SELECT id FROM module WHERE route = '" . db_escape($cleanRoute) . "' OR route = '" . db_escape($module) . "' LIMIT 1");
        if ($modRow) {
            $moduleId = (int)$modRow['id'];
        }
    }

    if ($moduleId <= 0) {
        return false;
    }

    // 3. Plan verification: company's plan panel_right must allow this module
    $planId = (int)($_SESSION['plan_id'] ?? 0);
    if ($planId > 0) {
        $planRow = db_row("SELECT panel_right FROM plan WHERE id = $planId AND status = 1 LIMIT 1");
        if ($planRow && !empty($planRow['panel_right'])) {
            $planModules = array_map('intval', array_filter(array_map('trim', explode(',', $planRow['panel_right']))));
            if (!in_array($moduleId, $planModules, true)) {
                return false;
            }
        }
    }

    // 4. Role Permissions check from role_permissions table
    $permissions = getUserRolePermissions();
    if (!isset($permissions[$moduleId])) {
        return false;
    }

    return !empty($permissions[$moduleId][$action]);
}

/**
 * Common permission checker for pages.
 * If user does not have permission, prints a styled alert box and cleanly terminates with footer.
 *
 * @param int|string $module Module ID or route slug (e.g. 'team-person', 'city', 'country')
 * @param string $action 'views', 'adds', 'updates', or 'deletes'
 */
function checkPermissionOrDeny($module, string $action = 'views'): void
{
    if (!hasPermission($module, $action)) {
        $actionVerb = match ($action) {
            'adds'    => 'add records in',
            'updates' => 'edit records in',
            'deletes' => 'delete records in',
            'print' => 'print in',
            'excel' => 'export to excel in',
            default   => 'view',
        };
        echo "
            <div class='row'>
            <div class='col-md-4 col-lg-6 col-xl-4 col-xxl-3 mx-auto'>
              <div class='card m-4 border-primary shadow-sm'>
                <div class='card-body text-primary py-4 text-center'>
                    <i data-lucide='shield-alert' class='mb-2' style='width: 48px; height: 48px;'></i>
                    <h5 class='card-title text-primary'>Access Denied</h5>
                    <p class='card-text mb-0'>You do not have permission to {$actionVerb} this page.</p>
                </div>
              </div>
              </div>
            </div>";
        if (defined('BASE_PATH')) {
            include_once BASE_PATH . '/include/footer.php';
        }
        exit;
    }
}
