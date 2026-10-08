<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

include BASE_PATH . '/include/header.php';

$pageNm = 'Company Lead';
$tbl = 'company_lead';
$moduleKey = 'company-lead';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isView = isset($_GET['view']) && $_GET['view'] == '1';
$isEdit = $id > 0 && !$isView;

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

$requiredAction = $isView ? 'views' : ($isEdit ? 'updates' : 'adds');
checkPermissionOrDeny($moduleKey, $requiredAction);

// Generate Next / Default Inquiry Number
$randomInqNo = 'INQ-' . mt_rand(1000, 9999);

$lead = [
    'id'                   => 0,
    'company_id'           => $sessionCompanyId,
    'inquiry_no'           => $randomInqNo,
    'inquiry_date'         => date('d-m-Y'),
    'customer_name'        => '',
    'contact_person'       => '',
    'mobile_no'            => '',
    'whatsapp_no'          => '',
    'email'                => '',
    'website'              => '',
    'inquiry_status'       => 'New Lead',
    'source_of_inquiry_id' => '',
    'address'              => '',
    'pincode'              => '',
    'country_id'           => '',
    'state_id'             => '',
    'city_id'              => '',
    'area'                 => '',
    'assigned_to'          => '',
    'attachment'           => '',
    'requirement_details'  => '',
    'followup_date'        => '',
    'followup_details'     => '',
    'status'               => 1
];

if ($id > 0) {
    $leadData = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$leadData) {
        die($pageNm . ' not found.');
    }

    // Permission check for company and assigned user
    if (!$isSuperadmin && $sessionCompanyId > 0 && (int)$leadData['company_id'] !== $sessionCompanyId) {
        die('Unauthorized access.');
    }

    $userType = $_SESSION['user_type'] ?? '';
    $currentUserId = getCurrentUserId();
    if ($userType === 'user' && (int)($leadData['assigned_to'] ?? 0) !== $currentUserId) {
        die('Unauthorized access.');
    }

    // If customer already created for this lead, direct editing is not allowed
    if ($isEdit) {
        $hasCust = db_row("SELECT id FROM customer WHERE company_lead_id = $id LIMIT 1");
        if ($hasCust) {
            header('Location: ' . SITE_URL . 'company-lead/view/' . encrypt_id($id));
            exit;
        }
    }

    $lead = array_merge($lead, $leadData);
    if (!empty($leadData['inquiry_date']) && $leadData['inquiry_date'] !== '0000-00-00') {
        $lead['inquiry_date'] = date('d-m-Y', strtotime($leadData['inquiry_date']));
    }
    if (!empty($leadData['followup_date']) && $leadData['followup_date'] !== '0000-00-00') {
        $lead['followup_date'] = date('d-m-Y', strtotime($leadData['followup_date']));
    } else {
        $lead['followup_date'] = '';
    }

    // If inquiry_status is stored as numeric ID, resolve to its name
    if (!empty($lead['inquiry_status']) && is_numeric($lead['inquiry_status'])) {
        $mInq = db_row("SELECT name FROM marketing_status WHERE id = " . (int)$lead['inquiry_status']);
        if ($mInq && !empty($mInq['name'])) {
            $lead['inquiry_status'] = $mInq['name'];
        }
    }
}

$currentCompanyId = $isSuperadmin ? (int)($lead['company_id'] ?: $sessionCompanyId) : $sessionCompanyId;

// 1. Fetch Countries
$countries = db_rows("SELECT id, name FROM country WHERE status = 1 ORDER BY name ASC");

// 2. Fetch Sources of Inquiry (company-wise from source_of_inquiry table)
$soiCondition = "status = 1";
if ($currentCompanyId > 0) {
    $soiCondition .= " AND (company_id = $currentCompanyId OR company_id = 0)";
}
$sourcesOfInquiry = db_rows("SELECT id, name FROM source_of_inquiry WHERE $soiCondition ORDER BY order_by ASC, name ASC");

