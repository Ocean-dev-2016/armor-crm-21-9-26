<?php
require_once __DIR__ . '/conn/db.php';
require_once __DIR__ . '/conn/dbqry.php';
require_once __DIR__ . '/conn/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = getCurrentUserId();
if ($userId <= 0) {
    header("Location: " . SITE_URL . "login");
    exit;
}

$userSql = "SELECT u.*, 
                   c.name as company_name, 
                   c.prefix as company_prefix, 
                   c.pan_card as company_pan, 
                   c.gst as company_gst, 
                   c.bank_details as company_bank,
                   c.address as company_address,
                   c.email as company_email,
                   c.mobile_no as company_mobile,
                   c.header_image,
                   c.app_logo,
                   r.name as role_name,
                   co.name as country_name,
                   s.name as state_name,
                   ci.name as city_name
            FROM users u 
            LEFT JOIN company c ON c.id = u.company_id 
            LEFT JOIN roles r ON r.id = u.role_id 
            LEFT JOIN country co ON co.id = u.country_id
            LEFT JOIN state s ON s.id = u.state_id
            LEFT JOIN city ci ON ci.id = u.city_id
            WHERE u.id = $userId LIMIT 1";

$user = db_row($userSql);
if (!$user) {
    die("User not found.");
}

$company = null;
if (!empty($user['company_id'])) {
    $company = db_row("SELECT * FROM company WHERE id = " . (int)$user['company_id'] . " LIMIT 1");
}

$isEdit = isset($_GET['action']) && $_GET['action'] === 'edit';

$pageNm = 'Profile';
if ($isEdit) {
    $breadcrumbType = 'form';
    $parentUrl = SITE_URL . 'profile';
} else {
    $breadcrumbType = 'list';
}

include BASE_PATH . '/include/header.php';
?>

<!-- Breadcrumb Component -->
<div class="card mb-2">
    <div class="card-body breadcrumb-body">
        <div class="page-header">
            <nav style="--bs-breadcrumb-divider: '>'; " aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= SITE_URL ?>">Home</a>
                    </li>
                    <?php if (!$isEdit): ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= htmlspecialchars($pageNm) ?>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?= SITE_URL ?>profile"><?= htmlspecialchars($pageNm) ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Edit Profile
                        </li>
                    <?php endif; ?>
                </ol>
            </nav>
            <div class="page-actions">
                <?php if (!$isEdit): ?>
                    <a href="<?= SITE_URL ?>" class="btn btn-primary">
                        <i data-lucide="arrow-left" class="me-1"></i> Back
                    </a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>profile" class="btn btn-primary">
                        <i data-lucide="arrow-left" class="me-1"></i> Back
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Determine User Profile Image and Logo sources
$userAvatarSrc = SITE_URL . 'assets/images/user-8.jpg';
if (!empty($user['profile_img']) && file_exists(BASE_PATH . '/uploads/profile/' . $user['profile_img'])) {
    $userAvatarSrc = SITE_URL . 'uploads/profile/' . $user['profile_img'];
}

$logoSrc = SITE_URL . 'assets/image/crm_logo.png';
if (!empty($user['profile_img']) && file_exists(BASE_PATH . '/uploads/profile/' . $user['profile_img'])) {
    $logoSrc = SITE_URL . 'uploads/profile/' . $user['profile_img'];
}
?>

