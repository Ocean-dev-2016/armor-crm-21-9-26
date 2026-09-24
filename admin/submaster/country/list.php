<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('country', 'views');

$pageNm = 'Country';
$tbl = 'country';
$sql = "SELECT * FROM $tbl WHERE status = 1";
$country = db_rows($sql);
$showAdd = hasPermission('country', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name';
$module = 'country';
$tbl = 'country';
include BASE_PATH . '/component/breadcrumb.php';
?>

<div class="modal fade" id="CountryModal" tabindex="-1" aria-labelledby="CountryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="CountryModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="countryForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Short Name</label>
                                <input type="text" name="short_name" id="short_name" class="form-control" placeholder="Enter <?= $pageNm ?> Short Name">
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
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/submaster/country/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Name</th>
                                <th>Short Name</th>
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
        $(document).on('click', '.open_modal', function(){
            $('#id').val('');
            $('#name').val('');
            $('#short_name').val('');
            $("#CountryModal").modal("show");
        });

        $("#countryForm").validate({
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                short_name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                }
            },
            messages: {
                name: {
                    required: "Please enter country name",
                    minlength: "Country name must be at least 3 characters",
                    maxlength: "Country name cannot exceed 100 characters"
                },
                short_name: {
                    required: "Please enter country short name",
                    minlength: "Country short name must be at least 2 characters",
                    maxlength: "Country short name cannot exceed 100 characters"
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
                    url: "admin/submaster/country/store.php",
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
                            console.log("Country saved successfully");

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
                            $("#CountryModal").modal("hide");
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

        $(document).on('click', '.country_edit', function(){
            var id = $(this).data('id');
            $.ajax({
                url: 'admin/submaster/country/store.php',
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
                    $('#short_name').val(response.data.short_name);
                    $("#CountryModal").modal("show");
                },
                error: function (xhr) {
                    console.log(xhr.responseText);
                    showToast('Something went wrong. Please try again.', 'error');
                }
            });
        })
    });
</script>
</body>

</html>