// 3. Fetch Team Person (Users table where company_id = company_id and status = 1)
$teamCondition = "status = 1";
if ($currentCompanyId > 0) {
    $teamCondition .= " AND company_id = $currentCompanyId";
}
$teamPersons = db_rows("SELECT id, name, user_type FROM users WHERE $teamCondition ORDER BY name ASC");

// Default Assigned To if new and logged in user is among team
if (!$isEdit && empty($lead['assigned_to'])) {
    $loggedUserId = getCurrentUserId();
    foreach ($teamPersons as $tp) {
        if ((int)$tp['id'] === $loggedUserId) {
            $lead['assigned_to'] = $loggedUserId;
            break;
        }
    }
    if (empty($lead['assigned_to']) && !empty($teamPersons)) {
        $lead['assigned_to'] = $teamPersons[0]['id'];
    }
}

// 4. Marketing Status Options (company-wise from marketing_status table)
$mktLeadCondition = "status = 1 AND type = 'Lead'";
if ($currentCompanyId > 0) {
    $mktLeadCondition .= " AND (company_id = $currentCompanyId OR company_id = 0)";
}

$inquiryStatuses  = db_rows("SELECT id, name, color FROM marketing_status WHERE $mktLeadCondition ORDER BY order_by ASC, name ASC");

// Companies list for Superadmin
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}

