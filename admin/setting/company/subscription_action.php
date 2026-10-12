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

if ($action === 'setup_payment') {
    $companyId = (int)($_POST['company_id'] ?? 0);
    $paymentType = trim($_POST['payment_type'] ?? '');
    if ($companyId <= 0 || !in_array($paymentType, ['full', 'installment'], true)) {
        echo json_encode(['status' => false, 'message' => 'Please select a valid company and payment type.']);
        exit;
    }

    $fullMethod = trim($_POST['full_payment_method'] ?? 'Cash');
    $fullTransactionNo = trim($_POST['full_transaction_no'] ?? '');
    if ($paymentType === 'full' &&
        (!in_array($fullMethod, ['Cash', 'UPI', 'Bank Transfer', 'Cheque'], true) || strlen($fullTransactionNo) > 100)) {
        echo json_encode(['status' => false, 'message' => 'Please enter valid full payment details.']);
        exit;
    }

    $installments = [];
    $totalCents = 0;
    $paidCents = 0;
    if ($paymentType === 'installment') {
        $postedInstallments = $_POST['installments'] ?? [];
        if (!is_array($postedInstallments) || count($postedInstallments) < 2 || count($postedInstallments) > 12) {
            echo json_encode(['status' => false, 'message' => 'Please provide between 2 and 12 installments.']);
            exit;
        }

        foreach ($postedInstallments as $row) {
            if (!is_array($row)) {
                echo json_encode(['status' => false, 'message' => 'Invalid installment schedule.']);
                exit;
            }
            $amountRaw = trim((string)($row['amount'] ?? ''));
            $amount = round((float)$amountRaw, 2);
            $dueDate = trim($row['due_date'] ?? '');
            $status = trim($row['status'] ?? 'pending');
            $method = trim($row['payment_method'] ?? 'Cash');
            $parsedDate = DateTime::createFromFormat('!Y-m-d', $dueDate);

            if (!is_numeric($amountRaw) || $amount <= 0 || !$parsedDate || $parsedDate->format('Y-m-d') !== $dueDate ||
                !in_array($status, ['paid', 'pending'], true) ||
                !in_array($method, ['Cash', 'UPI', 'Bank Transfer', 'Cheque'], true)) {
                echo json_encode(['status' => false, 'message' => 'Please provide valid installment amounts, dates, statuses, and payment methods.']);
                exit;
            }

            $amountCents = (int)round($amount * 100);
            $totalCents += $amountCents;
            if ($status === 'paid') {
                $paidCents += $amountCents;
            }
            $installments[] = [
                'amount' => $amountCents / 100,
                'due_date' => $dueDate,
                'status' => $status,
                'payment_method' => $method
            ];
        }
    }

    global $conn;
    if (!mysqli_begin_transaction($conn)) {
        echo json_encode(['status' => false, 'message' => 'Could not start payment setup. Please try again.']);
        exit;
    }
    $abortPaymentSetup = function ($message) use ($conn) {
        mysqli_rollback($conn);
        echo json_encode(['status' => false, 'message' => $message]);
        exit;
    };

    $subscription = db_row("
        SELECT *
        FROM company_subscription_plan
        WHERE company_id = $companyId AND subscription_status = 'active'
        ORDER BY id DESC
        LIMIT 1
        FOR UPDATE
    ");
    if (empty($subscription)) {
        $abortPaymentSetup('No active subscription found for this company.');
    }
    $subscriptionId = (int)$subscription['id'];
    $existingPayment = db_row("SELECT id FROM company_subscription_payment WHERE company_subscription_plan_id = $subscriptionId LIMIT 1 FOR UPDATE");
    if (!empty($existingPayment)) {
        $abortPaymentSetup('Payment details already exist for this subscription. Refresh the payment details and try again.');
    }

    $planDetails = !empty($subscription['extra_detail']) ? json_decode($subscription['extra_detail'], true) : [];
    $totalAmount = isset($planDetails['price']) ? round((float)$planDetails['price'], 2) : 0;
    if ($totalAmount <= 0) {
        $planRow = db_row("SELECT price FROM plan WHERE id = " . (int)$subscription['plan_id'] . " LIMIT 1");
        $totalAmount = round((float)($planRow['price'] ?? 0), 2);
    }
    if ($totalAmount <= 0) {
        $abortPaymentSetup('This subscription has no payable plan amount.');
    }
    if ($paymentType === 'installment' && $totalCents !== (int)round($totalAmount * 100)) {
        $abortPaymentSetup('Installment amounts must equal the plan amount.');
    }

    if ($paymentType === 'full') {
        $paidAmount = $totalAmount;
        $pendingAmount = 0;
        $paymentStatus = 'paid';
    } else {
        $paidAmount = $paidCents / 100;
        $pendingAmount = ($totalCents - $paidCents) / 100;
        $paymentStatus = ($paidCents >= $totalCents) ? 'paid' : ($paidCents > 0 ? 'partial' : 'pending');
    }

    $paymentSql = "INSERT INTO company_subscription_payment (
        company_subscription_plan_id, company_id, invoice_no, total_amount, paid_amount, pending_amount,
        payment_type, payment_status, created_by, created_at, updated_at
    ) VALUES (
        $subscriptionId, $companyId, NULL, $totalAmount, $paidAmount, $pendingAmount,
        '$paymentType', '$paymentStatus', $userId, NOW(), NOW()
    )";
    if (!db_query($paymentSql)) {
        $abortPaymentSetup('Failed to save payment details.');
    }
    $paymentId = db_insert_id();
    if ($paymentId <= 0) {
        $abortPaymentSetup('Failed to save payment details.');
    }

    if ($paymentType === 'full') {
        $methodEsc = db_escape($fullMethod);
        $transactionEsc = db_escape($fullTransactionNo);
        $today = date('Y-m-d');
        $installmentSql = "INSERT INTO company_subscription_installment (
            company_subscription_plan_id, company_id, payment_id, installment_no, amount, due_date,
            paid_amount, paid_date, payment_method, transaction_no, status, remarks, created_by, created_at, updated_at
        ) VALUES (
            $subscriptionId, $companyId, $paymentId, 1, $totalAmount, '$today',
            $totalAmount, '$today', '$methodEsc', '$transactionEsc', 'paid',
            'Full Payment Completed', $userId, NOW(), NOW()
        )";
        if (!db_query($installmentSql)) {
            $abortPaymentSetup('Failed to save full payment details.');
        }
    } else {
        foreach ($installments as $index => $installment) {
            $installmentNo = $index + 1;
            $amount = $installment['amount'];
            $dueDate = db_escape($installment['due_date']);
            $methodEsc = db_escape($installment['payment_method']);
            $status = $installment['status'];
            $installmentPaidAmount = ($status === 'paid') ? $amount : 0;
            $paidDate = ($status === 'paid') ? "'" . date('Y-m-d') . "'" : "NULL";
            $installmentSql = "INSERT INTO company_subscription_installment (
                company_subscription_plan_id, company_id, payment_id, installment_no, amount, due_date,
                paid_amount, paid_date, payment_method, status, remarks, created_by, created_at, updated_at
            ) VALUES (
                $subscriptionId, $companyId, $paymentId, $installmentNo, $amount, '$dueDate',
                $installmentPaidAmount, $paidDate, '$methodEsc', '$status', NULL, $userId, NOW(), NOW()
            )";
            if (!db_query($installmentSql)) {
                $abortPaymentSetup('Failed to save the installment schedule.');
            }
        }
    }

    if (!mysqli_commit($conn)) {
        mysqli_rollback($conn);
        echo json_encode(['status' => false, 'message' => 'Could not complete payment setup. Please try again.']);
        exit;
    }
    echo json_encode(['status' => true, 'message' => 'Payment details saved successfully.']);
    exit;
}

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
if ($action === 'pay_installment') {

    $installmentId = (int)($_POST['installment_id'] ?? 0);
    $companyId     = (int)($_POST['company_id'] ?? 0);
    $payAmount     = (float)($_POST['pay_amount'] ?? 0);
    $payDate       = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $payMethod     = trim($_POST['payment_method'] ?? 'Cash');
    $txnNo         = trim($_POST['transaction_no'] ?? '');
    $remarks       = trim($_POST['remarks'] ?? '');

    if ($installmentId <= 0 || $companyId <= 0 || $payAmount <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'Please provide a valid installment, company and payment amount.'
        ]);
        exit;
    }

    $inst = db_row("
        SELECT *
        FROM company_subscription_installment
        WHERE id = $installmentId
        LIMIT 1
    ");

    if (empty($inst)) {
        echo json_encode([
            'status' => false,
            'message' => 'Installment record not found.'
        ]);
        exit;
    }

    $cspId = (int)$inst['company_subscription_plan_id'];

    $paymentMaster = db_row("
        SELECT *
        FROM company_subscription_payment
        WHERE company_subscription_plan_id = $cspId
        LIMIT 1
    ");

    if (empty($paymentMaster)) {
        echo json_encode([
            'status' => false,
            'message' => 'Subscription payment record not found.'
        ]);
        exit;
    }

    $allInsts = db_rows("
        SELECT amount, paid_amount
        FROM company_subscription_installment
        WHERE company_subscription_plan_id = $cspId
    ");

    $totalAmount = 0;
    $totalPaid   = 0;

    foreach ($allInsts as $ai) {
        $totalAmount += (float)($ai['amount'] ?? 0);
        $totalPaid   += (float)($ai['paid_amount'] ?? 0);
    }

    $totalPending = max(0, $totalAmount - $totalPaid);

    if ($payAmount > $totalPending) {
        echo json_encode([
            'status' => false,
            'message' => 'Payment amount cannot be greater than total pending amount.',
            'pending_amount' => $totalPending
        ]);
        exit;
    }

    $selectedInstNo = (int)($inst['installment_no'] ?? 0);

    $pendingInstallments = db_rows("
        SELECT *
        FROM company_subscription_installment
        WHERE company_subscription_plan_id = $cspId
          AND installment_no >= $selectedInstNo
          AND status IN ('pending', 'partial')
        ORDER BY installment_no ASC
    ");

    if (empty($pendingInstallments)) {
        echo json_encode([
            'status' => false,
            'message' => 'No pending installments available.'
        ]);
        exit;
    }

    $payMethodEsc = db_escape($payMethod);
    $txnNoEsc     = db_escape($txnNo);
    $remarksEsc   = db_escape($remarks);
    $payDateEsc   = db_escape($payDate);
    $remainingPayment = $payAmount;

    $updatedInstallments = [];

    foreach ($pendingInstallments as $pendingInst) {
        if ($remainingPayment <= 0) {
            break;
        }
        $currentInstId = (int)$pendingInst['id'];
        $instAmount = (float)($pendingInst['amount'] ?? 0);
        $previousPaid = (float)($pendingInst['paid_amount'] ?? 0);
        $instPending = max(0, $instAmount - $previousPaid);
        if ($instPending <= 0) {
            continue;
        }
        $allocateAmount = min($remainingPayment, $instPending);
        $newPaid = $previousPaid + $allocateAmount;
        if ($newPaid >= $instAmount) {
            $newPaid = $instAmount;
            $newStatus = 'paid';
        } else {
            $newStatus = 'partial';
        }

        $updateInst = db_query("
            UPDATE company_subscription_installment
            SET
                paid_amount = $newPaid,
                paid_date = '$payDateEsc',
                payment_method = '$payMethodEsc',
                transaction_no = '$txnNoEsc',
                status = '$newStatus',
                remarks = '$remarksEsc',
                updated_by = $userId,
                updated_at = NOW()
            WHERE id = $currentInstId
        ");

        if (!$updateInst) {
            echo json_encode([
                'status' => false,
                'message' => 'Failed to update installment #' .
                    (int)$pendingInst['installment_no'] . '.'
            ]);
            exit;
        }

        $remainingPayment -= $allocateAmount;
        $updatedInstallments[] = [
            'installment_no' => (int)$pendingInst['installment_no'],
            'allocated' => $allocateAmount,
            'paid_amount' => $newPaid,
            'status' => $newStatus
        ];
    }

    $allInsts = db_rows("
        SELECT amount, paid_amount
        FROM company_subscription_installment
        WHERE company_subscription_plan_id = $cspId
    ");
    $totAmt  = 0;
    $totPaid = 0;
    foreach ($allInsts as $ai) {
        $totAmt += (float)($ai['amount'] ?? 0);
        $totPaid += (float)($ai['paid_amount'] ?? 0);
    }

    $pending = max(0, $totAmt - $totPaid);

    if ($totPaid >= $totAmt && $totAmt > 0) {
        $masterStatus = 'paid';
    } elseif ($totPaid > 0) {
        $masterStatus = 'partial';
    } else {
        $masterStatus = 'pending';
    }

    $updateMaster = db_query("
        UPDATE company_subscription_payment
        SET
            paid_amount = $totPaid,
            pending_amount = $pending,
            payment_status = '$masterStatus',
            updated_by = $userId,
            updated_at = NOW()
        WHERE company_subscription_plan_id = $cspId
    ");

    if (!$updateMaster) {
        echo json_encode([
            'status' => false,
            'message' => 'Installment updated but failed to update payment summary.'
        ]);
        exit;
    }

    echo json_encode([
        'status' => true,
        'message' => 'Payment recorded successfully!',
        'total_amount' => $totAmt,
        'paid_amount' => $totPaid,
        'pending_amount' => $pending,
        'payment_status' => $masterStatus,
        'updated_installments' => $updatedInstallments
    ]);
    exit;
}

if ($action === 'get_history_rows') {
    $companyId = (int)($_GET['company_id'] ?? 0);
    if ($companyId <= 0) {
        echo json_encode(['status' => false, 'data' => []]);
        exit;
    }

    $historySql = "SELECT csp.*, c.name AS company_name, p.name AS current_plan_name, p.days AS plan_days, p.price AS current_plan_price
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
            $planPrice = isset($extra['price']) ? (float)$extra['price'] : (float)($row['current_plan_price'] ?? 0);

            $planFromFormatted = !empty($row['plan_from']) ? date('d-m-Y', strtotime($row['plan_from'])) : '-';
            $planToFormatted = !empty($row['plan_to']) ? date('d-m-Y', strtotime($row['plan_to'])) : '-';
            $planExpiryFormatted = !empty($row['plan_expiry_date']) ? date('d-m-Y', strtotime($row['plan_expiry_date'])) : '-';

            $status = strtolower($row['subscription_status'] ?? 'expired');
            $isActive = ($status === 'active');
            $statusBadge = $isActive 
                ? '<span class="badge bg-success-subtle text-success px-2 py-1 fs-12">Active</span>' 
                : '<span class="badge bg-danger-subtle text-danger px-2 py-1 fs-12">Expired</span>';

            // Fetch payment summary for this subscription
            $cspId = (int)$row['id'];
            $paymentRow = db_row("SELECT * FROM company_subscription_payment WHERE company_subscription_plan_id = $cspId LIMIT 1");
            $installments = db_rows("SELECT * FROM company_subscription_installment WHERE company_subscription_plan_id = $cspId ORDER BY installment_no ASC");

            $paySummaryHtml = '';
            if (!empty($paymentRow)) {
                $payTypeBadge = ($paymentRow['payment_type'] === 'installment') 
                    ? '<span class="badge bg-purple-subtle text-primary border border-purple-subtle fs-11">EMI</span>' 
                    : '<span class="badge bg-info-subtle text-info border border-info-subtle fs-11">Full</span>';
                
                $pStatColor = ($paymentRow['payment_status'] === 'paid') ? 'success' : (($paymentRow['payment_status'] === 'partial') ? 'warning' : 'danger');
                $payStatusBadge = '<span class="badge bg-' . $pStatColor . '-subtle text-' . $pStatColor . ' fs-11">' . ucfirst($paymentRow['payment_status']) . '</span>';

                $paySummaryHtml = '<div class="d-flex flex-column gap-1">' .
                    '<div class="d-flex align-items-center gap-1">' . $payTypeBadge . ' ' . $payStatusBadge . '</div>' .
                    '<div class="fs-12"><strong>Paid:</strong> ₹' . number_format((float)$paymentRow['paid_amount'], 2) . ' / ₹' . number_format((float)$paymentRow['total_amount'], 2) . '</div>' .
                    ((float)$paymentRow['pending_amount'] > 0 ? '<div class="fs-11 text-danger fw-semibold">Pending: ₹' . number_format((float)$paymentRow['pending_amount'], 2) . '</div>' : '') .
                    '</div>';
            } else {
                $paySummaryHtml = '<div class="text-muted fs-12">Total: ₹' . number_format($planPrice, 2) . '</div>';
            }

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
                            <i data-lucide="calendar-plus" class="fs-13 align-middle me-1"></i> Add days
                        </button>
                        <button type="button" class="btn btn-success btn-sm px-2 py-1 btn-upgrade-plan" 
                            data-id="' . (int)$row['id'] . '" 
                            data-company-id="' . (int)$row['company_id'] . '" 
                            data-plan-id="' . (int)$row['plan_id'] . '" 
                            data-company-name="' . htmlspecialchars($row['company_name'] ?? '', ENT_QUOTES) . '" 
                            data-plan-name="' . htmlspecialchars($planTitle, ENT_QUOTES) . '" 
                            data-plan-days="' . $planDays . '">
                            <i data-lucide="arrow-up-circle" class="fs-13 align-middle me-1"></i> Upgrade
                        </button>
                    </div>';
            }

            // Installment schedule accordion/toggle button if installments exist
            $instToggleBtn = '';
            // if (!empty($installments)) {
            //     $instToggleBtn = '<button type="button" class="btn btn-sm btn-outline-secondary px-2 py-0 fs-11 mt-1 btn-toggle-inst" data-target="#instRow' . $cspId . '">
            //         <i data-lucide="calendar" class="fs-11 align-middle me-1"></i> View ' . count($installments) . ' Installments
            //     </button>';
            // }

            $html .= '<tr class="border-top">
                <td class="fw-semibold">' . htmlspecialchars($row['company_name'] ?? '') . '</td>
                <td>
                    <span class="fw-semibold text-dark">' . htmlspecialchars($planTitle) . '</span>
                    <div class="text-muted fs-11">' . $planDays . ' Days</div>
                </td>
                <td class="fs-12">' . $planFromFormatted . ' <span class="text-muted">to</span> ' . $planToFormatted . '</td>
                <td class="fw-semibold fs-12">' . $planExpiryFormatted . '</td>
                <td>' . $paySummaryHtml . $instToggleBtn . '</td>
                <td>' . $statusBadge . '</td>
                <td class="text-center">' . $actionBtns . '</td>
            </tr>';

            // Expandable Row for Installment Schedule Table
            if (!empty($installments)) {
                $html .= '<tr id="instRow' . $cspId . '" class="bg-light-subtle d-none inst-detail-row">
                    <td colspan="7" class="p-3">
                        <div class="card border mb-0">
                            <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between">
                                <h6 class="fs-13 fw-bold mb-0 text-primary d-flex align-items-center gap-1">
                                    <i data-lucide="credit-card" class="fs-14"></i> Installment Payment Schedule
                                </h6>
                                <span class="badge bg-primary-subtle text-primary fs-11">' . count($installments) . ' Installments</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 fs-12">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:50px;">#</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Paid Date</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th class="text-center" style="width:110px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

                foreach ($installments as $inst) {
                    $dueFmt = !empty($inst['due_date']) ? date('d-m-Y', strtotime($inst['due_date'])) : '-';
                    $paidDateFmt = !empty($inst['paid_date']) ? date('d-m-Y', strtotime($inst['paid_date'])) : '-';

                    $iStatus = strtolower($inst['status'] ?? 'pending');
                    $isInstPaid = ($iStatus === 'paid');
                    $isInstPartial = ($iStatus === 'partial');

                    $iBadgeColor = $isInstPaid ? 'success' : ($isInstPartial ? 'warning' : 'secondary');
                    $iBadge = '<span class="badge bg-' . $iBadgeColor . '-subtle text-' . $iBadgeColor . ' fs-11">' . ucfirst($iStatus) . '</span>';

                    $payBtn = '-';
                    if (!$isInstPaid) {
                        $remAmount = (float)$inst['amount'] - (float)$inst['paid_amount'];
                        $payBtn = '<button type="button" class="btn btn-xs btn-success py-1 px-2 fs-11 btn-pay-inst"
                            data-id="' . (int)$inst['id'] . '"
                            data-company-id="' . (int)$row['company_id'] . '"
                            data-company-name="' . htmlspecialchars($row['company_name'] ?? '', ENT_QUOTES) . '"
                            data-inst-no="' . (int)$inst['installment_no'] . '"
                            data-amount="' . $remAmount . '"
                            data-due-date="' . $dueFmt . '">
                            <i data-lucide="wallet" class="fs-11 align-middle me-1"></i> Pay Now
                        </button>';
                    } else {
                        $payBtn = '<span class="text-success fs-11"><i data-lucide="check-circle" class="fs-12 align-middle"></i> Paid</span>';
                    }

                    $html .= '<tr>
                        <td class="text-center fw-bold">' . (int)$inst['installment_no'] . '</td>
                        <td>' . $dueFmt . '</td>
                        <td class="fw-semibold">₹' . number_format((float)$inst['amount'], 2) . '</td>
                        <td class="text-success fw-semibold">₹' . number_format((float)$inst['paid_amount'], 2) . '</td>
                        <td>' . $paidDateFmt . '</td>
                        <td>' . htmlspecialchars($inst['payment_method'] ?: '-') . '</td>
                        <td>' . $iBadge . '</td>
                        <td class="text-center">' . $payBtn . '</td>
                    </tr>';
                }

                $html .= '              </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>';
            }
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

if ($action === 'get_payment_details') {
    $companyId = (int)($_GET['company_id'] ?? 0);
    if ($companyId <= 0) {
        echo json_encode([
            'status' => false,
            'html' => '',
            'message' => 'Invalid company.'
        ]);
        exit;
    }

    // Get latest active subscription
    $subscription = db_row("
        SELECT 
            csp.*,
            c.name AS company_name
        FROM company_subscription_plan csp
        LEFT JOIN company c 
            ON c.id = csp.company_id
        WHERE csp.company_id = $companyId
          AND csp.subscription_status = 'active'
        ORDER BY csp.id DESC
        LIMIT 1
    ");

    if (empty($subscription)) {
        echo json_encode([
            'status' => false,
            'html' => '<tr><td colspan="9" class="text-center py-4 text-muted">No active subscription found.</td></tr>',
            'message' => 'No active subscription found.'
        ]);
        exit;
    }

    $subscriptionId = (int)$subscription['id'];

    $planDetails = !empty($subscription['extra_detail']) ? json_decode($subscription['extra_detail'], true) : [];
    $planPrice = isset($planDetails['price']) ? (float)$planDetails['price'] : 0;
    if ($planPrice <= 0) {
        $planRow = db_row("SELECT price FROM plan WHERE id = " . (int)$subscription['plan_id'] . " LIMIT 1");
        $planPrice = (float)($planRow['price'] ?? 0);
    }
    $paymentRow = db_row("SELECT * FROM company_subscription_payment WHERE company_subscription_plan_id = $subscriptionId LIMIT 1");

    $installments = db_rows("
        SELECT *
        FROM company_subscription_installment
        WHERE company_subscription_plan_id = $subscriptionId
        ORDER BY installment_no ASC
    ");

    if (empty($installments)) {
        $paymentSetupRequired = empty($paymentRow) && $planPrice > 0;
        $emptyScheduleMessage = empty($paymentRow)
            ? ($paymentSetupRequired ? 'No payment has been recorded. Choose a payment type above to add payment details.' : 'This subscription has no payable plan amount.')
            : 'No installment payment schedule found.';
        echo json_encode([
            'status' => true,
            'html' => '<tr><td colspan="9" class="text-center py-4 text-muted">' . $emptyScheduleMessage . '</td></tr>',
            'company_name' => $subscription['company_name'] ?? '',
            'payment_setup_required' => $paymentSetupRequired,
            'plan_price' => $planPrice,
            'payment_type' => $paymentRow['payment_type'] ?? '',
            'total_amount' => (float)($paymentRow['total_amount'] ?? $planPrice),
            'paid_amount' => (float)($paymentRow['paid_amount'] ?? 0),
            'pending_amount' => (float)($paymentRow['pending_amount'] ?? $planPrice)
        ]);
        exit;
    }

    $html = '';

    $totalAmount = 0;
    $totalPaid = 0;
    $totalPending = 0;

    foreach ($installments as $inst) {
        $amount = (float)($inst['amount'] ?? 0);
        $paid   = (float)($inst['paid_amount'] ?? 0);
        $totalAmount += $amount;
        $totalPaid   += $paid;
        $totalPending += max(0, $amount - $paid);
    }
    $today = date('Y-m-d');
    foreach ($installments as $inst) {
        $installmentId = (int)$inst['id'];
        $installmentNo = (int)($inst['installment_no'] ?? 0);
        $amount = (float)($inst['amount'] ?? 0);
        $paidAmount = (float)($inst['paid_amount'] ?? 0);
        $pendingAmount = max(0, $amount - $paidAmount);
        $dueDate = $inst['due_date'] ?? '';
        $paidDate = $inst['paid_date'] ?? '';
        $status = strtolower(trim($inst['status'] ?? 'pending'));

        $dueFmt = !empty($dueDate)
            ? date('d-m-Y', strtotime($dueDate))
            : '-';

        $paidDateFmt = !empty($paidDate)
            ? date('d-m-Y', strtotime($paidDate))
            : '-';

        if ($status === 'paid' || $pendingAmount <= 0) {
            $displayStatus = 'Paid';
            $badgeColor = 'success';
            $isPaid = true;
        } elseif (!empty($dueDate) && $dueDate < $today) {
            $displayStatus = 'Overdue';
            $badgeColor = 'danger';
            $isPaid = false;
        } elseif ($status === 'partial') {
            $displayStatus = 'Partial';
            $badgeColor = 'warning';
            $isPaid = false;
        } else {
            $displayStatus = 'Pending';
            $badgeColor = 'secondary';
            $isPaid = false;
        }

        $statusBadge = '
            <span class="badge bg-' . $badgeColor . '-subtle 
                         text-' . $badgeColor . ' fs-11">
                ' . $displayStatus . '
            </span>
        ';

        if ($isPaid) {

            $actionBtn = '
                <span class="text-success fs-11 fw-semibold">
                    <i data-lucide="check-circle" 
                       class="fs-12 align-middle me-1"></i>
                    Paid
                </span>
            ';

        } else {

            $actionBtn = '
                <button type="button"
                    class="btn btn-success btn-sm py-1 px-2 fs-11 btn-pay-inst"

                    data-id="' . $installmentId . '"

                    data-company-id="' . $companyId . '"

                    data-company-name="' .
                        htmlspecialchars(
                            $subscription['company_name'] ?? '',
                            ENT_QUOTES
                        ) . '"

                    data-inst-no="' . $installmentNo . '"

                    data-amount="' . $pendingAmount . '"

                    data-due-date="' . $dueFmt . '">

                    <i data-lucide="wallet" 
                       class="fs-11 align-middle me-1"></i>

                    Pay Now

                </button>
            ';
        }
        
        $html .= '
            <tr>
                <td class="text-center fw-bold">
                    ' . $installmentNo . '
                </td>
                <td>
                    ' . $dueFmt . '
                </td>
                <td class="fw-semibold">
                    ₹' . number_format($amount, 2) . '
                </td>
                <td class="text-success fw-semibold">
                    ₹' . number_format($paidAmount, 2) . '
                </td>
                <td class="text-danger fw-semibold">
                    ₹' . number_format($pendingAmount, 2) . '
                </td>
                <td>
                    ' . $paidDateFmt . '
                </td>
                <td>
                    ' . htmlspecialchars(
                        $inst['payment_method'] ?? '-'
                    ) . '
                </td>
                <td>
                    ' . $statusBadge . '
                </td>
                <td class="text-center">
                    ' . $actionBtn . '
                </td>
            </tr>
        ';
    }

    echo json_encode([
        'status' => true,
        'html' => $html,
        'company_name' => $subscription['company_name'] ?? '',
        'payment_setup_required' => false,
        'payment_type' => $paymentRow['payment_type'] ?? '',
        'total_amount' => $totalAmount,
        'paid_amount' => $totalPaid,
        'pending_amount' => $totalPending
    ]);
    exit;
}

if ($action === 'payment-history') {
    $companyId = trim($_POST['company_id'] ?? '');

    if (empty($companyId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid company.'
        ]);
        exit;
    }
    $companyId = (int) decrypt_id($companyId);

    if ($companyId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid company.'
        ]);
        exit;
    }

    $history = db_rows("
        SELECT
            id,
            company_id,
            installment_no,
            due_date,
            amount,
            paid_amount,
            paid_date,
            payment_method,
            status
        FROM company_subscription_installment
        WHERE company_id = {$companyId}
        ORDER BY installment_no ASC, due_date ASC
    ");

    $totalAmount = 0;
    $paidAmount = 0;

    foreach ($history as &$row) {

        $totalAmount += (float)($row['amount'] ?? 0);
        $paidAmount  += (float)($row['paid_amount'] ?? 0);

        // Due Date
        if (!empty($row['due_date']) && $row['due_date'] !== '0000-00-00') {
            $row['due_date'] = formatDate($row['due_date'], 'd-m-Y');
        } else {
            $row['due_date'] = '-';
        }

        // Paid Date
        if (!empty($row['paid_date']) && $row['paid_date'] !== '0000-00-00') {
            $row['paid_date'] = formatDate($row['paid_date'], 'd-m-Y');
        } else {
            $row['paid_date'] = '-';
        }
    }

    unset($row);

    $pendingAmount = max(0, $totalAmount - $paidAmount);

    echo json_encode([
        'success' => true,

        'summary' => [
            'total_amount'   => $totalAmount,
            'paid_amount'    => $paidAmount,
            'pending_amount' => $pendingAmount
        ],

        'history' => $history
    ]);

    exit;
}

echo json_encode(['status' => false, 'message' => 'Invalid action.']);
exit;
