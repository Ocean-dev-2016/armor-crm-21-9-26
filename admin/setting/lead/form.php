<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$pageNm = 'Lead';
$tbl = 'lead';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isEdit = $id > 0;

$lead = [
    'id'                 => 0,
    'lead_number'        => 'OI-' . strtoupper(substr(uniqid(), -6)),
    'business_name'      => '',
    'contact_name'       => '',
    'mobile_no'          => '',
    'whatsapp_no'        => '',
    'email'              => '',
    'website'            => '',
    'country_id'         => '',
    'state_id'           => '',
    'city_id'            => '',
    'pincode'            => '',
    'address'            => '',
    'team_size'          => 1,
    'interested_plan_id' => '',
    'lead_source'        => 1,
    'lead_stage'         => 1,
    'assign_to'          => '',
    'demo_date'          => date('d-m-Y'),
    'requirements'       => '',
    'notes'              => '',
    'status'             => 1
];

if ($isEdit) {
    $leadData = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$leadData) {
        die($pageNm . ' not found.');
    }
    $lead = array_merge($lead, $leadData);
    if (!empty($leadData['demo_date']) && $leadData['demo_date'] !== '0000-00-00 00:00:00' && $leadData['demo_date'] !== '0000-00-00') {
        $formattedDemo = formatDate($leadData['demo_date'], 'd-m-Y');
        $lead['demo_date'] = $formattedDemo ? $formattedDemo : '';
    } else {
        $lead['demo_date'] = '';
    }
}
$countries     = db_rows("SELECT id, name FROM country WHERE status = 1 ORDER BY name ASC");
$leadSources   = db_rows("SELECT id, name FROM lead_source_of_inquiry WHERE status = 1 ORDER BY name ASC");
$leadStatuses  = db_rows("SELECT id, name, color FROM lead_status WHERE status = 1 ORDER BY order_by ASC, id ASC");
$leadEmployees = db_rows("SELECT id, name FROM lead_employee WHERE status = 1 ORDER BY name ASC");

include BASE_PATH . '/include/header.php';

