<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'transporter-detail';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Transporter Detail';
$tbl = 'transporter_detail';
$fields = 'transport_by_id:Transport By,name:Transport Name';
$module = 'transporter-detail';
$showAdd = hasPermission($moduleKey, 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$sessionCompanyId = (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : 0;

$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}

// Prefetch transport_by for non-superadmin (active company)
$transportByList = [];
if (!$isSuperadmin && $sessionCompanyId > 0) {
    $transportByList = db_rows("SELECT id, name FROM transport_by WHERE company_id = $sessionCompanyId AND status = 1 ORDER BY name ASC");
}
?>

<!-- Transporter Detail Modal -->
<div class="modal fade" id="TransporterDetailModal" tabindex="-1" aria-labelledby="TransporterDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="TransporterDetailModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transporterDetailForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row g-3">
                        <?php if ($isSuperadmin): ?>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Company</label>
                                <select name="company_id" id="select-company" class="form-select company_id">
                                    <option value="">Select a Company</option>
                                    <?php foreach ($companies as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Transport By</label>
                                <select name="transport_by_id" id="select-transport-by" class="form-select">
                                    <option value="">Select Transport By</option>
                                    <?php if (!$isSuperadmin): ?>
                                        <?php foreach ($transportByList as $tb): ?>
                                            <option value="<?= $tb['id'] ?>">
                                                <?= htmlspecialchars($tb['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Transport Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="submitText">Submit</span>
                        <span id="submitLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm; ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/submaster/transporter-detail/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <?php if ($isSuperadmin): ?>
                                    <th>Company Name</th>
                                <?php endif; ?>
                                <th>Transport By</th>
                                <th>Transport Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
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
        let sessionCompanyId = '<?= $sessionCompanyId > 0 ? $sessionCompanyId : '' ?>';

        function getTomSelect(selector) {
            let el = $(selector)[0];
            if (!el) return null;
            if (el.tomselect) return el.tomselect;
            if (typeof TomSelect !== 'undefined') {
                return new TomSelect(el, {
                    create: false,
                    allowEmptyOption: true,
                    sortField: [{ field: '$order' }]
                });
            }
            return null;
        }

        let tsCompany = isSuperadmin ? getTomSelect('#select-company') : null;
        let tsTransportBy = getTomSelect('#select-transport-by');

        function setSelectVal(selector, val) {
            let el = $(selector)[0];
            if (el && el.tomselect) {
                el.tomselect.setValue(val ? String(val) : '');
            } else {
                $(selector).val(val ? String(val) : '').trigger('change');
            }
        }

        // Function to load transport_by for a company
        function loadTransportByOptions(companyId, selectedTbId = '') {
            let tbTs = getTomSelect('#select-transport-by');
            if (tbTs) {
                tbTs.clear();
                tbTs.clearOptions();
                tbTs.addOption({ value: '', text: 'Select Transport By', $order: 1 });
                tbTs.setValue('');
            } else {
                $('#select-transport-by').html('<option value="">Select Transport By</option>');
            }

            if (!companyId) return;

            $.ajax({
                url: SITE_URL + 'admin/submaster/transporter-detail/store.php',
                type: 'GET',
                data: {
                    action: 'get_transport_by',
                    company_id: companyId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true) {
                        if (tbTs) {
                            tbTs.clear();
                            tbTs.clearOptions();
                            tbTs.addOption({ value: '', text: 'Select Transport By', $order: 1 });
                            let orderIdx = 2;
                            $.each(response.data, function(key, val) {
                                tbTs.addOption({
                                    value: String(val.id),
                                    text: val.name,
                                    $order: orderIdx++
                                });
                            });
                            if (selectedTbId) {
                                tbTs.setValue(String(selectedTbId));
                            } else {
                                tbTs.setValue('');
                            }
                            tbTs.refreshOptions(false);
                        } else {
                            let opts = '<option value="">Select Transport By</option>';
                            $.each(response.data, function(key, val) {
                                let sel = (String(selectedTbId) === String(val.id)) ? 'selected' : '';
                                opts += '<option value="' + val.id + '" ' + sel + '>' + val.name + '</option>';
                            });
                            $('#select-transport-by').html(opts);
                            if (selectedTbId) $('#select-transport-by').val(selectedTbId).trigger('change');
                        }
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function() {
                    showToast('Failed to load transport by options.', 'error');
                }
            });
        }

        // When company changes in superadmin mode
        $(document).on('change', '#select-company', function() {
            let compId = $(this).val();
            loadTransportByOptions(compId);
        });

        // Open Add Modal
        $(document).on('click', '.open_modal', function() {
            $('#id').val('');
            $('#name').val('');
            if (isSuperadmin) {
                setSelectVal('#select-company', '');
                let tbTs = getTomSelect('#select-transport-by');
                if (tbTs) {
                    tbTs.clear();
                    tbTs.clearOptions();
                    tbTs.addOption({ value: '', text: 'Select Transport By', $order: 1 });
                    tbTs.setValue('');
                } else {
                    $('#select-transport-by').html('<option value="">Select Transport By</option>');
                }
            } else {
                setSelectVal('#select-transport-by', '');
            }
            $('#TransporterDetailModalLabel').text('Add <?= $pageNm ?>');
            $('#transporterDetailForm').validate().resetForm();
            $("#TransporterDetailModal").modal("show");
        });

        // Form Validation
        let validationRules = {
            transport_by_id: {
                required: true
            },
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            }
        };
        let validationMessages = {
            transport_by_id: {
                required: "Please select transport by"
            },
            name: {
                required: "Please enter transport name",
                minlength: "Name must be at least 2 characters",
                maxlength: "Name cannot exceed 100 characters"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select a company" };
        }

        $("#transporterDetailForm").validate({
            rules: validationRules,
            messages: validationMessages,
            errorElement: "span",
            errorClass: "text-danger",
            submitHandler: function(form) {
                let $form = $(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");

                $.ajax({
                    url: SITE_URL + "admin/submaster/transporter-detail/store.php",
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
                            $('#id').val('');
                            $('#name').val('');
                            if (isSuperadmin) {
                                setSelectVal('#select-company', '');
                                let tbTs = getTomSelect('#select-transport-by');
                                if (tbTs) {
                                    tbTs.clear();
                                    tbTs.clearOptions();
                                    tbTs.addOption({ value: '', text: 'Select Transport By', $order: 1 });
                                    tbTs.setValue('');
                                } else {
                                    $('#select-transport-by').html('<option value="">Select Transport By</option>');
                                }
                            } else {
                                setSelectVal('#select-transport-by', '');
                            }
                            form.reset();
                            $(form).validate().resetForm();
                            $("#TransporterDetailModal").modal("hide");
                            $(".data-table").DataTable().ajax.reload(null, false);
                        } else {
                            showToast(response.message, "error");
                        }
                    },
                    error: function() {
                        showToast("Something went wrong. Please try again.", "error");
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                        $text.text("Submit");
                        $loader.addClass("d-none");
                    }
                });
            }
        });

        // Edit Transporter Detail
        $(document).on('click', '.transporter_detail_edit', function() {
            var id = $(this).data('id');
            $.ajax({
                url: SITE_URL + 'admin/submaster/transporter-detail/store.php',
                type: 'GET',
                data: {
                    id: id,
                    action: 'edit'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true) {
                        let data = response.data;
                        $('#id').val(data.id);
                        $('#name').val(data.name);
                        $('#TransporterDetailModalLabel').text('Edit <?= $pageNm ?>');

                        if (isSuperadmin) {
                            setSelectVal('#select-company', data.company_id);
                            loadTransportByOptions(data.company_id, data.transport_by_id);
                        } else {
                            setSelectVal('#select-transport-by', data.transport_by_id);
                        }

                        $("#TransporterDetailModal").modal("show");
                        if (window.lucide) lucide.createIcons();
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    showToast('Something went wrong. Please try again.', 'error');
                }
            });
        });
    });
</script>
</body>
</html>
