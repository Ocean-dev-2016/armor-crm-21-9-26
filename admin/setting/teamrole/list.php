<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('teamrole', 'views');

$pageNm = 'Team Role';
$tbl = 'role';
$sql = "SELECT * FROM company WHERE status = 1";
$company = db_rows($sql);
$showAdd = hasPermission('teamrole', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';
?>

<div class="modal fade" id="TeamRoleModal" tabindex="-1" aria-labelledby="TeamRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="TeamRoleModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="TeamRole" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Company</label>
                                <select name="company_id" id="select-single" class="form-select company_id">
                                    <option value="">Select a Company</option>
                                    <?php foreach ($company as $val) { ?>
                                        <option value="<?= $val['id'] ?>">
                                            <?= htmlspecialchars($val['name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Parent Company</label>
                                <select name="parent_id" id="select-parent-role" class="form-select parent_id">
                                    <option value="">Select a Parent Company</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
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
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/setting/teamrole/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Company Name</th>
                                <th>Parent Company</th>
                                <th>Name</th>
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
         function setCountryValue(val) {
            $('#select-single').each(function() {
                if (this.tomselect) {
                    this.tomselect.setValue(val || '');
                } else {
                    $(this).val(val || '').trigger('change');
                }
            });
        }
        
        function setStateValue(val) {
            $('#select-parent-role').each(function() {
                if (this.tomselect) {
                    this.tomselect.setValue(val || '');
                } else {
                    $(this).val(val || '').trigger('change');
                }
            });
        }

        let sessionCompanyId = '<?= (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : '' ?>';

        $(document).on('click', '.open_modal', function(){
            $('#id').val('');
            $('#name').val('');
            if (sessionCompanyId) {
                setCountryValue(sessionCompanyId);
                loadParentCompanies(sessionCompanyId);
            } else {
                setCountryValue('');
                let ts = getParentTomSelect();
                if (ts) {
                    ts.clear();
                    ts.clearOptions();
                    ts.addOption({ value: '', text: 'Select a Parent Company', $order: 1 });
                    ts.setValue('');
                } else {
                    $('.parent_id').html('<option value="">Select a Parent Company</option>');
                }
            }
            $("#TeamRoleModal").modal("show");
        });

        $("#TeamRole").validate({
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                company_id: {
                    required: true,
                },
                parent_id: {
                    required: true,
                },
            },
            messages: {
                name: {
                    required: "Please enter country name",
                    minlength: "Team Role name must be at least 2 characters",
                    maxlength: "Team Role name cannot exceed 100 characters"
                },
                company_id: {
                    required: "Please select company",
                },
                parent_id: {
                    required: "Please select company",
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
                    url: "admin/setting/teamrole/store.php",
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
                            console.log("Team Role saved successfully");

                        showToast(
                            response.message,
                            "success"
                        );
                            showToast(response.message, "success");
                            $('#id').val('');
                            $('#name').val('');
                            $('#short_name').val('');
                            $form[0].reset();
                            $form.validate().resetForm();
                            $("#TeamRoleModal").modal("hide");
                            $(".data-table").DataTable().ajax.reload(null, false);
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
                //return false;
            }
        });

        $(document).on('click', '.team_role_edit', function(){
            var id = $(this).data('id');
            $.ajax({
                url: 'admin/setting/teamrole/store.php',
                type: 'GET',
                data: {
                    id: id,
                    action: 'edit',
                },
                dataType: 'json',
                success: function (response) {
                    console.log(response)
                    $('#id').val(response.data.id);
                    $('#name').val(response.data.name);
                    let companyToSet = response.data.company_id || sessionCompanyId;
                    setCountryValue(companyToSet);
                    loadParentCompanies(
                        companyToSet,
                        response.data.parent_id
                    );
                    $("#TeamRoleModal").modal("show");
                },
                error: function (xhr) {
                    console.log(xhr.responseText);
                    showToast('Something went wrong. Please try again.', 'error');
                }
            });
        })
        $(document).on('change', '.company_id', function() {

            let companyId = $(this).val();

            loadParentCompanies(companyId);

        });
    });

    function getParentTomSelect() {
        let el = document.getElementById('select-parent-role');
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

    function loadParentCompanies(companyId, selectedParentCompanyId = '') {

        let ts = getParentTomSelect();

        // Reset dropdown
        if (ts) {
            ts.clear();
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: 'Select a Parent Company',
                $order: 1
            });
            ts.setValue('');
        } else {
            $('.parent_id, #select-parent-role').html(
                '<option value="">Select a Parent Company</option>'
            );
        }

        if (!companyId) {
            return;
        }

        $.ajax({
            url: SITE_URL + 'admin/setting/teamrole/store.php',
            type: 'GET',
            data: {
                id: companyId,
                action: 'get_parent_company'
            },
            dataType: 'json',

            success: function(response) {

                if (response.status === true) {

                    let ts = getParentTomSelect();

                    /*
                    |--------------------------------------------------------------------------
                    | TomSelect
                    |--------------------------------------------------------------------------
                    */

                    if (ts) {

                        ts.clear();
                        ts.clearOptions();

                        // Add placeholder first with $order: 1
                        ts.addOption({
                            value: '',
                            text: 'Select a Parent Company',
                            $order: 1
                        });

                        let orderIndex = 2;
                        $.each(response.data, function(key, value) {

                            ts.addOption({
                                value: value.id,
                                text: value.name,
                                $order: orderIndex++
                            });

                        });

                        // IMPORTANT: Set selected parent company AFTER options are added
                        if (selectedParentCompanyId) {
                            ts.setValue(
                                String(selectedParentCompanyId)
                            );
                        } else {
                            ts.setValue('');
                        }

                        ts.refreshOptions(false);

                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normal Select
                    |--------------------------------------------------------------------------
                    */

                    else {

                        let option =
                            '<option value="">Select a Parent Company</option>';

                        $.each(response.data, function(key, value) {

                            let selected =
                                String(selectedParentCompanyId) === String(value.id)
                                    ? ' selected'
                                    : '';

                            option +=
                                '<option value="' +
                                value.id +
                                '"' +
                                selected +
                                '>' +
                                value.name +
                                '</option>';
                        });

                        $('.parent_id, #select-parent-role').html(option);

                        if (selectedParentCompanyId) {
                            $('.parent_id, #select-parent-role')
                                .val(selectedParentCompanyId)
                                .trigger('change');
                        }
                    }

                } else {

                    showToast(response.message, 'error');

                }
            },

            error: function(xhr) {

                console.log(xhr.responseText);

                showToast(
                    'Something went wrong. Please try again.',
                    'error'
                );
            }
        });
    }
</script>
</body>

</html>