<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$pageNm = 'Customer';
$tbl = 'customer';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isEdit = $id > 0;

$customer = [
    'id'               => 0,
    'company_id'       => '',
    'company_lead_id'  => 0,
    'client_code'      => '',
    'customer_type_id' => '',
    'gst_no'           => '',
    'name'             => '',
    'password'         => '',
    'contact_person'   => '',
    'mobile_no'        => '',
    'whatsapp_no'      => '',
    'email'            => '',
    'birth_date'       => '',
    'country_id'       => '',
    'state_id'         => '',
    'city_id'          => '',
    'area'             => '',
    'pincode'          => '',
    'price_list'       => '',
    'address'          => '',
    'shipping_address' => '',
    'billing_address'  => '',
    'latitude'         => '',
    'longitude'        => '',
    'assigned_to'      => 0,
    'status'           => 1
];

if ($isEdit) {
    $custData = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$custData) {
        die($pageNm . ' not found.');
    }

    $isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
    $sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
    if (!$isSuperadmin && $sessionCompanyId > 0 && (int)$custData['company_id'] !== $sessionCompanyId) {
        die('Unauthorized access.');
    }

    $userType = $_SESSION['user_type'] ?? '';
    $currentUserId = getCurrentUserId();
    if ($userType === 'user' && (int)($custData['assigned_to'] ?? 0) !== $currentUserId && (int)($custData['created_by'] ?? 0) !== $currentUserId) {
        die('Unauthorized access.');
    }

    $customer = array_merge($customer, $custData);
} else {
    // Generate default Client Code for Add
    $maxCustId = (int)(db_row("SELECT MAX(id) as mid FROM `$tbl`")['mid'] ?? 0) + 1;
    $customer['client_code'] = 'CC-' . str_pad($maxCustId, 3, '0', STR_PAD_LEFT);
}

include BASE_PATH . '/include/header.php';

$requiredAction = $isEdit ? 'updates' : 'adds';
checkPermissionOrDeny('customer', $requiredAction);

$breadcrumbType = 'form';
$parentUrl = SITE_URL . 'customer';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;
$selectedCompanyId = $isSuperadmin ? (int)($customer['company_id'] ?: 0) : $sessionCompanyId;

// Companies for Superadmin
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}

// Customer Types
$custTypeCond = "status = 1";
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $custTypeCond .= " AND (company_id = $sessionCompanyId OR company_id = 0)";
} elseif ($isSuperadmin && $selectedCompanyId > 0) {
    $custTypeCond .= " AND (company_id = $selectedCompanyId OR company_id = 0)";
}
$customerTypes = db_rows("SELECT id, name FROM customer_type WHERE $custTypeCond ORDER BY name ASC");

// Countries
$countries = db_rows("SELECT id, name FROM country WHERE status = 1 ORDER BY name ASC");

// Active Team Persons for Assign To (Only users with user_type = 'user')
$teamCondition = "status = 1 AND user_type = 'user'";
if ($selectedCompanyId > 0) {
    $teamCondition .= " AND company_id = $selectedCompanyId";
}
$teamPersons = db_rows("SELECT id, name, user_type FROM users WHERE $teamCondition ORDER BY name ASC");

// Default Assigned To if new and logged in user is among team
if (!$isEdit && empty($customer['assigned_to'])) {
    $loggedUserId = getCurrentUserId();
    $isUserInTeam = false;
    foreach ($teamPersons as $tp) {
        if ((int)$tp['id'] === $loggedUserId) {
            $isUserInTeam = true;
            break;
        }
    }
    if ($isUserInTeam) {
        $customer['assigned_to'] = $loggedUserId;
    }
}

