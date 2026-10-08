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
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return null;
    }

    $dateObj = null;

    // Detect current format
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
        $dateObj = DateTime::createFromFormat('d-m-Y', $date);
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $date)) {
        $dateObj = new DateTime($date);
    } else {
        $ts = strtotime($date);
        if ($ts !== false) {
            $dateObj = (new DateTime())->setTimestamp($ts);
        }
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

/**
 * Common helper function to upload and convert images to WebP format.
 * If the image is an ICO file, it saves directly. Other images (JPG, PNG, GIF, WEBP)
 * are converted into optimized .webp files with transparency preserved.
 *
 * @param array $file $_FILES['input_name'] array
 * @param string $targetDir Target folder path (e.g. BASE_PATH . '/uploads/profile/')
 * @param string $prefix File name prefix (e.g. 'profile', 'login_logo', 'header_logo')
 * @param int $quality WebP quality (1 to 100, default: 85)
 * @return string|false Return new filename (e.g. 'logo_1234567_1234.webp') or false on failure
 */
function upload_and_convert_to_webp($file, $targetDir, $prefix = 'img', $quality = 85)
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $targetDir = rtrim($targetDir, '/\\') . '/';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $tmpPath = $file['tmp_name'];
    $origName = $file['name'] ?? '';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    // Handle .ico and .svg files directly without conversion
    if ($ext === 'ico' || $ext === 'svg') {
        $newFileName = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($tmpPath, $targetDir . $newFileName)) {
            return $newFileName;
        }
        return false;
    }

    $imgInfo = @getimagesize($tmpPath);
    if ($imgInfo === false) {
        return false;
    }

    $mime = $imgInfo['mime'];
    $srcImg = null;

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $srcImg = @imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $srcImg = @imagecreatefrompng($tmpPath);
            break;
        case 'image/webp':
            $srcImg = @imagecreatefromwebp($tmpPath);
            break;
        case 'image/gif':
            $srcImg = @imagecreatefromgif($tmpPath);
            break;
        default:
            return false;
    }

    if (!$srcImg) {
        return false;
    }

    // Preserve transparency for PNG / WebP / GIF
    imagepalettetotruecolor($srcImg);
    imagealphablending($srcImg, false);
    imagesavealpha($srcImg, true);

    $newFileName = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.webp';
    $destPath = $targetDir . $newFileName;

    $saved = @imagewebp($srcImg, $destPath, $quality);
    imagedestroy($srcImg);

    return $saved ? $newFileName : false;
}

/**
 * Lead Dynamic Helper Functions from DB Tables
 */
function get_lead_sources(): array
{
    $rows = db_rows("SELECT id, name FROM lead_source_of_inquiry WHERE status = 1 ORDER BY name ASC");
    $list = [];
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $list[(int)$r['id']] = $r['name'];
        }
    }
    return $list;
}

function get_lead_statuses(): array
{
    $rows = db_rows("SELECT id, name, color FROM lead_status WHERE status = 1 ORDER BY order_by ASC");
    $list = [];
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $list[(int)$r['id']] = $r['name'];
        }
    }
    return $list;
}

function get_lead_stages(): array
{
    return get_lead_statuses();
}

function get_lead_status_label($val): string
{
    if (empty($val)) return '-';
    if (is_numeric($val)) {
        $row = db_row("SELECT name FROM lead_status WHERE id = " . (int)$val . " LIMIT 1");
        if (!empty($row['name'])) {
            return $row['name'];
        }
    }
    $row = db_row("SELECT name FROM lead_status WHERE LOWER(name) = '" . db_escape(strtolower((string)$val)) . "' LIMIT 1");
    if (!empty($row['name'])) {
        return $row['name'];
    }
    return ucfirst(str_replace('_', ' ', (string)$val));
}

function get_lead_stage_label($val): string
{
    return get_lead_status_label($val);
}

function get_lead_source_label($val): string
{
    if (empty($val)) return '-';
    if (is_numeric($val)) {
        $row = db_row("SELECT name FROM lead_source_of_inquiry WHERE id = " . (int)$val . " LIMIT 1");
        if (!empty($row['name'])) {
            return $row['name'];
        }
    }
    return (string)$val;
}

function get_lead_status_color($val): string
{
    if (is_numeric($val)) {
        $row = db_row("SELECT color FROM lead_status WHERE id = " . (int)$val . " LIMIT 1");
        if (!empty($row['color'])) {
            return $row['color'];
        }
    }
    $row = db_row("SELECT color FROM lead_status WHERE LOWER(name) = '" . db_escape(strtolower((string)$val)) . "' LIMIT 1");
    if (!empty($row['color'])) {
        return $row['color'];
    }
    return '#0e5a6c';
}

function get_lead_stage_slug($val): string
{
    $label = get_lead_stage_label($val);
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $label), '_'));
}

function get_lead_team_size_label($val): string
{
    if (empty($val)) return '-';
    if (is_numeric($val)) {
        return (int)$val . ' Users';
    }
    return (string)$val;
}

function get_lead_followup_types(): array
{
    $rows = db_rows("SELECT id, name FROM lead_followup_type WHERE status = 1 ORDER BY id ASC");
    $list = [];
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $list[(int)$r['id']] = $r['name'];
        }
    }
    return $list;
}

function get_lead_followup_type_label($val): string
{
    if (empty($val)) return '-';
    if (is_numeric($val)) {
        $row = db_row("SELECT name FROM lead_followup_type WHERE id = " . (int)$val . " LIMIT 1");
        if (!empty($row['name'])) {
            return $row['name'];
        }
    }
    return (string)$val;
}

