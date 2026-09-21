<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$pageNm = 'Team Person';
$tbl = 'users';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isEdit = $id > 0;
$user = [
    'id' => 0,
    'company_id' => '',
    'parent_user' => '',
    'name' => '',
    'mobile_no' => '',
    'username' => '',
    'email' => '',
    'address' => '',
    'country_id' => '',
    'state_id' => '',
    'city_id' => '',
    'role_id' => '',
];

if ($isEdit) {
    $userData = db_row("SELECT * FROM $tbl WHERE id = $id AND user_type = 'company_admin' LIMIT 1");
    if (!$userData) {
        die($pageNm . ' not found.');
    }
    $user = $userData;
}

$sql_company = "SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC";
$companies = db_rows($sql_company);

$sql_country = "SELECT * FROM country WHERE status = 1 ORDER BY name ASC";
$countries = db_rows($sql_country);

include BASE_PATH . '/include/header.php';

$requiredAction = $isEdit ? 'updates' : 'adds';
checkPermissionOrDeny('team-person', $requiredAction);

$breadcrumbType = 'form';
$isEdit = $isEdit ? true : false;
$parentUrl = SITE_URL . 'team-person';
include BASE_PATH . '/component/breadcrumb.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="teamPersonForm">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= (int)$user['id'] ?>" id="id">
                    <?php endif; ?>
                    <div id="planLimitAlert" class="alert alert-warning d-none mb-3" role="alert">
                        <i data-lucide="alert-circle" class="me-2" style="width: 18px; height: 18px;"></i>
                        <span id="planLimitMsg"></span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Company</label>
                                <select name="company_id" id="select-company" class="form-select company_id">
                                    <option value="">Select a Company</option>
                                    <?php 
                                        $selectedCompanyId = !empty($user['company_id']) ? (int)$user['company_id'] : ((isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0);
                                        foreach ($companies as $c): 
                                    ?>
                                        <option value="<?= $c['id'] ?>" <?= ((int)$c['id'] === $selectedCompanyId) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter Name" 
                                    value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Parent Team Person</label>
                                <select name="parent_user" id="select-parent-user" class="form-select parent_user">
                                    <option value="">Select Parent Team Person</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Mobile No</label>
                                <input type="text" name="mobile_no" id="mobile_no" class="form-control" placeholder="Enter Mobile No" 
                                    value="<?= htmlspecialchars($user['mobile_no'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Enter Username" 
                                    value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Password </label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Enter Password">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" name="email" id="email" class="form-control" placeholder="Enter Email" 
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role_id" id="select-role" class="form-select role_id">
                                    <option value="">Select Role</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <select name="country_id" id="select-single" class="form-select country_base_state">
                                    <option value="">Select a Country</option>
                                    <?php foreach ($countries as $val) { ?>
                                        <option value="<?= $val['id'] ?>" <?= ((int)$val['id'] === (int)$user['country_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($val['name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">State</label>
                                <select name="state_id" id="select-state" class="form-select state_base_city">
                                    <option value="">Select a State</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <select name="city_id" id="select-city" class="form-select city_base_state">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="address" class="form-control" rows="2" placeholder="Enter Address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit"
                            class="btn btn-primary"
                            id="submitBtn">
                            <span id="submitText"><?= $isEdit ? 'Update' : 'Submit' ?></span>
                            <span id="submitLoader"
                                class="spinner-border spinner-border-sm d-none">
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
include BASE_PATH . '/include/footer.php';
?>
<script>
    function getTomSelectInstance(selector, placeholder = 'Select') {
        let el = $(selector)[0];
        if (!el) return null;
        if (el.tomselect) return el.tomselect;
        return new TomSelect(el, {
            create: false,
            allowEmptyOption: true,
            sortField: [
                { field: '$order' }
            ]
        });
    }

    function setSelectValue(selector, val) {
        let el = $(selector)[0];
        if (el && el.tomselect) {
            el.tomselect.setValue(val ? String(val) : '');
        } else {
            $(selector).val(val ? String(val) : '').trigger('change');
        }
    }

    $(document).ready(function() {
        let isEdit = <?= $isEdit ? 'true' : 'false' ?>;
        let selectedRoleId = '<?= $user['role_id'] ?>';
        let selectedParentUserId = '<?= $user['parent_user'] ?>';

        let tsCompany = getTomSelectInstance('#select-company', 'Select a Company');
        let tsParentUser = getTomSelectInstance('#select-parent-user', 'Select Parent Team Person');
        let tsRole = getTomSelectInstance('#select-role', 'Select Role');

        function loadCompanyData(companyId, preRoleId = '', preParentUserId = '') {
            let roleTs = getTomSelectInstance('#select-role');
            let parentTs = getTomSelectInstance('#select-parent-user');

            if (!companyId) {
                if (roleTs) {
                    roleTs.clear();
                    roleTs.clearOptions();
                    roleTs.addOption({ value: '', text: 'Select Role', $order: 1 });
                    roleTs.setValue('');
                } else {
                    $('#select-role').html('<option value="">Select Role</option>');
                }

                if (parentTs) {
                    parentTs.clear();
                    parentTs.clearOptions();
                    parentTs.addOption({ value: '', text: 'Select Parent Team Person', $order: 1 });
                    parentTs.setValue('');
                } else {
                    $('#select-parent-user').html('<option value="">Select Parent Team Person</option>');
                }
                return;
            }

            $.ajax({
                url: SITE_URL + 'admin/setting/team-person/store.php',
                type: 'GET',
                data: {
                    action: 'get_company_data',
                    company_id: companyId
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === true) {
                        if (roleTs) {
                            roleTs.clear();
                            roleTs.clearOptions();
                            roleTs.addOption({ value: '', text: 'Select Role', $order: 1 });
                            let rOrder = 2;
                            $.each(res.roles, function(i, r) {
                                roleTs.addOption({ value: String(r.id), text: r.name, $order: rOrder++ });
                            });
                            if (preRoleId) {
                                roleTs.setValue(String(preRoleId));
                            } else {
                                roleTs.setValue('');
                            }
                            roleTs.refreshOptions(false);
                        } else {
                            let roleOptions = '<option value="">Select Role</option>';
                            $.each(res.roles, function(i, r) {
                                let sel = (String(preRoleId) === String(r.id)) ? 'selected' : '';
                                roleOptions += '<option value="' + r.id + '" ' + sel + '>' + r.name + '</option>';
                            });
                            $('#select-role').html(roleOptions);
                            if (preRoleId) $('#select-role').val(preRoleId).trigger('change');
                        }

                        let currentId = $('#id').val();
                        if (parentTs) {
                            parentTs.clear();
                            parentTs.clearOptions();
                            parentTs.addOption({ value: '', text: 'Select Parent Team Person', $order: 1 });
                            let pOrder = 2;
                            $.each(res.parent_users, function(i, u) {
                                if (currentId && String(currentId) === String(u.id)) {
                                    return true; 
                                }
                                let displayName = u.name ? (u.name + (u.username ? ' (' + u.username + ')' : '')) : u.username;
                                parentTs.addOption({ value: String(u.id), text: displayName, $order: pOrder++ });
                            });
                            if (preParentUserId) {
                                parentTs.setValue(String(preParentUserId));
                            } else {
                                parentTs.setValue('');
                            }
                            parentTs.refreshOptions(false);
                        } else {
                            let parentOptions = '<option value="">Select Parent Team Person</option>';
                            $.each(res.parent_users, function(i, u) {
                                if (currentId && String(currentId) === String(u.id)) {
                                    return true;
                                }
                                let sel = (String(preParentUserId) === String(u.id)) ? 'selected' : '';
                                let displayName = u.name ? (u.name + (u.username ? ' (' + u.username + ')' : '')) : u.username;
                                parentOptions += '<option value="' + u.id + '" ' + sel + '>' + displayName + '</option>';
                            });
                            $('#select-parent-user').html(parentOptions);
                            if (preParentUserId) $('#select-parent-user').val(preParentUserId).trigger('change');
                        }

                        // 3. Plan max_team_user limit verification
                        if (!isEdit && res.plan_info) {
                            let pInfo = res.plan_info;
                            if (pInfo.max_team_user > 0 && !pInfo.can_add) {
                                $('#planLimitMsg').html('<strong>Limit Reached:</strong> The selected company plan (<strong>' + pInfo.plan_name + '</strong>) allows a maximum of <strong>' + pInfo.max_team_user + '</strong> team person(s), and all <strong>' + pInfo.current_count + '</strong> slot(s) are already filled. You cannot add more team persons to this company.');
                                $('#planLimitAlert').removeClass('d-none');
                                $('#submitBtn').prop('disabled', true);
                            } else {
                                $('#planLimitAlert').addClass('d-none');
                                $('#submitBtn').prop('disabled', false);
                            }
                            if (window.lucide) lucide.createIcons();
                        } else {
                            $('#planLimitAlert').addClass('d-none');
                            $('#submitBtn').prop('disabled', false);
                        }
                    } else {
                        $('#planLimitAlert').addClass('d-none');
                        $('#submitBtn').prop('disabled', false);
                        if (roleTs) {
                            roleTs.clear();
                            roleTs.clearOptions();
                            roleTs.addOption({ value: '', text: 'Select Role', $order: 1 });
                            roleTs.setValue('');
                        }
                        if (parentTs) {
                            parentTs.clear();
                            parentTs.clearOptions();
                            parentTs.addOption({ value: '', text: 'Select Parent Team Person', $order: 1 });
                            parentTs.setValue('');
                        }
                    }
                },
                error: function() {
                    showToast('Failed to load company data.', 'error');
                }
            });
        }

        $(document).on('change', '#select-company', function() {
            let compId = $(this).val();
            loadCompanyData(compId);
        });

        let sessionCompanyId = '<?= (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : '' ?>';

        <?php if ($isEdit && !empty($user['company_id'])): ?>
            let editCompId = '<?= $user['company_id'] ?>';
            setSelectValue('#select-company', editCompId);
            loadCompanyData(editCompId, selectedRoleId, selectedParentUserId);
        <?php else: ?>
            if (sessionCompanyId) {
                setSelectValue('#select-company', sessionCompanyId);
                loadCompanyData(sessionCompanyId, selectedRoleId, selectedParentUserId);
            }
        <?php endif; ?>

        <?php if ($isEdit && !empty($user['country_id'])): ?>
            let editCountryId = '<?= $user['country_id'] ?>';
            let editStateId = '<?= $user['state_id'] ?>';
            let editCityId = '<?= $user['city_id'] ?>';

            setSelectValue('#select-single', editCountryId);

            loadStatesByCountry(editCountryId, editStateId, function() {
                if (editStateId) {
                    loadCitiesByState(editStateId, editCityId);
                }
            });
        <?php endif; ?>

        $(document).on('change', '#select-single', function() {
            let countryId = $(this).val();
            loadStatesByCountry(countryId, null);
        });

        $(document).on('change', '#select-state', function() {
            let stateId = $(this).val();
            loadCitiesByState(stateId, null);
        });

        $.validator.addMethod("strongPassword", function(value, element) {
            if (this.optional(element)) {
                return true;
            }
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,12}$/.test(value);
        }, "Password must contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and be between 8 and 12 characters.");

        $("#teamPersonForm").validate({
            rules: {
                company_id: {
                    required: true,
                },
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                username: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                email: {
                    required: true,
                    email: true,
                },
                password: {
                    required: <?= $isEdit ? 'false' : 'true' ?>,
                    minlength: 8,
                    maxlength: 12,
                    strongPassword: true,
                },
                mobile_no: {
                    required: true,
                    minlength: 10,
                    maxlength: 10,
                    number: true,
                },
                role_id: {
                    required: true,
                },
                country_id: {
                    required: true,
                },
                state_id: {
                    required: true,
                },
                city_id: {
                    required: true,
                }
            },
            messages: {
                company_id: {
                    required: "Please select a company",
                },
                name: {
                    required: "Please enter name",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name cannot exceed 100 characters"
                },
                username: {
                    required: "Please enter username",
                    minlength: "Username must be at least 3 characters",
                    maxlength: "Username cannot exceed 100 characters"
                },
                email: {
                    required: "Please enter email",
                    email: "Please enter valid email",
                },
                password: {
                    required: "Please enter password",
                    minlength: "Password must be at least 8 characters",
                    maxlength: "Password cannot exceed 12 characters",
                    strongPassword: "Password must contain at least 1 uppercase, 1 lowercase, and 1 number",
                },
                mobile_no: {
                    required: "Please enter mobile number",
                    minlength: "Mobile number must be 10 digits",
                    maxlength: "Mobile number must be 10 digits",
                    number: "Please enter valid mobile number",
                },
                role_id: {
                    required: "Please select a role",
                },
                country_id: {
                    required: "Please select country",
                },
                state_id: {
                    required: "Please select state",
                },
                city_id: {
                    required: "Please select city",
                },
            },
            errorElement: "span",
            errorClass: "text-danger",
            submitHandler: function(form) {
                let $form = $(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");
                $.ajax({
                    url: SITE_URL + "admin/setting/team-person/store.php",
                    type: "POST",
                    data: $form.serialize(),
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true);
                        $text.text("Saving...");
                        $loader.removeClass("d-none");
                    },
                    success: function(response) {
                        if (response.status === true) {
                            showToast(response.message, "success");
                            $form[0].reset();
                            $form.validate().resetForm();
                            setTimeout(function() {
                                window.location.href = '<?= SITE_URL ?>team-person';
                            }, 1000);
                        } else {
                            showToast(response.message, "error");
                        }
                    },
                    error: function(xhr) {
                        showToast("Something went wrong. Please try again.", "error");
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                        $text.text(isEdit ? "Update" : "Submit");
                        $loader.addClass("d-none");
                    }
                });
                return false;
            }
        });
    });
</script>
</body>

</html>