// Format birth date for display
$birthDateFormatted = '';
if (!empty($customer['birth_date']) && $customer['birth_date'] !== '0000-00-00') {
    $birthDateFormatted = date('d-m-Y', strtotime($customer['birth_date']));
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold"><?= $isEdit ? 'Edit ' . $pageNm : 'Add ' . $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="customerForm">
                    <?php if ($id > 0): ?>
                        <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>" id="id">
                    <?php endif; ?>
                    <input type="hidden" name="company_lead_id" value="<?= (int)($customer['company_lead_id'] ?? 0) ?>" id="company_lead_id">

                    <?php if ($isSuperadmin): ?>
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Company</label>
                                <select name="company_id" id="select-company" class="form-select">
                                    <option value="">Select Company</option>
                                    <?php foreach ($companies as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$customer['company_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <!-- ROW 1: Client Code | Customer Type | GST Number -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Client Code</label>
                                <input type="text"
                                    name="client_code"
                                    id="client_code"
                                    class="form-control bg-light"
                                    placeholder="Enter Client Code"
                                    value="<?= htmlspecialchars($customer['client_code'] ?? '') ?>"
                                    readonly>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Customer Type</label>
                                <select name="customer_type_id" id="select-customer-type" class="form-select">
                                    <option value="">Select Customer Type</option>
                                    <?php foreach ($customerTypes as $ct): ?>
                                        <option value="<?= (int)$ct['id'] ?>" <?= ((int)$customer['customer_type_id'] === (int)$ct['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ct['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Customer/Company Name in 4th col of Row 1 -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Customer/Company Name</label>
                                <input type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    placeholder="Enter Customer Name"
                                    value="<?= htmlspecialchars($customer['name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Contact Person Name</label>
                                <input type="text"
                                    name="contact_person"
                                    id="contact_person"
                                    class="form-control"
                                    placeholder="Enter Contact Person Name"
                                    value="<?= htmlspecialchars($customer['contact_person'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Mobile No</label>
                                <input type="text"
                                    name="mobile_no"
                                    id="mobile_no"
                                    class="form-control"
                                    placeholder="Mobile No"
                                    maxlength="15"
                                    value="<?= htmlspecialchars($customer['mobile_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label mb-0">WhatsApp No</label>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="checkbox" id="same_as_mobile">
                                        <label class="form-check-label fs-12 text-muted" for="same_as_mobile">Same as mobile</label>
                                    </div>
                                </div>
                                <input type="text"
                                    name="whatsapp_no"
                                    id="whatsapp_no"
                                    class="form-control"
                                    placeholder="WhatsApp No"
                                    maxlength="15"
                                    value="<?= htmlspecialchars($customer['whatsapp_no'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 2: Password | Contact Person Name | Mobile No | WhatsApp No -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Password</label>
                                <input type="password"
                                    name="password"
                                    id="password"
                                    class="form-control"
                                    placeholder="<?= $isEdit ? 'Leave blank to keep unchanged' : 'Enter Password' ?>"
                                    autocomplete="new-password">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Email ID</label>
                                <input type="email"
                                    name="email"
                                    id="email"
                                    class="form-control"
                                    placeholder="Enter Email ID"
                                    value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 4: Birth Date | Select Country | Select State -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Birth Date</label>
                                <input type="text"
                                    name="birth_date"
                                    id="birth_date"
                                    class="form-control"
                                    placeholder="DD-MM-YYYY"
                                    value="<?= htmlspecialchars($birthDateFormatted) ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">GST Number</label>
                                <input type="text"
                                    name="gst_no"
                                    id="gst_no"
                                    class="form-control text-uppercase"
                                    placeholder="Enter GST number"
                                    maxlength="15"
                                    value="<?= htmlspecialchars($customer['gst_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Select Country</label>
                                <select name="country_id" id="select-country" class="form-select country_base_state">
                                    <option value="">Select Country</option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$customer['country_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Select State</label>
                                <select name="state_id" id="select-state" class="form-select state_base_city">
                                    <option value="">Select State</option>
                                </select>
                            </div>
                        </div>

                        <!-- ROW 5: Select City | Area | Pincode -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Select City</label>
                                <select name="city_id" id="select-city" class="form-select city_base_state">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Area</label>
                                <input type="text"
                                    name="area"
                                    id="area"
                                    class="form-control"
                                    placeholder="Select Area"
                                    value="<?= htmlspecialchars($customer['area'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Pincode</label>
                                <input type="text"
                                    name="pincode"
                                    id="pincode"
                                    class="form-control"
                                    placeholder="Enter Pincode"
                                    maxlength="10"
                                    value="<?= htmlspecialchars($customer['pincode'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 6: Select Price List -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Select Price List</label>
                                <select name="price_list" id="select-price-list" class="form-select">
                                    <option value="">Select Price List</option>
                                    <option value="Retail" <?= ($customer['price_list'] === 'Retail') ? 'selected' : '' ?>>Retail</option>
                                    <option value="Wholesale" <?= ($customer['price_list'] === 'Wholesale') ? 'selected' : '' ?>>Wholesale</option>
                                    <option value="Dealer" <?= ($customer['price_list'] === 'Dealer') ? 'selected' : '' ?>>Dealer</option>
                                    <option value="Distributor" <?= ($customer['price_list'] === 'Distributor') ? 'selected' : '' ?>>Distributor</option>
                                    <option value="Special" <?= ($customer['price_list'] === 'Special') ? 'selected' : '' ?>>Special</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Address</label>
                                <textarea
                                    name="address"
                                    id="address"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Enter Address"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Shipping Address</label>
                                <textarea
                                    name="shipping_address"
                                    id="shipping_address"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Enter Shipping Address"><?= htmlspecialchars($customer['shipping_address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Billing Address</label>
                                <textarea
                                    name="billing_address"
                                    id="billing_address"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Enter Billing Address"><?= htmlspecialchars($customer['billing_address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- ROW 8: Latitude | Longitude | Status -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Latitude</label>
                                <input type="text"
                                    name="latitude"
                                    id="latitude"
                                    class="form-control"
                                    placeholder="Enter Latitude"
                                    value="<?= htmlspecialchars($customer['latitude'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Longitude</label>
                                <input type="text"
                                    name="longitude"
                                    id="longitude"
                                    class="form-control"
                                    placeholder="Enter Longitude"
                                    value="<?= htmlspecialchars($customer['longitude'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 9: Assigned To -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Assign To</label>
                                <select name="assigned_to" id="select-assigned-to" class="form-select">
                                    <option value="">Select User</option>
                                    <?php foreach ($teamPersons as $tp): ?>
                                        <option value="<?= (int)$tp['id'] ?>" <?= ((int)($customer['assigned_to'] ?? 0) === (int)$tp['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tp['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-start gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                            <span id="submitText"><?= $isEdit ? 'Update' : 'Submit' ?></span>
                            <span id="submitLoader" class="spinner-border spinner-border-sm d-none ms-1"></span>
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
$(document).ready(function() {
    let isSuperadmin = <?= $isSuperadmin ? 'true' : 'false' ?>;

    // 1. Same as mobile toggle
    $('#same_as_mobile').on('change', function() {
        if ($(this).is(':checked')) {
            $('#whatsapp_no').val($('#mobile_no').val()).prop('readonly', true);
        } else {
            $('#whatsapp_no').prop('readonly', false);
        }
    });

    $('#mobile_no').on('input', function() {
        if ($('#same_as_mobile').is(':checked')) {
            $('#whatsapp_no').val($(this).val());
        }
    });

    if ($('#mobile_no').val() && $('#mobile_no').val() === $('#whatsapp_no').val()) {
        $('#same_as_mobile').prop('checked', true);
        $('#whatsapp_no').prop('readonly', true);
    }

    // 2. Initialize flatpickr for birth date
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#birth_date", {
            dateFormat: "d-m-Y",
            allowInput: true
        });
    }

    function initTom(selector, placeholder) {
        if (typeof TomSelect !== 'undefined' && $(selector).length) {
            let el = $(selector)[0];
            if (el.tomselect) return el.tomselect;
            return new TomSelect(selector, {
                create: false,
                allowEmptyOption: true,
                placeholder: placeholder || 'Select an option'
            });
        }
        return null;
    }

    if (isSuperadmin) {
        initTom('#select-company', 'Select Company');
    }
    initTom('#select-customer-type', 'Select Customer Type');
    initTom('#select-country', 'Select Country');
    initTom('#select-state', 'Select State');
    initTom('#select-city', 'Select City');
    initTom('#select-price-list', 'Select Price List');
    initTom('#select-assigned-to', 'Select User');

    // Preselect State & City on Edit
    <?php if ($isEdit && !empty($customer['country_id'])): ?>
        let editCountryId = '<?= (int)$customer['country_id'] ?>';
        let editStateId   = '<?= (int)($customer['state_id'] ?? 0) ?>';
        let editCityId    = '<?= (int)($customer['city_id'] ?? 0) ?>';

        if (typeof loadStatesByCountry === 'function') {
            loadStatesByCountry(editCountryId, editStateId, function() {
                if (editStateId && typeof loadCitiesByState === 'function') {
                    loadCitiesByState(editStateId, editCityId);
                }
            });
        }
    <?php endif; ?>

    // Dynamic customer types reload when Superadmin changes company
    if (isSuperadmin) {
        $(document).on('change', '#select-company', function() {
            let compId = $(this).val();
            let ctSelect = $('#select-customer-type')[0];
            if (!compId) return;

            $.ajax({
                url: '<?= SITE_URL ?>admin/sales/customer/ajax.php',
                type: 'GET',
                data: { action: 'get_company_types', company_id: compId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === true && ctSelect && ctSelect.tomselect) {
                        ctSelect.tomselect.clear();
                        ctSelect.tomselect.clearOptions();
                        ctSelect.tomselect.addOption({ value: '', text: 'Select Customer Type', $order: 1 });
                        let order = 2;
                        $.each(res.data, function(i, item) {
                            ctSelect.tomselect.addOption({ value: String(item.id), text: item.name, $order: order++ });
                        });
                        ctSelect.tomselect.setValue('');
                        ctSelect.tomselect.refreshOptions(false);
                    }
                }
            });

            let tpSelect = $('#select-assigned-to')[0];
            $.ajax({
                url: '<?= SITE_URL ?>admin/sales/customer/ajax.php',
                type: 'GET',
                data: { action: 'get_company_users', company_id: compId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === true && tpSelect && tpSelect.tomselect) {
                        tpSelect.tomselect.clear();
                        tpSelect.tomselect.clearOptions();
                        tpSelect.tomselect.addOption({ value: '', text: 'Select User', $order: 1 });
                        let order = 2;
                        $.each(res.data, function(i, item) {
                            tpSelect.tomselect.addOption({ value: String(item.id), text: item.name, $order: order++ });
                        });
                        tpSelect.tomselect.setValue('');
                        tpSelect.tomselect.refreshOptions(false);
                    }
                }
            });
        });
    }

    // Form Validation & AJAX Submit
    $("#customerForm").validate({
        rules: {
            <?php if ($isSuperadmin): ?>
            company_id: {
                required: true
            },
            <?php endif; ?>
            customer_type_id: {
                required: true
            },
            name: {
                required: true,
                minlength: 2,
                maxlength: 150
            },
            contact_person: {
                required: true,
                minlength: 2,
                maxlength: 150
            },
            mobile_no: {
                required: true,
                minlength: 10,
                maxlength: 15
            },
            address: {
                required: true
            },
            email: {
                email: true
            }
        },
        messages: {
            <?php if ($isSuperadmin): ?>
            company_id: {
                required: "Please select company"
            },
            <?php endif; ?>
            customer_type_id: {
                required: "Please select customer type"
            },
            name: {
                required: "Please enter customer/company name",
                minlength: "Customer name must be at least 2 characters"
            },
            contact_person: {
                required: "Please enter contact person name",
                minlength: "Contact person must be at least 2 characters"
            },
            mobile_no: {
                required: "Please enter mobile number",
                minlength: "Mobile number must be at least 10 digits"
            },
            address: {
                required: "Please enter address"
            },
            email: {
                email: "Please enter a valid email address"
            }
        },
        errorElement: "span",
        errorClass: "text-danger fs-12",
        errorPlacement: function(error, element) {
            if (element.hasClass('form-select') && element[0].tomselect) {
                error.insertAfter($(element[0].tomselect.wrapper));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function(form) {
            let formData = new FormData(form);
            let $button = $("#submitBtn");
            let $text = $("#submitText");
            let $loader = $("#submitLoader");

            $.ajax({
                url: "<?= SITE_URL ?>admin/sales/customer/store.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                beforeSend: function() {
                    $button.prop("disabled", true);
                    $text.text("Saving...");
                    $loader.removeClass("d-none");
                },
                success: function(response) {
                    if (response.status === true) {
                        showToast(response.message, "success");
                        setTimeout(function() {
                            window.location.href = "<?= SITE_URL ?>customer";
                        }, 1000);
                    } else {
                        showToast(response.message, "error");
                        $button.prop("disabled", false);
                        $text.text("<?= $isEdit ? 'Update' : 'Submit' ?>");
                        $loader.addClass("d-none");
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    showToast("An error occurred while saving the customer.", "error");
                    $button.prop("disabled", false);
                    $text.text("<?= $isEdit ? 'Update' : 'Submit' ?>");
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

