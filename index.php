<?php
include 'include/header.php';
?>
<!-- Row 1: Key CRM Metrics -->
<div class="row g-3 mb-4">
  <!-- Total Contacts -->
  <div class="col-xl-3 col-sm-6">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar avatar-md rounded bg-primary-subtle text-primary">
              <i data-lucide="users"></i>
            </div>
            <div>
              <span class="d-block text-muted fs-13 fw-medium">Total Contacts</span>
              <h4 class="mb-0"><span class="counter-value">14,842</span></h4>
            </div>
          </div>
          <button class="btn btn-sm btn-icon btn-white border-0 bg-transparent p-0"><i
              data-lucide="more-vertical" class="fs-16"></i></button>
        </div>
        <div class="d-flex align-items-center gap-1 mt-3">
          <span class="text-success fs-12 fw-medium"><i data-lucide="arrow-up" class="fs-10"></i> 12.5%</span>
          <span class="text-muted fs-12">vs last month</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Active Deals -->
  <div class="col-xl-3 col-sm-6">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar avatar-md rounded bg-warning-soft text-warning">
              <i data-lucide="briefcase"></i>
            </div>
            <div>
              <span class="d-block text-muted fs-13 fw-medium">Active Deals</span>
              <h4 class="mb-0"><span class="counter-value">485</span></h4>
            </div>
          </div>
          <button class="btn btn-sm btn-icon btn-white border-0 bg-transparent p-0"><i
              data-lucide="more-vertical" class="fs-16"></i></button>
        </div>
        <div class="d-flex align-items-center gap-1 mt-3">
          <span class="text-success fs-12 fw-medium"><i data-lucide="arrow-up" class="fs-10"></i> 8.2%</span>
          <span class="text-muted fs-12">vs last month</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Pipeline Value -->
  <div class="col-xl-3 col-sm-6">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar avatar-md rounded bg-success-soft text-success">
              <i data-lucide="dollar-sign"></i>
            </div>
            <div>
              <span class="d-block text-muted fs-13 fw-medium">Pipeline Value</span>
              <h4 class="mb-0">$1.2M</h4>
            </div>
          </div>
          <button class="btn btn-sm btn-icon btn-white border-0 bg-transparent p-0"><i
              data-lucide="more-vertical" class="fs-16"></i></button>
        </div>
        <div class="d-flex align-items-center gap-1 mt-3">
          <span class="text-success fs-12 fw-medium"><i data-lucide="arrow-up" class="fs-10"></i> 15.3%</span>
          <span class="text-muted fs-12">vs last month</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Conversion Rate -->
  <div class="col-xl-3 col-sm-6">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar avatar-md rounded bg-info-soft text-info">
              <i data-lucide="bar-chart-2"></i>
            </div>
            <div>
              <span class="d-block text-muted fs-13 fw-medium">Conversion Rate</span>
              <h4 class="mb-0"><span class="counter-value">18.4%</span></h4>
            </div>
          </div>
          <button class="btn btn-sm btn-icon btn-white border-0 bg-transparent p-0"><i
              data-lucide="more-vertical" class="fs-16"></i></button>
        </div>
        <div class="d-flex align-items-center gap-1 mt-3">
          <span class="text-danger fs-12 fw-medium"><i data-lucide="arrow-down" class="fs-10"></i> -2.4%</span>
          <span class="text-muted fs-12">vs last month</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 1: Pipeline & Leads -->