/**
 * Sync or create customer from company_lead
 *
 * @param int $leadId
 * @param int $userId
 * @return int|false Customer ID or false on failure
 */
function syncLeadToCustomer(int $leadId, int $userId = 0)
{
    if ($leadId <= 0) {
        return false;
    }

    $lead = db_row("SELECT * FROM `company_lead` WHERE `id` = $leadId LIMIT 1");
    if (!$lead) {
        return false;
    }

    $leadCompanyId = (int)($lead['company_id'] ?? 0);
    $custName = trim($lead['customer_name'] ?? '');
    if ($custName === '') {
        $custName = trim($lead['contact_person'] ?? '');
    }
    if ($custName === '') {
        $custName = 'Lead #' . $leadId;
    }

    $mobileNo       = trim($lead['mobile_no'] ?? '');
    $whatsappNo     = trim($lead['whatsapp_no'] ?? '');
    $contactPerson  = trim($lead['contact_person'] ?? '');
    $email          = trim($lead['email'] ?? '');
    $address        = trim($lead['address'] ?? '');
    $area           = trim($lead['area'] ?? '');
    $pincode        = trim($lead['pincode'] ?? '');
    $countryId      = (int)($lead['country_id'] ?? 0);
    $stateId        = (int)($lead['state_id'] ?? 0);
    $cityId         = (int)($lead['city_id'] ?? 0);

    // Find customer type: first try to find one for this company or fallback to default
    $custTypeCond = "status = 1";
    if ($leadCompanyId > 0) {
        $custTypeCond .= " AND (company_id = $leadCompanyId OR company_id = 0)";
    }
    $custTypeRow = db_row("SELECT id FROM `customer_type` WHERE $custTypeCond ORDER BY (company_id = $leadCompanyId) DESC, id ASC LIMIT 1");
    $customerTypeId = $custTypeRow ? (int)$custTypeRow['id'] : 0;
    if ($customerTypeId <= 0) {
        $anyType = db_row("SELECT id FROM `customer_type` WHERE status = 1 ORDER BY id ASC LIMIT 1");
        $customerTypeId = $anyType ? (int)$anyType['id'] : 1;
    }

    // Check if customer already exists for this lead
    $existingCust = db_row("SELECT id FROM `customer` WHERE `company_lead_id` = $leadId LIMIT 1");
    if (!$existingCust && !empty($mobileNo)) {
        // Also check by mobile number within same company
        $mobCond = "`mobile_no` = '" . db_escape($mobileNo) . "'";
        if ($leadCompanyId > 0) {
            $mobCond .= " AND `company_id` = $leadCompanyId";
        }
        $existingCust = db_row("SELECT id FROM `customer` WHERE $mobCond LIMIT 1");
    }

    $nameEsc       = db_escape($custName);
    $cpEsc         = ($contactPerson !== '') ? "'" . db_escape($contactPerson) . "'" : "NULL";
    $mobileEsc     = db_escape($mobileNo);
    $waSql         = ($whatsappNo !== '') ? "'" . db_escape($whatsappNo) . "'" : "NULL";
    $emailSql      = (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) ? "'" . db_escape($email) . "'" : "NULL";
    $addrSql       = (!empty($address)) ? "'" . db_escape($address) . "'" : "NULL";
    $areaSql       = (!empty($area)) ? "'" . db_escape($area) . "'" : "NULL";
    $pinSql        = (!empty($pincode)) ? "'" . db_escape($pincode) . "'" : "NULL";
    $countrySql    = ($countryId > 0) ? (int)$countryId : "NULL";
    $stateSql      = ($stateId > 0) ? (int)$stateId : "NULL";
    $citySql       = ($cityId > 0) ? (int)$cityId : "NULL";

    $assignUserId = $userId > 0 ? $userId : (int)($lead['assigned_to'] ?? 0);
    $assignSql = ($assignUserId > 0) ? (int)$assignUserId : "NULL";

    if ($existingCust) {
        // Update existing customer record with company_lead_id and assigned_to if missing
        $existId = (int)$existingCust['id'];
        db_query("UPDATE `customer` SET 
            `company_lead_id` = $leadId,
            `assigned_to` = COALESCE(NULLIF(`assigned_to`, 0), $assignSql),
            `updated_by` = $userId,
            `updated_at` = NOW()
            WHERE `id` = $existId AND (`company_lead_id` IS NULL OR `company_lead_id` = 0)");
        return $existId;
    } else {
        // Generate auto client code for new customer
        $maxCustId = (int)(db_row("SELECT MAX(id) as mid FROM `customer`")['mid'] ?? 0) + 1;
        $clientCode = 'CC-' . str_pad($maxCustId, 3, '0', STR_PAD_LEFT);

        // Insert new customer record
        $insSql = "INSERT INTO `customer` (
            `company_id`, `company_lead_id`, `assigned_to`, `client_code`, `customer_type_id`, `name`, `contact_person`,
            `email`, `mobile_no`, `whatsapp_no`,
            `address`, `area`, `pincode`, `country_id`, `state_id`, `city_id`, `status`,
            `created_by`, `created_at`, `updated_at`
        ) VALUES (
            $leadCompanyId, $leadId, $assignSql, '$clientCode', $customerTypeId, '$nameEsc', $cpEsc,
            $emailSql, '$mobileEsc', $waSql,
            $addrSql, $areaSql, $pinSql, $countrySql, $stateSql, $citySql, 1,
            $userId, NOW(), NOW()
        )";
        $ins = db_query($insSql);
        return $ins ? db_insert_id() : false;
    }
}

