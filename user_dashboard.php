<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/conn/db.php';
require_once __DIR__ . '/conn/dbqry.php';
require_once __DIR__ . '/conn/helper.php';

$currentUserType = $_SESSION['user_type'] ?? '';

// Access check: Only standard users ('user') can access user_dashboard.php
if ($currentUserType === 'superadmin') {
    header('Location: ' . SITE_URL);
    exit;
} elseif ($currentUserType !== 'user') {
    header('Location: ' . SITE_URL . 'dashboard');
    exit;
}

include 'include/header.php';

$currentUserId = getCurrentUserId();
$companyDashboardId = (int)($headerCompanyId ?: ($_SESSION['company_id'] ?? 0));

// -------------------------------------------------------------------------
// 1. KPI COUNTERS FOR THIS USER
// -------------------------------------------------------------------------

// A. Total Customer Count for this user (assigned to user OR created by user)
$custCond = "status = 1";
if ($companyDashboardId > 0) {
    $custCond .= " AND company_id = $companyDashboardId";
}
if ($currentUserId > 0) {
    $custCond .= " AND (assigned_to = $currentUserId OR created_by = $currentUserId)";
}
$totalUserCustomers = (int)(db_row("SELECT COUNT(*) as total FROM customer WHERE $custCond")['total'] ?? 0);

// B. Today's Lead Follow-ups Count for this user (created_by = $currentUserId or assigned_to)
$compConditionLead = ($companyDashboardId > 0) ? "AND clf.company_id = $companyDashboardId" : "";
if ($currentUserId > 0) {
    $compConditionLead .= " AND clf.created_by = $currentUserId";
}

