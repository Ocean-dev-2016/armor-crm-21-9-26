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

// Company Header Logo (with default fallback)
$defaultCompanyLogo = SITE_URL . 'assets/image/crm_logo.png';
$headerCompanyLogo = $defaultCompanyLogo;
if (!empty($headerCompany['header_image']) && file_exists(BASE_PATH . '/uploads/company/' . $headerCompany['header_image'])) {
  $headerCompanyLogo = SITE_URL . 'uploads/company/' . $headerCompany['header_image'];
}

// Company Favicon (with default fallback)
$headerFavicon = $defaultCompanyLogo;
if (!empty($headerCompany['favicon']) && file_exists(BASE_PATH . '/uploads/company/' . $headerCompany['favicon'])) {
  $headerFavicon = SITE_URL . 'uploads/company/' . $headerCompany['favicon'];
} elseif ($headerCompanyLogo !== $defaultCompanyLogo) {
  $headerFavicon = $headerCompanyLogo;
}

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
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

include BASE_PATH . '/include/css.php';
?>

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

          <a href="<?= SITE_URL ?>"><img src="<?= $headerCompanyLogo ?>" alt="Logo" class="logo"></a>
        </div>

        <div class="d-flex align-items-center gap-3">
          <button id="theme-switcher" class="btn btn-icon btn-light rounded-circle">
            <i data-lucide="moon" class="fs-20"></i>
          </button>


          <!-- Notification Dropdown -->
          <div class="dropdown">
            <button class="btn btn-icon btn-light rounded-circle position-relative" data-bs-toggle="dropdown"
              aria-expanded="false">
              <i data-lucide="bell" class="fs-20"></i>
              <span
                class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                <span class="visually-hidden">New alerts</span>
              </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end border shadow-lg mt-2 p-0 dropdown-notification">
              <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <h6 class="mb-0">Notifications</h6>
                <a href="#" class="text-primary small fw-medium">Clear All</a>
              </div>
              <div class="overflow-auto notification-list">
                <a href="#" class="dropdown-item py-2 px-3 d-flex align-items-start border-bottom text-wrap">
                  <div class="avatar avatar-sm bg-primary-subtle text-primary me-3 flex-shrink-0 rounded-circle">
                    <i data-lucide="mail" class="fs-14"></i>
                  </div>
                  <div>
                    <p class="mb-1 fw-medium text-dark">You have 3 new messages</p>
                    <small class="text-muted">2 minutes ago</small>
                  </div>
                </a>
                <a href="#" class="dropdown-item py-2 px-3 d-flex align-items-start border-bottom text-wrap">
                  <div class="avatar avatar-sm bg-success-subtle text-success me-3 flex-shrink-0 rounded-circle">
                    <i data-lucide="check-circle" class="fs-14"></i>
                  </div>
                  <div>
                    <p class="mb-1 fw-medium text-dark">Your report is ready</p>
                    <small class="text-muted">1 hour ago</small>
                  </div>
                </a>
                <a href="#" class="dropdown-item py-2 px-3 d-flex align-items-start text-wrap">
                  <div class="avatar avatar-sm bg-warning-subtle text-warning me-3 flex-shrink-0 rounded-circle">
                    <i data-lucide="alert-triangle" class="fs-14"></i>
                  </div>
                  <div>
                    <p class="mb-1 fw-medium text-dark">System update scheduled</p>
                    <small class="text-muted">Yesterday</small>
                  </div>
                </a>
              </div>
              <div class="px-3 py-2 border-top text-center">
                <a href="#" class="text-primary small fw-medium text-decoration-none">View All Notifications</a>
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
            <a href="<?= SITE_URL ?>"><img src="<?= $headerCompanyLogo ?>" alt="Logo" class="logo"></a>
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
                <a href="<?= SITE_URL ?>module" class="list-group-item list-group-item-action ps-5">Module</a>
                <a href="<?= SITE_URL ?>plan" class="list-group-item list-group-item-action ps-5">Plan</a>
                <a href="<?= SITE_URL ?>company-type" class="list-group-item list-group-item-action ps-5">Company Type</a>
                <a href="<?= SITE_URL ?>company" class="list-group-item list-group-item-action ps-5">Company</a>
                <a href="<?= SITE_URL ?>teamrole" class="list-group-item list-group-item-action ps-5">Team Role</a>
                <a href="<?= SITE_URL ?>team-person" class="list-group-item list-group-item-action ps-5">Team Person</a>
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
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>module'>Module</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>plan'>Plan</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>company-type'>Company Type</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>company'>Company</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>teamrole'>Team Role</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>team-person'>Team Person</a></li>
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