<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

header('Content-Type: application/json');

$userId = getCurrentUserId();
if ($userId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Please login to continue.']);
    exit;
}

if (!hasPermission('company', 'updates')) {
    echo json_encode(['status' => false, 'message' => 'Permission denied.']);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

if ($action === 'add_days') {
    $subscriptionId = (int)($_POST['subscription_id'] ?? 0);
    $companyId      = (int)($_POST['company_id'] ?? 0);
    $addDays        = (int)($_POST['add_days'] ?? 0);

    if ($subscriptionId <= 0 || $companyId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid subscription details.']);
        exit;
    }

    if ($addDays <= 0) {
        echo json_encode(['status' => false, 'message' => 'Please enter a valid number of days (at least 1 day).']);
        exit;
    }

    // Fetch active subscription
    $sub = db_row("SELECT * FROM company_subscription_plan WHERE id = $subscriptionId AND company_id = $companyId AND subscription_status = 'active' LIMIT 1");
    if (empty($sub)) {
        echo json_encode(['status' => false, 'message' => 'Active subscription record not found or already expired.']);
        exit;
    }

    // Base date for adding days: expiry_date if valid and >= CURDATE(), otherwise CURDATE()
    $baseDate = !empty($sub['plan_expiry_date']) ? $sub['plan_expiry_date'] : date('Y-m-d');
    if (strtotime($baseDate) < strtotime(date('Y-m-d'))) {
        $baseDate = date('Y-m-d');
    }

    $newExpiryDate = date('Y-m-d', strtotime("$baseDate +$addDays days"));

    // Also update plan_to if it exists
    $basePlanTo = !empty($sub['plan_to']) ? $sub['plan_to'] : $baseDate;
    if (strtotime($basePlanTo) < strtotime(date('Y-m-d'))) {
        $basePlanTo = date('Y-m-d');
    }
    $newPlanTo = date('Y-m-d', strtotime("$basePlanTo +$addDays days"));

    // Update subscription record
    $updateSql = "UPDATE company_subscription_plan 
                  SET plan_to = '$newPlanTo', 
                      plan_expiry_date = '$newExpiryDate', 
                      subscription_status = 'active', 
                      updated_by = $userId, 
                      updated_at = NOW() 
                  WHERE id = $subscriptionId";
    $updated = db_query($updateSql);

    if ($updated) {
        echo json_encode([
            'status' => true,
            'message' => "Successfully added $addDays days. New expiry date is " . date('d-m-Y', strtotime($newExpiryDate)) . ".",
            'new_expiry_date' => date('d-m-Y', strtotime($newExpiryDate)),
            'new_plan_to' => date('d-m-Y', strtotime($newPlanTo))
        ]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to update subscription days.']);
    }
    exit;
}

if ($action === 'upgrade_plan') {
    $companyId = (int)($_POST['company_id'] ?? 0);
    $newPlanId = (int)($_POST['plan_id'] ?? 0);

    if ($companyId <= 0 || $newPlanId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Please select a valid plan to upgrade.']);
        exit;
    }

    // Fetch company info
    $company = db_row("SELECT * FROM company WHERE id = $companyId LIMIT 1");
    if (empty($company)) {
        echo json_encode(['status' => false, 'message' => 'Company not found.']);
        exit;
    }

    // Fetch new plan details
    $newPlan = db_row("SELECT * FROM plan WHERE id = $newPlanId AND status = 1 LIMIT 1");
    if (empty($newPlan)) {
        echo json_encode(['status' => false, 'message' => 'Selected plan not found or inactive.']);
        exit;
    }

    // Mark previous active subscriptions for this company as expired
    db_query("UPDATE company_subscription_plan 
              SET subscription_status = 'expired', updated_by = $userId, updated_at = NOW() 
              WHERE company_id = $companyId AND subscription_status = 'active'");

    // Calculate dates
    $planDays = (int)($newPlan['days'] ?? 0);
    if ($planDays <= 0) {
        $planDays = 30; // default fallback
    }

    $planFrom = date('Y-m-d');
    $planTo = date('Y-m-d', strtotime("+$planDays days"));
    $planExpiryDate = $planTo;

    // Prepare extra_detail JSON
    $extraDetailArray = [
        'id' => (int)$newPlan['id'],
        'name' => $newPlan['name'] ?? '',
        'price' => (float)($newPlan['price'] ?? 0),
        'days' => $planDays,
        'plan_valid_day' => $planDays,
        'max_team_user' => (int)($newPlan['max_team_user'] ?? 0),
        'max_customer' => (int)($newPlan['max_customer'] ?? 0),
        'max_inquiry' => (int)($newPlan['max_inquiry'] ?? 0),
    ];
    $extraDetailJson = db_escape(json_encode($extraDetailArray, JSON_UNESCAPED_UNICODE));

    // Insert new active subscription
    $insertSubSql = "INSERT INTO company_subscription_plan (
                        company_id, plan_id, plan_from, plan_to, extra_detail, 
                        plan_expiry_date, subscription_status, platform, 
                        created_by, updated_by, created_at, updated_at
                     ) VALUES (
                        $companyId, $newPlanId, '$planFrom', '$planTo', '$extraDetailJson', 
                        '$planExpiryDate', 'active', 'web', 
                        $userId, $userId, NOW(), NOW()
                     )";
    $insertedSub = db_query($insertSubSql);

    // Update company table's current plan_id
    db_query("UPDATE company SET plan_id = $newPlanId, updated_by = $userId, updated_at = NOW() WHERE id = $companyId");

    // Update company admin user's company_plan_id
    db_query("UPDATE users SET company_plan_id = $newPlanId, updated_at = NOW() WHERE company_id = $companyId AND user_type = 'company_admin'");

    // Update role permissions for company admin role based on new plan panel_right
    if (!empty($newPlan['panel_right'])) {
        $roleRow = db_row("SELECT id FROM roles WHERE company_id = $companyId LIMIT 1");
        if (!empty($roleRow['id'])) {
            $roleId = (int)$roleRow['id'];
            $moduleIds = array_filter(array_map('trim', explode(',', $newPlan['panel_right'])));
            foreach ($moduleIds as $mId) {
                $mId = (int)$mId;
                if ($mId <= 0) continue;

                $existPerm = db_row("SELECT id FROM role_permissions WHERE company_id = $companyId AND role_id = $roleId AND module_id = $mId LIMIT 1");
                if (!empty($existPerm)) {
                    db_query("UPDATE role_permissions SET views = 1, adds = 1, updates = 1, deletes = 1, print = 1, excel = 1, updated_by = $userId, updated_at = NOW() WHERE id = " . (int)$existPerm['id']);
                } else {
                    db_query("INSERT INTO role_permissions (company_id, role_id, module_id, views, adds, updates, deletes, print, excel, created_by, created_at, updated_at) VALUES ($companyId, $roleId, $mId, 1, 1, 1, 1, 1, 1, $userId, NOW(), NOW())");
                }
            }
        }
    }

    if ($insertedSub) {
        echo json_encode([
            'status' => true,
            'message' => "Company plan successfully upgraded to " . htmlspecialchars($newPlan['name']) . "!",
            'plan_name' => $newPlan['name']
        ]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to record upgraded subscription.']);
    }
    exit;
}

if ($action === 'get_history_rows') {
    $companyId = (int)($_GET['company_id'] ?? 0);
    if ($companyId <= 0) {
        echo json_encode(['status' => false, 'data' => []]);
        exit;
    }

    $historySql = "SELECT csp.*, c.name AS company_name, p.name AS current_plan_name, p.days AS plan_days
                   FROM company_subscription_plan csp
                   LEFT JOIN company c ON c.id = csp.company_id
                   LEFT JOIN plan p ON p.id = csp.plan_id
                   WHERE csp.company_id = $companyId
                   ORDER BY csp.id DESC";
    $subscriptions = db_rows($historySql);

    $html = '';
    if (empty($subscriptions)) {
        $html .= '<tr><td colspan="7" class="text-center py-4 text-muted">No subscription history found.</td></tr>';
    } else {
        foreach ($subscriptions as $row) {
            $extra = !empty($row['extra_detail']) ? json_decode($row['extra_detail'], true) : [];
            $planTitle = !empty($extra['name']) ? $extra['name'] : (!empty($row['current_plan_name']) ? $row['current_plan_name'] : 'Plan #' . $row['plan_id']);
            $planDays = !empty($extra['days']) ? (int)$extra['days'] : (!empty($row['plan_days']) ? (int)$row['plan_days'] : 0);

            $planFromFormatted = !empty($row['plan_from']) ? date('d-m-Y', strtotime($row['plan_from'])) : '-';
            $planToFormatted = !empty($row['plan_to']) ? date('d-m-Y', strtotime($row['plan_to'])) : '-';
            $planExpiryFormatted = !empty($row['plan_expiry_date']) ? date('d-m-Y', strtotime($row['plan_expiry_date'])) : '-';

            $status = strtolower($row['subscription_status'] ?? 'expired');
            $isActive = ($status === 'active');
            $statusBadge = $isActive 
                ? '<span class="badge bg-success-subtle text-success px-2 py-1 fs-12">active</span>' 
                : '<span class="badge bg-danger-subtle text-danger px-2 py-1 fs-12">expired</span>';

            $actionBtns = '-';
            if ($isActive) {
                $actionBtns = '
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm px-2 py-1 btn-add-days" 
                            data-id="' . (int)$row['id'] . '" 
                            data-company-id="' . (int)$row['company_id'] . '" 
                            data-company-name="' . htmlspecialchars($row['company_name'] ?? '', ENT_QUOTES) . '" 
                            data-plan-name="' . htmlspecialchars($planTitle, ENT_QUOTES) . '" 
                            data-expiry="' . $planExpiryFormatted . '">
                            <i data-lucide="calendar-plus" class="fs-14 align-middle me-1"></i> Add days
                        </button>
                        <button type="button" class="btn btn-success btn-sm px-2 py-1 btn-upgrade-plan" 
                            data-id="' . (int)$row['id'] . '" 
                            data-company-id="' . (int)$row['company_id'] . '" 
                            data-plan-id="' . (int)$row['plan_id'] . '" 
                            data-company-name="' . htmlspecialchars($row['company_name'] ?? '', ENT_QUOTES) . '" 
                            data-plan-name="' . htmlspecialchars($planTitle, ENT_QUOTES) . '" 
                            data-plan-days="' . $planDays . '">
                            <i data-lucide="arrow-up-circle" class="fs-14 align-middle me-1"></i> Upgrade
                        </button>
                    </div>';
            }

            $html .= '<tr>
                <td>' . htmlspecialchars($row['company_name'] ?? '') . '</td>
                <td>' . htmlspecialchars($planTitle) . '</td>
                <td>' . $planFromFormatted . '</td>
                <td>' . $planToFormatted . '</td>
                <td>' . $planExpiryFormatted . '</td>
                <td>' . $statusBadge . '</td>
                <td>' . $actionBtns . '</td>
            </tr>';
        }
    }

    // Get company name if needed
    $companyName = '';
    if (!empty($subscriptions[0]['company_name'])) {
        $companyName = $subscriptions[0]['company_name'];
    } else {
        $compRow = db_row("SELECT name FROM company WHERE id = $companyId LIMIT 1");
        $companyName = $compRow['name'] ?? '';
    }

    echo json_encode(['status' => true, 'html' => $html, 'company_name' => $companyName]);
    exit;
}

echo json_encode(['status' => false, 'message' => 'Invalid action.']);
exit;
