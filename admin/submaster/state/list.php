<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('state', 'views');

$pageNm = 'State';
$tbl = 'state';
$sql = "SELECT * FROM country WHERE status = 1";
$country = db_rows($sql);
$showAdd = hasPermission('state', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'country_id:Country, name:Name';
$module = 'state';
$tbl = 'state';
include BASE_PATH . '/component/breadcrumb.php';
?>

<div class="modal fade" id="StateModal" tabindex="-1" aria-labelledby="StateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="StateModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="stateForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <select name="country_id" id="select-single" class="form-select country_id">
                                    <option value="">Select a Country</option>
                                    <?php foreach ($country as $val) { ?>
                                        <option value="<?= $val['id'] ?>">
                                            <?= htmlspecialchars($val['name']) ?>
                                        </option>
                                    <?php } ?>
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
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/submaster/state/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Country</th>
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


        $(document).on('click', '.open_modal', function(){
            $('#id').val('');
            $('#name').val('');
            setCountryValue('');
            $("#StateModal").modal("show");
        })

        $("#stateForm").validate({
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                country_id: {
                    required: true,
                }
            },
            messages: {
                name: {
                    required: "Please enter state name",
                    minlength: "State name must be at least 3 characters",
                    maxlength: "State name cannot exceed 100 characters"
                },
                country_id: {
                    required: "Please Select Country",
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
                    url: "admin/submaster/state/store.php",
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
                            console.log("State saved successfully");

                            showToast(
                                response.message,
                                "success"
                            );
                            showToast(response.message, "success");
                            $('#id').val('');
                            $('#name').val('');
                            setCountryValue('');
                            $form[0].reset();
                            $form.validate().resetForm();
                            $("#StateModal").modal("hide");
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
                return false;
            }
        });

        $(document).on('click', '.state_edit', function() {
            var id = $(this).data('id');
            $.ajax({
                url: 'admin/submaster/state/store.php',
                type: 'GET',
                data: {
                    id: id,
                    action: 'edit',
                },
                dataType: 'json',
                success: function(response) {
                    console.log(response)
                    $('#id').val(response.data.id);
                    $('#name').val(response.data.name);
                    setCountryValue(response.data.country_id);
                    $("#StateModal").modal("show");
                },
                error: function(xhr) {
                    showToast('Something went wrong. Please try again.', 'error');
                }
            });
        })
    });
</script>
</body>

</html>