$todayLeadCount = (int)(db_row("SELECT COUNT(*) as total 
                                FROM company_lead_followups clf 
                                WHERE 1=1 $compConditionLead 
                                AND (DATE(clf.followup_date) = CURDATE() OR (DATE(clf.followup_date) <= CURDATE() AND LOWER(TRIM(clf.followup_status)) IN ('pending', 'open', '3', '')))")['total'] ?? 0);

// C. Today's Customer Follow-ups Count for this user
$compConditionCust = ($companyDashboardId > 0) ? "AND cf.company_id = $companyDashboardId" : "";
if ($currentUserId > 0) {
    $compConditionCust .= " AND cf.created_by = $currentUserId";
}

$todayCustCount = (int)(db_row("SELECT COUNT(*) as total 
                                FROM customer_followups cf 
                                WHERE 1=1 $compConditionCust 
                                AND (DATE(cf.followup_date) = CURDATE() OR (DATE(cf.followup_date) <= CURDATE() AND LOWER(TRIM(cf.followup_status)) IN ('pending', 'open', '3', '')))")['total'] ?? 0);

// -------------------------------------------------------------------------
// 2. FETCH TODAY'S LEAD FOLLOW-UPS LIST
// -------------------------------------------------------------------------
$todayLeadSql = "SELECT clf.*, 
                        'lead' as source_type,
                        cl.id as entity_id,
                        cl.customer_name, 
                        cl.contact_person, 
                        cl.mobile_no, 
                        cl.inquiry_no,
                        u.id as sales_person_id,
                        u.name as sales_person_name, 
                        lft.name as through_name 
                 FROM company_lead_followups clf 
                 LEFT JOIN company_lead cl ON cl.id = clf.lead_id 
                 LEFT JOIN users u ON u.id = cl.assigned_to 
                 LEFT JOIN lead_followup_type lft ON lft.id = clf.followup_type_id 
                 WHERE 1=1 $compConditionLead 
                 AND (DATE(clf.followup_date) = CURDATE() OR (DATE(clf.followup_date) <= CURDATE() AND LOWER(TRIM(clf.followup_status)) IN ('pending', 'open', '3', '')))
                 ORDER BY clf.followup_date DESC, clf.id DESC";
$todayLeadList = db_rows($todayLeadSql);

// Breakdown for Today's Lead Followups
$todayLeadTotal = count($todayLeadList);
$todayLeadPending = 0;
$todayLeadCompleted = 0;
foreach ($todayLeadList as $tf) {
    $st = strtolower(trim((string)($tf['followup_status'] ?? 'pending')));
    if (in_array($st, ['completed', 'close', 'closed', '4', 'end', 'responded'])) {
        $todayLeadCompleted++;
    } else {
        $todayLeadPending++;
    }
}

// -------------------------------------------------------------------------
// 3. FETCH TODAY'S CUSTOMER FOLLOW-UPS LIST
// -------------------------------------------------------------------------
$todayCustSql = "SELECT cf.*, 
                        'customer' as source_type,
                        cust.id as entity_id,
                        cust.name as customer_name, 
                        cust.name as contact_person, 
                        cust.mobile_no, 
                        '' as inquiry_no,
                        u.id as sales_person_id,
                        u.name as sales_person_name, 
                        lft.name as through_name,
                        cfr.name as reason_name
                 FROM customer_followups cf 
                 LEFT JOIN customer cust ON cust.id = cf.customer_id 
                 LEFT JOIN users u ON u.id = cf.created_by 
                 LEFT JOIN lead_followup_type lft ON lft.id = cf.followup_type_id 
                 LEFT JOIN customer_followup_reason cfr ON cfr.id = cf.reason_id
                 WHERE 1=1 $compConditionCust 
                 AND (DATE(cf.followup_date) = CURDATE() OR (DATE(cf.followup_date) <= CURDATE() AND LOWER(TRIM(cf.followup_status)) IN ('pending', 'open', '3', '')))
                 ORDER BY cf.followup_date DESC, cf.id DESC";
$todayCustList = db_rows($todayCustSql);

// Breakdown for Today's Customer Followups
$todayCustTotal = count($todayCustList);
$todayCustPending = 0;
$todayCustCompleted = 0;
foreach ($todayCustList as $tf) {
    $st = strtolower(trim((string)($tf['followup_status'] ?? 'pending')));
    if (in_array($st, ['completed', 'close', 'closed', '4', 'end', 'responded'])) {
        $todayCustCompleted++;
    } else {
        $todayCustPending++;
    }
}

$greetingName = !empty($headerUser['name']) ? htmlspecialchars($headerUser['name']) : 'User';
?>

<!-- ==========================================
     USER WORKSPACE DASHBOARD
=========================================== -->
<div class="user-dashboard-wrapper mb-4">
  <!-- Primary Metric Cards (Total Customers, Today's Lead Followups, Today's Customer Followups) -->
  <div class="row g-3 mb-4">
    <!-- 1. Total Customer Count -->
    <div class="col-md-4 col-sm-6">
      <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 10px;">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="avatar avatar-lg rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
            <i data-lucide="users" class="fs-24"></i>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <span class="d-block text-muted fs-12 fw-medium text-uppercase tracking-wider">Total Customers</span>
            <div class="d-flex align-items-baseline gap-2 mt-1">
              <h3 class="mb-0 fw-bold text-dark"><?= number_format($totalUserCustomers) ?></h3>
              <span class="fs-12 text-muted">Assigned / Created</span>
            </div>
            <a href="<?= SITE_URL ?>customer" class="text-primary text-decoration-none fs-12 fw-semibold d-inline-flex align-items-center gap-1 mt-1">
              Go to Customer List <i data-lucide="arrow-right" class="fs-12"></i>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Today's Lead Followups -->
    <div class="col-md-4 col-sm-6">
      <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 10px;">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="avatar avatar-lg rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
            <i data-lucide="phone-call" class="fs-24"></i>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <span class="d-block text-muted fs-12 fw-medium text-uppercase tracking-wider">Today's Lead Followups</span>
            <div class="d-flex align-items-baseline gap-2 mt-1">
              <h3 class="mb-0 fw-bold text-dark"><?= number_format($todayLeadCount) ?></h3>
              <span class="fs-12 text-warning fw-semibold">(<?= $todayLeadPending ?> Pending)</span>
            </div>
            <a href="#sectionTodayLeadFollowup" class="text-info text-decoration-none fs-12 fw-semibold d-inline-flex align-items-center gap-1 mt-1">
              View Follow-ups <i data-lucide="arrow-down" class="fs-12"></i>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Today's Customer Followups -->
    <div class="col-md-4 col-sm-12">
      <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 10px;">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="avatar avatar-lg rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
            <i data-lucide="calendar-check" class="fs-24"></i>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <span class="d-block text-muted fs-12 fw-medium text-uppercase tracking-wider">Today's Customer Followups</span>
            <div class="d-flex align-items-baseline gap-2 mt-1">
              <h3 class="mb-0 fw-bold text-dark"><?= number_format($todayCustCount) ?></h3>
              <span class="fs-12 text-warning fw-semibold">(<?= $todayCustPending ?> Pending)</span>
            </div>
            <a href="#sectionTodayCustFollowup" class="text-success text-decoration-none fs-12 fw-semibold d-inline-flex align-items-center gap-1 mt-1">
              View Follow-ups <i data-lucide="arrow-down" class="fs-12"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 1. TODAY'S LEAD FOLLOWUP Section -->
  <div class="card mb-4 border-0 shadow-sm overflow-hidden" id="sectionTodayLeadFollowup">
    <!-- Header Banner -->
    <div class="card-header bg-primary text-white py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-2">
        <i data-lucide="phone-call" class="fs-18"></i>
        <h5 class="mb-0 fw-bold text-white tracking-wide">
          Today's Lead Followup
        </h5>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= SITE_URL ?>company-lead-followup" class="btn btn-light btn-sm text-primary fw-semibold px-2 py-1 fs-12">
          View All Leads
        </a>
      </div>
    </div>

    <div class="card-body p-4">
      <!-- Sub-header Counters & Date Display -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
            <span class="text-primary fw-bold fs-14 me-1"><?= $todayLeadTotal ?></span> Total
          </span>
          <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
            <span class="text-warning fw-bold fs-14 me-1"><?= $todayLeadPending ?></span> Pending
          </span>
          <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
            <span class="text-success fw-bold fs-14 me-1"><?= $todayLeadCompleted ?></span> Responded
          </span>
        </div>
        <div>
          <span class="badge bg-light text-secondary border px-3 py-2 fs-12 d-flex align-items-center gap-1">
            <i data-lucide="calendar" class="fs-14 text-primary"></i> <?= date('d M Y, l') ?>
          </span>
        </div>
      </div>

      <!-- Responsive Table with Pagination -->
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle mb-0 text-nowrap" id="tableUserTodayLeadFollowup">
          <thead class="table-light fs-12 text-secondary">
            <tr>
              <th class="text-center" style="width: 50px;">NO.</th>
              <th>CUSTOMER NAME</th>
              <th>PERSON NAME</th>
              <th>MOBILE NO.</th>
              <th>DATE & TIME</th>
              <th>DESCRIPTION</th>
              <th class="text-center">THROUGH</th>
              <th class="text-center">STATUS</th>
              <th class="text-center" style="width: 80px;">ACTION</th>
            </tr>
          </thead>
          <tbody class="fs-13" id="userTodayLeadFollowupTbody">
            <?php if (empty($todayLeadList)): ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No lead followups scheduled for today.</td>
              </tr>
            <?php else: ?>
              <?php 
              $rowNo = 1;
              $dashNowTs = time();
              foreach ($todayLeadList as $tfRow): 
                $isCompleted = in_array(strtolower(trim((string)($tfRow['followup_status'] ?? ''))), ['completed', 'close', 'closed', '4', 'end', 'responded']);
                $dtTimestamp = !empty($tfRow['followup_date']) ? strtotime($tfRow['followup_date']) : time();
                $isPassedTime = ($dtTimestamp < $dashNowTs);

                if ($isCompleted) {
                  $statusBadgeClass = 'bg-success-subtle text-success border border-success-subtle';
                  $statusText = 'RESPONDED';
                } elseif ($isPassedTime) {
                  $statusBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                  $statusText = 'OVERDUE';
                } else {
                  $statusBadgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                  $statusText = 'PENDING';
                }
                
                $throughName = !empty($tfRow['through_name']) ? strtoupper(htmlspecialchars($tfRow['through_name'])) : 'CALL';
                $throughClass = ($throughName === 'VISIT' || strpos($throughName, 'MEET') !== false) ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-info-subtle text-info border border-info-subtle';
                
                $rowDateTimeFormatted = date('d-m-Y h:i A', $dtTimestamp);
                $custName = !empty($tfRow['customer_name']) ? htmlspecialchars($tfRow['customer_name']) : ('Inquiry #' . ($tfRow['inquiry_no'] ?: $tfRow['lead_id']));
                $personName = !empty($tfRow['contact_person']) ? htmlspecialchars($tfRow['contact_person']) : '-';
                $mobileNo = !empty($tfRow['mobile_no']) ? htmlspecialchars($tfRow['mobile_no']) : '';
                $desc = !empty($tfRow['remarks']) ? htmlspecialchars($tfRow['remarks']) : (!empty($tfRow['response']) ? htmlspecialchars($tfRow['response']) : '-');
                $leadEncId = encrypt_id($tfRow['lead_id'] ?? $tfRow['entity_id']);
                $actionUrl = SITE_URL . 'company-lead/view/' . $leadEncId;
              ?>
                <tr class="user-lead-row">
                  <td class="text-center fw-medium text-muted"><?= $rowNo++ ?></td>
                  <td class="fw-semibold text-dark"><?= $custName ?></td>
                  <td class="text-secondary"><?= $personName ?></td>
                  <td>
                    <?php if ($mobileNo): ?>
                      <a href="tel:<?= $mobileNo ?>" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                        <i data-lucide="phone" class="fs-13"></i> <?= $mobileNo ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted"><?= $rowDateTimeFormatted ?></td>
                  <td class="text-dark fw-medium text-truncate" style="max-width: 250px;" title="<?= $desc ?>"><?= $desc ?></td>
                  <td class="text-center">
                    <span class="badge <?= $throughClass ?> px-2 py-1"><?= $throughName ?></span>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $statusBadgeClass ?> px-2 py-1"><?= $statusText ?></span>
                  </td>
                  <td class="text-center">
                    <a href="<?= $actionUrl ?>" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Details">
                      <i data-lucide="eye" class="fs-14"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 2. TODAY'S CUSTOMER FOLLOWUP Section -->
  <div class="card mb-4 border-0 shadow-sm overflow-hidden" id="sectionTodayCustFollowup">
    <!-- Header Banner -->
    <div class="card-header bg-primary text-white py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-2">
        <i data-lucide="calendar-check" class="fs-18"></i>
        <h5 class="mb-0 fw-bold text-white tracking-wide">
          Today's Customer Followup
        </h5>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= SITE_URL ?>customer-followup" class="btn btn-light btn-sm text-primary fw-semibold px-2 py-1 fs-12">
          View All Follow-ups
        </a>
      </div>
    </div>

    <div class="card-body p-4">
      <!-- Sub-header Counters & Date Display -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
            <span class="text-primary fw-bold fs-14 me-1"><?= $todayCustTotal ?></span> Total
          </span>
          <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
            <span class="text-warning fw-bold fs-14 me-1"><?= $todayCustPending ?></span> Pending
          </span>
          <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
            <span class="text-success fw-bold fs-14 me-1"><?= $todayCustCompleted ?></span> Responded
          </span>
        </div>
        <div>
          <span class="badge bg-light text-secondary border px-3 py-2 fs-12 d-flex align-items-center gap-1">
            <i data-lucide="calendar" class="fs-14 text-primary"></i> <?= date('d M Y, l') ?>
          </span>
        </div>
      </div>

      <!-- Responsive Table with Pagination -->
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle mb-0 text-nowrap" id="tableUserTodayCustomerFollowup">
          <thead class="table-light fs-12 text-secondary">
            <tr>
              <th class="text-center" style="width: 50px;">NO.</th>
              <th>CUSTOMER NAME</th>
              <th>MOBILE NO.</th>
              <th>DATE & TIME</th>
              <th>REASON / REMARKS</th>
              <th class="text-center">THROUGH</th>
              <th class="text-center">STATUS</th>
              <th class="text-center" style="width: 80px;">ACTION</th>
            </tr>
          </thead>
          <tbody class="fs-13" id="userTodayCustFollowupTbody">
            <?php if (empty($todayCustList)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">No customer followups scheduled for today.</td>
              </tr>
            <?php else: ?>
              <?php 
              $rowCustNo = 1;
              $dashNowTs = time();
              foreach ($todayCustList as $cfRow): 
                $isCompleted = in_array(strtolower(trim((string)($cfRow['followup_status'] ?? ''))), ['completed', 'close', 'closed', '4', 'end', 'responded']);
                $dtTimestamp = !empty($cfRow['followup_date']) ? strtotime($cfRow['followup_date']) : time();
                $isPassedTime = ($dtTimestamp < $dashNowTs);

                if ($isCompleted) {
                  $statusBadgeClass = 'bg-success-subtle text-success border border-success-subtle';
                  $statusText = 'RESPONDED';
                } elseif ($isPassedTime) {
                  $statusBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                  $statusText = 'OVERDUE';
                } else {
                  $statusBadgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                  $statusText = 'PENDING';
                }
                
                $throughName = !empty($cfRow['through_name']) ? strtoupper(htmlspecialchars($cfRow['through_name'])) : 'CALL';
                $throughClass = ($throughName === 'VISIT' || strpos($throughName, 'MEET') !== false) ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-info-subtle text-info border border-info-subtle';
                
                $rowDateTimeFormatted = date('d-m-Y h:i A', $dtTimestamp);
                $custName = !empty($cfRow['customer_name']) ? htmlspecialchars($cfRow['customer_name']) : ('Customer #' . ($cfRow['customer_id'] ?? $cfRow['entity_id']));
                $mobileNo = !empty($cfRow['mobile_no']) ? htmlspecialchars($cfRow['mobile_no']) : '';
                $desc = !empty($cfRow['remarks']) ? htmlspecialchars($cfRow['remarks']) : (!empty($cfRow['response']) ? htmlspecialchars($cfRow['response']) : (!empty($cfRow['reason_name']) ? htmlspecialchars($cfRow['reason_name']) : '-'));
                $actionUrl = SITE_URL . 'customer-followup?customer_id=' . encrypt_id($cfRow['customer_id'] ?? $cfRow['entity_id']);
              ?>
                <tr class="user-cust-row">
                  <td class="text-center fw-medium text-muted"><?= $rowCustNo++ ?></td>
                  <td class="fw-semibold text-dark"><?= $custName ?></td>
                  <td>
                    <?php if ($mobileNo): ?>
                      <a href="tel:<?= $mobileNo ?>" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                        <i data-lucide="phone" class="fs-13"></i> <?= $mobileNo ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted"><?= $rowDateTimeFormatted ?></td>
                  <td class="text-dark fw-medium text-truncate" style="max-width: 250px;" title="<?= $desc ?>"><?= $desc ?></td>
                  <td class="text-center">
                    <span class="badge <?= $throughClass ?> px-2 py-1"><?= $throughName ?></span>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $statusBadgeClass ?> px-2 py-1"><?= $statusText ?></span>
                  </td>
                  <td class="text-center">
                    <a href="<?= $actionUrl ?>" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Followup">
                      <i data-lucide="eye" class="fs-14"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php
include BASE_PATH . '/include/footer.php';
?>