<div class="row g-3 mb-4">
  <!-- Sales Pipeline -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0">Sales Pipeline</h6>
        <select class="form-select form-select-sm w-auto bg-transparent">
          <option selected>This Month</option>
          <option>Last Month</option>
        </select>
      </div>
      <div class="card-body">
        <div id="sales-pipeline-chart"></div>
        <div class="mt-2 text-center">
          <span class="text-dark fw-bold">Total Deals: 3,272</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Leads Overview -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0">Leads Overview</h6>
        <select class="form-select form-select-sm w-auto bg-transparent">
          <option selected>This Week</option>
          <option>Last Week</option>
        </select>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center gap-4 mb-2">
          <div class="d-flex align-items-center gap-2">
            <span class="bg-primary rounded-circle p-1"></span>
            <span class="text-dark fs-13 fw-medium">New Leads</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="bg-success rounded-circle p-1"></span>
            <span class="text-muted fs-13">Converted Leads</span>
          </div>
        </div>
        <div id="leads-overview-chart"></div>
      </div>
    </div>
  </div>

  <!-- Recent Activities -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0">Recent Activities</h6>
        <a href="#" class="text-primary text-decoration-none fs-13 fw-medium">View All</a>
      </div>
      <div class="card-body">
        <div class="list-group list-group-flush gap-3">
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm bg-primary-subtle">
                <i data-lucide="mail" class="fs-14"></i>
              </div>
              <div>
                <span class="text-dark fw-medium fs-14 d-block">Email sent to Sarah Johnson</span>
                <span class="text-muted fs-13">Introduction to our new product</span>
              </div>
            </div>
            <span class="text-muted fs-12">10:30 AM</span>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm bg-success-soft">
                <i data-lucide="phone-call" class="fs-14"></i>
              </div>
              <div>
                <span class="text-dark fw-medium fs-14 d-block">Call with Michael Brown</span>
                <span class="text-muted fs-13">Discussed project requirements</span>
              </div>
            </div>
            <span class="text-muted fs-12">Yesterday</span>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm bg-danger-soft">
                <i data-lucide="calendar" class="fs-14"></i>
              </div>
              <div>
                <span class="text-dark fw-medium fs-14 d-block">Meeting with TechCorp Inc.</span>
                <span class="text-muted fs-13">Product demo scheduled</span>
              </div>
            </div>
            <span class="text-muted fs-12">May 20</span>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm bg-success-soft">
                <i data-lucide="check-square" class="fs-14"></i>
              </div>
              <div>
                <span class="text-dark fw-medium fs-14 d-block">Task completed</span>
                <span class="text-muted fs-13">Follow up with James Wilson</span>
              </div>
            </div>
            <span class="text-muted fs-12">May 19</span>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm bg-info-soft">
                <i data-lucide="user-plus" class="fs-14"></i>
              </div>
              <div>
                <span class="text-dark fw-medium fs-14 d-block">New lead added</span>
                <span class="text-muted fs-13">David Miller from DataSoft</span>
              </div>
            </div>
            <span class="text-muted fs-12">May 18</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 2: Sources, Status & Tasks -->
<div class="row g-3 mb-4">
  <!-- Top Performing Sources -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header ">
        <h6 class="mb-0">Top Performing Sources</h6>
      </div>
      <div class="card-body d-flex align-items-center justify-content-center gap-4">
        <div id="sources-chart" class="w-50"></div>
        <div class="w-50">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-primary rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Website</span>
            </div>
            <div class="text-end">
              <span class="d-inline-block text-dark fw-bold fs-13 me-1">1,102</span>
              <span class="text-muted fs-12">(45%)</span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-success rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Referral</span>
            </div>
            <div class="text-end">
              <span class="d-inline-block text-dark fw-bold fs-13 me-1">612</span>
              <span class="text-muted fs-12">(25%)</span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-warning rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Social Media</span>
            </div>
            <div class="text-end">
              <span class="d-inline-block text-dark fw-bold fs-13 me-1">367</span>
              <span class="text-muted fs-12">(15%)</span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-info rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Email Campaign</span>
            </div>
            <div class="text-end">
              <span class="d-inline-block text-dark fw-bold fs-13 me-1">245</span>
              <span class="text-muted fs-12">(10%)</span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-danger rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Other</span>
            </div>
            <div class="text-end">
              <span class="d-inline-block text-dark fw-bold fs-13 me-1">132</span>
              <span class="text-muted fs-12">(5%)</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Deal Status -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-3 ">
        <h6 class="mb-0">Deal Status</h6>
        <a href="#" class="text-primary text-decoration-none fs-13 fw-medium">View Report</a>
      </div>
      <div class="card-body">
        <div id="deal-status-chart"></div>
      </div>
    </div>
  </div>

  <!-- Tasks Overview -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-3 ">
        <h6 class="mb-0">Tasks Overview</h6>
        <a href="#" class="text-primary text-decoration-none fs-13 fw-medium">View All</a>
      </div>
      <div class="card-body d-flex align-items-center justify-content-center gap-4">
        <div id="tasks-overview-chart" class="w-50"></div>
        <div class="w-50">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-primary rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Total Tasks</span>
            </div>
            <span class="text-dark fw-bold fs-14">120</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-success rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Completed</span>
            </div>
            <span class="text-dark fw-bold fs-14">86</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-warning rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">In Progress</span>
            </div>
            <span class="text-dark fw-bold fs-14">20</span>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <span class="bg-secondary rounded-circle p-1"></span>
              <span class="text-dark fs-13 fw-medium">Pending</span>
            </div>
            <span class="text-dark fw-bold fs-14">14</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 3: Actionable Lists -->
