<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('module', 'views');

$pageNm = 'Module';
$tbl = 'module';
$sql = "SELECT * FROM $tbl WHERE status = 1 AND parent_id = 0";
$module = db_rows($sql);
$showAdd = hasPermission('module', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';
?>

<div class="modal fade" id="ModuleModal" tabindex="-1" aria-labelledby="ModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="ModuleModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="moduleForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="mb-1">
                                <label class="form-label">Parent</label>
                                <select name="parent_id" id="select-single" class="form-select parent_id">
                                    <option value="">Select a Parent...</option>
                                    <?php foreach ($module as $val) { ?>
                                        <option value="<?= $val['id'] ?>">
                                            <?= htmlspecialchars($val['name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-1">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-1">
                                <label class="form-label">Icon</label>
                                <input type="text" name="icon" id="icon" class="form-control" placeholder="Enter <?= $pageNm ?> Icon">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-1">
                                <label class="form-label">Route</label>
                                <input type="text" name="route" id="route" class="form-control" placeholder="Enter <?= $pageNm ?> Route">
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
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/setting/module/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Name</th>
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
    function setParentValue(val) {
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
        $('#icon').val('');
        $('#route').val('');
        setParentValue('');
        $("#ModuleModal").modal("show");
    });
    $(document).ready(function() {
        $("#moduleForm").validate({
            rules: {
                parent_id: {
                    required: false
                },
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                icon: {
                    required: function () {
                        return $('#select-single').val() === '';
                    },
                },
                route: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "Please enter module name",
                    minlength: "Module name must be at least 2 characters",
                    maxlength: "Module name cannot exceed 100 characters"
                },
                route: {
                    required: "Please enter module route",
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
                    url: "admin/setting/module/store.php",
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

                        showToast(
                            response.message,
                            "success"
                        );
                            showToast(response.message, "success");
                            $form[0].reset();
                            $form.validate().resetForm();
                            $("#ModuleModal").modal("hide");
                            $(".data-table").DataTable().ajax.reload(null, false);
                            setTimeout(function() {
                                window.location.href = "<?= SITE_URL ?>module";
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
                //return false;
            }
        });

        $(document).on('click', '.module_edit', function(){
            var id = $(this).data('id');
            $.ajax({
                url: 'admin/setting/module/store.php',
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
                    $('#icon').val(response.data.icon);
                    $('#route').val(response.data.route);
                    if(response.data.parent_id != 0)
                    {                        
                        setParentValue(response.data.parent_id);
                    }
                    else
                    {
                        setParentValue('');
                    }
                    $("#ModuleModal").modal("show");
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