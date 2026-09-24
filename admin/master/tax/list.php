<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('tax', 'views');

$pageNm = 'Tax';
$tbl = 'tax';
$showAdd = hasPermission('tax', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name';
$module = ' tax';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}
?>

<!-- Tax Modal -->
<div class="modal fade" id="TaxModal" tabindex="-1" aria-labelledby="TaxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="TaxModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="taxForm" method="POST">
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
                                <label class="form-label">Tax Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Value <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="value" id="value" class="form-control" placeholder="Enter Value (e.g. 18.00)">
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
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/master/tax/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <?php if ($isSuperadmin): ?>
                                    <th>Company Name</th>
                                <?php endif; ?>
                                <th>Tax Name</th>
                                <th>Value (%)</th>
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

        function setCompanySelectValue(val) {
            let el = $('#select-company')[0];
            if (el && el.tomselect) {
                el.tomselect.setValue(val ? String(val) : '');
            } else {
                $('#select-company').val(val || '').trigger('change');
            }
        }

        if (isSuperadmin && typeof TomSelect !== 'undefined' && $('#select-company').length) {
            new TomSelect('#select-company', {
                create: false,
                allowEmptyOption: true
            });
        }

        // Open Add Modal
        $(document).on('click', '.open_modal', function() {
            $('#id').val('');
            $('#name').val('');
            $('#value').val('');
            if (isSuperadmin) {
                setCompanySelectValue('');
            }
            $('#TaxModalLabel').text('Add <?= $pageNm ?>');
            $('#taxForm').validate().resetForm();
            $("#TaxModal").modal("show");
        });

        // Form Validation
        let validationRules = {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            value: {
                required: true,
                number: true,
                min: 0
            }
        };
        let validationMessages = {
            name: {
                required: "Please enter tax name",
                minlength: "Tax name must be at least 2 characters",
                maxlength: "Tax name cannot exceed 100 characters"
            },
            value: {
                required: "Please enter tax value",
                number: "Please enter a valid numeric value",
                min: "Tax value cannot be negative"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select company" };
        }

        $("#taxForm").validate({
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
                    url: SITE_URL + "admin/master/tax/store.php",
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
                            $('#value').val('');
                            if (isSuperadmin) {
                                setCompanySelectValue('');
                            }
                            form.reset();
                            $(form).validate().resetForm();
                            $("#TaxModal").modal("hide");
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

        // Edit Tax
        $(document).on('click', '.tax_edit', function() {
            var id = $(this).data('id');
            $.ajax({
                url: SITE_URL + 'admin/master/tax/store.php',
                type: 'GET',
                data: {
                    id: id,
                    action: 'edit'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true) {
                        let rec = response.data;
                        $('#id').val(rec.id);
                        $('#name').val(rec.name);
                        $('#value').val(rec.value);
                        $('#TaxModalLabel').text('Edit <?= $pageNm ?>');

                        if (isSuperadmin) {
                            setCompanySelectValue(rec.company_id);
                        }

                        $("#TaxModal").modal("show");
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
