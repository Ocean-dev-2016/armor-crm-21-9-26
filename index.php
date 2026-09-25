<?php
include 'include/header.php';
?>
<!-- Top Global Filter: SEARCH Bar (1 Line Layout & Responsive) -->
<div class="card mb-4 border-0 shadow-sm">
  <div class="card-body p-3 dashboard-search-scroll">
    <h6 class="mb-0 fw-bold text-secondary tracking-wide d-flex align-items-center gap-1">
      <i data-lucide="search" class="fs-18 text-primary"></i> Search
    </h6>
    <div class="dashboard-search-container">
      <div class="dashboard-search-item">
        <label class="form-label fs-12 text-muted mb-1 text-nowrap">From Date</label>
        <input type="date" class="form-control form-control-sm" id="global_from_date" value="2026-09-01">
      </div>
      <div class="dashboard-search-item">
        <label class="form-label fs-12 text-muted mb-1 text-nowrap">To Date</label>
        <input type="date" class="form-control form-control-sm" id="global_to_date" value="2026-09-30">
      </div>
      <div class="dashboard-search-item">
        <label class="form-label fs-12 text-muted mb-1 text-nowrap">Customer</label>
        <select class="form-select" id="global_customer">
          <option value="">Select Customer</option>
          <option value="1">Infinity Infotech Parks Ltd. - C</option>
          <option value="2">MUKESH JARGAR - C</option>
          <option value="3">TechCorp Inc</option>
          <option value="4">Bright Future Ltd.</option>
        </select>
      </div>
      <div class="dashboard-search-item">
        <label class="form-label fs-12 text-muted mb-1 text-nowrap">Sales Executive</label>
        <select class="form-select" id="global_sales_executive">
          <option value="">Select Executive</option>
          <option value="1">Kaushik Mondal</option>
          <option value="2">Vipul Patel</option>
          <option value="3">Ankit Pathak</option>
          <option value="4">Rahul Sharma</option>
        </select>
      </div>
    </div>
  </div>
</div>

<!-- TODAY'S FOLLOWUP Section -->
<div class="card mb-4 border-0 shadow-sm overflow-hidden">
  <!-- Header Banner -->
  <div class="card-header bg-primary text-white py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2">
      <h5 class="mb-0 fw-bold text-white tracking-wide">
        Today's Followup
      </h5>
    </div>
  </div>

  <div class="card-body p-4">
    <!-- Sub-header Counters & Date Display -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
          <span class="text-primary fw-bold fs-14 me-1" id="badge_today_total">2</span> Total
        </span>
        <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
          <span class="text-warning fw-bold fs-14 me-1" id="badge_today_pending">2</span> Pending
        </span>
        <span class="badge bg-light text-dark border px-3 py-2 fs-13 fw-semibold">
          <span class="text-success fw-bold fs-14 me-1" id="badge_today_responded">0</span> Responded
        </span>
      </div>
      <div>
        <span class="badge bg-light text-secondary border px-3 py-2 fs-12 d-flex align-items-center gap-1">
          <i data-lucide="calendar" class="fs-14 text-primary"></i> 25 Sep 2026, Friday
        </span>
      </div>
    </div>

    <!-- Responsive Table with Pagination -->
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0 text-nowrap" id="tableTodayFollowup">
        <thead class="table-light fs-12 text-secondary">
          <tr>
            <th class="text-center" style="width: 50px;">NO.</th>
            <th>CUSTOMER NAME</th>
            <th>PERSON NAME</th>
            <th>MOBILE NO.</th>
            <th>SALES PERSON</th>
            <th>DATE & TIME</th>
            <th>DESCRIPTION</th>
            <th class="text-center">THROUGH</th>
            <th class="text-center">TYPE</th>
            <th class="text-center">ENTRY TYPE</th>
            <th class="text-center">STATUS</th>
            <th class="text-center" style="width: 80px;">ACTION</th>
          </tr>
        </thead>
        <tbody class="fs-13" id="todayFollowupTbody">
          <tr class="followup-row" data-customer="1" data-executive="1" data-date="2026-09-25" data-status="PENDING">
            <td class="text-center fw-medium text-muted">1</td>
            <td class="fw-semibold text-dark">Infinity Infotech Parks Ltd. - C</td>
            <td class="text-secondary">Debajyoti Guha</td>
            <td>
              <a href="tel:9830421660" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                <i data-lucide="phone" class="fs-13"></i> 9830421660
              </a>
            </td>
            <td class="text-secondary">Kaushik Mondal</td>
            <td class="text-muted">25-09-2026 12:32 PM</td>
            <td class="text-dark fw-medium">NEED TO VISIT</td>
            <td class="text-center">
              <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">CALL</span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-secondary border px-2 py-1">Customer</span>
            </td>
            <td class="text-center text-secondary">Sales App</td>
            <td class="text-center">
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">PENDING</span>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Details">
                <i data-lucide="eye" class="fs-14"></i>
              </button>
            </td>
          </tr>
          <tr class="followup-row" data-customer="2" data-executive="2" data-date="2026-09-24" data-status="PENDING">
            <td class="text-center fw-medium text-muted">2</td>
            <td class="fw-semibold text-dark">MUKESH JARGAR - C</td>
            <td class="text-secondary">MUKESH JARGAR</td>
            <td>
              <a href="tel:9425387157" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                <i data-lucide="phone" class="fs-13"></i> 9425387157
              </a>
            </td>
            <td class="text-secondary">Vipul Patel</td>
            <td class="text-muted">24-09-2026 04:52 PM</td>
            <td class="text-dark fw-medium">CALLING FOR REQUIREMENTS</td>
            <td class="text-center">
              <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">CALL</span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-secondary border px-2 py-1">Customer</span>
            </td>
            <td class="text-center text-secondary">Sales App</td>
            <td class="text-center">
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">PENDING</span>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Details">
                <i data-lucide="eye" class="fs-14"></i>
              </button>
            </td>
          </tr>
          <tr class="followup-row" data-customer="3" data-executive="3" data-date="2026-09-20" data-status="PENDING">
            <td class="text-center fw-medium text-muted">3</td>
            <td class="fw-semibold text-dark">TechCorp Solutions Inc.</td>
            <td class="text-secondary">Sarah Jenkins</td>
            <td>
              <a href="tel:9821034455" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                <i data-lucide="phone" class="fs-13"></i> 9821034455
              </a>
            </td>
            <td class="text-secondary">Ankit Pathak</td>
            <td class="text-muted">20-09-2026 02:15 PM</td>
            <td class="text-dark fw-medium">QUOTATION DISCUSSION</td>
            <td class="text-center">
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">VISIT</span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-secondary border px-2 py-1">Customer</span>
            </td>
            <td class="text-center text-secondary">CRM Web</td>
            <td class="text-center">
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">PENDING</span>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Details">
                <i data-lucide="eye" class="fs-14"></i>
              </button>
            </td>
          </tr>
          <tr class="followup-row" data-customer="4" data-executive="4" data-date="2026-09-18" data-status="RESPONDED">
            <td class="text-center fw-medium text-muted">4</td>
            <td class="fw-semibold text-dark">Bright Future Ltd.</td>
            <td class="text-secondary">Emma Watson</td>
            <td>
              <a href="tel:9712045566" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                <i data-lucide="phone" class="fs-13"></i> 9712045566
              </a>
            </td>
            <td class="text-secondary">Rahul Sharma</td>
            <td class="text-muted">18-09-2026 03:40 PM</td>
            <td class="text-dark fw-medium">PAYMENT FOLLOWUP</td>
            <td class="text-center">
              <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">CALL</span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-secondary border px-2 py-1">Customer</span>
            </td>
            <td class="text-center text-secondary">Sales App</td>
            <td class="text-center">
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">RESPONDED</span>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Details">
                <i data-lucide="eye" class="fs-14"></i>
              </button>
            </td>
          </tr>
          <tr class="followup-row" data-customer="1" data-executive="3" data-date="2026-09-12" data-status="PENDING">
            <td class="text-center fw-medium text-muted">5</td>
            <td class="fw-semibold text-dark">Apex Global Enterprise</td>
            <td class="text-secondary">Vikram Malhotra</td>
            <td>
              <a href="tel:9845012399" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                <i data-lucide="phone" class="fs-13"></i> 9845012399
              </a>
            </td>
            <td class="text-secondary">Priya Patel</td>
            <td class="text-muted">12-09-2026 05:10 PM</td>
            <td class="text-dark fw-medium">SAMPLE DELIVERY CHECK</td>
            <td class="text-center">
              <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">CALL</span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-secondary border px-2 py-1">Customer</span>
            </td>
            <td class="text-center text-secondary">Sales App</td>
            <td class="text-center">
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">PENDING</span>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Details">
                <i data-lucide="eye" class="fs-14"></i>
              </button>
            </td>
          </tr>
          <tr class="followup-row" data-customer="2" data-executive="1" data-date="2026-09-08" data-status="PENDING">
            <td class="text-center fw-medium text-muted">6</td>
            <td class="fw-semibold text-dark">Delta Logistics Park</td>
            <td class="text-secondary">Rajesh Khanna</td>
            <td>
              <a href="tel:9810234567" class="text-primary text-decoration-none fw-medium d-flex align-items-center gap-1">
                <i data-lucide="phone" class="fs-13"></i> 9810234567
              </a>
            </td>
            <td class="text-secondary">Kaushik Mondal</td>
            <td class="text-muted">08-09-2026 05:45 PM</td>
            <td class="text-dark fw-medium">CONTRACT SIGNING</td>
            <td class="text-center">
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">VISIT</span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-secondary border px-2 py-1">Customer</span>
            </td>
            <td class="text-center text-secondary">CRM Web</td>
            <td class="text-center">
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">PENDING</span>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-primary btn-icon rounded-circle p-1" title="View Details">
                <i data-lucide="eye" class="fs-14"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination & Entries Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3 border-top">
      <div class="text-muted fs-13">
        Showing <span id="followupPageStart">1</span> to <span id="followupPageEnd">5</span> of <span id="followupTotalCount">6</span> entries
      </div>
      <nav aria-label="Followup Table Pagination">
        <ul class="pagination pagination-sm mb-0" id="followupPagination">
          <li class="page-item disabled" id="followupPrevPage">
            <a class="page-link" href="javascript:void(0);" aria-label="Previous">
              <span aria-hidden="true">&laquo; Prev</span>
            </a>
          </li>
          <li class="page-item active" data-page="1"><a class="page-link" href="javascript:void(0);">1</a></li>
          <li class="page-item" data-page="2"><a class="page-link" href="javascript:void(0);">2</a></li>
          <li class="page-item" id="followupNextPage">
            <a class="page-link" href="javascript:void(0);" aria-label="Next">
              <span aria-hidden="true">Next &raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<!-- Attendance Statistic Section -->
