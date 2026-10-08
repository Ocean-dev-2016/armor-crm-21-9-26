<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../conn/db.php';
if (empty($_SESSION)) {
  header("Location: " . SITE_URL . "login");
  exit;
}
require_once __DIR__ . '/../conn/dbqry.php';
require_once __DIR__ . '/../conn/helper.php';

$msql = "SELECT * FROM module WHERE status = 1 ORDER BY order_by ASC, id ASC";
$mres = db_rows($msql);

// Fetch logged-in user and company profile/logos
$headerUserId = (int)($_SESSION['user_id'] ?? 0);
$headerUser = null;
if ($headerUserId > 0) {
  $headerUser = db_row("SELECT id, name, username, email, company_id, profile_img FROM users WHERE id = $headerUserId LIMIT 1");
}

$headerCompanyId = (int)($headerUser['company_id'] ?? ($_SESSION['company_id'] ?? 0));
$headerCompany = null;
if ($headerCompanyId > 0) {
  $headerCompany = db_row("SELECT id, name, header_image, app_logo, favicon FROM company WHERE id = $headerCompanyId LIMIT 1");
}

// User Profile Image (with default fallback)
$defaultUserAvatar = SITE_URL . 'assets/images/user-8.jpg';
$headerUserProfileImg = $defaultUserAvatar;
if (!empty($headerUser['profile_img']) && file_exists(BASE_PATH . '/uploads/profile/' . $headerUser['profile_img'])) {
  $headerUserProfileImg = SITE_URL . 'uploads/profile/' . $headerUser['profile_img'];
}

// Fetch Superadmin Branding (System logos fallback)
$superadminBranding = db_row("SELECT header_logo, favicon FROM users WHERE user_type = 'superadmin' AND (header_logo IS NOT NULL OR favicon IS NOT NULL) ORDER BY id ASC LIMIT 1");

// Company Header Logo (with default fallback)
$defaultCompanyLogo = SITE_URL . 'assets/image/crm_logo.png';
if (!empty($superadminBranding['header_logo']) && file_exists(BASE_PATH . '/uploads/system/' . $superadminBranding['header_logo'])) {
  $defaultCompanyLogo = SITE_URL . 'uploads/system/' . $superadminBranding['header_logo'];
}

