<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$pageNm = 'Company';
$tbl = 'company';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isEdit = $id > 0;
$company = [
    'id' => 0,
    'name' => '',
    'person_name' => '',
    'mobile_no' => '',
    'email' => '',
    'country_id' => '',
    'state_id' => '',
    'city_id' => '',
    'plan_id' => '',
];
if ($isEdit) {
    $companyData = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$companyData) {
        die($pageNm . ' not found.');
    }
    $company = $companyData;
}
$sql_country = "SELECT * FROM country WHERE status = 1";
$country = db_rows($sql_country);

$sql_plan = "SELECT * FROM plan WHERE status = 1";
$plan = db_rows($sql_plan)  ;

include BASE_PATH . '/include/header.php';

$requiredAction = $isEdit ? 'updates' : 'adds';
checkPermissionOrDeny('company', $requiredAction);

$breadcrumbType = 'form';
$isEdit = $isEdit ? true : false;
$parentUrl = SITE_URL . 'company';
include BASE_PATH . '/component/breadcrumb.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="companyForm">
                    <?php if ($isEdit): ?>
                        <input type="hidden"
                            name="id"
                            value="<?= (int)$company['id'] ?>" id="id">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name" 
                                    value="<?= htmlspecialchars($company['name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Person Name</label>
                                <input type="text" name="person_name" id="person_name" class="form-control" placeholder="Enter <?= $pageNm ?> Person Name" 
                                    value="<?= htmlspecialchars($company['person_name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" name="email" id="email" class="form-control" placeholder="Enter <?= $pageNm ?> Email" 
                                    value="<?= htmlspecialchars($company['email']) ?>">
                            </div>
                        </div>
                        <?php 
                            if(!$isEdit){
                                ?>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter <?= $pageNm ?> Password">
                                    </div>
                                </div>
                                <?php 
                            }
                        ?>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Mobile No</label>
                                <input type="text" name="mobile_no" id="mobile_no" class="form-control" placeholder="Enter <?= $pageNm ?> Mobile No" 
                                    value="<?= htmlspecialchars($company['mobile_no']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <select name="country_id" id="select-single" class="form-select country_base_state">
                                    <option value="">Select a Country</option>
                                    <?php foreach ($country as $val) { ?>
                                        <option value="<?= $val['id'] ?>" <?= ((int)$val['id'] === (int)$company['country_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($val['name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">State</label>
                                <select name="state_id" id="select-state" class="form-select state_base_city">
                                    <option value="">Select a State</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <select name="city_id" id="select-city" class="form-select city_base_state">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Plan</label>
                                <select name="plan_id" id="select-plan" class="form-select">
                                    <option value="">Select Plan</option>
                                    <?php foreach ($plan as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $company['plan_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit"
                            class="btn btn-primary"
                            id="submitBtn">
                            <span id="submitText"> <?= $isEdit ? 'Update' : 'Submit' ?></span>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    $(document).ready(function() {
        <?php if ($isEdit && !empty($company['country_id'])): ?>
            let editCountryId = '<?= $company['country_id'] ?>';
            let editStateId = '<?= $company['state_id'] ?>';
            let editCityId = '<?= $company['city_id'] ?>';

            // Set Country in TomSelect
            let countryEl = $('#select-single')[0];
            if (countryEl && countryEl.tomselect) {
                countryEl.tomselect.setValue(editCountryId);
            } else {
                $('#select-single').val(editCountryId);
            }

            // Load States and preselect State
            loadStatesByCountry(editCountryId, editStateId, function() {
                if (editStateId) {
                    // Load Cities and preselect City
                    loadCitiesByState(editStateId, editCityId);
                }
            });
        <?php endif; ?>

        $.validator.addMethod("strongPassword", function(value, element) {
            if (this.optional(element)) {
                return true;
            }
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,12}$/.test(value);
        }, "Password must contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and be between 8 and 12 characters.");

        $("#companyForm").validate({
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                person_name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                email: {
                    required: <?= $isEdit ? 'false' : 'true' ?>,
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
                country_id: {
                    required: true,
                },
                state_id: {
                    required: true,
                },
                city_id: {
                    required: true,
                },
                plan_id: {
                    required: true,
                }
            },
            messages: {
                name: {
                    required: "Please enter city name",
                    minlength: "City name must be at least 3 characters",
                    maxlength: "City name cannot exceed 100 characters"
                },
                person_name: {
                    required: "Please enter person name",
                    minlength: "Person name must be at least 3 characters",
                    maxlength: "Person name cannot exceed 100 characters"
                },
                email: {
                    required: "Please enter email",
                    email: "Please enter valid email",
                },
                password: {
                    required: "Please enter password",
                    minlength: "Password must be at least 8 characters",
                    maxlength: "Password cannot exceed 12 characters",
                    strongPassword: "Password must contain at least 1 uppercase letter, 1 lowercase letter, and 1 number",
                },
                mobile_no: {
                    required: "Please enter mobile number",
                    minlength: "Mobile number must be at least 10 digits",
                    maxlength: "Mobile number cannot exceed 10 digits",
                    number: "Please enter valid mobile number",
                },
                country_id: {
                    required: "Please Select Country",
                },
                state_id: {
                    required: "Please Select State",
                },
                city_id: {
                    required: "Please Select City",
                },
                plan_id: {
                    required: "Please Select Plan",
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
                    url: SITE_URL+"admin/setting/company/store.php",
                    type: "POST",
                    data: $form.serialize(),
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true);
                        $text.text("Saving...");
                        $loader.removeClass("d-none");
                    },
                    success: function(response) {
                        console.log("AJAX response:", response);

                        if (response.status === true) {
                            console.log("City saved successfully");

                            showToast(
                                response.message,
                                "success"
                            );
                            showToast(response.message, "success");
                            $form[0].reset();
                            $form.validate().resetForm();
                            setTimeout(function() {
                                window.location.href = '<?= SITE_URL ?>company';
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
                        $text.text("Submit");
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