<div class="row g-3">
  <!-- Recent Leads -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-3 ">
        <h6 class="mb-0">Recent Leads</h6>
        <a href="#" class="text-primary text-decoration-none fs-13 fw-medium">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless align-middle mb-0 mt-1">
          <tbody class="fs-13">
            <tr>
              <td class="ps-3 py-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="assets/images/user-7.jpg" alt="Avatar" class="avatar avatar-xs rounded-circle">
                  <span class="text-dark fw-medium">David Miller</span>
                </div>
              </td>
              <td class="text-muted py-2">david.miller@datasoft.com</td>
              <td class="text-muted py-2">DataSoft Inc.</td>
              <td class="pe-3 text-end py-2"><span class="badge bg-primary-subtle">New</span></td>
            </tr>
            <tr>
              <td class="ps-3 py-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="assets/images/user-8.jpg" alt="Avatar" class="avatar avatar-xs rounded-circle">
                  <span class="text-dark fw-medium">Emma Wilson</span>
                </div>
              </td>
              <td class="text-muted py-2">emma.wilson@brightfuture.com</td>
              <td class="text-muted py-2">Bright Future Ltd.</td>
              <td class="pe-3 text-end py-2"><span class="badge bg-info-soft">Contacted</span></td>
            </tr>
            <tr>
              <td class="ps-3 py-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="assets/images/user-10.jpg" alt="Avatar"
                    class="avatar avatar-xs rounded-circle">
                  <span class="text-dark fw-medium">James Anderson</span>
                </div>
              </td>
              <td class="text-muted py-2">james.anderson@innovate.io</td>
              <td class="text-muted py-2">Innovate IO</td>
              <td class="pe-3 text-end py-2"><span class="badge bg-success-soft">Qualified</span></td>
            </tr>
            <tr>
              <td class="ps-3 py-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="assets/images/user-9.jpg" alt="Avatar" class="avatar avatar-xs rounded-circle">
                  <span class="text-dark fw-medium">Olivia Martinez</span>
                </div>
              </td>
              <td class="text-muted py-2">olivia.martinez@nextgen.com</td>
              <td class="text-muted py-2">NextGen Corp.</td>
              <td class="pe-3 text-end py-2"><span class="badge bg-primary-subtle">New</span></td>
            </tr>
            <tr>
              <td class="ps-3 py-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="assets/images/user-11.jpg" alt="Avatar"
                    class="avatar avatar-xs rounded-circle">
                  <span class="text-dark fw-medium">Daniel Taylor</span>
                </div>
              </td>
              <td class="text-muted py-2">daniel.taylor@cloudtech.com</td>
              <td class="text-muted py-2">CloudTech Solutions</td>
              <td class="pe-3 text-end py-2"><span class="badge bg-info-soft">Contacted</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Top Deals -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-3 ">
        <h6 class="mb-0">Top Deals</h6>
        <a href="#" class="text-primary text-decoration-none fs-13 fw-medium">View All</a>
      </div>
      <div class="card-body">
        <div class="list-group list-group-flush gap-2">
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <span class="text-muted fs-13 fw-medium">1</span>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">TechCorp Enterprise Deal</span>
                <span class="text-muted fs-12">TechCorp Inc.</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-4">
              <span class="text-dark fw-bold fs-13">$12,500</span>
              <span class="badge bg-primary-subtle">Negotiation</span>
            </div>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <span class="text-muted fs-13 fw-medium">2</span>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">Bright Future Solution</span>
                <span class="text-muted fs-12">Bright Future Ltd.</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-4">
              <span class="text-dark fw-bold fs-13">$8,750</span>
              <span class="badge bg-info-soft">Proposal</span>
            </div>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <span class="text-muted fs-13 fw-medium">3</span>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">Innovate IO Platform</span>
                <span class="text-muted fs-12">Innovate IO</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-4">
              <span class="text-dark fw-bold fs-13">$7,200</span>
              <span class="badge bg-success-soft">Qualified</span>
            </div>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <span class="text-muted fs-13 fw-medium">4</span>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">CloudTech Services</span>
                <span class="text-muted fs-12">CloudTech Solutions</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-4">
              <span class="text-dark fw-bold fs-13">$6,300</span>
              <span class="badge bg-info-soft">Proposal</span>
            </div>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <span class="text-muted fs-13 fw-medium">5</span>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">NextGen Implementation</span>
                <span class="text-muted fs-12">NextGen Corp.</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-4">
              <span class="text-dark fw-bold fs-13">$5,900</span>
              <span class="badge bg-success-soft">Qualified</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Upcoming Meetings -->
  <div class="col-xl-4">
    <div class="card h-100 ">
      <div class="card-header d-flex justify-content-between align-items-center py-3 ">
        <h6 class="mb-0">Upcoming Meetings</h6>
        <a href="#" class="text-primary text-decoration-none fs-13 fw-medium">View Calendar</a>
      </div>
      <div class="card-body">
        <div class="list-group list-group-flush gap-3">
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="border rounded text-center overflow-hidden w-25">
                <div class="bg-primary text-white fs-10 fw-medium py-1">MAY</div>
                <div class="fs-16 py-1">22</div>
              </div>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">Meeting with TechCorp Inc.</span>
                <span class="text-muted fs-12">Product demonstration</span>
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="text-muted fs-11">10:00 AM - 11:00 AM</span>
              <div class="avatar-group">
                <img src="assets/images/user-10.jpg" alt="Avatar"
                  class="avatar avatar-xs rounded-circle border border-2 border-white">
                <div
                  class="avatar avatar-xs rounded-circle border border-2 border-white bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-10">
                  +2</div>
              </div>
            </div>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="border rounded text-center overflow-hidden w-25">
                <div class="bg-primary text-white fs-10 fw-medium py-1">MAY</div>
                <div class="fs-16 py-1">23</div>
              </div>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">Call with Bright Future Ltd.</span>
                <span class="text-muted fs-12">Project discussion</span>
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="text-muted fs-11">02:00 PM - 03:00 PM</span>
              <div class="avatar-group">
                <img src="assets/images/user-10.jpg" alt="Avatar"
                  class="avatar avatar-xs rounded-circle border border-2 border-white">
              </div>
            </div>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="border rounded text-center overflow-hidden w-25">
                <div class="bg-primary text-white fs-10 fw-medium py-1">MAY</div>
                <div class="fs-16 py-1">24</div>
              </div>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">Demo with Innovate IO</span>
                <span class="text-muted fs-12">Platform walkthrough</span>
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="text-muted fs-11">11:30 AM - 12:30 PM</span>
              <div class="avatar-group">
                <img src="assets/images/user-8.jpg" alt="Avatar"
                  class="avatar avatar-xs rounded-circle border border-2 border-white">
              </div>
            </div>
          </div>
          <div class="list-group-item bg-white border-0 p-0 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="border rounded text-center overflow-hidden w-25">
                <div class="bg-primary text-white fs-10 fw-medium py-1">MAY</div>
                <div class="fs-16 py-1">27</div>
              </div>
              <div>
                <span class="text-dark fw-medium fs-13 d-block">Review with CloudTech</span>
                <span class="text-muted fs-12">Contract discussion</span>
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="text-muted fs-11">03:00 PM - 04:00 PM</span>
              <div class="avatar-group">
                <img src="assets/images/user-7.jpg" alt="Avatar"
                  class="avatar avatar-xs rounded-circle border border-2 border-white">
                <img src="assets/images/user-8.jpg" alt="Avatar"
                  class="avatar avatar-xs rounded-circle border border-2 border-white">
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

</body>

</html>