$breadcrumbType = 'form';
$isEdit = $isEdit ? true : false;
$parentUrl = SITE_URL . 'lead';
include BASE_PATH . '/component/breadcrumb.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="saasLeadForm">
                    <?php if ($isEdit): ?>
                        <input type="hidden"
                            name="id"
                            value="<?= (int)$lead['id'] ?>" id="id">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Lead Number</label>
                                <input type="text"
                                    name="lead_number"
                                    id="lead_number"
                                    class="form-control"
                                    readonly
                                    value="<?= htmlspecialchars($lead['lead_number'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Business Name / Customer Name</label>
                                <input type="text"
                                    name="business_name"
                                    id="business_name"
                                    class="form-control"
                                    placeholder="Enter Business Name / Customer Name"
                                    value="<?= htmlspecialchars($lead['business_name'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text"
                                    name="contact_name"
                                    id="contact_name"
                                    class="form-control"
                                    placeholder="Enter Contact Person"
                                    value="<?= htmlspecialchars($lead['contact_name'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Mobile No.</label>
                                <input type="text"
                                    name="mobile_no"
                                    id="mobile_no"
                                    class="form-control"
                                    placeholder="Enter Mobile No."
                                    value="<?= htmlspecialchars($lead['mobile_no'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0" for="whatsapp_no">WhatsApp No.</label>
                                    <label class="form-check-label small text-muted cursor-pointer mb-0" style="cursor: pointer;">
                                        <input type="checkbox" class="form-check-input me-1" id="same_as_mobile" onchange="copyWhatsappNo()"> Same as Mobile
                                    </label>
                                </div>
                                <input type="text"
                                    name="whatsapp_no"
                                    id="whatsapp_no"
                                    class="form-control"
                                    placeholder="Enter WhatsApp No."
                                    value="<?= htmlspecialchars($lead['whatsapp_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email"
                                    name="email"
                                    id="email"
                                    class="form-control"
                                    placeholder="Enter Email"
                                    value="<?= htmlspecialchars($lead['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <select name="country_id" id="select-country" class="form-select country_base_state">
                                    <option value="">Select a Country</option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" <?= ((int)($lead['country_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                <label class="form-label">Website</label>
                                <input type="text"
                                    name="website"
                                    id="website"
                                    class="form-control"
                                    placeholder="Enter Website"
                                    value="<?= htmlspecialchars($lead['website'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Pincode</label>
                                <input type="text"
                                    name="pincode"
                                    id="pincode"
                                    class="form-control"
                                    placeholder="Enter Pincode"
                                    value="<?= htmlspecialchars($lead['pincode'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Team Size</label>
                                <input type="number"
                                    name="team_size"
                                    id="team_size"
                                    class="form-control"
                                    placeholder="Enter Team Size"
                                    value="<?= htmlspecialchars($lead['team_size'] ?? '') ?>"
                                    min="1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3"> 
                                <label class="form-label">Lead Source of Inquiry</label>
                                <select name="lead_source" id="select-lead-source" class="form-select">
                                    <option value="">Select Lead Source of Inquiry</option>
                                    <?php foreach ($leadSources as $src): ?>
                                        <option value="<?= (int)$src['id'] ?>" <?= ((string)($lead['lead_source'] ?? '') === (string)$src['id'] || (string)($lead['lead_source'] ?? '') === $src['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($src['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Lead Status</label>
                                <select name="lead_stage" id="select-lead-stage" class="form-select">
                                    <option value="">Select Lead Status</option>
                                    <?php foreach ($leadStatuses as $stg): ?>
                                        <option value="<?= (int)$stg['id'] ?>" <?= ((string)($lead['lead_stage'] ?? '') === (string)$stg['id'] || strtolower((string)($lead['lead_stage'] ?? '')) === strtolower($stg['name'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($stg['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="text"
                                    id="demo_date"
                                    name="demo_date"
                                    class="form-control"
                                    placeholder="Select Date"
                                    value="<?= htmlspecialchars($lead['demo_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3"> 
                                <label class="form-label">Selected By</label>
                                <select name="selected_by" id="select-selected-by" class="form-select" disabled>
                                    <option value="1">Superadmin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3"> 
                                <label class="form-label">Assign To</label>
                                <select name="assign_to" id="select-assign-to" class="form-select">
                                    <option value="">Select Assign To</option>
                                    <?php foreach ($leadEmployees as $emp): ?>
                                        <option value="<?= (int)$emp['id'] ?>" <?= ((string)($lead['assign_to'] ?? '') === (string)$emp['id'] || (string)($lead['assign_to'] ?? '') === $emp['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($emp['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="mb-3">
                                <label class="form-label">Requirements</label>
                                <input type="text"
                                    name="requirements"
                                    id="requirements"
                                    class="form-control"
                                    placeholder="Enter Requirements"
                                    value="<?= htmlspecialchars($lead['requirements'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                    id="address"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Enter Office / Company Address"><?= htmlspecialchars($lead['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes"
                                    id="notes"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Enter Notes / Interaction history"><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
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

<script>
    function copyWhatsappNo() {
        if ($('#same_as_mobile').is(':checked')) {
            $('#whatsapp_no').val($('#mobile_no').val());
        }
    }

    $(document).ready(function() {
        if (window.lucide) {
            lucide.createIcons();
        }

        // Live sync if checkbox is checked and mobile number changes
        $('#mobile_no').on('input change', function() {
            if ($('#same_as_mobile').is(':checked')) {
                $('#whatsapp_no').val($(this).val());
            }
        });

        // Uncheck if user manually changes whatsapp number
        $('#whatsapp_no').on('input', function() {
            if ($(this).val() !== $('#mobile_no').val()) {
                $('#same_as_mobile').prop('checked', false);
            }
        });

        // Check if values already match on edit load
        if ($('#mobile_no').val() && $('#mobile_no').val() === $('#whatsapp_no').val()) {
            $('#same_as_mobile').prop('checked', true);
        }

        // Initialize TomSelect on all selects
        function initTom(selector, allowCreate = false) {
            let el = document.querySelector(selector);
            if (el && typeof TomSelect !== 'undefined' && !el.tomselect) {
                new TomSelect(el, {
                    create: allowCreate,
                    allowEmptyOption: true,
                    sortField: allowCreate ? { field: "text", direction: "asc" } : [{ field: '$order' }]
                });
            }
        }

        initTom('#select-country', false);
        initTom('#select-state', false);
        initTom('#select-city', false);
        initTom('#select-plan', false);
        initTom('#select-team-size', false);
        initTom('#select-lead-source', false);
        initTom('#select-lead-stage', false);
        initTom('#select-assign-to', false);

        // Preselect and load dynamic state/city in Edit Mode
        <?php if ($isEdit && !empty($lead['country_id'])): ?>
            let editCountryId = '<?= (int)$lead['country_id'] ?>';
            let editStateId   = '<?= (int)($lead['state_id'] ?? 0) ?>';
            let editCityId    = '<?= (int)($lead['city_id'] ?? 0) ?>';

            let countryEl = $('#select-country')[0];
            if (countryEl && countryEl.tomselect) {
                countryEl.tomselect.setValue(editCountryId);
            } else {
                $('#select-country').val(editCountryId);
            }

            if (typeof loadStatesByCountry === 'function') {
                loadStatesByCountry(editCountryId, editStateId, function() {
                    if (editStateId && typeof loadCitiesByState === 'function') {
                        loadCitiesByState(editStateId, editCityId);
                    }
                });
            }
        <?php endif; ?>

        if ($("#demo_date").length && typeof flatpickr !== 'undefined') {
            flatpickr("#demo_date", {
                dateFormat: "d-m-Y",
                allowInput: true,
                defaultDate: $("#demo_date").val() || new Date()
            });
        }

        $("#saasLeadForm").validate({
            rules: {
                business_name: {
                    required: true,
                    minlength: 2,
                    maxlength: 150
                },
                contact_name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                mobile_no: {
                    required: true,
                    minlength: 10,
                    maxlength: 10
                },
                whatsapp_no: {
                    required: true,
                    minlength: 10,
                    maxlength: 10
                }
            },
            messages: {
                business_name: {
                    required: "Please enter business name",
                    minlength: "Business name must be at least 2 characters",
                    maxlength: "Business name cannot exceed 150 characters"
                },
                contact_name: {
                    required: "Please enter contact person name",
                    minlength: "Contact name must be at least 2 characters"
                },
                mobile_no: {
                    required: "Please enter mobile number",
                    minlength: "Mobile number must be at least 10 digits"
                },
                whatsapp_no: {
                    required: "Please enter WhatsApp number",
                    minlength: "WhatsApp number must be at least 10 digits"
                }
            },
            errorElement: "span",
            errorClass: "text-danger",
            submitHandler: function(form) {
                let $form = $(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");

                $.ajax({
                    url: "<?= SITE_URL ?>admin/setting/lead/store.php",
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
                            showToast(
                                response.message,
                                "success"
                            );
                            setTimeout(function() {
                                window.location.href = "<?= SITE_URL ?>lead";
                            }, 1000);
                        } else {
                            showToast(
                                response.message,
                                "error"
                            );
                        }
                    },
                    error: function(xhr) {
                        console.log("AJAX error:", xhr);
                        showToast(
                            "Something went wrong. Please try again.",
                            "error"
                        );
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                        $text.text(
                            <?= $isEdit ? "'Update'" : "'Submit'" ?>
                        );
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