<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="calendar-check" class="fs-18 align-middle me-1 text-primary"></i> Attendance Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12">
          Current Overview
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Attendance Statistics (5 Cards in 1 Single Line) -->
        <div class="row row-cols-5 g-2 mb-4 flex-nowrap overflow-auto pb-1">
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="users" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Sales Exec</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_exec">83</h5>
              </div>
            </div>
          </div>
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="smartphone" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Mobile Users</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_use_mobile">56/0</h5>
              </div>
            </div>
          </div>
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-dark-subtle text-dark d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="calendar-check" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Total Att.</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_attendance">78</h5>
              </div>
            </div>
          </div>
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="log-in" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">In (Present)</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_in">72</h5>
              </div>
            </div>
          </div>
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="log-out" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Out</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_out">6</h5>
              </div>
            </div>
          </div>
        </div>
    
        <!-- Attendance Chart Section -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Attendance Performance (Daily)
            </h6>
          </div>
          <div id="attendance-statistic-chart" class="dashboard-chart-box-280"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Visit Statistic Section (Right Side) -->
  <div class="col-md-6 mb-3">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="map-pin" class="fs-18 align-middle me-1 text-primary"></i> Visit Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12" id="visit_chart_badge">
          September 2026
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Total Visits, Completed, In-Progress -->
        <div class="row g-3 mb-4">
          <div class="col-sm-4 col-12">
            <div class="p-3 bg-light rounded border d-flex align-items-center gap-3">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                <i data-lucide="navigation" class="fs-18"></i>
              </div>
              <div>
                <span class="d-block text-muted fs-12 fw-medium">Total Visits</span>
                <h4 class="mb-0 fw-bold text-dark" id="stat_total_visit">13,247</h4>
              </div>
            </div>
          </div>
          <div class="col-sm-4 col-6">
            <div class="p-3 bg-light rounded border d-flex align-items-center gap-3">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center">
                <i data-lucide="check-circle-2" class="fs-18"></i>
              </div>
              <div>
                <span class="d-block text-muted fs-12 fw-medium">Completed</span>
                <h4 class="mb-0 fw-bold text-dark" id="stat_completed_visit">12,890</h4>
              </div>
            </div>
          </div>
          <div class="col-sm-4 col-6">
            <div class="p-3 bg-light rounded border d-flex align-items-center gap-3">
              <div class="avatar avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                <i data-lucide="clock" class="fs-18"></i>
              </div>
              <div>
                <span class="d-block text-muted fs-12 fw-medium">Scheduled</span>
                <h4 class="mb-0 fw-bold text-dark" id="stat_pending_visit">357</h4>
              </div>
            </div>
          </div>
        </div>

        <!-- Visit Chart Section -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Visit Performance (Daily)
            </h6>
          </div>
          <div id="visit-statistic-chart" class="dashboard-chart-box-280"></div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Quotation Statistic Section -->
  <div class="col-md-6 mb-3">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="file-text" class="fs-18 align-middle me-1 text-primary"></i> Quotation Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12">
          Annual Overview
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Quotation Statistics (4 Cards in 1 Single Line) -->
        <div class="row row-cols-4 g-2 mb-4 flex-nowrap overflow-auto pb-1">
          <!-- Total Quotation -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="file-text" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Total Quotation</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_quotation">1488</h5>
                <span class="fs-11 fw-semibold text-primary d-block text-truncate" id="stat_total_quotation_amt">₹587.9M</span>
              </div>
            </div>
          </div>
  
          <!-- Approved Quotation -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="check-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Approved</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_approved_quotation">524</h5>
                <span class="fs-11 fw-semibold text-success d-block text-truncate" id="stat_approved_quotation_amt">₹308.4M</span>
              </div>
            </div>
          </div>
  
          <!-- Quotation To Order -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="shopping-cart" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">To Order</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_to_order_quotation">935</h5>
                <span class="fs-11 fw-semibold text-success d-block text-truncate" id="stat_to_order_quotation_amt">₹260.8M</span>
              </div>
            </div>
          </div>
  
          <!-- Lost Quotation -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="x-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Lost</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_lost_quotation">10</h5>
                <span class="fs-11 fw-semibold text-danger d-block text-truncate" id="stat_lost_quotation_amt">₹11.9M</span>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Quotation Chart Section (Styled like Top Performing Sources) -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Quotation Monthly Breakdown
            </h6>
          </div>
          <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
            <div id="quotation-statistic-chart" class="flex-grow-1 dashboard-breakdown-chart"></div>
            <div class="flex-grow-1 dashboard-breakdown-content">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-primary"></span>
                  <span class="text-dark fs-13 fw-medium">March</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="quot_break_1">254</span>
                  <span class="text-muted fs-12" id="quot_pct_1">(17.1%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-success"></span>
                  <span class="text-dark fs-13 fw-medium">February</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="quot_break_2">241</span>
                  <span class="text-muted fs-12" id="quot_pct_2">(16.2%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-info"></span>
                  <span class="text-dark fs-13 fw-medium">January</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="quot_break_3">220</span>
                  <span class="text-muted fs-12" id="quot_pct_3">(14.8%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-warning"></span>
                  <span class="text-dark fs-13 fw-medium">April</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="quot_break_4">192</span>
                  <span class="text-muted fs-12" id="quot_pct_4">(12.9%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-purple"></span>
                  <span class="text-dark fs-13 fw-medium">June</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="quot_break_5">173</span>
                  <span class="text-muted fs-12" id="quot_pct_5">(11.6%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-secondary"></span>
                  <span class="text-dark fs-13 fw-medium">Others</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="quot_break_6">408</span>
                  <span class="text-muted fs-12" id="quot_pct_6">(27.4%)</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Order Statistic Section -->
  <div class="col-md-6 mb-3">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="shopping-bag" class="fs-18 align-middle me-1 text-primary"></i> Order Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12">
          Overview
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Order Statistics (5 Cards in 1 Single Line) -->
        <div class="row row-cols-5 g-2 mb-4 flex-nowrap overflow-auto pb-1">
          <!-- Total Order -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="shopping-bag" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Total Order</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_order">1963</h5>
                <span class="fs-11 fw-semibold text-primary d-block text-truncate" id="stat_total_order_amt">₹458.5M</span>
              </div>
            </div>
          </div>

          <!-- Pending Order -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="clock" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Pending</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_pending_order">102</h5>
                <span class="fs-11 fw-semibold text-warning d-block text-truncate" id="stat_pending_order_amt">₹33.8M</span>
              </div>
            </div>
          </div>

          <!-- Approved Order -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="check-circle-2" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Approved</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_approved_order">1855</h5>
                <span class="fs-11 fw-semibold text-success d-block text-truncate" id="stat_approved_order_amt">₹424.5M</span>
              </div>
            </div>
          </div>

          <!-- Cancelled Order -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="ban" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Cancelled</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_cancelled_order">0</h5>
                <span class="fs-11 fw-semibold text-muted d-block text-truncate" id="stat_cancelled_order_amt">₹0.00</span>
              </div>
            </div>
          </div>

          <!-- Disapproved Order -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="x-octagon" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Disapproved</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_disapproved_order">0</h5>
                <span class="fs-11 fw-semibold text-muted d-block text-truncate" id="stat_disapproved_order_amt">₹0.00</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Chart Section -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Order Performance (Monthly)
            </h6>
          </div>
          <div id="order-statistic-chart" class="dashboard-chart-box-250"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Followup Statistic Section -->
  <div class="col-md-6 mb-3">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="phone-forwarded" class="fs-18 align-middle me-1 text-primary"></i> Followup Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12">
          Yearly Overview
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Followup Statistics (4 Cards in 1 Single Line) -->
        <div class="row row-cols-4 g-2 mb-4 flex-nowrap overflow-auto pb-1">
          <!-- Total Followup -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="phone-forwarded" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Total Followup</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_followup">32,894</h5>
              </div>
            </div>
          </div>

          <!-- Today's Followup -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="calendar-check" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Today's</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_today_followup">2</h5>
              </div>
            </div>
          </div>

          <!-- Future Followup -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="clock" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Future</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_future_followup">159</h5>
              </div>
            </div>
          </div>

          <!-- Pending Followup -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="hourglass" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Pending</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_pending_followup">10,409</h5>
              </div>
            </div>
          </div>
        </div>

        <!-- Followup Chart Section (Gradient Spline Chart with theme styling) -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Followup Monthly Trends
            </h6>
          </div>
          <div id="followup-statistic-chart" class="dashboard-chart-box-250"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Leave Statistic Section -->
  <div class="col-md-6 mb-3">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="calendar-x-2" class="fs-18 align-middle me-1 text-primary"></i> Leave Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12">
          Leave Status
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Leave Statistics (5 Cards in 1 Single Line) -->
        <div class="row row-cols-5 g-2 mb-4 flex-nowrap overflow-auto pb-1">
          <!-- Total Leave -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="calendar-x-2" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Total Leave</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_leave">108</h5>
              </div>
            </div>
          </div>

          <!-- Generated -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="file-plus" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Generated</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_generated_leave">51</h5>
              </div>
            </div>
          </div>

          <!-- Accepted -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="check-check" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Accepted</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_accepted_leave">57</h5>
              </div>
            </div>
          </div>

          <!-- Rejected -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="x-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Rejected</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_rejected_leave">0</h5>
              </div>
            </div>
          </div>

          <!-- Cancel -->
          <div class="col dashboard-stat-col-5">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="slash" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Cancel</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_cancelled_leave">0</h5>
              </div>
            </div>
          </div>
        </div>

        <!-- Leave Chart Section (Modern Rounded Stacked Column Chart) -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Leave Monthly Distribution
            </h6>
          </div>
          <div id="leave-statistic-chart" class="dashboard-chart-box-250"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Expense Statistic Section -->
  <div class="col-md-6">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="receipt" class="fs-18 align-middle me-1 text-primary"></i> Expense Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12">
          Annual Status
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Expense Statistics (4 Cards in 1 Single Line) -->
        <div class="row row-cols-4 g-2 mb-4 flex-nowrap overflow-auto pb-1">
          <!-- Total Expense -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="receipt" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Total Expense</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_expense">5,681</h5>
              </div>
            </div>
          </div>

          <!-- Requested -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="send" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Requested</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_requested_expense">540</h5>
              </div>
            </div>
          </div>

          <!-- Pass -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="check-circle-2" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Pass</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_pass_expense">5,015</h5>
              </div>
            </div>
          </div>

          <!-- Reject -->
          <div class="col dashboard-stat-col-4">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="x-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Reject</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_reject_expense">126</h5>
              </div>
            </div>
          </div>
        </div>

        <!-- Expense Chart Section (Modern Bar Chart with Pass vs Requested) -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Expense Monthly Trends
            </h6>
          </div>
          <div id="expense-statistic-chart" class="dashboard-chart-box-250"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Complain Statistic Section -->
  <div class="col-md-6">
    <div class="card mb-4 h-100">
      <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
        <h6 class="mb-0 fw-bold text-secondary tracking-wide">
          <i data-lucide="alert-circle" class="fs-18 align-middle me-1 text-primary"></i> Complain Statistic
        </h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12">
          Resolution Rate
        </span>
      </div>
      <div class="card-body p-4">
        <!-- Metric Cards: Complain Statistics (6 Cards in 1 Single Line) -->
        <div class="row row-cols-6 g-2 mb-4 flex-nowrap overflow-auto pb-1">
          <!-- Total Complain -->
          <div class="col dashboard-stat-col-6">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="alert-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Total</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_total_complain">4</h5>
              </div>
            </div>
          </div>

          <!-- Generated -->
          <div class="col dashboard-stat-col-6">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="plus-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Generated</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_gen_complain">4</h5>
              </div>
            </div>
          </div>

          <!-- In process -->
          <div class="col dashboard-stat-col-6">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="loader" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">In Process</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_proc_complain">0</h5>
              </div>
            </div>
          </div>

          <!-- Complete -->
          <div class="col dashboard-stat-col-6">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="check-circle-2" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Complete</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_comp_complain">0</h5>
              </div>
            </div>
          </div>

          <!-- Reject -->
          <div class="col dashboard-stat-col-6">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="x-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Reject</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_rej_complain">0</h5>
              </div>
            </div>
          </div>

          <!-- Not Done -->
          <div class="col dashboard-stat-col-6">
            <div class="p-2 bg-light rounded border d-flex align-items-center gap-2 h-100">
              <div class="avatar avatar-md rounded bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center flex-shrink-0">
                <i data-lucide="help-circle" class="fs-18"></i>
              </div>
              <div class="overflow-hidden">
                <span class="d-block text-muted fs-11 fw-medium text-truncate">Not Done</span>
                <h5 class="mb-0 fw-bold text-dark" id="stat_notdone_complain">0</h5>
              </div>
            </div>
          </div>
        </div>

        <!-- Complain Chart Section (Styled like Quotation Statistic / Top Performing Sources) -->
        <div class="pt-2">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-secondary fs-13 mb-0">
              Complain Status Breakdown
            </h6>
          </div>
          <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
            <div id="complain-statistic-chart" class="flex-grow-1 dashboard-breakdown-chart"></div>
            <div class="flex-grow-1 dashboard-breakdown-content">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-info"></span>
                  <span class="text-dark fs-13 fw-medium">Generated</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="comp_break_gen">4</span>
                  <span class="text-muted fs-12" id="comp_pct_gen">(100%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-warning"></span>
                  <span class="text-dark fs-13 fw-medium">In Process</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="comp_break_proc">0</span>
                  <span class="text-muted fs-12" id="comp_pct_proc">(0%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-success"></span>
                  <span class="text-dark fs-13 fw-medium">Complete</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="comp_break_comp">0</span>
                  <span class="text-muted fs-12" id="comp_pct_comp">(0%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-danger"></span>
                  <span class="text-dark fs-13 fw-medium">Reject</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="comp_break_rej">0</span>
                  <span class="text-muted fs-12" id="comp_pct_rej">(0%)</span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <span class="indicator-dot dot-secondary"></span>
                  <span class="text-dark fs-13 fw-medium">Not Done</span>
                </div>
                <div class="text-end">
                  <span class="d-inline-block text-dark fw-bold fs-13 me-1" id="comp_break_notdone">0</span>
                  <span class="text-muted fs-12" id="comp_pct_notdone">(0%)</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
include 'include/footer.php';
?>

<script>
document.addEventListener("DOMContentLoaded", function () {
  // Chart references
  var attendanceChart, visitChart, quotationChart, orderChart, followupChart, leaveChart, expenseChart, complainChart;

  if (document.querySelector("#attendance-statistic-chart")) {
    
    // Dataset for static users and filters
    const staticAttendanceData = {
      // 1: ANKIT PATHAK
      1: {
        totalExec: 83,
        useMobile: "56/0",
        totalAtt: 78,
        inCount: 72,
        outCount: 6,
        chart: {
          categories: ["01 Feb", "02 Feb", "03 Feb", "04 Feb", "05 Feb", "06 Feb", "07 Feb", "08 Feb", "09 Feb", "10 Feb", "11 Feb", "12 Feb", "13 Feb", "14 Feb", "15 Feb"],
          present: [45, 52, 58, 60, 65, 55, 70, 75, 68, 80, 83, 78, 82, 79, 81],
          mobile: [30, 35, 42, 40, 48, 38, 50, 52, 49, 54, 56, 51, 55, 53, 56],
          out: [5, 8, 12, 10, 15, 14, 18, 20, 16, 22, 25, 20, 24, 21, 26]
        }
      },
      // 2: RAHUL SHARMA
      2: {
        totalExec: 45,
        useMobile: "38/2",
        totalAtt: 42,
        inCount: 39,
        outCount: 3,
        chart: {
          categories: ["01 Feb", "02 Feb", "03 Feb", "04 Feb", "05 Feb", "06 Feb", "07 Feb", "08 Feb", "09 Feb", "10 Feb", "11 Feb", "12 Feb", "13 Feb", "14 Feb", "15 Feb"],
          present: [28, 32, 35, 36, 40, 34, 42, 44, 41, 45, 45, 43, 44, 42, 43],
          mobile: [20, 24, 28, 27, 32, 25, 35, 36, 33, 37, 38, 35, 37, 36, 38],
          out: [3, 4, 6, 5, 8, 7, 9, 10, 8, 11, 12, 9, 10, 8, 11]
        }
      },
      // 3: PRIYA PATEL
      3: {
        totalExec: 25,
        useMobile: "22/1",
        totalAtt: 24,
        inCount: 23,
        outCount: 1,
        chart: {
          categories: ["01 Feb", "02 Feb", "03 Feb", "04 Feb", "05 Feb", "06 Feb", "07 Feb", "08 Feb", "09 Feb", "10 Feb", "11 Feb", "12 Feb", "13 Feb", "14 Feb", "15 Feb"],
          present: [18, 20, 22, 21, 24, 20, 25, 25, 24, 25, 25, 24, 25, 24, 25],
          mobile: [14, 16, 19, 18, 21, 17, 22, 22, 21, 22, 22, 21, 22, 21, 22],
          out: [1, 2, 3, 2, 4, 3, 4, 5, 3, 4, 5, 4, 4, 3, 5]
        }
      },
      // 4: ALL EXECUTIVES
      4: {
        totalExec: 153,
        useMobile: "116/3",
        totalAtt: 144,
        inCount: 134,
        outCount: 10,
        chart: {
          categories: ["01 Feb", "02 Feb", "03 Feb", "04 Feb", "05 Feb", "06 Feb", "07 Feb", "08 Feb", "09 Feb", "10 Feb", "11 Feb", "12 Feb", "13 Feb", "14 Feb", "15 Feb"],
          present: [91, 104, 115, 117, 129, 109, 137, 144, 133, 150, 153, 145, 151, 145, 149],
          mobile: [64, 75, 89, 85, 101, 80, 107, 110, 103, 113, 116, 107, 114, 110, 116],
          out: [9, 14, 21, 17, 27, 24, 31, 35, 27, 37, 42, 33, 38, 32, 42]
        }
      }
    };

    var optionsAttendance = {
      series: [
        {
          name: "Present / In",
          data: staticAttendanceData[1].chart.present
        },
        {
          name: "Mobile App Users",
          data: staticAttendanceData[1].chart.mobile
        },
        {
          name: "Out",
          data: staticAttendanceData[1].chart.out
        }
      ],
      chart: {
        height: 290,
        type: 'area',
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      colors: ['#0d6efd', '#0dcaf0', '#ffc107'],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.35,
          opacityTo: 0.05,
          stops: [0, 100]
        }
      },
      xaxis: {
        categories: staticAttendanceData[1].chart.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#6c757d', fontSize: '11px' } }
      },
      yaxis: {
        labels: { style: { colors: '#6c757d' } }
      },
      grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 4,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: true } }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '12px',
        fontWeight: 500,
        markers: { radius: 12 }
      },
      tooltip: {
        shared: true,
        intersect: false
      }
    };

    var attendanceChart = new ApexCharts(document.querySelector("#attendance-statistic-chart"), optionsAttendance);
    attendanceChart.render();

    // Filter Trigger Function
    function applyAttendanceFilter() {
      let selectedUser = parseInt($('#att_filter_user').val()) || 1;
      let selectedMonth = parseInt($('#att_filter_month').val()) || 2;
      let selectedYear = $('#att_filter_year').val() || '2024';

      let baseData = staticAttendanceData[selectedUser] || staticAttendanceData[1];

      // Month name abbreviation
      const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      let mName = monthNames[selectedMonth - 1] || "Feb";

      // Dynamically calculate multipliers based on year and month to make filter feel responsive
      let factor = 1.0;
      if (selectedMonth % 2 === 0) factor *= 1.05;
      else factor *= 0.92;

      let calcTotalExec = Math.round(baseData.totalExec * factor);
      let calcIn = Math.round(baseData.inCount * factor);
      let calcOut = Math.round(baseData.outCount * factor);
      let calcAtt = calcIn + calcOut;
      let mobileCount = Math.round(parseInt(baseData.useMobile.split('/')[0]) * factor);

      // Update Statistic Counter Cards
      $('#stat_total_exec').text(calcTotalExec);
      $('#stat_use_mobile').text(mobileCount + '/0');
      $('#stat_total_attendance').text(calcAtt);
      $('#stat_in').text(calcIn);
      $('#stat_out').text(calcOut);

      // Generate dynamic categories matching the selected month
      let newCategories = [];
      let newPresent = [];
      let newMobile = [];
      let newOut = [];

      for (let i = 1; i <= 15; i++) {
        let dayStr = (i < 10 ? '0' + i : i) + ' ' + mName;
        newCategories.push(dayStr);
        let origIdx = i - 1;
        newPresent.push(Math.round(baseData.chart.present[origIdx] * factor));
        newMobile.push(Math.round(baseData.chart.mobile[origIdx] * factor));
        newOut.push(Math.round(baseData.chart.out[origIdx] * factor));
      }

      // Update Chart Series and Categories
      attendanceChart.updateOptions({
        xaxis: {
          categories: newCategories
        }
      });

      attendanceChart.updateSeries([
        { name: "Present / In", data: newPresent },
        { name: "Mobile App Users", data: newMobile },
        { name: "Out", data: newOut }
      ]);
    }

    // Attendance Chart is ready and displayed with static data
  }

  // Visit Statistic Chart Initialization (Theme Design Matching)
  if (document.querySelector("#visit-statistic-chart")) {
    const getThemeColor = (name, fallback) => {
      const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return val ? val : fallback;
    };

    const themePrimary = getThemeColor('--bs-primary', '#0d6efd');
    const themeSecondary = getThemeColor('--bs-secondary', '#6c757d');
    const themeBorder = getThemeColor('--bs-border-color', '#f1f1f1');

    var optionsVisit = {
      series: [{
        name: 'Total Visit',
        data: [12, 18, 15, 24, 20, 28, 22, 30]
      }],
      chart: {
        type: 'bar',
        height: 280,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      colors: [themePrimary],
      plotOptions: {
        bar: {
          columnWidth: '40%',
          borderRadius: 4,
          borderRadiusApplication: 'end'
        }
      },
      dataLabels: {
        enabled: false
      },
      xaxis: {
        categories: ['1 Sep', '4 Sep', '8 Sep', '12 Sep', '16 Sep', '20 Sep', '24 Sep', '28 Sep'],
        axisBorder: { show: false },
        axisTicks: { show: false },
        title: {
          text: 'Days',
          style: {
            color: themeSecondary,
            fontSize: '12px',
            fontWeight: 600
          }
        },
        labels: {
          style: {
            colors: themeSecondary,
            fontSize: '12px',
            fontWeight: 500
          }
        }
      },
      yaxis: {
        min: 0,
        tickAmount: 5,
        title: {
          text: 'Total Visit',
          style: {
            color: themeSecondary,
            fontSize: '12px',
            fontWeight: 600
          }
        },
        labels: {
          style: {
            colors: themeSecondary,
            fontSize: '12px'
          },
          formatter: function(val) {
            return Math.round(val);
          }
        }
      },
      grid: {
        borderColor: themeBorder,
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function(val) {
            return val + " Visits";
          }
        }
      }
    };

    var visitChart = new ApexCharts(document.querySelector("#visit-statistic-chart"), optionsVisit);
    visitChart.render();
  }

  // Quotation Statistic Chart Initialization (Top Performing Sources style Donut)
  if (document.querySelector("#quotation-statistic-chart")) {
    const getThemeColor = (name, fallback) => {
      const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return val ? val : fallback;
    };

    const primaryColor = getThemeColor('--bs-primary', '#0d6efd');
    const successColor = getThemeColor('--bs-success', '#198754');
    const infoColor = getThemeColor('--bs-info', '#0dcaf0');
    const warningColor = getThemeColor('--bs-warning', '#ffc107');
    const purpleColor = '#6f42c1';
    const secondaryColor = getThemeColor('--bs-secondary', '#6c757d');

    var optionsQuotation = {
      series: [254, 241, 220, 192, 173, 408],
      chart: {
        type: 'donut',
        height: 250,
        fontFamily: 'inherit'
      },
      labels: ['March', 'February', 'January', 'April', 'June', 'Others'],
      colors: [primaryColor, successColor, infoColor, warningColor, purpleColor, secondaryColor],
      dataLabels: { enabled: false },
      plotOptions: {
        pie: {
          donut: {
            size: '72%',
            labels: {
              show: true,
              name: {
                show: true,
                fontSize: '13px',
                fontWeight: 600,
                color: secondaryColor,
                offsetY: -5
              },
              value: {
                show: true,
                fontSize: '18px',
                fontWeight: 700,
                color: '#212529',
                offsetY: 5,
                formatter: function (val) {
                  return val;
                }
              },
              total: {
                show: true,
                showAlways: true,
                label: 'Total',
                fontSize: '13px',
                fontWeight: 600,
                color: secondaryColor,
                formatter: function (w) {
                  return "1,488";
                }
              }
            }
          }
        }
      },
      stroke: { width: 2, colors: ['#ffffff'] },
      legend: { show: false },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val + " Quotations";
          }
        }
      }
    };

    var quotationChart = new ApexCharts(document.querySelector("#quotation-statistic-chart"), optionsQuotation);
    quotationChart.render();
  }

  // Order Statistic Chart Initialization (Theme Smooth Area/Bar Chart)
  if (document.querySelector("#order-statistic-chart")) {
    const getThemeColor = (name, fallback) => {
      const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return val ? val : fallback;
    };

    const primaryColor = getThemeColor('--bs-primary', '#0d6efd');
    const successColor = getThemeColor('--bs-success', '#198754');
    const secondaryColor = getThemeColor('--bs-secondary', '#6c757d');
    const borderColor = getThemeColor('--bs-border-color', '#f1f1f1');

    var optionsOrder = {
      series: [
        {
          name: 'Approved Orders',
          data: [135, 160, 190, 175, 210, 195, 230, 215, 185, 165, 190, 200]
        },
        {
          name: 'Pending Orders',
          data: [12, 10, 8, 14, 9, 7, 11, 8, 10, 5, 8, 6]
        }
      ],
      chart: {
        type: 'area',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      colors: [successColor, primaryColor],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.35,
          opacityTo: 0.05,
          stops: [0, 100]
        }
      },
      xaxis: {
        categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: secondaryColor, fontSize: '11px' } }
      },
      yaxis: {
        labels: { style: { colors: secondaryColor, fontSize: '11px' } }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '12px',
        labels: { colors: secondaryColor }
      },
      markers: { size: 3 }
    };

    var orderChart = new ApexCharts(document.querySelector("#order-statistic-chart"), optionsOrder);
    orderChart.render();
  }

  // Followup Statistic Chart Initialization (Gradient Area / Spline)
  if (document.querySelector("#followup-statistic-chart")) {
    const getThemeColor = (name, fallback) => {
      const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return val ? val : fallback;
    };

    const primaryColor = getThemeColor('--bs-primary', '#0d6efd');
    const secondaryColor = getThemeColor('--bs-secondary', '#6c757d');
    const borderColor = getThemeColor('--bs-border-color', '#f1f1f1');

    var optionsFollowup = {
      series: [{
        name: 'Followups',
        data: [3323, 2781, 2948, 2939, 3960, 3937, 3721, 2035, 180, 60, 10, 7]
      }],
      chart: {
        type: 'area',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      colors: [primaryColor],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 3 },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.45,
          opacityTo: 0.05,
          stops: [0, 100]
        }
      },
      xaxis: {
        categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: secondaryColor, fontSize: '11px' } }
      },
      yaxis: {
        labels: {
          style: { colors: secondaryColor, fontSize: '11px' },
          formatter: function(val) {
            return val >= 1000 ? (val / 1000).toFixed(1) + 'k' : val;
          }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val.toLocaleString() + " Followups";
          }
        }
      },
      markers: { size: 3, colors: [primaryColor], strokeColors: '#fff', strokeWidth: 2 }
    };

    var followupChart = new ApexCharts(document.querySelector("#followup-statistic-chart"), optionsFollowup);
    followupChart.render();
  }

  // Leave Statistic Chart Initialization (Modern Bar / Column Chart)
  if (document.querySelector("#leave-statistic-chart")) {
    const getThemeColor = (name, fallback) => {
      const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return val ? val : fallback;
    };

    const successColor = getThemeColor('--bs-success', '#198754');
    const primaryColor = getThemeColor('--bs-primary', '#0d6efd');
    const secondaryColor = getThemeColor('--bs-secondary', '#6c757d');
    const borderColor = getThemeColor('--bs-border-color', '#f1f1f1');

    var optionsLeave = {
      series: [
        {
          name: 'Accepted',
          data: [6, 8, 9, 10, 15, 7, 1, 1, 0, 0, 0, 0]
        },
        {
          name: 'Generated',
          data: [5, 7, 6, 9, 13, 7, 1, 1, 2, 0, 0, 0]
        }
      ],
      chart: {
        type: 'bar',
        height: 250,
        stacked: true,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      colors: [primaryColor, successColor],
      plotOptions: {
        bar: {
          columnWidth: '35%',
          borderRadius: 4,
          borderRadiusApplication: 'end'
        }
      },
      dataLabels: { enabled: false },
      xaxis: {
        categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: secondaryColor, fontSize: '11px' } }
      },
      yaxis: {
        labels: {
          style: { colors: secondaryColor, fontSize: '11px' },
          formatter: function(val) {
            return Math.round(val);
          }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '12px',
        labels: { colors: secondaryColor }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val + " Leaves";
          }
        }
      }
    };

    var leaveChart = new ApexCharts(document.querySelector("#leave-statistic-chart"), optionsLeave);
    leaveChart.render();
  }

  // Expense Statistic Chart Initialization (Theme Double-Bar Chart: Requested vs Pass)
  if (document.querySelector("#expense-statistic-chart")) {
    const getThemeColor = (name, fallback) => {
      const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return val ? val : fallback;
    };

    const primaryColor = getThemeColor('--bs-primary', '#0d6efd');
    const successColor = getThemeColor('--bs-success', '#198754');
    const secondaryColor = getThemeColor('--bs-secondary', '#6c757d');
    const borderColor = getThemeColor('--bs-border-color', '#f1f1f1');

    var optionsExpense = {
      series: [
        {
          name: 'Pass Expense',
          data: [775, 793, 745, 643, 809, 737, 799, 375, 5, 0, 0, 0]
        },
        {
          name: 'Requested',
          data: [65, 80, 70, 55, 90, 75, 85, 40, 2, 0, 0, 0]
        }
      ],
      chart: {
        type: 'bar',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit'
      },
      colors: [primaryColor, successColor],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '45%',
          borderRadius: 4,
          borderRadiusApplication: 'end'
        }
      },
      dataLabels: { enabled: false },
      stroke: { show: true, width: 2, colors: ['transparent'] },
      xaxis: {
        categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: secondaryColor, fontSize: '11px' } }
      },
      yaxis: {
        labels: {
          style: { colors: secondaryColor, fontSize: '11px' },
          formatter: function(val) {
            return Math.round(val);
          }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '12px',
        labels: { colors: secondaryColor }
      },
      tooltip: {
        theme: 'light',
        y: {
          formatter: function (val) {
            return val + " Expenses";
          }
        }
      }
    };

    var expenseChart = new ApexCharts(document.querySelector("#expense-statistic-chart"), optionsExpense);
    expenseChart.render();
  }

  // Complain Statistic Chart Initialization (Theme Tasks Overview style RadialBar)
  if (document.querySelector("#complain-statistic-chart")) {
    const getThemeColor = (name, fallback) => {
      const val = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return val ? val : fallback;
    };

    const primaryColor = getThemeColor('--bs-primary', '#0d6efd');
    const secondaryColor = getThemeColor('--bs-secondary', '#6c757d');
    const borderColor = getThemeColor('--bs-border-color', '#f1f1f1');

    var optionsComplain = {
      series: [100],
      chart: {
        height: 250,
        type: 'radialBar',
        fontFamily: 'inherit'
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '70%',
          },
          track: {
            background: borderColor,
            margin: 0
          },
          dataLabels: {
            show: true,
            name: {
              offsetY: 20,
              show: true,
              color: secondaryColor,
              fontSize: '13px'
            },
            value: {
              offsetY: -10,
              color: primaryColor,
              fontSize: '26px',
              fontWeight: 700,
              show: true,
              formatter: function (val) {
                return val + "%";
              }
            }
          }
        }
      },
      colors: [primaryColor],
      labels: ['Resolution Rate (4/4)'],
      stroke: { lineCap: 'round' }
    };

    var complainChart = new ApexCharts(document.querySelector("#complain-statistic-chart"), optionsComplain);
    complainChart.render();
  }

  // Central Pagination Controller for Today's Followup
  const rowsPerPage = 5;
  let currentFollowupPage = 1;

  function renderFollowupPagination(matchingRows) {
    const $matching = matchingRows || $('#todayFollowupTbody .followup-row:not(.filtered-out)');
    const totalMatching = $matching.length;
    const totalPages = Math.ceil(totalMatching / rowsPerPage) || 1;

    if (currentFollowupPage > totalPages) currentFollowupPage = 1;
    if (currentFollowupPage < 1) currentFollowupPage = 1;

    const start = (currentFollowupPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    $('#todayFollowupTbody .followup-row').hide();
    $matching.slice(start, end).show();

    $('#followupPageStart').text(totalMatching > 0 ? start + 1 : 0);
    $('#followupPageEnd').text(Math.min(end, totalMatching));
    $('#followupTotalCount').text(totalMatching);

    // Build pagination item numbers
    let paginationHtml = '';
    for (let p = 1; p <= totalPages; p++) {
      paginationHtml += `<li class="page-item ${p === currentFollowupPage ? 'active' : ''}" data-page="${p}"><a class="page-link" href="#">${p}</a></li>`;
    }
    $('#followupPagination li[data-page]').remove();
    $('#followupPrevPage').after(paginationHtml);

    if (currentFollowupPage === 1) {
      $('#followupPrevPage').addClass('disabled');
    } else {
      $('#followupPrevPage').removeClass('disabled');
    }

    if (currentFollowupPage >= totalPages || totalMatching === 0) {
      $('#followupNextPage').addClass('disabled');
    } else {
      $('#followupNextPage').removeClass('disabled');
    }

    if (window.lucide) lucide.createIcons();
  }

  $(document).on('click', '#followupPagination li[data-page] a', function(e) {
    e.preventDefault();
    currentFollowupPage = parseInt($(this).parent().attr('data-page'));
    renderFollowupPagination();
  });

  $(document).on('click', '#followupPrevPage a', function(e) {
    e.preventDefault();
    if (currentFollowupPage > 1) {
      currentFollowupPage--;
      renderFollowupPagination();
    }
  });

  $(document).on('click', '#followupNextPage a', function(e) {
    e.preventDefault();
    const $matching = $('#todayFollowupTbody .followup-row:not(.filtered-out)');
    const totalPages = Math.ceil($matching.length / rowsPerPage) || 1;
    if (currentFollowupPage < totalPages) {
      currentFollowupPage++;
      renderFollowupPagination();
    }
  });

  // Global Filter Function across all sections
  function applyGlobalDashboardFilter() {
    const customerId = $('#global_customer').val();
    const executiveId = $('#global_sales_executive').val();
    const fromDate = $('#global_from_date').val();
    const toDate = $('#global_to_date').val();

    // 1. Filter Today's Followup Table
    let countTotal = 0;
    let countPending = 0;
    let countResponded = 0;

    $('#todayFollowupTbody .followup-row').each(function() {
      const rowCustomer = $(this).attr('data-customer');
      const rowExecutive = $(this).attr('data-executive');
      const rowStatus = $(this).attr('data-status');
      const rowDate = $(this).attr('data-date'); // YYYY-MM-DD

      let matchesCustomer = !customerId || (rowCustomer === customerId);
      let matchesExecutive = !executiveId || (rowExecutive === executiveId);
      let matchesFromDate = !fromDate || (rowDate >= fromDate);
      let matchesToDate = !toDate || (rowDate <= toDate);

      if (matchesCustomer && matchesExecutive && matchesFromDate && matchesToDate) {
        $(this).removeClass('filtered-out');
        countTotal++;
        if (rowStatus === 'PENDING') countPending++;
        if (rowStatus === 'RESPONDED') countResponded++;
      } else {
        $(this).addClass('filtered-out');
      }
    });

    // Update Today's Followup Badges
    $('#badge_today_total').text(countTotal);
    $('#badge_today_pending').text(countPending);
    $('#badge_today_responded').text(countResponded);

    // Refresh pagination to page 1
    currentFollowupPage = 1;
    renderFollowupPagination();

    // 2. Calculate scaling multipliers based on selected filters (Customer, Executive, Dates)
    let multiplier = 1.0;
    if (executiveId) {
      const execMultipliers = { '1': 1.0, '2': 0.72, '3': 0.48, '4': 1.25 };
      multiplier *= (execMultipliers[executiveId] || 1.0);
    }
    if (customerId) {
      const custMultipliers = { '1': 0.9, '2': 0.65, '3': 0.45, '4': 1.15 };
      multiplier *= (custMultipliers[customerId] || 1.0);
    }

    // Incorporate Date Range into Multiplier so all sections react to Date Filter
    if (fromDate && toDate) {
      const fDate = new Date(fromDate);
      const tDate = new Date(toDate);
      const diffTime = tDate.getTime() - fDate.getTime();
      const diffDays = Math.max(1, Math.round(diffTime / (1000 * 3600 * 24)) + 1);
      // Normalized against standard monthly 30 days window
      const dateFactor = Math.min(2.0, Math.max(0.15, diffDays / 30));
      multiplier *= dateFactor;
    } else if (fromDate || toDate) {
      multiplier *= 0.85;
    }

    const m = (val) => Math.max(1, Math.round(val * multiplier));
    const mDec = (val) => (val * multiplier).toFixed(1);

    // 3. Update Attendance Statistic Section
    const baseExec = 83, basePresent = 72, baseOut = 6;
    const calcExec = m(baseExec);
    const calcIn = m(basePresent);
    const calcOut = Math.max(1, Math.round(baseOut * multiplier));
    const calcTotalAtt = calcIn + calcOut;
    const calcMobile = Math.round(calcIn * 0.78);

    $('#stat_total_exec').text(calcExec);
    $('#stat_use_mobile').text(calcMobile + '/0');
    $('#stat_total_attendance').text(calcTotalAtt);
    $('#stat_in').text(calcIn);
    $('#stat_out').text(calcOut);

    if (attendanceChart) {
      const basePresentSeries = [45, 52, 58, 60, 65, 55, 70, 75, 68, 80, 83, 78, 82, 79, 81];
      const baseMobileSeries  = [30, 35, 42, 40, 48, 38, 50, 52, 49, 54, 56, 51, 55, 53, 56];
      const baseOutSeries     = [5, 8, 12, 10, 15, 14, 18, 20, 16, 22, 25, 20, 24, 21, 26];

      attendanceChart.updateSeries([
        { name: "Present / In", data: basePresentSeries.map(v => m(v)) },
        { name: "Mobile App Users", data: baseMobileSeries.map(v => m(v)) },
        { name: "Out", data: baseOutSeries.map(v => Math.max(1, Math.round(v * multiplier))) }
      ]);
    }

    // 4. Update Visit Statistic Section
    const calcTotalVisit = m(13247);
    const calcCompletedVisit = Math.round(calcTotalVisit * 0.973);
    const calcPendingVisit = calcTotalVisit - calcCompletedVisit;

    $('#stat_total_visit').text(calcTotalVisit.toLocaleString());
    $('#stat_completed_visit').text(calcCompletedVisit.toLocaleString());
    $('#stat_pending_visit').text(calcPendingVisit.toLocaleString());

    if (visitChart) {
      const baseVisitData = [12, 18, 15, 24, 20, 28, 22, 30];
      visitChart.updateSeries([{
        name: 'Total Visit',
        data: baseVisitData.map(v => m(v))
      }]);
    }

    // 5. Update Quotation Statistic Section
    const calcTotQuot = m(1488);
    const calcApprQuot = m(524);
    const calcOrderQuot = m(935);
    const calcLostQuot = Math.max(1, Math.round(10 * multiplier));

    $('#stat_total_quotation').text(calcTotQuot.toLocaleString());
    $('#stat_total_quotation_amt').text('₹' + mDec(587.9) + 'M');
    $('#stat_approved_quotation').text(calcApprQuot.toLocaleString());
    $('#stat_approved_quotation_amt').text('₹' + mDec(308.4) + 'M');
    $('#stat_to_order_quotation').text(calcOrderQuot.toLocaleString());
    $('#stat_to_order_quotation_amt').text('₹' + mDec(260.8) + 'M');
    $('#stat_lost_quotation').text(calcLostQuot.toLocaleString());
    $('#stat_lost_quotation_amt').text('₹' + mDec(11.9) + 'M');

    // Quotation Breakdown
    const q1 = m(254), q2 = m(241), q3 = m(220), q4 = m(192), q5 = m(173), q6 = m(408);
    const sumQ = q1 + q2 + q3 + q4 + q5 + q6;

    $('#quot_break_1').text(q1);
    $('#quot_pct_1').text(`(${((q1 / sumQ) * 100).toFixed(1)}%)`);
    $('#quot_break_2').text(q2);
    $('#quot_pct_2').text(`(${((q2 / sumQ) * 100).toFixed(1)}%)`);
    $('#quot_break_3').text(q3);
    $('#quot_pct_3').text(`(${((q3 / sumQ) * 100).toFixed(1)}%)`);
    $('#quot_break_4').text(q4);
    $('#quot_pct_4').text(`(${((q4 / sumQ) * 100).toFixed(1)}%)`);
    $('#quot_break_5').text(q5);
    $('#quot_pct_5').text(`(${((q5 / sumQ) * 100).toFixed(1)}%)`);
    $('#quot_break_6').text(q6);
    $('#quot_pct_6').text(`(${((q6 / sumQ) * 100).toFixed(1)}%)`);

    if (quotationChart) {
      quotationChart.updateOptions({
        plotOptions: {
          pie: {
            donut: {
              labels: {
                total: {
                  formatter: function() { return sumQ.toLocaleString(); }
                }
              }
            }
          }
        }
      });
      quotationChart.updateSeries([q1, q2, q3, q4, q5, q6]);
    }

    // 6. Update Order Statistic Section
    const calcTotOrder = m(1963);
    const calcPendOrder = m(102);
    const calcApprOrder = calcTotOrder - calcPendOrder;

    $('#stat_total_order').text(calcTotOrder.toLocaleString());
    $('#stat_total_order_amt').text('₹' + mDec(458.5) + 'M');
    $('#stat_pending_order').text(calcPendOrder.toLocaleString());
    $('#stat_pending_order_amt').text('₹' + mDec(33.8) + 'M');
    $('#stat_approved_order').text(calcApprOrder.toLocaleString());
    $('#stat_approved_order_amt').text('₹' + mDec(424.5) + 'M');

    if (orderChart) {
      const baseApprovedOrders = [135, 160, 190, 175, 210, 195, 230, 215, 185, 165, 190, 200];
      const basePendingOrders  = [12, 10, 8, 14, 9, 7, 11, 8, 10, 5, 8, 6];
      orderChart.updateSeries([
        { name: 'Approved Orders', data: baseApprovedOrders.map(v => m(v)) },
        { name: 'Pending Orders', data: basePendingOrders.map(v => Math.max(1, Math.round(v * multiplier))) }
      ]);
    }

    // 7. Update Followup Statistic Section
    const calcTotFollowup = m(32894);
    const calcTodayFollowup = Math.max(1, Math.round(2 * multiplier));
    const calcFutureFollowup = m(159);
    const calcPendFollowup = m(10409);

    $('#stat_total_followup').text(calcTotFollowup.toLocaleString());
    $('#stat_today_followup').text(calcTodayFollowup.toLocaleString());
    $('#stat_future_followup').text(calcFutureFollowup.toLocaleString());
    $('#stat_pending_followup').text(calcPendFollowup.toLocaleString());

    if (followupChart) {
      const baseFollowupData = [3323, 2781, 2948, 2939, 3960, 3937, 3721, 2035, 180, 60, 10, 7];
      followupChart.updateSeries([{
        name: 'Followups',
        data: baseFollowupData.map(v => m(v))
      }]);
    }

    // 8. Update Leave Statistic Section
    const calcTotLeave = m(108);
    const calcGenLeave = m(51);
    const calcAccLeave = calcTotLeave - calcGenLeave;

    $('#stat_total_leave').text(calcTotLeave.toLocaleString());
    $('#stat_generated_leave').text(calcGenLeave.toLocaleString());
    $('#stat_accepted_leave').text(calcAccLeave.toLocaleString());

    if (leaveChart) {
      const baseAcceptedLeave  = [6, 8, 9, 10, 15, 7, 1, 1, 0, 0, 0, 0];
      const baseGeneratedLeave = [5, 7, 6, 9, 13, 7, 1, 1, 2, 0, 0, 0];
      leaveChart.updateSeries([
        { name: 'Accepted', data: baseAcceptedLeave.map(v => Math.round(v * multiplier)) },
        { name: 'Generated', data: baseGeneratedLeave.map(v => Math.round(v * multiplier)) }
      ]);
    }

    // 9. Update Expense Statistic Section
    const calcTotExp = m(5681);
    const calcReqExp = m(540);
    const calcPassExp = m(5015);
    const calcRejExp = Math.max(1, Math.round(126 * multiplier));

    $('#stat_total_expense').text(calcTotExp.toLocaleString());
    $('#stat_requested_expense').text(calcReqExp.toLocaleString());
    $('#stat_pass_expense').text(calcPassExp.toLocaleString());
    $('#stat_reject_expense').text(calcRejExp.toLocaleString());

    if (expenseChart) {
      const basePassExpense = [775, 793, 745, 643, 809, 737, 799, 375, 5, 0, 0, 0];
      const baseReqExpense  = [65, 80, 70, 55, 90, 75, 85, 40, 2, 0, 0, 0];
      expenseChart.updateSeries([
        { name: 'Pass Expense', data: basePassExpense.map(v => m(v)) },
        { name: 'Requested', data: baseReqExpense.map(v => Math.round(v * multiplier)) }
      ]);
    }

    // 10. Update Complain Statistic Section
    let compGen = Math.max(1, Math.round(4 * multiplier));
    let compProc = (multiplier > 1.0) ? 1 : 0;
    let compComplete = (multiplier > 0.8) ? Math.round(compGen * 0.5) : 0;
    let compTot = compGen + compProc + compComplete;
    let compRate = compTot > 0 ? Math.round((compComplete / compTot) * 100) : 100;
    if (compRate === 0 && compComplete === 0) compRate = 100; // 100% resolution or baseline

    $('#stat_total_complain').text(compTot);
    $('#stat_gen_complain').text(compGen);
    $('#stat_proc_complain').text(compProc);
    $('#stat_comp_complain').text(compComplete);

    $('#comp_break_gen').text(compGen);
    $('#comp_pct_gen').text(`(${compTot > 0 ? Math.round((compGen / compTot) * 100) : 100}%)`);
    $('#comp_break_proc').text(compProc);
    $('#comp_pct_proc').text(`(${compTot > 0 ? Math.round((compProc / compTot) * 100) : 0}%)`);
    $('#comp_break_comp').text(compComplete);
    $('#comp_pct_comp').text(`(${compTot > 0 ? Math.round((compComplete / compTot) * 100) : 0}%)`);

    if (complainChart) {
      complainChart.updateOptions({
        labels: [`Resolution Rate (${compComplete || compGen}/${compTot})`]
      });
      complainChart.updateSeries([compRate]);
    }
  }

  // Initialize TomSelect on Customer & Sales Executive Selectboxes
  let tsCustomer = null;
  let tsExecutive = null;

  if (typeof TomSelect !== 'undefined') {
    const custEl = document.getElementById('global_customer');
    if (custEl) {
      tsCustomer = new TomSelect(custEl, {
        create: false,
        allowEmptyOption: true,
        sortField: {
          field: "text",
          direction: "asc"
        },
        onChange: function() {
          applyGlobalDashboardFilter();
        }
      });
    }

    const execEl = document.getElementById('global_sales_executive');
    if (execEl) {
      tsExecutive = new TomSelect(execEl, {
        create: false,
        allowEmptyOption: true,
        sortField: {
          field: "text",
          direction: "asc"
        },
        onChange: function() {
          applyGlobalDashboardFilter();
        }
      });
    }
  }

  $('#global_customer, #global_sales_executive, #global_to_date, #global_from_date').on('change', function() {
    applyGlobalDashboardFilter();
  });


  // Initial render of pagination
  renderFollowupPagination();
});
</script>

</body>

</html>