<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
include BASE_PATH . '/include/header.php';

checkPermissionOrDeny('module', 'views');

$pageNm = 'Module';
$tbl = 'module';
$sql = "SELECT * FROM $tbl WHERE status = 1 AND parent_id = 0";
$parent_modules = db_rows($sql);
$showAdd = hasPermission('module', 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
$fields = 'name:Name';
$module = 'module';
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
                                    <?php foreach ($parent_modules as $val) { ?>
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

<!-- Change Position Modal -->
<div class="modal fade" id="ChangePositionModal" tabindex="-1" aria-labelledby="ChangePositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-16" id="ChangePositionModalLabel">
                    <i data-lucide="move" class="me-1 fs-16"></i> Change Module Position
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="positionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Parent Module</label>
                        <select name="position_parent_id" id="position_parent_id" class="form-select">
                            <option value="">Select a Parent Module...</option>
                            <?php foreach ($parent_modules as $val) { ?>
                                <option value="<?= $val['id'] ?>">
                                    <?= htmlspecialchars($val['name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div id="sortableContainer" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Sub Modules (Drag to Reorder)</label>
                        </div>
                        <ul id="sortableSubModules" class="list-group">
                        </ul>
                    </div>

                    <div id="positionEmptyMsg" class="text-center text-muted py-4 d-none">
                        <i data-lucide="inbox" class="fs-24 mb-1"></i>
                        <p class="mb-0">No sub-modules found for this parent.</p>
                    </div>

                    <div id="positionLoading" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="savePositionBtn" disabled>
                        <span id="savePositionText">Save Position</span>
                        <span id="savePositionLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><?= $pageNm; ?></h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ChangePositionModal">
                    <i data-lucide="move" class="me-1 fs-14"></i> Change Position
                </button>
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
        });

        // Change Position Modal Logic
        let sortableInstance = null;
        let positionTomSelect = null;

        if (document.getElementById('position_parent_id') && typeof TomSelect !== 'undefined') {
            positionTomSelect = new TomSelect('#position_parent_id', {
                create: false,
                allowEmptyOption: true,
                placeholder: 'Select a Parent Module...',
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        }

        $('#ChangePositionModal').on('show.bs.modal', function () {
            if (positionTomSelect) {
                positionTomSelect.setValue('', true);
            } else {
                $('#position_parent_id').val('');
            }
            $('#sortableContainer').addClass('d-none');
            $('#positionEmptyMsg').addClass('d-none');
            $('#positionLoading').addClass('d-none');
            $('#savePositionBtn').prop('disabled', true);
            $('#sortableSubModules').empty();
            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }
        });

        $('#position_parent_id').on('change', function() {
            var parentId = $(this).val();
            var $container = $('#sortableContainer');
            var $list = $('#sortableSubModules');
            var $empty = $('#positionEmptyMsg');
            var $loading = $('#positionLoading');
            var $btn = $('#savePositionBtn');

            $list.empty();
            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }

            if (!parentId) {
                $container.addClass('d-none');
                $empty.addClass('d-none');
                $loading.addClass('d-none');
                $btn.prop('disabled', true);
                return;
            }

            $container.addClass('d-none');
            $empty.addClass('d-none');
            $loading.removeClass('d-none');
            $btn.prop('disabled', true);

            $.ajax({
                url: 'admin/setting/module/store.php',
                type: 'GET',
                data: {
                    action: 'get_sub_modules',
                    parent_id: parentId
                },
                dataType: 'json',
                success: function(res) {
                    $loading.addClass('d-none');
                    if (res.status && res.data && res.data.length > 0) {
                        res.data.forEach(function(item) {
                            // var iconHtml = item.icon ? '<i data-lucide="' + item.icon + '" class="fs-16 me-2 text-primary"></i>' : '<i data-lucide="circle-dot" class="fs-14 me-2 text-muted"></i>';
                            // var routeHtml = item.route ? '<small class="text-muted ms-auto">(' + item.route + ')</small>' : '';
                            var li = '<li class="list-group-item d-flex align-items-center py-2 px-3 mb-1 border rounded shadow-none" style="cursor: grab;" data-id="' + item.id + '">' +
                                '<i data-lucide="grip-vertical" class="text-muted me-2 drag-handle" style="cursor: grab;"></i>' +
                                '<span class="fw-medium text-dark">' + item.name + '</span>' +
                            '</li>';
                            $list.append(li);
                        });

                        $container.removeClass('d-none');
                        $btn.prop('disabled', false);

                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }

                        var el = document.getElementById('sortableSubModules');
                        if (el && typeof Sortable !== 'undefined') {
                            sortableInstance = new Sortable(el, {
                                animation: 150,
                                ghostClass: 'bg-light',
                                handle: '.drag-handle, li'
                            });
                        }
                    } else {
                        $empty.removeClass('d-none');
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                },
                error: function() {
                    $loading.addClass('d-none');
                    showToast('Failed to load sub modules.', 'error');
                }
            });
        });

        $('#positionForm').on('submit', function(e) {
            e.preventDefault();
            var parentId = $('#position_parent_id').val();
            if (!parentId) {
                showToast('Please select a parent module.', 'error');
                return;
            }

            var order = [];
            $('#sortableSubModules li').each(function() {
                var id = $(this).data('id');
                if (id) {
                    order.push(id);
                }
            });

            if (order.length === 0) {
                showToast('No sub modules to reorder.', 'error');
                return;
            }

            var $btn = $('#savePositionBtn');
            var $text = $('#savePositionText');
            var $loader = $('#savePositionLoader');

            $.ajax({
                url: 'admin/setting/module/store.php',
                type: 'POST',
                data: {
                    action: 'update_position',
                    parent_id: parentId,
                    order: order
                },
                dataType: 'json',
                beforeSend: function() {
                    $btn.prop('disabled', true);
                    $text.text('Saving...');
                    $loader.removeClass('d-none');
                },
                success: function(response) {
                    if (response.status) {
                        showToast(response.message, 'success');
                        $('#ChangePositionModal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        showToast(response.message || 'Failed to save position.', 'error');
                    }
                },
                error: function() {
                    showToast('Something went wrong. Please try again.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $text.text('Save Position');
                    $loader.addClass('d-none');
                }
            });
        });
    });
</script>
</body>

</html>