<?php if (!$isEdit): ?>
    <!-- ========================================== -->
    <!-- PROFILE VIEW MODE (Card with Horizontal Tabs) -->
    <!-- ========================================== -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <!-- Card Header with Horizontal Tabs & Edit Action Button -->
                <div class="card-header bg-white py-2 d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                    <ul class="nav nav-tabs card-header-tabs border-bottom-0" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-profile-btn" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button" role="tab">
                                <i data-lucide="user" class="fs-16"></i>
                                <span>Profile Details</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-contact-btn" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button" role="tab">
                                <i data-lucide="phone" class="fs-16"></i>
                                <span>Contact Information</span>
                            </button>
                        </li>
                        <?php if (!empty($company)): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-company-btn" data-bs-toggle="tab" data-bs-target="#tab-company" type="button" role="tab">
                                    <i data-lucide="building" class="fs-16"></i>
                                    <span>Company Details</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-bank-btn" data-bs-toggle="tab" data-bs-target="#tab-bank" type="button" role="tab">
                                    <i data-lucide="credit-card" class="fs-16"></i>
                                    <span>Bank Details</span>
                                </button>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-medium" id="tab-security-btn" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                                <i data-lucide="shield" class="fs-16"></i>
                                <span>Account & Security</span>
                            </button>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2 py-1">
                        <a href="<?= SITE_URL ?>profile/edit" class="btn btn-sm btn-primary d-flex align-items-center gap-1" title="Edit Profile">
                            <i data-lucide="edit" class="fs-14"></i>
                            <span>Edit</span>
                        </a>
                    </div>
                </div>

                <!-- Card Body: Tab Content Panes -->
                <div class="card-body p-4">
                    <div class="tab-content" id="profileTabsContent">
                        
                        <!-- TAB 1: Profile Details -->
                        <div class="tab-pane fade show active" id="tab-profile" role="tabpanel">
                            <div class="d-flex flex-wrap align-items-center gap-4 p-3 bg-light rounded border mb-4">
                                <div class="p-2 border rounded bg-white d-inline-flex align-items-center justify-content-center" style="min-width: 130px; min-height: 75px; max-width: 160px;">
                                    <img src="<?= $logoSrc ?>" alt="Profile Logo" class="img-fluid" style="max-height: 65px; object-fit: contain;">
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h5 class="fw-bold text-dark text-uppercase mb-0"><?= htmlspecialchars($company['name'] ?? $user['name'] ?? 'User') ?></h5>
                                        <?php if ($user['status'] == 1): ?>
                                            <span class="badge bg-success px-2 py-1 text-white">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-2 py-1 text-white">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small">
                                        <strong>Role:</strong> <?= htmlspecialchars($user['role_name'] ?? $user['user_type'] ?? 'User') ?>
                                        <span class="mx-2">•</span>
                                        <strong>Username:</strong> <?= htmlspecialchars($user['username'] ?? '-') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">User Code</div>
                                    <div class="fw-bold text-dark">
                                        <?= !empty($company['prefix']) ? htmlspecialchars($company['prefix']) . (int)$user['id'] : 'USR' . (int)$user['id'] ?>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Username</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['username'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Full Name</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['name'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">User Type</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['user_type'] ?? 'User'))) ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Role</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['role_name'] ?? 'Administrator') ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Status</div>
                                    <div><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Account Created On</div>
                                    <div class="fw-bold text-dark"><?= !empty($user['created_at']) ? date('d-M-Y', strtotime($user['created_at'])) : '-' ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Last Updated</div>
                                    <div class="fw-bold text-dark"><?= !empty($user['updated_at']) ? date('d-M-Y', strtotime($user['updated_at'])) : '-' ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">PAN Card Number</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($company['pan_card'] ?? '-') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Contact Information -->
                        <div class="tab-pane fade" id="tab-contact" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Primary Email</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['email'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Mobile / Whatsapp</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['mobile_no'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">Country</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['country_name'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">State</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['state_name'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small mb-1">City</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['city_name'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-12">
                                    <div class="text-muted small mb-1">Address</div>
                                    <div class="fw-bold text-dark">
                                        <?= !empty($user['address']) ? strip_tags($user['address']) : (!empty($company['address']) ? strip_tags($company['address']) : '-') ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($company)): ?>
                            <!-- TAB 3: Company Details -->
                            <div class="tab-pane fade" id="tab-company" role="tabpanel">
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small mb-1">Company Name</div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($company['name'] ?? '-') ?></div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small mb-1">Company Prefix</div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($company['prefix'] ?? '-') ?></div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small mb-1">GST Number</div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($company['gst'] ?? '-') ?></div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small mb-1">Company Email</div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($company['email'] ?? '-') ?></div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small mb-1">Company Mobile</div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($company['mobile_no'] ?? '-') ?></div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small mb-1">PAN Card</div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($company['pan_card'] ?? '-') ?></div>
                                    </div>
                                </div>

                                <h6 class="fw-bold text-dark mb-2">Company Logo</h6>
                                <div class="p-3 border rounded bg-light d-inline-block">
                                    <img src="<?= $logoSrc ?>" alt="Company Logo" style="max-height: 80px; max-width: 200px; object-fit: contain;">
                                </div>
                            </div>

                            <!-- TAB 4: Bank Details -->
                            <div class="tab-pane fade" id="tab-bank" role="tabpanel">
                                <div>
                                    <div class="text-muted small mb-2">Registered Bank Information</div>
                                    <div class="p-3 bg-light border rounded text-dark">
                                        <?= !empty($company['bank_details']) ? $company['bank_details'] : '<span class="text-muted">No bank details added.</span>' ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- TAB 5: Account & Security -->
                        <div class="tab-pane fade" id="tab-security" role="tabpanel">

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Username</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['username'] ?? '-') ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Password</div>
                                    <div class="fw-bold text-dark">••••••••••••</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Last Login IP</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['ip_address'] ?? '127.0.0.1') ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Account Role</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['role_name'] ?? 'Administrator') ?></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================== -->
    <!-- PROFILE EDIT PAGE (EDIT MODE)              -->
    <!-- ========================================== -->
    <div class="row g-4">
        <!-- Left: Logo Upload Card -->
        <div class="col-lg-4 col-md-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Profile Logo</h6>
                </div>
                <div class="card-body">
                    <form id="logoUploadForm" enctype="multipart/form-data">
                        <div class="text-center mb-3">
                            <div class="p-2 border rounded bg-white d-inline-flex align-items-center justify-content-center" style="min-width: 140px; min-height: 80px; max-width: 180px;">
                                <img id="currentLogoPreview" src="<?= $logoSrc ?>" alt="Profile Logo" class="img-fluid" style="max-height: 70px; object-fit: contain;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Upload New Logo</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" id="logoFileNameDisplay" 
                                    placeholder="No File Selected" readonly>
                                <input type="file" name="logo_image" id="logoFileInput" class="d-none" accept="image/png, image/jpeg, image/jpg, image/webp">
                                <button class="btn btn-select-file" type="button" id="selectLogoBtn">
                                    SELECT FILE
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">Allowed formats: JPG, PNG, WEBP (Max 5MB)</small>
                        </div>

                        <button type="submit" class="btn btn-primary" id="saveLogoBtn">
                            <i data-lucide="upload" class="fs-14 align-middle me-1"></i> Save Logo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Account Settings Form Card -->
        <div class="col-lg-8 col-md-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Account Settings</h6>
                </div>
                <div class="card-body">
                    <form id="accountSettingsForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Profile Name</label>
                                <input type="text" name="profile_name" id="profile_name" class="form-control" 
                                    value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Enter Profile Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="text" name="email" id="email" class="form-control" 
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Enter Email" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Old Password</label>
                                <input type="password" name="old_password" id="old_password" class="form-control" placeholder="Enter Old Password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter New Password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm New Password">
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary" id="saveAccountBtn">
                                <i data-lucide="save" class="fs-14 align-middle me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
include BASE_PATH . '/include/footer.php';
?>

<script>
    $(document).ready(function() {
        if (window.lucide) {
            lucide.createIcons();
        }

        // Custom File Chooser Trigger
        $('#selectLogoBtn').on('click', function() {
            $('#logoFileInput').trigger('click');
        });

        $('#logoFileInput').on('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let validExtensions = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!validExtensions.includes(file.type)) {
                    showToast('Please select a valid image (JPEG, PNG, WEBP)', 'error');
                    $(this).val('');
                    $('#logoFileNameDisplay').val('');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Image size cannot exceed 5MB', 'error');
                    $(this).val('');
                    $('#logoFileNameDisplay').val('');
                    return;
                }

                $('#logoFileNameDisplay').val(file.name);

                let reader = new FileReader();
                reader.onload = function(evt) {
                    $('#currentLogoPreview').attr('src', evt.target.result);
                };
                reader.readAsDataURL(file);
            }
        });

        // 1. Logo Upload Form Submit
        $('#logoUploadForm').on('submit', function(e) {
            e.preventDefault();
            let fileInput = $('#logoFileInput')[0];
            if (!fileInput || !fileInput.files.length) {
                showToast('Please select an image file first.', 'error');
                return false;
            }

            let formData = new FormData(this);
            formData.append('action', 'update_logo');

            let $btn = $('#saveLogoBtn');
            let origHtml = $btn.html();

            $.ajax({
                url: SITE_URL + 'admin/setting/profile_action.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
                },
                success: function(res) {
                    if (res.status === true) {
                        showToast(res.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showToast(res.message, 'error');
                    }
                },
                error: function() {
                    showToast('Something went wrong. Please try again.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(origHtml);
                    if (window.lucide) lucide.createIcons();
                }
            });
        });

        // Strong password method (Same as company form)
        if (!$.validator.methods.strongPassword) {
            $.validator.addMethod("strongPassword", function(value, element) {
                if (this.optional(element)) {
                    return true;
                }
                return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,12}$/.test(value);
            }, "Password must contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and be between 8 and 12 characters.");
        }

        // 2. Account Settings Form Validation & Submit
        $("#accountSettingsForm").validate({
            rules: {
                profile_name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                email: {
                    required: true,
                    email: true
                },
                old_password: {
                    required: function() {
                        return $('#new_password').val().trim().length > 0;
                    }
                },
                new_password: {
                    required: function() {
                        return $('#old_password').val().trim().length > 0;
                    },
                    minlength: 8,
                    maxlength: 12,
                    strongPassword: true
                },
                confirm_password: {
                    required: function() {
                        return $('#new_password').val().trim().length > 0;
                    },
                    equalTo: "#new_password"
                }
            },
            messages: {
                profile_name: {
                    required: "Please enter profile name",
                    minlength: "Profile name must be at least 2 characters",
                    maxlength: "Profile name cannot exceed 100 characters"
                },
                email: {
                    required: "Please enter email",
                    email: "Please enter valid email"
                },
                old_password: {
                    required: "Please enter your current old password"
                },
                new_password: {
                    required: "Please enter new password",
                    minlength: "Password must be at least 8 characters",
                    maxlength: "Password cannot exceed 12 characters",
                    strongPassword: "Password must contain at least 1 uppercase letter, 1 lowercase letter, and 1 number"
                },
                confirm_password: {
                    required: "Please confirm your new password",
                    equalTo: "Passwords do not match"
                }
            },
            errorElement: "span",
            errorClass: "text-danger",
            errorPlacement: function(error, element) {
                if (element.closest('.input-group').length) {
                    error.insertAfter(element.closest('.input-group'));
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                let formData = new FormData(form);
                formData.append('action', 'update_account');

                let $btn = $("#saveAccountBtn");
                let origHtml = $btn.html();

                $.ajax({
                    url: SITE_URL + 'admin/setting/profile_action.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
                    },
                    success: function(res) {
                        if (res.status === true) {
                            showToast(res.message, 'success');
                            setTimeout(function() {
                                window.location.href = SITE_URL + 'profile';
                            }, 1000);
                        } else {
                            showToast(res.message, 'error');
                        }
                    },
                    error: function() {
                        showToast('Something went wrong. Please try again.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(origHtml);
                        if (window.lucide) lucide.createIcons();
                    }
                });
                return false;
            }
        });
    });
</script>
</body>
</html>
