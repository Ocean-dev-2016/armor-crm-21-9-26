<?php
session_start();
require_once __DIR__ . '/../conn/db.php';
if(empty($_SESSION))
  {
    header("Location: " . SITE_URL . "login");
    exit;
  }
require_once __DIR__ . '/../conn/dbqry.php';

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

          <img src="<?=SITE_URL ?>assets/image/crm_logo.png" alt="Logo" class="logo">
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
              <img src="assets/images/user-8.jpg" alt="User" class="rounded-circle w-100 h-100">
            </button>
            <ul class="dropdown-menu setting_dropdown w-100 shadow-lg border mb-2 pb-0 pt-2 rounded-3">
              <li><a class='dropdown-item' href='<?= SITE_URL ?>'><i data-lucide="star" class="me-2 fs-16"></i>Special Permission</a></li>
              <li><a class='dropdown-item' href='<?= SITE_URL ?>'><i data-lucide="user" class="me-2 fs-16"></i> Profile</a></li>
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
          <img src="<?=SITE_URL ?>assets/image/crm_logo.png" alt="Logo" class="logo">
          <button type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="Close">
          </button>
        </div>
        <div class="offcanvas-body p-0">
          <div class="list-group list-group-flush">
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobiledashboardMenu">
                <span>Dashboard</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobiledashboardMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">MIS Dash</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Tracking Dash</a>
              </div>
            </div>
            <div class="mobile-menu-item">
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileSalesMenu">
                <span>Sales & Marketing</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileSalesMenu">
                <a href="<?= SITE_URL ?>"
                  class="list-group-item list-group-item-action ps-5">
                  Quotation
                </a>
                <a href="<?= SITE_URL ?>"
                  class="list-group-item list-group-item-action ps-5">
                  Manually A/C Receivable Import
                </a>
                <a href="<?= SITE_URL ?>"
                  class="list-group-item list-group-item-action ps-5">
                  Customer
                </a>
                <a href="<?= SITE_URL ?>"
                  class="list-group-item list-group-item-action ps-5">
                  Prospect Customer
                </a>
                <a href="<?= SITE_URL ?>"
                  class="list-group-item list-group-item-action ps-5">
                  Price List
                </a>
                <a href="<?= SITE_URL ?>"
                  class="list-group-item list-group-item-action ps-5">
                  Customer Visit
                </a>
                <a href="<?= SITE_URL ?>"
                  class="list-group-item list-group-item-action ps-5">
                  Manage Complain
                </a>
                <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center ps-5"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#mobileChannelPartner">
                  Channel Partner
                  <i data-lucide="chevron-down" class="fs-16"></i>
                </button>
                <div class="collapse" id="mobileChannelPartner">

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    Channel Partner
                  </a>

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    Customer
                  </a>

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    My Stock
                  </a>

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    CP Stock Overview
                  </a>

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    CP Sales Report
                  </a>

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    CP Ledger
                  </a>

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    CP Customer Ledger
                  </a>

                  <a href="<?= SITE_URL ?>"
                    class="list-group-item list-group-item-action ps-6">
                    CP Receive Payment
                  </a>

                </div>

              </div>
            </div>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileOrderMenu">
                <span>Order History</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileOrderMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">All Orders</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Channel Partner Portal Orders</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5"> Channel Partner Orders</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Pending Payments</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Receive Payments</a>
              </div>
            </div>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileHrMenu">
                <span>HR</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileHrMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Sales Person</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Manage Expense</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Manage Attendance</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Today's Followup</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Future Followup</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Leave Request</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Master Route Planning</a>
              </div>
            </div>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileMasterMenu">
                <span>Master</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileMasterMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Category</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Sub Category</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Department</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Designation/a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Tax</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Variant</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Product</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Followup Reason</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Industry Type</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Company Type</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Company Master</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Brand Master</a>
              </div>
            </div>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileSubMasterMenu">
                <span>Sub Master</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileSubMasterMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Country</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">State</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">City</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Route</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Expense Category</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Expense Sub Category</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Leave Type</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Complain Category</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Complain Sub Category</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Source Of Inquiry</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Zone</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Transport By</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Transporter Detail</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Dispatch Order Status</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Visit Purpose Master</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Terms & Condition</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Quotation/PI Suggested Products</a>
              </div>
            </div>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileUtilityMenu">
                <span>Utility</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileUtilityMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">News</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Banner</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">System User</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Push Notification</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Document List</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Document Type</a>
              </div>
            </div>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileReportsMenu">
                <span>Customer Reports</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileReportsMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Inquiry Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Order Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Customer Wise Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Active/Deactive Customer Report</a>
              </div>
            </div>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileCustoReportsMenu">
                <span>Sales Team Reports</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileCustoReportsMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Daily Sales Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Expense Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Attendance Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Visit Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Followup Pending Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Sales person Wise Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Sales VS Plan Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Consultant Approval Process Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Payment Followup Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Employee Visit KRA Report</a>
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Assign KRA</a>
              </div>
            </div>
            <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action py-3">Chat</a>
            <div>
              <button class="list-group-item list-group-item-action w-100 d-flex justify-content-between align-items-center py-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mobileRemarkReportsMenu">
                <span>Remark Analysis Reports</span>
                <i data-lucide="chevron-down"></i>
              </button>
              <div class="collapse" id="mobileRemarkReportsMenu">
                <a href="<?= SITE_URL ?>" class="list-group-item list-group-item-action ps-5">Remark Wise Report</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="horizontal-menu bg-white border-bottom shadow-sm d-none d-lg-block">
        <div class="container-fluid">
          <nav class="navbar navbar-expand-lg navbar-light p-0">
            <div class="collapse navbar-collapse" id="horizontalMenuCollapse">
              <ul class="navbar-nav justify-content-center w-100 gap-2">
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="dashboardDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Dashboard</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="dashboardDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>MIS Dash</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Tracking Dash</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="salesDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Sales & Marketing</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="salesDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Quotation</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Manually A/C Receivable Import</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Customer</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Prospect Customer</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Price List</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Customer Visit</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Manage Complain</a></li>
                    <li class="dropend">
                      <a class="dropdown-item sub-link dropdown-toggle d-flex justify-content-between align-items-center"
                        href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        Channel Partner
                      </a>
                      <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu">
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Channel Partner</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Customer</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>My Stock</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>CP Stock Overview</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>CP Sales Report</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>CP Ledger</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>CP Customer Ledger</a></li>
                        <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>CP Receive Payment</a></li>
                      </ul>
                    </li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="orderDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Order History</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="orderDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>All Orders</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Channel Partner Portal Orders</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Channel Partner Orders</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Pending Payments</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Receive Payments</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="hrDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">HR</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="hrDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Sales Person</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Manage Expense</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Manage Attendance</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Today's Followup</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Future Followup</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Leave Request</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Master Route Planning</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="masterDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Master</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="masterDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Category</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Sub Category</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Department</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Designation</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Tax</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Variant</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Product</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Followup Reason</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Industry Type</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Company Type</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Company Master</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Brand Master</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="submasterDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Sub Master</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="submasterDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>country'>Country</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>state'>State</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>city'>City</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Route</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Expense Category</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Expense Sub Category</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Leave Type</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Complain Category</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Complain Sub Category</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Source Of Inquiry</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Zone</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Transport By</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Transporter Detail</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Dispatch Order Status</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Visit Purpose Master</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Terms & Condition</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Quotation/PI Suggested Products</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="utilityDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Utility</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="utilityDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>News</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Banner</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>System User</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Push Notification</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Document List</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Document Type</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="cusreportDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Customer Reports</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="cusreportDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Inquiry Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Order Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Customer Wise Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Active/Deactive Customer Report</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="teamreportDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Sales Team Reports</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="teamreportDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Daily Sales Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Expense Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Attendance Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Visit Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Followup Pending Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Sales Person Wise Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Sales Vs Plan Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Consultant Approval Process Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Payment Followup Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Employee Visit KRA Report</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Assign KRA</a></li>
                  </ul>
                </li>
                <li class="nav-item">
                  <a class="nav-link">
                    <span class="nav-text">Chat</span>
                  </a>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="remarkDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Remark Analysis Report</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="remarkDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>'>Remark Wise Report</a></li>
                  </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="settingDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-text">Setting</span>
                  </a>
                  <ul class="dropdown-menu shadow-lg border-0 mt-0 sidebar-submenu" aria-labelledby="settingDropdown">
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>module'>Module</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>plan'>Plan</a></li>
                    <li><a class='dropdown-item sub-link' href='<?= SITE_URL ?>company'>Company</a></li>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
        </div>
      </div>

      <main class="page-content">