$headerCompanyLogo = $defaultCompanyLogo;
if (!empty($headerCompany['header_image']) && file_exists(BASE_PATH . '/uploads/company/' . $headerCompany['header_image'])) {
  $headerCompanyLogo = SITE_URL . 'uploads/company/' . $headerCompany['header_image'];
} elseif (!empty($headerCompany['app_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $headerCompany['app_logo'])) {
  $headerCompanyLogo = SITE_URL . 'uploads/company/' . $headerCompany['app_logo'];
} else {
  $headerCompanyLogo = $defaultCompanyLogo;
}

// Company Favicon (with default fallback)
$defaultFavicon = $defaultCompanyLogo;
if (!empty($superadminBranding['favicon']) && file_exists(BASE_PATH . '/uploads/system/' . $superadminBranding['favicon'])) {
  $defaultFavicon = SITE_URL . 'uploads/system/' . $superadminBranding['favicon'];
}
$headerFavicon = $defaultFavicon;
if (!empty($headerCompany['favicon']) && file_exists(BASE_PATH . '/uploads/company/' . $headerCompany['favicon'])) {
  $headerFavicon = SITE_URL . 'uploads/company/' . $headerCompany['favicon'];
} elseif (!empty($headerCompany['app_logo']) && file_exists(BASE_PATH . '/uploads/company/' . $headerCompany['app_logo'])) {
  $headerFavicon = SITE_URL . 'uploads/company/' . $headerCompany['app_logo'];
} elseif ($headerCompanyLogo !== $defaultCompanyLogo) {
  $headerFavicon = $headerCompanyLogo;
} else {
  $headerFavicon = $defaultFavicon;
}

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$isStandardUser = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';
$homeUrl = $isSuperadmin ? SITE_URL : ($isStandardUser ? SITE_URL . 'user-dashboard' : SITE_URL . 'dashboard');
$allowedModules = null; 

if (!$isSuperadmin) {
  $plan_id = (int)($_SESSION['plan_id'] ?? 0);
  $allowedPlanModules = [];
  if ($plan_id > 0) {
    $plan_row = db_row("SELECT panel_right FROM plan WHERE id = $plan_id AND status = 1 LIMIT 1");
    if ($plan_row && !empty($plan_row['panel_right'])) {
      $allowedPlanModules = array_map('intval', array_filter(array_map('trim', explode(',', $plan_row['panel_right']))));
    }
  }

  $rolePermissions = getUserRolePermissions();

  $allowedModules = [];
  foreach ($allowedPlanModules as $mId) {
    if (!empty($rolePermissions[$mId]['views'])) {
      $allowedModules[] = (int)$mId;
    }
  }
}

$menuTree = [];

foreach ($mres as $menu) {
  if ((int)$menu['parent_id'] === 0) {
    if (!$isSuperadmin && $allowedModules !== null) {
      $parentAllowed = in_array((int)$menu['id'], $allowedModules, true);
      if (!$parentAllowed) {
        $hasAllowedChild = false;
        foreach ($mres as $child) {
          if ((int)$child['parent_id'] === (int)$menu['id'] && in_array((int)$child['id'], $allowedModules, true)) {
            $hasAllowedChild = true;
            break;
          }
        }
        if (!$hasAllowedChild) {
          continue; 
        }
      }
    }
    $menuTree[$menu['id']] = [
      'id'       => $menu['id'],
      'name'     => $menu['name'],
      'icon'     => $menu['icon'],
      'route'    => $menu['route'],
      'sub_menu' => []
    ];
  }
}

foreach ($mres as $menu) {
  if ((int)$menu['parent_id'] !== 0) {
    $parentId = (int)$menu['parent_id'];
    if (!isset($menuTree[$parentId])) {
      continue; 
    }
    if (!$isSuperadmin && $allowedModules !== null) {
      if (!in_array((int)$menu['id'], $allowedModules, true)) {
        continue;
      }
    }
    $menuTree[$parentId]['sub_menu'][] = $menu;
  }
}

foreach ($menuTree as $key => $item) {
  if (!$isSuperadmin && $allowedModules !== null) {
    $isDirectlyAllowed = in_array((int)$item['id'], $allowedModules, true);
    if (!$isDirectlyAllowed && empty($item['sub_menu'])) {
      unset($menuTree[$key]);
    }
  }
}

// Prepare searchable menu list for Header Search
$searchableMenus = [];
foreach ($menuTree as $mItem) {
  if (!empty($mItem['sub_menu'])) {
    foreach ($mItem['sub_menu'] as $sub) {
      $searchableMenus[] = [
        'name'   => $sub['name'],
        'parent' => $mItem['name'],
        'route'  => SITE_URL . ltrim($sub['route'], '/'),
        'icon'   => $sub['icon'] ?? $mItem['icon'] ?? 'circle'
      ];
    }
  } else {
    $searchableMenus[] = [
      'name'   => $mItem['name'],
      'parent' => '',
      'route'  => SITE_URL . ltrim($mItem['route'], '/'),
      'icon'   => $mItem['icon'] ?? 'circle'
    ];
  }
}

if ($isSuperadmin) {
  $superMenus = [
    ['name' => 'Lead Status', 'parent' => 'Main Setting', 'route' => SITE_URL . 'lead-status', 'icon' => 'tag'],
    ['name' => 'Lead Source Of Inquiry', 'parent' => 'Main Setting', 'route' => SITE_URL . 'lead-source-of-inquiry', 'icon' => 'help-circle'],
    ['name' => 'Lead Employee', 'parent' => 'Main Setting', 'route' => SITE_URL . 'lead-employee', 'icon' => 'user-check'],
    ['name' => 'Lead Followup Type', 'parent' => 'Main Setting', 'route' => SITE_URL . 'lead-followup-type', 'icon' => 'calendar-clock'],
    ['name' => 'Module', 'parent' => 'Main Setting', 'route' => SITE_URL . 'module', 'icon' => 'box'],
    ['name' => 'Plan', 'parent' => 'Main Setting', 'route' => SITE_URL . 'plan', 'icon' => 'layers'],
    ['name' => 'Company Type', 'parent' => 'Main Setting', 'route' => SITE_URL . 'company-type', 'icon' => 'briefcase'],
    ['name' => 'Company', 'parent' => 'Main Setting', 'route' => SITE_URL . 'company', 'icon' => 'building-2'],
    ['name' => 'Team Role', 'parent' => 'Main Setting', 'route' => SITE_URL . 'teamrole', 'icon' => 'shield'],
    ['name' => 'Team Person', 'parent' => 'Main Setting', 'route' => SITE_URL . 'team-person', 'icon' => 'users'],
    ['name' => 'Company Lead', 'parent' => 'Main Setting', 'route' => SITE_URL . 'company-lead', 'icon' => 'briefcase'],
    ['name' => 'Company Lead Follow Up', 'parent' => 'Main Setting', 'route' => SITE_URL . 'company-lead-followup', 'icon' => 'calendar-clock']
  ];
  foreach ($superMenus as $sm) {
    $searchableMenus[] = $sm;
  }
}

$headerDisplayName = !empty($headerUser['name']) ? $headerUser['name'] : ($_SESSION['name'] ?? ($_SESSION['username'] ?? 'User'));

// Follow-ups & Reminders for Notification Bell (Company / Superadmin)
$notifFollowups = [];
$notifTotalPending = 0;
$notifOverdue = [];
$notifToday = [];
$notifUpcoming = [];

if (function_exists('db_rows')) {
    $nowTs = time();
    $todayDate = date('Y-m-d', $nowTs);
    $notifCompId = (int)($headerCompanyId ?: ($_SESSION['company_id'] ?? 0));
    $headerUserType = $_SESSION['user_type'] ?? '';
    $userHeaderCondLead = "";
    $userHeaderCondCust = "";
    if ($headerUserType === 'user' && $headerUserId > 0) {
        $userHeaderCondLead = " AND clf.created_by = $headerUserId";
        $userHeaderCondCust = " AND cf.created_by = $headerUserId";
    }

    if (!$isSuperadmin && $notifCompId > 0) {
        // Company login: Fetch pending follow-ups from company_lead_followups
        $companyPendingRows = db_rows("SELECT clf.*, 
                                              cl.customer_name, 
                                              cl.contact_person, 
                                              cl.mobile_no, 
                                              cl.inquiry_no, 
                                              lft.name as through_name 
                                       FROM company_lead_followups clf 
                                       LEFT JOIN company_lead cl ON cl.id = clf.lead_id 
                                       LEFT JOIN lead_followup_type lft ON lft.id = clf.followup_type_id 
                                       WHERE clf.company_id = $notifCompId $userHeaderCondLead
                                         AND (LOWER(TRIM(clf.followup_status)) IN ('pending', 'open', '3', '') OR clf.followup_status IS NULL)
                                       ORDER BY clf.followup_date ASC");

        foreach ($companyPendingRows as $cfRow) {
            $cfRow['source_type'] = 'lead';
            $fDateRaw = (!empty($cfRow['followup_date']) && $cfRow['followup_date'] !== '0000-00-00 00:00:00') ? $cfRow['followup_date'] : '';
            $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
            $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

            if (empty($fTs) || $fTs < $nowTs) {
                // If scheduled date/time has passed, it is Overdue
                $cfRow['timing_category'] = 'overdue';
                $cfRow['timing_label'] = 'Overdue';
                $cfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
                $notifOverdue[] = $cfRow;
            } elseif ($fDateOnly === $todayDate) {
                // If scheduled for today and the scheduled time has NOT yet passed
                $cfRow['timing_category'] = 'today';
                $cfRow['timing_label'] = 'Today';
                $cfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
                $notifToday[] = $cfRow;
            } else {
                // Future dates
                $cfRow['timing_category'] = 'upcoming';
                $cfRow['timing_label'] = 'Upcoming';
                $cfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
                $notifUpcoming[] = $cfRow;
            }
            $notifFollowups[] = $cfRow;
        }

        // Customer Follow-ups (Sales & Marketing)
        $customerPendingRows = db_rows("SELECT cf.*, 
                                               cust.name as customer_name, 
                                               cust.mobile_no, 
                                               cust.email, 
                                               lft.name as through_name, 
                                               cfr.name as reason_name
                                        FROM customer_followups cf 
                                        LEFT JOIN customer cust ON cust.id = cf.customer_id 
                                        LEFT JOIN lead_followup_type lft ON lft.id = cf.followup_type_id 
                                        LEFT JOIN customer_followup_reason cfr ON cfr.id = cf.reason_id
                                        WHERE cf.company_id = $notifCompId $userHeaderCondCust
                                          AND (LOWER(TRIM(cf.followup_status)) IN ('pending', 'open', '3', '') OR cf.followup_status IS NULL)
                                        ORDER BY cf.followup_date ASC");

        foreach ($customerPendingRows as $cfRow) {
            $cfRow['source_type'] = 'customer';
            $cfRow['contact_person'] = $cfRow['customer_name'] ?? '';
            $fDateRaw = (!empty($cfRow['followup_date']) && $cfRow['followup_date'] !== '0000-00-00 00:00:00') ? $cfRow['followup_date'] : '';
            $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
            $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

            if (empty($fTs) || $fTs < $nowTs) {
                $cfRow['timing_category'] = 'overdue';
                $cfRow['timing_label'] = 'Overdue';
                $cfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
                $notifOverdue[] = $cfRow;
            } elseif ($fDateOnly === $todayDate) {
                $cfRow['timing_category'] = 'today';
                $cfRow['timing_label'] = 'Today';
                $cfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
                $notifToday[] = $cfRow;
            } else {
                $cfRow['timing_category'] = 'upcoming';
                $cfRow['timing_label'] = 'Upcoming';
                $cfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
                $notifUpcoming[] = $cfRow;
            }
            $notifFollowups[] = $cfRow;
        }

        $notifTotalPending = count($companyPendingRows) + count($customerPendingRows);
    } else {
        // Superadmin: Fetch pending followups from lead_followups (Superadmin Lead)
        $leadPendingRows = db_rows("SELECT f.*, 
                                           l.business_name, 
                                           l.contact_name, 
                                           l.mobile_no, 
                                           l.lead_number,
                                           lft.name as through_name
                                    FROM lead_followups f 
                                    JOIN lead l ON l.id = f.lead_id 
                                    LEFT JOIN lead_followup_type lft ON (lft.id = f.followup_type OR LOWER(lft.name) = LOWER(f.followup_type))
                                    WHERE LOWER(TRIM(f.reminder_status)) = 'pending' 
                                    ORDER BY f.followup_date ASC");

        foreach ($leadPendingRows as $lfRow) {
            $lfRow['source_type'] = 'superadmin_lead';
            $fDateRaw = (!empty($lfRow['followup_date']) && $lfRow['followup_date'] !== '0000-00-00 00:00:00') ? $lfRow['followup_date'] : '';
            $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
            $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

            if (empty($fTs) || $fTs < $nowTs) {
                // If scheduled date/time has passed, it is Overdue
                $lfRow['timing_category'] = 'overdue';
                $lfRow['timing_label'] = 'Overdue';
                $lfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
                $notifOverdue[] = $lfRow;
            } elseif ($fDateOnly === $todayDate) {
                // If scheduled for today and the scheduled time has NOT yet passed
                $lfRow['timing_category'] = 'today';
                $lfRow['timing_label'] = 'Today';
                $lfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
                $notifToday[] = $lfRow;
            } else {
                // Future dates
                $lfRow['timing_category'] = 'upcoming';
                $lfRow['timing_label'] = 'Upcoming';
                $lfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
                $notifUpcoming[] = $lfRow;
            }
            if (empty($lfRow['through_name']) && !empty($lfRow['followup_type'])) {
                $lfRow['through_name'] = function_exists('get_lead_followup_type_label') ? get_lead_followup_type_label($lfRow['followup_type']) : ucfirst($lfRow['followup_type']);
            }
            $notifFollowups[] = $lfRow;
        }

        // Superadmin: Fetch pending followups from company_lead_followups (Company Leads)
        $compLeadAdminRows = db_rows("SELECT clf.*, 
                                             cl.customer_name, 
                                             cl.contact_person, 
                                             cl.mobile_no, 
                                             cl.inquiry_no, 
                                             lft.name as through_name,
                                             c.name as company_name
                                      FROM company_lead_followups clf 
                                      LEFT JOIN company_lead cl ON cl.id = clf.lead_id 
                                      LEFT JOIN lead_followup_type lft ON lft.id = clf.followup_type_id 
                                      LEFT JOIN company c ON c.id = clf.company_id
                                      WHERE (LOWER(TRIM(clf.followup_status)) IN ('pending', 'open', '3', '') OR clf.followup_status IS NULL)
                                      ORDER BY clf.followup_date ASC");

        foreach ($compLeadAdminRows as $cfRow) {
            $cfRow['source_type'] = 'lead';
            $fDateRaw = (!empty($cfRow['followup_date']) && $cfRow['followup_date'] !== '0000-00-00 00:00:00') ? $cfRow['followup_date'] : '';
            $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
            $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

            if (empty($fTs) || $fTs < $nowTs) {
                $cfRow['timing_category'] = 'overdue';
                $cfRow['timing_label'] = 'Overdue';
                $cfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
                $notifOverdue[] = $cfRow;
            } elseif ($fDateOnly === $todayDate) {
                $cfRow['timing_category'] = 'today';
                $cfRow['timing_label'] = 'Today';
                $cfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
                $notifToday[] = $cfRow;
            } else {
                $cfRow['timing_category'] = 'upcoming';
                $cfRow['timing_label'] = 'Upcoming';
                $cfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
                $notifUpcoming[] = $cfRow;
            }
            $notifFollowups[] = $cfRow;
        }

        // Superadmin: Customer Follow-ups
        $custAdminRows = db_rows("SELECT cf.*, 
                                         cust.name as customer_name, 
                                         cust.mobile_no, 
                                         cust.email, 
                                         lft.name as through_name,
                                         cfr.name as reason_name
                                  FROM customer_followups cf 
                                  LEFT JOIN customer cust ON cust.id = cf.customer_id 
                                  LEFT JOIN lead_followup_type lft ON lft.id = cf.followup_type_id 
                                  LEFT JOIN customer_followup_reason cfr ON cfr.id = cf.reason_id
                                  WHERE (LOWER(TRIM(cf.followup_status)) IN ('pending', 'open', '3', '') OR cf.followup_status IS NULL)
                                  ORDER BY cf.followup_date ASC");

        foreach ($custAdminRows as $cfRow) {
            $cfRow['source_type'] = 'customer';
            $cfRow['contact_person'] = $cfRow['customer_name'] ?? '';
            $fDateRaw = (!empty($cfRow['followup_date']) && $cfRow['followup_date'] !== '0000-00-00 00:00:00') ? $cfRow['followup_date'] : '';
            $fTs = $fDateRaw ? strtotime($fDateRaw) : 0;
            $fDateOnly = $fTs ? date('Y-m-d', $fTs) : '';

            if (empty($fTs) || $fTs < $nowTs) {
                $cfRow['timing_category'] = 'overdue';
                $cfRow['timing_label'] = 'Overdue';
                $cfRow['timing_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
                $notifOverdue[] = $cfRow;
            } elseif ($fDateOnly === $todayDate) {
                $cfRow['timing_category'] = 'today';
                $cfRow['timing_label'] = 'Today';
                $cfRow['timing_badge'] = 'bg-warning-subtle text-warning border border-warning-subtle';
                $notifToday[] = $cfRow;
            } else {
                $cfRow['timing_category'] = 'upcoming';
                $cfRow['timing_label'] = 'Upcoming';
                $cfRow['timing_badge'] = 'bg-info-subtle text-info border border-info-subtle';
                $notifUpcoming[] = $cfRow;
            }
            $notifFollowups[] = $cfRow;
        }

        $notifTotalPending = count($leadPendingRows) + count($compLeadAdminRows) + count($custAdminRows);
    }
}
$todayFollowupCount = $notifTotalPending;

include BASE_PATH . '/include/css.php';
?>
<script>
  window.HEADER_SEARCH_MENUS = <?= json_encode($searchableMenus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<body>
  <div class="main-wrapper">
    <div class="content-wrapper">

      <header class="header">
        <div class="d-flex align-items-center">
          <button
            id="sidebar-toggler"
            class="btn btn-icon btn-light me-3 d-lg-none"
            type="button">
            <i data-lucide="menu"></i>
          </button>

          <a href="<?= $homeUrl ?>"><img src="<?= $headerCompanyLogo ?>" alt="Logo" class="logo"></a>
        </div>

        <!-- Header Middle: Search Bar & Live User/Date/Time Info -->
        <div class="header-center-section d-flex align-items-center flex-grow-1 mx-3 gap-3">
          <!-- Menu Search Box -->
          <div class="header-search-wrapper position-relative">
            <div class="input-group header-search-input-group">
              <span class="input-group-text bg-transparent border-end-0 text-muted ps-3 pe-2">
                <i data-lucide="search" class="fs-16"></i>
              </span>
              <input 
                type="text" 
                id="headerMenuSearchInput" 
                class="form-control border-start-0 border-end-0 ps-1 pe-2 shadow-none" 
                placeholder="Search menu (type 3+ chars)..." 
                autocomplete="off">
              <span class="input-group-text bg-transparent border-start-0 text-muted pe-3 ps-1 header-search-clear-btn" id="headerSearchClearBtn">
                <i data-lucide="x" class="fs-15"></i>
              </span>
            </div>

            <!-- Search Results Dropdown -->
            <div id="headerSearchResults" class="header-search-dropdown shadow-lg border rounded-3 p-2 bg-white">
              <!-- Dynamic items injected here via JS -->
            </div>
          </div>

          <!-- Logged-in User Info & Live Clock -->
          <div class="header-user-meta ms-auto d-none d-md-flex align-items-center gap-2">
            <!-- User Name Badge -->
            <div class="header-meta-pill header-user-pill d-flex align-items-center gap-2 px-2 py-1 rounded-pill">
              <i data-lucide="user" class="fs-16 text-primary"></i>
              <span class="fw-semibold text-truncate header-user-name" title="<?= htmlspecialchars($headerDisplayName) ?>">
                <?= htmlspecialchars($headerDisplayName) ?>
              </span>
            </div>

            <!-- Current Date -->
            <div class="header-meta-pill header-date-pill d-none d-lg-flex align-items-center gap-2 px-2 py-1 rounded-pill">
              <i data-lucide="calendar" class="fs-16 text-info"></i>
              <span id="headerLiveDate" class="fw-medium">
                <?= date('d M Y') ?>
              </span>
            </div>

            <!-- Real-time Clock -->
            <div class="header-meta-pill header-clock-pill d-flex align-items-center gap-2 px-2 py-1 rounded-pill">
              <i data-lucide="clock" class="fs-16 text-warning"></i>
              <span id="headerLiveClock" class="fw-semibold">
                <?= date('h:i:s A') ?>
              </span>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-3">
          <button id="theme-switcher" class="btn btn-icon btn-light rounded-circle">
            <i data-lucide="moon" class="fs-20"></i>
          </button>


          <!-- Notification Dropdown: Overdue, Today & Upcoming Pending Follow-ups -->
          <div class="dropdown" id="headerNotificationDropdown">
            <button class="btn btn-icon btn-light rounded-circle position-relative" id="headerNotifBellBtn" data-bs-toggle="dropdown"
              aria-expanded="false" title="Pending Follow-up Reminders">
              <i data-lucide="bell" class="fs-20"></i>
              <span id="headerNotifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light <?= $notifTotalPending > 0 ? '' : 'd-none' ?>" style="font-size: 10px; padding: 3px 6px;">
                <?= $notifTotalPending > 99 ? '99+' : $notifTotalPending ?>
                <span class="visually-hidden">pending follow-ups</span>
              </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end border shadow-lg mt-2 p-0 dropdown-notification" style="width: 360px;">
              <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-light-subtle">
                <h6 class="mb-0 fw-bold fs-13 d-flex align-items-center gap-1">
                  <i data-lucide="bell" class="fs-15 text-primary"></i> Follow-up Notifications
                </h6>
                <span id="headerNotifPendingBadge" class="badge bg-danger-subtle text-danger border border-danger-subtle fs-11"><?= $notifTotalPending ?> Pending</span>
              </div>

              <!-- Unified Single List of Pending Follow-ups -->
              <div class="overflow-auto notification-list" id="headerNotifList" style="max-height: 380px;">
                <?php if (empty($notifFollowups)): ?>
                  <div class="text-center py-4 px-3 text-muted">
                    <i data-lucide="check-circle-2" class="fs-24 text-success mb-2"></i>
                    <p class="mb-0 fs-12">No pending follow-ups found.</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($notifFollowups as $tf): 
                    $fDateRaw = (!empty($tf['followup_date']) && $tf['followup_date'] !== '0000-00-00 00:00:00') ? $tf['followup_date'] : '';
                    $timingLabel = $tf['timing_label'] ?? 'Pending';
                    $timingBadge = $tf['timing_badge'] ?? 'bg-secondary-subtle text-secondary';
                    $dateText = $fDateRaw ? date('d M, h:i A', strtotime($fDateRaw)) : '-';
                    
                    $isCustomerType = (($tf['source_type'] ?? '') === 'customer');
                    $isSuperadminLead = (($tf['source_type'] ?? '') === 'superadmin_lead');
                    $titleName = htmlspecialchars($tf['customer_name'] ?? ($tf['business_name'] ?? ($isCustomerType ? 'Customer' : 'Lead')));
                    $personName = htmlspecialchars($tf['contact_person'] ?? ($tf['contact_name'] ?? ($tf['mobile_no'] ?? '')));
                    $thName = htmlspecialchars($tf['through_name'] ?? 'Call');
                    $reasonName = htmlspecialchars($tf['reason_name'] ?? '');
                    $remText = htmlspecialchars($tf['remarks'] ?? '');

                    if ($isCustomerType) {
                      $viewUrl = !empty($tf['customer_id']) ? (SITE_URL . 'customer-followup?customer_id=' . encrypt_id($tf['customer_id'])) : (SITE_URL . 'customer-followup');
                    } elseif ($isSuperadminLead) {
                      $viewUrl = !empty($tf['lead_id']) ? (SITE_URL . 'lead/followup-history/' . encrypt_id($tf['lead_id'])) : (SITE_URL . 'lead/followup-history');
                    } else {
                      $viewUrl = !empty($tf['lead_id']) ? (SITE_URL . 'company-lead/view/' . encrypt_id($tf['lead_id'])) : (SITE_URL . 'company-lead-followup');
                    }

                    // Badge text & class
                    if ($isCustomerType) {
                      $badgeText = 'Customer';
                      $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                    } elseif ($isSuperadminLead) {
                      $badgeText = 'Superadmin Lead';
                      $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                    } else {
                      $badgeText = 'Lead';
                      $badgeClass = 'bg-info-subtle text-info border border-info-subtle';
                    }

                    // Icon and background highlight based on category
                    $isOverdue = ($timingLabel === 'Overdue');
                    $isToday = ($timingLabel === 'Today');
                    $itemBgClass = $isOverdue ? 'bg-danger-subtle bg-opacity-10' : ($isToday ? 'bg-warning-subtle bg-opacity-10' : '');
                    $iconName = $isOverdue ? 'alert-circle' : ($isToday ? 'calendar-clock' : 'calendar');
                    $iconColor = $isOverdue ? 'text-danger bg-danger-subtle' : ($isToday ? 'text-warning bg-warning-subtle' : 'text-info bg-info-subtle');
                  ?>
                    <a href="<?= $viewUrl ?>" class="dropdown-item py-2 px-3 d-flex align-items-start border-bottom text-wrap <?= $itemBgClass ?>">
                      <div class="avatar avatar-sm <?= $iconColor ?> me-2 flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i data-lucide="<?= $iconName ?>" class="fs-14"></i>
                      </div>
                      <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                          <div class="d-flex align-items-center gap-1 overflow-hidden">
                            <span class="badge <?= $badgeClass ?> fs-9 px-1 py-0.5 rounded">
                              <?= $badgeText ?>
                            </span>
                            <p class="mb-0 fw-bold text-dark fs-12 text-truncate" style="max-width: 135px;" title="<?= $titleName ?>">
                              <?= $titleName ?>
                            </p>
                          </div>
                          <span class="badge <?= $timingBadge ?> fs-10 px-2 py-0.5 rounded-pill flex-shrink-0"><?= $timingLabel ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-muted fs-11">
                          <span class="text-truncate" style="max-width: 140px;">
                            <?= $reasonName ? $reasonName : ($personName ?: '-') ?>
                          </span>
                          <span class="text-nowrap fs-10 text-muted"><i data-lucide="clock" class="fs-10 align-middle"></i> <?= $dateText ?></span>
                        </div>
                        <?php if ($remText): ?>
                          <div class="text-secondary small text-truncate mt-1" style="font-size: 11px;" title="<?= $remText ?>">
                            <i data-lucide="message-square" class="fs-10 align-middle me-1"></i><?= $remText ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <!-- Footer with quick links -->
              <div class="px-3 py-2 border-top text-center bg-light-subtle d-flex justify-content-between align-items-center">
                <?php if ($isSuperadmin): ?>
                  <a href="<?= SITE_URL ?>lead/followup-history" class="text-primary small fw-semibold text-decoration-none">
                    Lead Follow-ups <i data-lucide="arrow-right" class="fs-12 align-middle"></i>
                  </a>
                  <a href="<?= SITE_URL ?>customer-followup" class="text-success small fw-semibold text-decoration-none">
                    Customer Follow-ups <i data-lucide="arrow-right" class="fs-12 align-middle"></i>
                  </a>
                <?php else: ?>
                  <a href="<?= SITE_URL ?>company-lead-followup" class="text-primary small fw-semibold text-decoration-none">
                    Lead Follow-ups <i data-lucide="arrow-right" class="fs-12 align-middle"></i>
                  </a>
                  <a href="<?= SITE_URL ?>customer-followup" class="text-success small fw-semibold text-decoration-none">
                    Customer Follow-ups <i data-lucide="arrow-right" class="fs-12 align-middle"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="dropdown dropup">
            <button class="btn btn-light rounded-circle p-0 avatar avatar-md" data-bs-toggle="dropdown">
              <a href="<?= SITE_URL ?>"><img src="<?= $headerUserProfileImg ?>" alt="User" class="rounded-circle w-100 h-100" style="object-fit: cover;"></a>
            </button>
            <ul class="dropdown-menu setting_dropdown w-100 shadow-lg border mb-2 pb-0 pt-2 rounded-3">
              <li><a class='dropdown-item' href='javascript:void(0)'><i data-lucide="user" class="me-2 fs-16"></i>Current User : <?php echo $_SESSION['username'] ?? '' ?></a></li>
              <li><a class='dropdown-item' href='<?= SITE_URL ?>'><i data-lucide="star" class="me-2 fs-16"></i>Special Permission</a></li>
              <li><a class='dropdown-item' href='<?= SITE_URL ?>profile'><i data-lucide="user" class="me-2 fs-16"></i> Profile</a></li>
              <li><a class="dropdown-item" href="<?= SITE_URL ?>"><i data-lucide="phone" class="me-2 fs-16"></i> Web Apis</a></li>
              <li><a class="dropdown-item" href="<?= SITE_URL ?>"><i data-lucide="server" class="me-2 fs-16"></i> Application Info</a></li>
              <li><a class='dropdown-item' href='<?= SITE_URL ?>'><i data-lucide="info" class="me-2 fs-16"></i> Blocked IP</a></li>
              <li><a class="dropdown-item" href="<?= SITE_URL ?>"><i data-lucide="database" class="me-2 fs-16"></i> Database Backup</a></li>
              <li><a class='dropdown-item' href='<?= SITE_URL ?>'><i data-lucide="key" class="me-2 fs-16"></i> Encrypted Key</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>logout"><i data-lucide="log-out" class="me-2 fs-16"></i> Logout</a>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <!-- Mobile Menu -->
      <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu"
        aria-labelledby="mobileMenuLabel">
        <div class="offcanvas-header border-bottom">
            <a href="<?= $homeUrl ?>"><img src="<?= $headerCompanyLogo ?>" alt="Logo" class="logo"></a>
          <button type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="Close">
          </button>
        </div>
        <div class="offcanvas-body p-0">
          <div class="list-group list-group-flush">
            <?php foreach ($menuTree as $menu): ?>
              <?php if (!empty($menu['sub_menu'])): ?>
                <div>
                  <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mobiledashboardMenu<?= $menu['id'] ?>">
                    <span><?= htmlspecialchars($menu['name']) ?></span>
                    <i data-lucide="chevron-down"></i>
                  </button>
                  <div class="collapse" id="mobiledashboardMenu<?= $menu['id'] ?>">
                    <?php foreach ($menu['sub_menu'] as $subMenu): ?>
                      <a href="<?= SITE_URL . '' . $subMenu['route'] ?>" class="list-group-item list-group-item-action ps-5"><?= htmlspecialchars($subMenu['name']) ?></a>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php else: ?>
                <a href="<?= SITE_URL . '' . $menu['route'] ?>" class="list-group-item list-group-item-action py-3"><?= htmlspecialchars($menu['name']) ?></a>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php
            if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin') 
            {
            ?>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileSalesMenu">
                <span>Main Setting</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileSalesMenu">
                <a href="<?= SITE_URL ?>lead" class="list-group-item list-group-item-action ps-5">Leads</a>
                <a href="<?= SITE_URL ?>lead-status" class="list-group-item list-group-item-action ps-5">Lead Status</a>
                <a href="<?= SITE_URL ?>lead-source-of-inquiry" class="list-group-item list-group-item-action ps-5">Lead Source Of Inquiry</a>
                <a href="<?= SITE_URL ?>lead-employee" class="list-group-item list-group-item-action ps-5">Lead Employee</a>
                <a href="<?= SITE_URL ?>lead-followup-type" class="list-group-item list-group-item-action ps-5">Lead Followup Type</a>
                <a href="<?= SITE_URL ?>module" class="list-group-item list-group-item-action ps-5">Module</a>
                <a href="<?= SITE_URL ?>plan" class="list-group-item list-group-item-action ps-5">Plan</a>
                <a href="<?= SITE_URL ?>company-type" class="list-group-item list-group-item-action ps-5">Company Type</a>
                <a href="<?= SITE_URL ?>company" class="list-group-item list-group-item-action ps-5">Company</a>
                <a href="<?= SITE_URL ?>teamrole" class="list-group-item list-group-item-action ps-5">Team Role</a>
                <a href="<?= SITE_URL ?>team-person" class="list-group-item list-group-item-action ps-5">Team Person</a>
                <a href="<?= SITE_URL ?>company-lead" class="list-group-item list-group-item-action ps-5">Company Lead</a>
                <a href="<?= SITE_URL ?>company-lead-followup" class="list-group-item list-group-item-action ps-5">Company Lead Follow Up</a>
              </div>
            </div>
            <?php 
            } ?>
          </div>
        </div>
      </div>
      <div class="horizontal-menu bg-white border-bottom shadow-sm d-none d-lg-block mb-3">
        <div class="container-fluid">
          <nav class="navbar navbar-expand-lg navbar-light p-0">
            <div class="collapse navbar-collapse" id="horizontalMenuCollapse">
              <ul class="navbar-nav justify-content-center w-100 gap-2">

                <?php foreach ($menuTree as $menu): ?>
                  <?php if (!empty($menu['sub_menu'])): ?>
                    <li class="nav-item dropdown">
                      <a
                        class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                        href="#"
                        id="menuDropdown<?= $menu['id'] ?>"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <span class="nav-text">
                          <?= htmlspecialchars($menu['name']) ?>
                        </span>
                      </a>
                      <ul
                        class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu"
                        aria-labelledby="menuDropdown<?= $menu['id'] ?>">
                        <?php foreach ($menu['sub_menu'] as $subMenu): ?>
                          <li>
                            <a
                              class="dropdown-item sub-link"
                              href="<?= SITE_URL . '' . $subMenu['route'] ?>">
                              <?= htmlspecialchars($subMenu['name']) ?>
                            </a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </li>
                  <?php else: ?>
                    <li class="nav-item">
                      <a class="nav-link" href="<?= SITE_URL . '' . $menu['route'] ?>">
                        <span class="nav-text">
                          <?= htmlspecialchars($menu['name']) ?>
                        </span>
                      </a>
                    </li>
                  <?php endif; ?>
                <?php endforeach; ?>
                <?php 
                  if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin') 
                  {
                     ?>
                    <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="settingDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="nav-text">Main Setting</span>
                      </a>
                      <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="settingDropdown">
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>lead'>Leads</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>lead-status'>Lead Status</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>lead-source-of-inquiry'>Lead Source Of Inquiry</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>lead-employee'>Lead Employee</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>lead-followup-type'>Lead Followup Type</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>module'>Module</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>plan'>Plan</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>company-type'>Company Type</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>company'>Company</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>teamrole'>Team Role</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>team-person'>Team Person</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>company-lead'>Company Lead</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>company-lead-followup'>Company Lead Follow Up</a></li>
                      </ul>
                    </li>
                     <?php 
                  }
                ?>
                
              </ul>
            </div>
          </nav>
        </div>
      </div>

      <main class="page-content">