<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/conn/db.php';

$currentUserType = $_SESSION['user_type'] ?? '';

// Access check: Only superadmin can access index.php
if ($currentUserType === 'user') {
    header('Location: ' . SITE_URL . 'user-dashboard');
    exit;
} elseif ($currentUserType !== 'superadmin') {
    header('Location: ' . SITE_URL . 'dashboard');
    exit;
}

include 'include/header.php';

if ($isSuperadmin) {
    $recentInstallments = db_rows("
      SELECT 
          cpi.*,
          c.name as company_name,
          c.plan_id,
          p.name AS plan_name
      FROM company_subscription_payment cpi
      LEFT JOIN company c 
          ON c.id = cpi.company_id
      LEFT JOIN company_subscription_plan csp 
          ON csp.id = cpi.company_subscription_plan_id
      LEFT JOIN plan p 
          ON p.id = csp.plan_id
      ORDER BY cpi.id DESC
      LIMIT 10
  ");
    $upcomingInstallments = db_rows("
        SELECT
            i.id,
            i.company_id,
            i.installment_no,
            i.amount,
            i.paid_amount,
            i.due_date,
            i.status,
            c.name AS company_name,
            p.name AS plan_name,
            DATEDIFF(i.due_date, CURDATE()) AS days_until_due
        FROM company_subscription_installment i
        JOIN company c ON c.id = i.company_id
        LEFT JOIN company_subscription_plan csp
            ON csp.id = i.company_subscription_plan_id
        LEFT JOIN plan p ON p.id = csp.plan_id
        WHERE i.status IN ('pending', 'partial')
          AND i.amount > i.paid_amount
          AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY i.due_date ASC, c.name ASC, i.installment_no ASC
    ");
    $overdueInstallments = db_rows("
        SELECT
            i.id,
            i.company_id,
            i.installment_no,
            i.amount,
            i.paid_amount,
            i.due_date,
            i.status,
            c.name AS company_name,
            p.name AS plan_name,
            DATEDIFF(CURDATE(), i.due_date) AS days_overdue
        FROM company_subscription_installment i
        JOIN company c ON c.id = i.company_id
        LEFT JOIN company_subscription_plan csp
            ON csp.id = i.company_subscription_plan_id
        LEFT JOIN plan p ON p.id = csp.plan_id
        WHERE i.status IN ('pending', 'partial', 'overdue')
          AND i.amount > i.paid_amount
          AND i.due_date < CURDATE()
        ORDER BY i.due_date ASC, c.name ASC, i.installment_no ASC
    ");
    $compStats = db_row("SELECT COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active FROM company");
    $totalCompanies = (int)($compStats['total'] ?? 0);
    $activeCompanies = (int)($compStats['active'] ?? 0);

    $planStats = db_row("SELECT COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active FROM plan");
    $totalPlans = (int)($planStats['total'] ?? 0);
    $activePlans = (int)($planStats['active'] ?? 0);

    $userStats = db_row("SELECT COUNT(*) as total FROM users");
    $totalUsers = (int)($userStats['total'] ?? 0);

    $moduleStats = db_row("SELECT COUNT(*) as total FROM module WHERE status = 1");
    $totalModules = (int)($moduleStats['total'] ?? 0);

    $revStats = db_row("SELECT SUM(p.price) as total_val FROM company c LEFT JOIN plan p ON p.id = c.plan_id WHERE c.status = 1");
    $platformRevValue = (float)($revStats['total_val'] ?? 0);

    $recentCompanies = db_rows("SELECT c.id, c.name, c.person_name, c.mobile_no, c.email, c.status, c.created_at, 
                                       p.name as plan_name, p.price as plan_price,
                                       (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count 
                                FROM company c 
                                LEFT JOIN plan p ON p.id = c.plan_id 
                                ORDER BY c.id DESC LIMIT 6");

    $planDistRows = db_rows("SELECT p.name, COUNT(c.id) as count 
                             FROM plan p 
                             LEFT JOIN company c ON c.plan_id = p.id 
                             WHERE p.status = 1 
                             GROUP BY p.id");
    $planLabels = [];
    $planCounts = [];
    foreach ($planDistRows as $pRow) {
        $planLabels[] = $pRow['name'] ?? 'Unnamed Plan';
        $planCounts[] = (int)($pRow['count'] ?? 0);
    }
    if (empty($planLabels)) {
        $planLabels = ['No Plans'];
        $planCounts = [0];
    }

    $activePlanList = db_rows("SELECT p.*, (SELECT COUNT(*) FROM company c WHERE c.plan_id = p.id) as assigned_companies 
                               FROM plan p 
                               WHERE p.status = 1 
                               ORDER BY p.id DESC LIMIT 4");

    // 6. Companies whose subscription plan expires in last/next 7 days (from company_subscription_plan)
    $expiringCompanies = db_rows("SELECT c.id, c.name, c.person_name, c.mobile_no, c.email,
                                         csp.plan_id, csp.plan_from as start_date, csp.plan_to,
                                         csp.plan_expiry_date as expiry_date,
                                         csp.subscription_status,
                                         p.name as plan_name, p.price as plan_price,
                                         DATEDIFF(csp.plan_expiry_date, CURDATE()) as days_left
                                  FROM company_subscription_plan csp
                                  JOIN company c ON c.id = csp.company_id
                                  LEFT JOIN plan p ON p.id = csp.plan_id
                                  WHERE c.status = 1 
                                    AND csp.subscription_status = 'active'
                                    AND DATEDIFF(csp.plan_expiry_date, CURDATE()) BETWEEN -7 AND 7
                                  ORDER BY days_left ASC");

    // 7. Lead Stats & Recent Leads for Superadmin Dashboard
    $leadStats = db_row("SELECT 
        COUNT(*) as total_leads,
        SUM(CASE WHEN lead_stage IN ('1', 'new') THEN 1 ELSE 0 END) as new_leads,
        SUM(CASE WHEN lead_stage IN ('2', 'contacted') THEN 1 ELSE 0 END) as contacted_leads,
        SUM(CASE WHEN lead_stage IN ('3', 'demo_scheduled') THEN 1 ELSE 0 END) as demo_leads,
        SUM(CASE WHEN lead_stage IN ('4', 'trial_active') THEN 1 ELSE 0 END) as trial_leads,
        SUM(CASE WHEN lead_stage IN ('5', 'negotiation') THEN 1 ELSE 0 END) as negotiation_leads,
        SUM(CASE WHEN lead_stage IN ('6', 'converted') THEN 1 ELSE 0 END) as converted_leads,
        SUM(CASE WHEN lead_stage IN ('7', 'lost') THEN 1 ELSE 0 END) as lost_leads,
        SUM(CASE WHEN lead_stage NOT IN ('6', 'converted', '7', 'lost') THEN 1 ELSE 0 END) as active_leads
    FROM lead WHERE status = 1");
    $totalSaasLeads = (int)($leadStats['total_leads'] ?? 0);
    $convertedSaasLeads = (int)($leadStats['converted_leads'] ?? 0);
    $activeSaasLeads = (int)($leadStats['active_leads'] ?? 0);

    $recentLeads = db_rows("SELECT l.*, p.name as plan_name 
                           FROM lead l 
                           LEFT JOIN plan p ON p.id = l.interested_plan_id 
                           WHERE l.status = 1 
                           ORDER BY l.id DESC LIMIT 8");

    // 8. Lead Follow-up Reminders (Pending reminders for Today & Overdue)
    $todayLeadReminders = db_rows("SELECT f.*, l.lead_number, l.business_name, l.contact_name, l.mobile_no 
                                   FROM lead_followups f 
                                   JOIN lead l ON l.id = f.lead_id 
                                   WHERE f.reminder_status = 'pending' 
                                     AND DATE(f.followup_date) <= CURDATE() 
                                   ORDER BY f.followup_date ASC LIMIT 5");
    $totalPendingRemindersCount = (int)(db_row("SELECT COUNT(*) as cnt FROM lead_followups WHERE reminder_status = 'pending'")['cnt'] ?? 0);
}

?>
<div class="super-hero-banner text-white p-4 p-md-4 mb-4">
  <div class="row align-items-center position-relative" style="z-index: 2;">
    <div class="col-lg-8 mb-3 mb-lg-0">
      <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-20 mb-2">
        <span class="badge rounded-pill bg-white text-dark fw-bold fs-11 px-2 py-1">SUPERADMIN</span>
        <span class="fs-12 text-white-50">Multi-Tenant Platform Control</span>
      </div>
      <h3 class="fw-bold mb-1 text-white">Welcome back Superadmin</h3>
      <p class="mb-0 text-white-50 fs-13" style="max-width: 620px;">
        Monitor tenant businesses, subscription tiers, platform licenses, and active enterprise modules with unified theme intelligence.
      </p>
    </div>
    <div class="col-lg-4 text-lg-end">
      <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
        <a href="<?= SITE_URL ?>lead/add" class="btn btn-warning text-dark fw-bold btn-sm px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1">
          <i data-lucide="user-plus" class="fs-16"></i> Add Lead
        </a>
        <a href="<?= SITE_URL ?>company/add" class="btn btn-light fw-semibold btn-sm px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1">
          <i data-lucide="plus-circle" class="fs-16 text-primary"></i> Create Company
        </a>
        <a href="<?= SITE_URL ?>plan/add" class="btn btn-outline-light btn-sm px-3 py-2 d-inline-flex align-items-center gap-1">
          <i data-lucide="shield-plus" class="fs-16"></i> New Plan
        </a>
      </div>
    </div>
  </div>
</div>

<!-- 2. Primary KPI Counter Cards (Themed & Accent-Colored) -->
<div class="row g-3 mb-4">
  <!-- Total Companies -->
  <div class="col-xl-3 col-md-6">
    <div class="stat-card-modern card-primary p-3 shadow-sm h-100">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold fs-12 text-uppercase tracking-wider">Total Companies</span>
        <div class="stat-icon-wrapper icon-bg-primary">
          <i data-lucide="building-2" class="fs-20"></i>
        </div>
      </div>
      <div class="d-flex align-items-baseline justify-content-between">
        <h2 class="fw-bold mb-0 text-dark"><?= number_format($totalCompanies) ?></h2>
        <span class="badge bg-success-subtle text-success fs-12 border border-success-subtle px-2 py-1">
          <i data-lucide="check-circle" class="fs-12 align-middle me-1"></i><?= $activeCompanies ?> Active
        </span>
      </div>
      <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between fs-12 text-muted">
        <span>Active business tenants</span>
        <a href="<?= SITE_URL ?>company" class="text-primary fw-medium text-decoration-none">Manage &rarr;</a>
      </div>
    </div>
  </div>

  <!-- Subscription Plans -->
  <div class="col-xl-3 col-md-6">
    <div class="stat-card-modern card-purple p-3 shadow-sm h-100">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold fs-12 text-uppercase tracking-wider">Subscription Plans</span>
        <div class="stat-icon-wrapper icon-bg-purple">
          <i data-lucide="package-check" class="fs-20"></i>
        </div>
      </div>
      <div class="d-flex align-items-baseline justify-content-between">
        <h2 class="fw-bold mb-0 text-dark"><?= number_format($totalPlans) ?></h2>
        <span class="badge bg-purple-subtle text-purple border border-purple-subtle fs-12 px-2 py-1" style="background: rgba(111,66,193,0.12); color:#6f42c1;">
          <?= $activePlans ?> Active Tiers
        </span>
      </div>
      <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between fs-12 text-muted">
        <span>Module access levels</span>
        <a href="<?= SITE_URL ?>plan" class="text-primary fw-medium text-decoration-none">Tiers &rarr;</a>
      </div>
    </div>
  </div>

  <!-- Leads KPI -->
  <div class="col-xl-3 col-md-6">
    <div class="stat-card-modern card-warning p-3 shadow-sm h-100">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold fs-12 text-uppercase tracking-wider">Total Leads</span>
        <div class="stat-icon-wrapper icon-bg-warning">
          <i data-lucide="user-plus" class="fs-20"></i>
        </div>
      </div>
      <div class="d-flex align-items-baseline justify-content-between">
        <h2 class="fw-bold mb-0 text-dark"><?= number_format($totalSaasLeads) ?></h2>
        <span class="badge bg-warning-subtle text-warning fs-12 border border-warning-subtle px-2 py-1">
          <?= $activeSaasLeads ?> Active Pipeline
        </span>
      </div>
      <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between fs-12 text-muted">
        <span><?= $convertedSaasLeads ?> Won / Converted</span>
        <a href="<?= SITE_URL ?>lead" class="text-primary fw-medium text-decoration-none">Leads &rarr;</a>
      </div>
    </div>
  </div>

  <!-- Follow-up Reminders KPI -->
  <div class="col-xl-3 col-md-6">
    <div class="stat-card-modern card-success p-3 shadow-sm h-100">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted fw-semibold fs-12 text-uppercase tracking-wider">Follow-up Reminders</span>
        <div class="stat-icon-wrapper icon-bg-success">
          <i data-lucide="calendar-clock" class="fs-20"></i>
        </div>
      </div>
      <div class="d-flex align-items-baseline justify-content-between">
        <h2 class="fw-bold mb-0 text-dark"><?= number_format($totalPendingRemindersCount) ?></h2>
        <span class="badge <?= (!empty($todayLeadReminders)) ? 'bg-danger text-white' : 'bg-success-subtle text-success' ?> fs-12 px-2 py-1">
          <?= count($todayLeadReminders) ?> Due Today
        </span>
      </div>
      <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between fs-12 text-muted">
        <span>Calls, Demos & Meetings</span>
        <a href="<?= SITE_URL ?>lead/followup-history" class="text-primary fw-medium text-decoration-none">Review &rarr;</a>
      </div>
    </div>
  </div>
</div>

<!-- 3. Visual Analytics Charts -->
<div class="row g-3 mb-4 align-items-stretch">
  <!-- Subscription Plan Distribution -->
  <div class="col-xl-3 col-lg-4 d-flex flex-column">
    <div class="card border-0 shadow-sm rounded-4 h-100 w-100 d-flex flex-column">
      <div class="card-header theme-card-header py-3 d-flex align-items-center justify-content-between">
        <div>
          <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
            <i data-lucide="pie-chart" class="fs-18 text-primary"></i> Plan Breakdown
          </h6>
        </div>
        <span class="badge bg-light text-muted border px-2 py-1 fs-12">Plans</span>
      </div>
      <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center flex-grow-1">
        <div id="superadmin-plan-chart" style="width: 100%; min-height: 290px;"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-9 col-lg-8 d-flex flex-column">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 w-100 d-flex flex-column">
      <div class="card-header theme-card-header py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-xs rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
            <i data-lucide="alert-triangle" class="fs-18"></i>
          </div>
          <div>
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
              Plan Expiry Alert (Last / Next 7 Days)
              <span class="badge rounded-pill bg-warning text-dark fs-11 px-2 py-1"><?= count($expiringCompanies) ?> Companies</span>
            </h6>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= SITE_URL ?>company" class="btn btn-sm btn-outline-warning text-dark px-3 fw-medium">
            Manage Renewals
          </a>
        </div>
      </div>
      <div class="card-body p-0 flex-grow-1 overflow-auto">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 superadmin-table">
            <thead>
              <tr>
                <th class="ps-3 py-3">Company / Tenant</th>
                <th>Contact Person</th>
                <th>Mobile No.</th>
                <th>Assigned Plan</th>
                <th>Start Date</th>
                <th>Expiry Date</th>
                <th class="text-center">Days Remaining</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody class="fs-13">
              <?php if (!empty($expiringCompanies)): ?>
                <?php foreach ($expiringCompanies as $ec): 
                  $encId = encrypt_id($ec['id']);
                  $daysLeft = (int)$ec['days_left'];
                ?>
                  <tr class="<?= ($daysLeft < 0) ? 'table-danger-subtle' : (($daysLeft <= 3) ? 'table-warning-subtle' : '') ?>">
                    <td class="ps-3 py-3 fw-semibold text-dark">
                      <div class="d-flex align-items-center gap-2">
                        <div class="company-avatar-box">
                          <?= strtoupper(substr($ec['name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <div>
                          <div class="text-truncate fw-bold text-dark" style="max-width: 220px;" title="<?= htmlspecialchars($ec['name'] ?? '') ?>">
                            <?= htmlspecialchars($ec['name'] ?? 'N/A') ?>
                          </div>
                          <span class="fs-11 text-muted"><?= htmlspecialchars($ec['email'] ?? '') ?></span>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($ec['person_name'] ?? '-') ?></td>
                    <td>
                      <a href="tel:<?= htmlspecialchars($ec['mobile_no'] ?? '') ?>" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                        <i data-lucide="phone" class="fs-13 text-muted"></i> <?= htmlspecialchars($ec['mobile_no'] ?? '-') ?>
                      </a>
                    </td>
                    <td>
                      <span class="badge badge-theme-subtle px-2 py-1">
                        <?= htmlspecialchars($ec['plan_name'] ?? 'No Plan') ?>
                      </span>
                    </td>
                    <td class="text-muted"><?= !empty($ec['start_date']) ? date('d M Y', strtotime($ec['start_date'])) : '-' ?></td>
                    <td class="fw-semibold text-dark"><?= !empty($ec['expiry_date']) ? date('d M Y', strtotime($ec['expiry_date'])) : '-' ?></td>
                    <td class="text-center">
                      <?php if ($daysLeft < 0): ?>
                        <span class="badge bg-danger text-white px-2 py-1">
                          <i data-lucide="x-circle" class="fs-11 align-middle me-1"></i>Expired <?= abs($daysLeft) ?> days ago
                        </span>
                      <?php elseif ($daysLeft === 0): ?>
                        <span class="badge bg-danger text-white px-2 py-1 animate__animated animate__pulse animate__infinite">
                          <i data-lucide="alert-octagon" class="fs-11 align-middle me-1"></i>Expires Today!
                        </span>
                      <?php elseif ($daysLeft <= 3): ?>
                        <span class="badge bg-warning text-dark px-2 py-1 fw-bold">
                          <i data-lucide="clock" class="fs-11 align-middle me-1"></i><?= $daysLeft ?> days left
                        </span>
                      <?php else: ?>
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                          <?= $daysLeft ?> days left
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="<?= SITE_URL ?>company?open_subscription=<?= (int)$ec['id'] ?>&company_name=<?= urlencode($ec['name'] ?? '') ?>" class="btn btn-sm btn-primary py-1 px-2 d-inline-flex align-items-center gap-1" title="Renew Plan / Subscription History">
                        <i data-lucide="refresh-cw" class="fs-13"></i> Renew Plan
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">
                    <i data-lucide="check-circle" class="fs-20 text-success align-middle me-1"></i> No companies with plan expiring in the last or next 7 days.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4 align-items-stretch">
  <div class="col-xl-6 d-flex flex-column">
    <div class="card border-0 shadow-sm rounded-4 h-100">
      <div class="card-header theme-card-header py-3 px-4 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
          <i data-lucide="calendar-clock" class="fs-18 text-primary"></i>
          Next Installments (Next 30 Days)
        </h6>
        <span class="badge rounded-pill bg-primary-subtle text-primary"><?= count($upcomingInstallments) ?> Due</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 superadmin-table">
            <thead>
              <tr>
                <th class="ps-3 py-3">Company / Plan</th>
                <th>Installment</th>
                <th>Due Date</th>
                <th>Pending</th>
                <th class="text-end pe-3">History</th>
              </tr>
            </thead>
            <tbody class="fs-13">
              <?php if (!empty($upcomingInstallments)): ?>
                <?php foreach ($upcomingInstallments as $installment):
                  $daysUntilDue = (int)$installment['days_until_due'];
                  $pendingAmount = max(0, (float)$installment['amount'] - (float)$installment['paid_amount']);
                ?>
                  <tr>
                    <td class="ps-3 py-3">
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($installment['company_name'] ?? '-') ?></div>
                      <small class="text-muted"><?= htmlspecialchars($installment['plan_name'] ?? 'Custom') ?></small>
                    </td>
                    <td>#<?= (int)$installment['installment_no'] ?></td>
                    <td>
                      <div class="fw-medium"><?= date('d M Y', strtotime($installment['due_date'])) ?></div>
                      <small class="text-primary">
                        <?= $daysUntilDue === 0 ? 'Due today' : $daysUntilDue . ' day' . ($daysUntilDue === 1 ? '' : 's') . ' left' ?>
                      </small>
                    </td>
                    <td class="fw-semibold text-danger">₹<?= number_format($pendingAmount, 2) ?></td>
                    <td class="text-end pe-3">
                      <button type="button"
                        class="btn btn-xs btn-outline-primary py-1 px-2 view-payment-history"
                        data-company-id="<?= encrypt_id($installment['company_id']) ?>"
                        data-company-name="<?= htmlspecialchars($installment['company_name'] ?? '-', ENT_QUOTES) ?>"
                        title="View Payment History">
                        <i data-lucide="eye" class="fs-13"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No installments are due in the next 30 days.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-6 d-flex flex-column">
    <div class="card border-0 shadow-sm rounded-4 h-100">
      <div class="card-header theme-card-header py-3 px-4 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
          <i data-lucide="triangle-alert" class="fs-18 text-danger"></i>
          Overdue Installments
        </h6>
        <span class="badge rounded-pill bg-danger-subtle text-danger"><?= count($overdueInstallments) ?> Overdue</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 superadmin-table">
            <thead>
              <tr>
                <th class="ps-3 py-3">Company / Plan</th>
                <th>Installment</th>
                <th>Due Date</th>
                <th>Pending</th>
                <th class="text-end pe-3">History</th>
              </tr>
            </thead>
            <tbody class="fs-13">
              <?php if (!empty($overdueInstallments)): ?>
                <?php foreach ($overdueInstallments as $installment):
                  $daysOverdue = (int)$installment['days_overdue'];
                  $pendingAmount = max(0, (float)$installment['amount'] - (float)$installment['paid_amount']);
                ?>
                  <tr class="table-danger-subtle">
                    <td class="ps-3 py-3">
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($installment['company_name'] ?? '-') ?></div>
                      <small class="text-muted"><?= htmlspecialchars($installment['plan_name'] ?? 'Custom') ?></small>
                    </td>
                    <td>#<?= (int)$installment['installment_no'] ?></td>
                    <td>
                      <div class="fw-medium"><?= date('d M Y', strtotime($installment['due_date'])) ?></div>
                      <small class="text-danger"><?= $daysOverdue ?> day<?= $daysOverdue === 1 ? '' : 's' ?> overdue</small>
                    </td>
                    <td class="fw-semibold text-danger">₹<?= number_format($pendingAmount, 2) ?></td>
                    <td class="text-end pe-3">
                      <button type="button"
                        class="btn btn-xs btn-outline-danger py-1 px-2 view-payment-history"
                        data-company-id="<?= encrypt_id($installment['company_id']) ?>"
                        data-company-name="<?= htmlspecialchars($installment['company_name'] ?? '-', ENT_QUOTES) ?>"
                        title="View Payment History">
                        <i data-lucide="eye" class="fs-13"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No overdue installments.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4 align-items-stretch">
  <!-- Left Side: Recent SaaS Leads & Pipeline (Col 8) -->
  <div class="col-xl-8 col-lg-7 d-flex flex-column">
    <div class="card border-0 shadow-sm rounded-4 h-100 w-100 d-flex flex-column">
      <div class="card-header theme-card-header py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-xs rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
            <i data-lucide="user-check" class="fs-18"></i>
          </div>
          <div>
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
              Recent SaaS Leads & Pipeline
              <span class="badge rounded-pill bg-primary text-white fs-11 px-2 py-1"><?= $totalSaasLeads ?> Leads</span>
              <span class="badge rounded-pill bg-success text-white fs-11 px-2 py-1"><?= $convertedSaasLeads ?> Converted</span>
            </h6>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= SITE_URL ?>lead/add" class="btn btn-sm btn-primary px-3 fw-medium d-inline-flex align-items-center gap-1">
            <i data-lucide="plus" class="fs-14"></i> Add Lead
          </a>
          <a href="<?= SITE_URL ?>lead" class="btn btn-sm btn-light border px-3 fw-medium text-dark">
            View All &rarr;
          </a>
        </div>
      </div>
      <div class="card-body p-0 flex-grow-1">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 superadmin-table">
            <thead>
              <tr>
                <th class="ps-3 py-3">Lead No.</th>
                <th>Business Name</th>
                <th>Contact</th>
                <th>Interested Plan</th>
                <th>Stage</th>
                <th class="text-end pe-3">Action</th>
              </tr>
            </thead>
            <tbody class="fs-13">
              <?php if (!empty($recentLeads)): ?>
                <?php foreach ($recentLeads as $rl): 
                  $encLeadId = encrypt_id($rl['id']);
                  $leadStageSlug = get_lead_stage_slug($rl['lead_stage'] ?? 'new');
                  $leadStageText = get_lead_stage_label($rl['lead_stage'] ?? 'new');
                  $stageBadgeClass = 'bg-secondary-subtle text-secondary';
                  if ($leadStageSlug === 'new') $stageBadgeClass = 'bg-info-subtle text-info border border-info-subtle';
                  elseif ($leadStageSlug === 'contacted') $stageBadgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                  elseif ($leadStageSlug === 'demo_scheduled') $stageBadgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                  elseif ($leadStageSlug === 'trial_active') $stageBadgeClass = 'bg-purple-subtle text-purple border border-purple-subtle';
                  elseif ($leadStageSlug === 'negotiation') $stageBadgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                  elseif ($leadStageSlug === 'converted') $stageBadgeClass = 'bg-success-subtle text-success border border-success-subtle';
                  elseif ($leadStageSlug === 'lost') $stageBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                ?>
                  <tr>
                    <td class="ps-3 py-3 fw-bold text-primary">
                      <?= htmlspecialchars($rl['lead_number'] ?? ('OI-' . $rl['id'])) ?>
                    </td>
                    <td class="fw-semibold text-dark">
                      <div class="text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($rl['business_name'] ?? '') ?>">
                        <?= htmlspecialchars($rl['business_name'] ?? '-') ?>
                      </div>
                      <small class="text-muted"><?= htmlspecialchars($rl['contact_name'] ?? '') ?></small>
                    </td>
                    <td>
                      <a href="tel:<?= htmlspecialchars($rl['mobile_no'] ?? '') ?>" class="text-decoration-none fw-medium text-dark text-nowrap">
                        <i data-lucide="phone" class="fs-12 text-muted align-middle me-1"></i><?= htmlspecialchars($rl['mobile_no'] ?? '-') ?>
                      </a>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border px-2 py-1">
                        <?= htmlspecialchars($rl['plan_name'] ?? 'Custom') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge px-2 py-1 fs-11 <?= $stageBadgeClass ?>">
                        <?= htmlspecialchars($leadStageText) ?>
                      </span>
                    </td>
                    <td class="text-end pe-3">
                      <div class="d-inline-flex gap-1">
                        <a href="<?= SITE_URL ?>lead" class="btn btn-xs btn-outline-primary py-1 px-2 d-inline-flex align-items-center gap-1" title="View & Follow up">
                          <i data-lucide="calendar-clock" class="fs-13"></i>
                        </a>
                        <a href="<?= SITE_URL ?>lead/edit/<?= $encLeadId ?>" class="btn btn-xs btn-light border py-1 px-2 d-inline-flex align-items-center" title="Edit Lead">
                          <i data-lucide="edit" class="fs-13"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">
                    No leads added yet. <a href="<?= SITE_URL ?>lead/add" class="fw-medium text-primary">Add your first lead &rarr;</a>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Side Column: Active Plans & Follow-up Reminders & Quick Administration (Col 4) -->
  <div class="col-xl-4 col-lg-5 d-flex flex-column gap-3">
    <!-- 1. Lead Follow-up & Reminder Alert Hub -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header theme-card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
          <i data-lucide="bell-ring" class="fs-18 text-warning"></i> Lead Reminders
          <?php if (!empty($todayLeadReminders)): ?>
            <span class="badge rounded-pill bg-danger text-white fs-10 px-2"><?= count($todayLeadReminders) ?> Due</span>
          <?php endif; ?>
        </h6>
        <a href="<?= SITE_URL ?>lead/followup-history" class="fs-12 text-primary text-decoration-none fw-medium">All Follow-ups &rarr;</a>
      </div>
      <div class="card-body p-3">
        <?php if (!empty($todayLeadReminders)): ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($todayLeadReminders as $rem): 
              $isOverdue = (strtotime($rem['followup_date']) < time());
              $remLeadEncId = encrypt_id($rem['lead_id']);
            ?>
              <div class="p-2 rounded-3 border <?= $isOverdue ? 'border-danger-subtle bg-danger-subtle' : 'border-warning-subtle bg-warning-subtle' ?>">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-warning text-dark' ?> fs-10 text-uppercase">
                    <?= htmlspecialchars(get_lead_followup_type_label($rem['followup_type'])) ?>
                  </span>
                  <span class="fs-11 fw-bold <?= $isOverdue ? 'text-danger' : 'text-dark' ?>">
                    <?= date('d M, h:i A', strtotime($rem['followup_date'])) ?>
                  </span>
                </div>
                <div class="fw-bold fs-12 text-dark text-truncate">
                  <?= htmlspecialchars($rem['business_name']) ?>
                </div>
                <div class="fs-11 text-muted text-truncate mb-1">
                  <?= htmlspecialchars($rem['remarks']) ?>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-1 border-top border-secondary-subtle">
                  <a href="tel:<?= htmlspecialchars($rem['mobile_no']) ?>" class="fs-11 text-decoration-none fw-semibold text-primary">
                    <i data-lucide="phone" class="fs-11 align-middle"></i> <?= htmlspecialchars($rem['mobile_no']) ?>
                  </a>
                  <a href="<?= SITE_URL ?>lead/followup-history/<?= $remLeadEncId ?>" class="btn btn-xs btn-light py-0 px-2 fs-11">Open &rarr;</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-2 text-muted fs-12">
            <i data-lucide="check-circle-2" class="fs-20 text-success mb-1"></i>
            <div>No urgent lead follow-up reminders.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 2. Active Subscription Tiers Widget -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header theme-card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
          <i data-lucide="shield-check" class="fs-18 text-primary"></i> Active Plans
        </h6>
        <a href="<?= SITE_URL ?>plan" class="fs-12 text-primary text-decoration-none fw-medium">All &rarr;</a>
      </div>
      <div class="card-body p-3">
        <?php if (!empty($activePlanList)): ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($activePlanList as $ap): ?>
              <div class="p-2 rounded-3 border bg-light-subtle d-flex align-items-center justify-content-between">
                <div>
                  <div class="fw-bold fs-13 text-dark"><?= htmlspecialchars($ap['name']) ?></div>
                  <small class="text-muted fs-11">
                    <?= (int)$ap['assigned_companies'] ?> Companies &bull; Max <?= (int)$ap['max_team_user'] ?> users
                  </small>
                </div>
                <span class="badge bg-primary text-white fw-semibold fs-12 px-2 py-1">
                  ₹<?= number_format((float)$ap['price']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted fs-12 mb-0">No active plans configured.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4 align-items-stretch">
  <div class="col-xl-4 col-lg-5 d-flex flex-column gap-3">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header theme-card-header py-3">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
          <i data-lucide="sliders" class="fs-18 text-primary"></i> Quick Administration
        </h6>
      </div>
      <div class="card-body p-3">
        <div class="d-flex flex-column gap-2">
          <a href="<?= SITE_URL ?>lead" class="quick-action-pill">
            <div class="d-flex align-items-center gap-2">
              <i data-lucide="user-plus" class="fs-16 text-primary"></i>
              <span class="fs-13 fw-medium">Platform Leads</span>
            </div>
            <i data-lucide="chevron-right" class="fs-14 text-muted"></i>
          </a>

          <a href="<?= SITE_URL ?>company" class="quick-action-pill">
            <div class="d-flex align-items-center gap-2">
              <i data-lucide="building-2" class="fs-16 text-primary"></i>
              <span class="fs-13 fw-medium">All Companies</span>
            </div>
            <i data-lucide="chevron-right" class="fs-14 text-muted"></i>
          </a>

          <a href="<?= SITE_URL ?>plan" class="quick-action-pill">
            <div class="d-flex align-items-center gap-2">
              <i data-lucide="package" class="fs-16 text-primary"></i>
              <span class="fs-13 fw-medium">Subscription Plans</span>
            </div>
            <i data-lucide="chevron-right" class="fs-14 text-muted"></i>
          </a>

          <a href="<?= SITE_URL ?>module" class="quick-action-pill">
            <div class="d-flex align-items-center gap-2">
              <i data-lucide="layers" class="fs-16 text-primary"></i>
              <span class="fs-13 fw-medium">Module Permissions</span>
            </div>
            <i data-lucide="chevron-right" class="fs-14 text-muted"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-8 col-lg-7 d-flex flex-column">
    <div class="card border-0 shadow-sm rounded-4 h-100 w-100 d-flex flex-column">
      <div class="card-header theme-card-header py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-xs rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
              style="width:34px;height:34px;">
            <i data-lucide="credit-card" class="fs-18"></i>
          </div>
          <div>
            <h6 class="mb-0 fw-bold text-dark">
              Company Installment History
            </h6>
            <small class="text-muted">
              Recent company plan payments
            </small>
          </div>
        </div>
      </div>
      <div class="card-body p-0 flex-grow-1">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 superadmin-table">
            <thead>
              <tr>
                <th class="ps-3 py-3">Company</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Payment Type</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Status</th>
                <th class="text-end pe-3">Action</th>
              </tr>
            </thead>
            <tbody class="fs-13">
            <?php if (!empty($recentInstallments)): ?>
              <?php foreach ($recentInstallments as $ri): 
                $amount = (float)($ri['total_amount'] ?? 0);
                $paid   = (float)($ri['paid_amount'] ?? 0);
                $remaining = max(0, $amount - $paid);
                $status = strtolower($ri['payment_status'] ?? 'pending');
                if ($status === 'paid') {
                    $statusBadge = 'bg-success-subtle text-success border border-success-subtle';
                    $statusText = 'Paid';
                }
                elseif ($status === 'partial') {
                    $statusBadge = 'bg-warning-subtle text-warning border border-warning-subtle';
                    $statusText = 'Partial';
                }
                else {
                    $statusBadge = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                    $statusText = 'Pending';
                }
                $encCompanyId = encrypt_id($ri['company_id']);
              ?>
              <tr>
                <td class="ps-3 py-3">
                  <div class="fw-semibold text-dark">
                    <?= htmlspecialchars($ri['company_name'] ?? '-') ?>
                  </div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border px-2 py-1">
                    <?= htmlspecialchars($ri['plan_name'] ?? 'Custom') ?>
                  </span>
                </td>
                <td class="fw-semibold text-dark">
                  ₹<?= number_format($amount, 2) ?>
                </td>
                <td>
                  <span class="badge bg-light text-dark border px-2 py-1">
                    <?= htmlspecialchars($ri['payment_type'] ?? '') ?>
                  </span>
                </td>
                <td class="text-success fw-semibold">
                  ₹<?= number_format($paid, 2) ?>
                </td>
                <td class="<?= $remaining > 0 ? 'text-danger fw-semibold' : 'text-info fw-semibold' ?>">
                  ₹<?= number_format($remaining, 2) ?>
                </td>
                <td>
                  <span class="badge px-2 py-1 fs-11 <?= $statusBadge ?>">
                    <?= htmlspecialchars($statusText) ?>
                  </span>
                </td>
                <td class="text-end pe-3">
                  <div class="d-inline-flex gap-1">
                    <button type="button"
                      class="btn btn-xs btn-outline-primary py-1 px-2 view-payment-history"
                      data-company-id="<?= $encCompanyId ?>"
                      data-company-name="<?= htmlspecialchars($ri['company_name'] ?? '-', ENT_QUOTES) ?>"
                      title="View Payment History">
                      <i data-lucide="eye" class="fs-13"></i>
                  </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                  No installment payment history found.
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4">

            <div class="modal-header theme-card-header px-4 py-3">

                <div>
                    <h5 class="modal-title fw-bold text-dark mb-1">
                        Company Payment History
                    </h5>

                    <small class="text-muted" id="paymentCompanyName">
                        -
                    </small>
                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                </button>

            </div>


            <div class="modal-body p-0">

                <div id="paymentHistoryLoader"
                     class="text-center py-5">

                    <div class="spinner-border text-primary"
                         role="status">
                    </div>

                    <div class="mt-2 text-muted">
                        Loading payment history...
                    </div>

                </div>


                <div id="paymentHistoryContent"
                     class="d-none">

                    <!-- Summary -->
                    <div class="row g-3 p-4 pb-3">

                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-3">
                                <div class="card-body">
                                    <small class="text-muted">
                                        Total Amount
                                    </small>

                                    <h5 class="mb-0 fw-bold"
                                        id="historyTotalAmount">
                                        ₹0.00
                                    </h5>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-4">
                            <div class="card border-0 bg-success-subtle rounded-3">
                                <div class="card-body">
                                    <small class="text-success">
                                        Total Paid
                                    </small>

                                    <h5 class="mb-0 fw-bold text-success"
                                        id="historyPaidAmount">
                                        ₹0.00
                                    </h5>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-4">
                            <div class="card border-0 bg-danger-subtle rounded-3">
                                <div class="card-body">
                                    <small class="text-danger">
                                        Total Pending
                                    </small>

                                    <h5 class="mb-0 fw-bold text-danger"
                                        id="historyPendingAmount">
                                        ₹0.00
                                    </h5>
                                </div>
                            </div>
                        </div>

                    </div>


                    <!-- History Table -->
                    <div class="table-responsive px-4 pb-4">

                        <table class="table table-hover align-middle mb-0">

                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Installment</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Payment Date</th>
                                    <th>Payment Type</th>
                                    <th>Remaining</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody id="paymentHistoryTableBody">
                            </tbody>

                        </table>

                    </div>

                </div>


                <div id="paymentHistoryError"
                     class="d-none text-center text-danger py-5">

                    Unable to load payment history.

                </div>

            </div>

        </div>

    </div>

</div>

<div class="modal fade" id="dashboardPayInstallmentModal" tabindex="-1" aria-labelledby="dashboardPayInstallmentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fs-16 fw-semibold mb-0" id="dashboardPayInstallmentModalLabel">Record Installment Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="dashboardPayInstallmentForm">
        <input type="hidden" name="action" value="pay_installment">
        <input type="hidden" name="installment_id" id="dashboardPayInstallmentId" value="">
        <input type="hidden" name="company_id" id="dashboardPayCompanyId" value="">
        <div class="modal-body p-4">
          <div class="alert alert-light border rounded-3 p-3 mb-3 bg-light-subtle">
            <div class="row g-2 fs-13">
              <div class="col-6"><strong>Company:</strong> <span id="dashboardPayCompanyName">-</span></div>
              <div class="col-6"><strong>Installment:</strong> <span id="dashboardPayInstallmentNo">-</span></div>
              <div class="col-6"><strong>Due Date:</strong> <span id="dashboardPayDueDate">-</span></div>
              <div class="col-6"><strong>Pending Amount:</strong> <span class="badge bg-primary fs-12" id="dashboardPayPendingAmount">₹0.00</span></div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fs-12 fw-semibold">Paying Amount (₹)</label>
              <input type="number" step="0.01" name="pay_amount" id="dashboardPayAmount" class="form-control" min="0.01" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fs-12 fw-semibold">Payment Date</label>
              <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fs-12 fw-semibold">Payment Method</label>
              <select name="payment_method" class="form-select" required>
                <option value="Cash">Cash</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fs-12 fw-semibold">Transaction / Cheque No.</label>
              <input type="text" name="transaction_no" class="form-control" placeholder="e.g. UPI Ref / Cheque No">
            </div>
            <div class="col-12">
              <label class="form-label fs-12 fw-semibold">Remarks (Optional)</label>
              <textarea name="remarks" class="form-control" rows="2" placeholder="Payment received details..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top py-2 px-3">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4" id="dashboardPaySubmit">
            <span id="dashboardPaySubmitText">Confirm Payment</span>
            <span id="dashboardPaySubmitLoader" class="spinner-border spinner-border-sm d-none"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
include 'include/footer.php';
?>

<script>

  $(document).on('click', '.dashboard-pay-installment', function () {
    const $button = $(this);
    const amount = parseFloat($button.data('amount')) || 0;

    $('#dashboardPayInstallmentId').val($button.data('installment-id'));
    $('#dashboardPayCompanyId').val($button.data('company-id'));
    $('#dashboardPayCompanyName').text($('#paymentCompanyName').text() || '-');
    $('#dashboardPayInstallmentNo').text('Installment #' + $button.data('installment-no'));
    $('#dashboardPayDueDate').text($button.data('due-date') || '-');
    $('#dashboardPayPendingAmount').text('₹' + amount.toFixed(2));
    $('#dashboardPayAmount').attr('max', amount.toFixed(2)).val(amount.toFixed(2));
    $('#dashboardPayInstallmentForm [name="transaction_no"], #dashboardPayInstallmentForm [name="remarks"]').val('');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardPayInstallmentModal')).show();
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  });

  $('#dashboardPayInstallmentForm').on('submit', function (e) {
    e.preventDefault();
    const $button = $('#dashboardPaySubmit');
    const amount = parseFloat($('#dashboardPayAmount').val()) || 0;
    const maxAmount = parseFloat($('#dashboardPayAmount').attr('max')) || 0;
    if (amount <= 0 || amount > maxAmount) {
      showToast('Payment amount must be greater than zero and no more than the pending amount.', 'error');
      return;
    }

    $button.prop('disabled', true);
    $('#dashboardPaySubmitText').addClass('d-none');
    $('#dashboardPaySubmitLoader').removeClass('d-none');

    $.ajax({
      url: '<?= SITE_URL ?>admin/setting/company/subscription_action.php',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function (response) {
        if (response.status) {
          showToast(response.message || 'Payment recorded successfully.', 'success');
          bootstrap.Modal.getInstance(document.getElementById('dashboardPayInstallmentModal')).hide();
          setTimeout(function () {
            window.location.reload();
          }, 600);
        } else {
          showToast(response.message || 'Payment could not be recorded.', 'error');
        }
      },
      error: function (xhr) {
        console.error(xhr.responseText);
        showToast('Server error while saving payment.', 'error');
      },
      complete: function () {
        $button.prop('disabled', false);
        $('#dashboardPaySubmitText').removeClass('d-none');
        $('#dashboardPaySubmitLoader').addClass('d-none');
      }
    });
  });

  $(document).on('click', '.view-payment-history', function () {
    const companyId   = $(this).data('company-id');
    const companyName = $(this).data('company-name');
    $('#paymentCompanyName').text(companyName);
    $('#paymentHistoryLoader').removeClass('d-none');
    $('#paymentHistoryContent').addClass('d-none');
    $('#paymentHistoryError').addClass('d-none');
    $('#paymentHistoryTableBody').html('');
    const modal = new bootstrap.Modal(
        document.getElementById('paymentHistoryModal')
    );
    modal.show();
    $.ajax({
        url: '<?= SITE_URL ?>admin/setting/company/subscription_action.php?action=payment-history',
        type: 'POST',
        dataType: 'json',
        data: {
            company_id: companyId
        },
        success: function (response) {
            $('#paymentHistoryLoader').addClass('d-none');
            if (!response.success) {
                $('#paymentHistoryError')
                    .removeClass('d-none')
                    .text(response.message || 'Payment history not found.');
                return;
            }
            $('#historyTotalAmount').text(
                '₹' + Number(response.summary.total_amount).toLocaleString('en-IN', {
                    minimumFractionDigits: 2
                })
            );
            $('#historyPaidAmount').text(
                '₹' + Number(response.summary.paid_amount).toLocaleString('en-IN', {
                    minimumFractionDigits: 2
                })
            );
            $('#historyPendingAmount').text(
                '₹' + Number(response.summary.pending_amount).toLocaleString('en-IN', {
                    minimumFractionDigits: 2
                })
            );
            let html = '';
            if (response.history.length > 0) {
                $.each(response.history, function (index, row) {
                    let amount = Number(row.amount || 0);
                    let paid = Number(row.paid_amount || 0);
                    let remaining = Math.max(0, amount - paid);
                    let status = (row.status || 'pending').toLowerCase();
                    let badgeClass = 'bg-secondary-subtle text-secondary';
                    if (status === 'paid') {
                        badgeClass = 'bg-success-subtle text-success';
                    } 
                    else if (status === 'partial') {
                        badgeClass = 'bg-warning-subtle text-warning';
                    }
                    else if (status === 'overdue') {
                        badgeClass = 'bg-danger-subtle text-danger';
                    }
                    let statusText = status.charAt(0).toUpperCase() + status.slice(1);
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>
                                <span class="fw-semibold">
                                    ${row.installment_no ?? '-'}
                                </span>
                            </td>
                            <td>
                                ${row.due_date ?? '-'}
                            </td>
                            <td class="fw-semibold">
                                ₹${amount.toLocaleString('en-IN', {
                                    minimumFractionDigits: 2
                                })}
                            </td>
                            <td class="text-success fw-semibold">
                                ₹${paid.toLocaleString('en-IN', {
                                    minimumFractionDigits: 2
                                })}
                            </td>
                            <td>
                                ${row.paid_date ?? '-'}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    ${row.payment_method ?? '-'}
                                </span>
                            </td>
                            <td class="text-danger fw-semibold">
                                ₹${remaining.toLocaleString('en-IN', {
                                    minimumFractionDigits: 2
                                })}
                            </td>
                            <td>
                                <span class="badge px-2 py-1 ${badgeClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td>
                                ${remaining > 0 ? `
                                    <button type="button"
                                        class="btn btn-success btn-sm py-1 px-2 dashboard-pay-installment"
                                        data-installment-id="${Number(row.id)}"
                                        data-company-id="${Number(row.company_id)}"
                                        data-installment-no="${Number(row.installment_no)}"
                                        data-amount="${remaining.toFixed(2)}"
                                        data-due-date="${$('<div>').text(row.due_date || '-').html()}">
                                        <i data-lucide="wallet" class="fs-12 align-middle me-1"></i> Pay Now
                                    </button>
                                ` : '<span class="text-success fs-12 fw-semibold">Paid</span>'}
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="10"
                            class="text-center py-4 text-
                            ">
                            No payment history found.
                        </td>
                    </tr>
                `;
            }

            $('#paymentHistoryTableBody').html(html);
            $('#paymentHistoryContent').removeClass('d-none');
        },
        error: function () {
            $('#paymentHistoryLoader').addClass('d-none');
            $('#paymentHistoryError').removeClass('d-none').text('Something went wrong while loading payment history.');
        }
    });

});

document.addEventListener('DOMContentLoaded', function () {
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // ----------------------------------------------------
  // DYNAMIC THEME COLOR EXTRACTOR FOR SUPERADMIN CHARTS
  // ----------------------------------------------------
  function getThemeColor(variableName, fallback) {
    const val = getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();
    return val ? val : fallback;
  }

  const themePrimary = getThemeColor('--bs-primary', '#117087');
  const themeInfo = getThemeColor('--bs-info', '#0dcaf0');
  const themeSuccess = getThemeColor('--bs-success', '#198754');
  const themeWarning = getThemeColor('--bs-warning', '#ffc107');

  // Plan Distribution Donut Chart
  const planLabels = <?= json_encode($planLabels) ?>;
  const planCounts = <?= json_encode($planCounts) ?>;

  if (document.querySelector("#superadmin-plan-chart")) {
    const planOptions = {
      series: planCounts,
      labels: planLabels,
      chart: {
        type: 'donut',
        height: 290,
        fontFamily: 'inherit'
      },
      colors: [themePrimary, themeInfo, themeSuccess, themeWarning, '#6f42c1'],
      legend: {
        position: 'bottom',
        fontSize: '12px',
        fontWeight: 500,
        markers: { radius: 12 }
      },
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              total: {
                show: true,
                label: 'Total Tenants',
                fontSize: '13px',
                fontWeight: 600,
                color: '#6c757d',
                formatter: function (w) {
                  return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                }
              }
            }
          }
        }
      },
      dataLabels: { enabled: false },
      stroke: { width: 2, colors: ['#ffffff'] }
    };
    const planChart = new ApexCharts(document.querySelector("#superadmin-plan-chart"), planOptions);
    planChart.render();
  }

});
</script>

</body>
</html>