$breadcrumbType = 'form';
$parentUrl = SITE_URL . 'company-lead';
include BASE_PATH . '/component/breadcrumb.php';
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold"><?= $isEdit ? 'Edit ' . $pageNm : 'Add ' . $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="companyLeadForm" enctype="multipart/form-data">
                    <?php if ($id > 0): ?>
                        <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>" id="id">
                    <?php endif; ?>

                    <?php if ($isSuperadmin): ?>
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Company</label>
                                <select name="company_id" id="select-company-owner" class="form-select">
                                    <option value="">Select Company</option>
                                    <?php foreach ($companies as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$lead['company_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <!-- ROW 1: Inquiry No | Inquiry Date | Customer Name | Contact Person Name -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Inquiry No</label>
                                <input type="text"
                                    name="inquiry_no"
                                    id="inquiry_no"
                                    class="form-control bg-light"
                                    readonly
                                    value="<?= htmlspecialchars($lead['inquiry_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Inquiry Date</label>
                                <input type="text"
                                    name="inquiry_date"
                                    id="inquiry_date"
                                    class="form-control"
                                    placeholder="DD-MM-YYYY"
                                    value="<?= htmlspecialchars($lead['inquiry_date'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Customer Name</label>
                                <input type="text"
                                    name="customer_name"
                                    id="customer_name"
                                    class="form-control"
                                    placeholder="Customer Name"
                                    value="<?= htmlspecialchars($lead['customer_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Contact Person Name</label>
                                <input type="text"
                                    name="contact_person"
                                    id="contact_person"
                                    class="form-control"
                                    placeholder="Contact Person Name"
                                    value="<?= htmlspecialchars($lead['contact_person'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 2: Mobile No | WhatsApp No (with checkbox) | Email | Website -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Mobile No</label>
                                <input type="text"
                                    name="mobile_no"
                                    id="mobile_no"
                                    class="form-control"
                                    placeholder="Mobile No"
                                    maxlength="15"
                                    value="<?= htmlspecialchars($lead['mobile_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
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
                                    value="<?= htmlspecialchars($lead['whatsapp_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Email</label>
                                <input type="email"
                                    name="email"
                                    id="email"
                                    class="form-control"
                                    placeholder="Email"
                                    value="<?= htmlspecialchars($lead['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Website</label>
                                <input type="text"
                                    name="website"
                                    id="website"
                                    class="form-control"
                                    placeholder="Website"
                                    value="<?= htmlspecialchars($lead['website'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 3: Select Inquiry Status | Select Source of Inquiry | Address -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Select Inquiry Status</label>
                                <select name="inquiry_status" id="select-inquiry-status" class="form-select">
                                    <option value="">Select Inquiry Status</option>
                                    <?php foreach ($inquiryStatuses as $st): ?>
                                        <option value="<?= (int)$st['id'] ?>" <?= ((string)($lead['inquiry_status'] ?? '') === (string)$st['id'] || strcasecmp((string)($lead['inquiry_status'] ?? ''), (string)$st['name']) === 0) ? 'selected' : '' ?>>
                                             <?= htmlspecialchars($st['name']) ?>
                                         </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Select Source of Inquiry</label>
                                <select name="source_of_inquiry_id" id="select-source-of-inquiry" class="form-select">
                                    <option value="">Select Source of Inquiry</option>
                                    <?php foreach ($sourcesOfInquiry as $soi): ?>
                                        <option value="<?= (int)$soi['id'] ?>" <?= ((int)$lead['source_of_inquiry_id'] === (int)$soi['id']) ? 'selected' : '' ?>>
                                             <?= htmlspecialchars($soi['name']) ?>
                                         </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Address</label>
                                <input type="text"
                                    name="address"
                                    id="address"
                                    class="form-control"
                                    placeholder="Address"
                                    value="<?= htmlspecialchars($lead['address'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 4: Pincode | Country | State | City -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Pincode</label>
                                <input type="text"
                                    name="pincode"
                                    id="pincode"
                                    class="form-control"
                                    placeholder="Pincode"
                                    maxlength="10"
                                    value="<?= htmlspecialchars($lead['pincode'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Country</label>
                                <select name="country_id" id="select-country" class="form-select country_base_state">
                                    <option value="">Select Country</option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$lead['country_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                             <?= htmlspecialchars($c['name']) ?>
                                         </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">State</label>
                                <select name="state_id" id="select-state" class="form-select state_base_city">
                                    <option value="">Select State</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">City</label>
                                <select name="city_id" id="select-city" class="form-select city_base_state">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>

                        <!-- ROW 5: Select Assigned To | Attachment | Requirement Details -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Select Assigned To</label>
                                <select name="assigned_to" id="select-assigned-to" class="form-select">
                                    <option value="">Select Assigned To</option>
                                    <?php foreach ($teamPersons as $tp): ?>
                                        <option value="<?= (int)$tp['id'] ?>" <?= ((int)$lead['assigned_to'] === (int)$tp['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tp['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Attachment</label>
                                <input type="file"
                                    name="attachment"
                                    id="attachment"
                                    class="form-control">
                                <?php if (!empty($lead['attachment'])): ?>
                                    <div class="mt-1 fs-12">
                                        <a href="<?= SITE_URL ?>uploads/lead/<?= htmlspecialchars($lead['attachment']) ?>" target="_blank" class="text-primary">
                                            <i data-lucide="paperclip" class="fs-12 align-middle"></i> View Current Attachment
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Requirement Details</label>
                                <input type="text"
                                    name="requirement_details"
                                    id="requirement_details"
                                    class="form-control"
                                    placeholder="Requirement Details"
                                    value="<?= htmlspecialchars($lead['requirement_details'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ROW 6: Followup Date | Followup Details -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Followup Date</label>
                                <input type="text"
                                    name="followup_date"
                                    id="followup_date"
                                    class="form-control"
                                    placeholder="Followup Date"
                                    value="<?= htmlspecialchars($lead['followup_date'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-2">
                                <label class="form-label">Followup Details</label>
                                <input type="text"
                                    name="followup_details"
                                    id="followup_details"
                                    class="form-control"
                                    placeholder="Followup Details"
                                    value="<?= htmlspecialchars($lead['followup_details'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 mt-4 d-flex justify-content-start gap-2">
                        <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                            <span id="submitLoader" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            <span id="submitText"><?= $isEdit ? 'Update' : 'Submit' ?></span>
                        </button>
                        <a href="<?= SITE_URL ?>company-lead" class="btn btn-light px-4">Cancel</a>
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

    // 2. Datepickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#inquiry_date", {
            dateFormat: "d-m-Y",
            allowInput: true
        });
        flatpickr("#followup_date", {
            dateFormat: "d-m-Y",
            allowInput: true
        });
    }

    // 3. TomSelect helper
    function initTom(selector) {
        let el = document.querySelector(selector);
        if (el && typeof TomSelect !== 'undefined' && !el.tomselect) {
            let ts = new TomSelect(el, {
                allowEmptyOption: true,
                sortField: [{ field: '$order' }]
            });
            // If native select had an initial value, ensure TomSelect keeps it selected
            if (el.value) {
                ts.setValue(el.value, true);
            }
            return ts;
        }
    }

    initTom('#select-inquiry-status');
    initTom('#select-source-of-inquiry');
    initTom('#select-country');
    initTom('#select-state');
    initTom('#select-city');
    initTom('#select-assigned-to');
    initTom('#select-company-owner');

    // Function to reload Source of Inquiry, Assigned To, Inquiry Status dropdowns when Company changes
    function reloadCompanyDropdowns(compVal, preAssignedTo = '', preSourceId = '', preInqStatus = '') {
        if (!compVal) {
            let tpSelect  = $('#select-assigned-to')[0];
            let soiSelect = $('#select-source-of-inquiry')[0];
            let inqSelect = $('#select-inquiry-status')[0];

            if (tpSelect && tpSelect.tomselect) {
                tpSelect.tomselect.clear();
                tpSelect.tomselect.clearOptions();
                tpSelect.tomselect.addOption({ value: '', text: 'Select Assigned To', $order: 1 });
                tpSelect.tomselect.setValue('');
            }
            if (soiSelect && soiSelect.tomselect) {
                soiSelect.tomselect.clear();
                soiSelect.tomselect.clearOptions();
                soiSelect.tomselect.addOption({ value: '', text: 'Select Source of Inquiry', $order: 1 });
                soiSelect.tomselect.setValue('');
            }
            if (inqSelect && inqSelect.tomselect) {
                inqSelect.tomselect.clear();
                inqSelect.tomselect.clearOptions();
                inqSelect.tomselect.addOption({ value: '', text: 'Select Inquiry Status', $order: 1 });
                inqSelect.tomselect.setValue('');
            }
            return;
        }

        $.ajax({
            url: "<?= SITE_URL ?>admin/setting/company-lead/store.php",
            type: "GET",
            data: {
                action: 'get_company_dropdowns',
                company_id: compVal
            },
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    // 1. Update Assigned To dropdown
                    let tpSelect = $('#select-assigned-to')[0];
                    if (tpSelect && tpSelect.tomselect) {
                        tpSelect.tomselect.clear();
                        tpSelect.tomselect.clearOptions();
                        tpSelect.tomselect.addOption({ value: '', text: 'Select Assigned To', $order: 1 });
                        let order = 2;
                        $.each(res.team_persons, function(i, item) {
                            tpSelect.tomselect.addOption({ value: String(item.id), text: item.name, $order: order++ });
                        });
                        if (preAssignedTo) {
                            tpSelect.tomselect.setValue(String(preAssignedTo));
                        } else {
                            tpSelect.tomselect.setValue('');
                        }
                        tpSelect.tomselect.refreshOptions(false);
                    } else {
                        let optHtml = '<option value="">Select Assigned To</option>';
                        $.each(res.team_persons, function(i, item) {
                            let sel = (String(preAssignedTo) === String(item.id)) ? 'selected' : '';
                            optHtml += '<option value="' + item.id + '" ' + sel + '>' + item.name + '</option>';
                        });
                        $('#select-assigned-to').html(optHtml);
                    }

                    // 2. Update Source of Inquiry dropdown
                    let soiSelect = $('#select-source-of-inquiry')[0];
                    if (soiSelect && soiSelect.tomselect) {
                        soiSelect.tomselect.clear();
                        soiSelect.tomselect.clearOptions();
                        soiSelect.tomselect.addOption({ value: '', text: 'Select Source of Inquiry', $order: 1 });
                        let order = 2;
                        $.each(res.sources_of_inquiry, function(i, item) {
                            soiSelect.tomselect.addOption({ value: String(item.id), text: item.name, $order: order++ });
                        });
                        if (preSourceId) {
                            soiSelect.tomselect.setValue(String(preSourceId));
                        } else {
                            soiSelect.tomselect.setValue('');
                        }
                        soiSelect.tomselect.refreshOptions(false);
                    } else {
                        let optHtml = '<option value="">Select Source of Inquiry</option>';
                        $.each(res.sources_of_inquiry, function(i, item) {
                            let sel = (String(preSourceId) === String(item.id)) ? 'selected' : '';
                            optHtml += '<option value="' + item.id + '" ' + sel + '>' + item.name + '</option>';
                        });
                        $('#select-source-of-inquiry').html(optHtml);
                    }

                    // 3. Update Inquiry Status dropdown (Type = 'Lead')
                    let inqSelect = $('#select-inquiry-status')[0];
                    if (inqSelect && inqSelect.tomselect) {
                        inqSelect.tomselect.clear();
                        inqSelect.tomselect.clearOptions();
                        inqSelect.tomselect.addOption({ value: '', text: 'Select Inquiry Status', $order: 1 });
                        let order = 2;
                        $.each(res.inquiry_statuses, function(i, item) {
                            inqSelect.tomselect.addOption({ value: String(item.id), text: item.name, $order: order++ });
                        });
                        if (preInqStatus) {
                            inqSelect.tomselect.setValue(String(preInqStatus));
                        } else {
                            inqSelect.tomselect.setValue('');
                        }
                        inqSelect.tomselect.refreshOptions(false);
                    } else {
                        let optHtml = '<option value="">Select Inquiry Status</option>';
                        $.each(res.inquiry_statuses, function(i, item) {
                            let sel = (String(preInqStatus) === String(item.id) || String(preInqStatus) === String(item.name)) ? 'selected' : '';
                            optHtml += '<option value="' + item.id + '" ' + sel + '>' + item.name + '</option>';
                        });
                        $('#select-inquiry-status').html(optHtml);
                    }
                }
            }
        });
    }

    // When Superadmin changes company dropdown
    $(document).on('change', '#select-company-owner', function() {
        let selectedCid = $(this).val();
        reloadCompanyDropdowns(selectedCid);
    });

    // 4. Preselect State & City in Edit/View Mode
    <?php if (($isEdit || $isView) && !empty($lead['country_id'])): ?>
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

    // 5. Form Validation & AJAX Submit
    $("#companyLeadForm").validate({
        rules: {
            customer_name: {
                required: true,
                minlength: 2,
                maxlength: 150
            },
            mobile_no: {
                required: true,
                minlength: 10,
                maxlength: 10,
                digits: true
            },
            inquiry_status: {
                required: true
            },
            source_of_inquiry_id: {
                required: true
            },
            assigned_to: {
                required: true
            }
        },
        messages: {
            customer_name: {
                required: "Please enter customer name",
                minlength: "Customer name must be at least 2 characters"
            },
            mobile_no: {
                required: "Please enter mobile number",
                minlength: "Mobile number must be at least 10 digits",
                maxlength: "Mobile number must be at most 10 digits",
                digits: "Mobile number must be digits only"
            },
            inquiry_status: {
                required: "Please select inquiry status"
            },
            source_of_inquiry_id: {
                required: "Please select source of inquiry"
            },
            assigned_to: {
                required: "Please select assigned person"
            }
        },
        errorElement: "span",
        errorClass: "text-danger",
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
                url: "<?= SITE_URL ?>admin/setting/company-lead/store.php",
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
                            window.location.href = "<?= SITE_URL ?>company-lead";
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
                    showToast("An error occurred while saving the lead.", "error");
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
