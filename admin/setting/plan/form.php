<?php

require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';

$pageNm = 'Plan';
$tbl = 'plan';

$id = isset($_GET['id']) ? decrypt_id($_GET['id']) : 0;
$isEdit = $id > 0;
$plan = [
    'id' => 0,
    'name' => '',
    'price' => '',
    'to_date' => '',
    'from_date' => '',
    'max_team_user' => '',
    'max_customer' => '',
    'max_inquiry' => '',
    'panel_right' => '',
    'app_right' => ''
];
if ($isEdit) {
    $planData = db_row("SELECT * FROM $tbl WHERE id = $id LIMIT 1");
    if (!$planData) {
        die($pageNm . ' not found.');
    }
    $plan = $planData;
    $plan['to_date'] = formatDate($planData['to_date'], 'd-m-Y');
    $plan['from_date'] = formatDate($planData['from_date'], 'd-m-Y');
}
$sql = "
    SELECT 
        m.id,
        m.parent_id,
        m.name,
        p.name AS parent_name
    FROM module m
    LEFT JOIN module p
        ON p.id = m.parent_id
        AND p.status = 1
    WHERE m.status = 1
      AND (
          -- Show child when parent has children
          m.parent_id != 0

          OR

          -- Show main only when it has NO children
          NOT EXISTS (
              SELECT 1
              FROM module c
              WHERE c.parent_id = m.id
                AND c.status = 1
          )
      )
    ORDER BY
        CASE
            WHEN m.parent_id = 0 THEN m.id
            ELSE m.parent_id
        END,
        m.id
";
$modules = db_rows($sql);
$selectedModuleIds = [];
if (!empty($plan['panel_right'])) {
    $selectedModuleIds = array_unique(
        array_filter(
            array_map(
                'intval',
                explode(',', $plan['panel_right'])
            )
        )
    );
}

$selectedAppModuleIds = [];
if (!empty($plan['app_right'])) {
    $selectedAppModuleIds = array_unique(
        array_filter(
            array_map(
                'intval',
                explode(',', $plan['app_right'])
            )
        )
    );
}
include BASE_PATH . '/include/header.php';

$requiredAction = $isEdit ? 'updates' : 'adds';
checkPermissionOrDeny('plan', $requiredAction);

$breadcrumbType = 'form';
$isEdit = $isEdit ? true : false;
$parentUrl = SITE_URL . 'plan';
include BASE_PATH . '/component/breadcrumb.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $pageNm ?></h6>
            </div>
            <div class="card-body">
                <form id="planForm">
                    <?php if ($isEdit): ?>
                        <input type="hidden"
                            name="id"
                            value="<?= (int)$plan['id'] ?>" id="id">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    placeholder="Enter <?= $pageNm ?> Name"
                                    value="<?= htmlspecialchars($plan['name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <input type="number"
                                    name="price"
                                    id="price"
                                    class="form-control"
                                    placeholder="Enter <?= $pageNm ?> Price"
                                    step="0.01"
                                    value="<?= htmlspecialchars($plan['price']) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Select To Date</label>
                                <input type="text" id="to-date" name="to_date" class="form-control" placeholder="Select To Date"
                                    value="<?= htmlspecialchars($plan['to_date']) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Select From Date</label>
                                <input type="text" id="from-date" name="from_date" class="form-control" placeholder="Select To Date" value="<?= htmlspecialchars($plan['from_date']) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Max Team User</label>
                                <input type="number"
                                    name="max_team_user"
                                    id="max_team_user"
                                    class="form-control"
                                    placeholder="Enter Max Team User"
                                    value="<?= (int)$plan['max_team_user'] ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Max Customer</label>
                                <input type="number"
                                    name="max_customer"
                                    id="max_customer"
                                    class="form-control"
                                    placeholder="Enter Max Customer"
                                    value="<?= (int)$plan['max_customer'] ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Max Inquiry</label>
                                <input type="number"
                                    name="max_inquiry"
                                    id="max_inquiry"
                                    class="form-control"
                                    placeholder="Enter Max Inquiry"
                                    value="<?= (int)$plan['max_inquiry'] ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <label>Panel Right</label>
                        <div class="col-md-5">
                            <div class="bg-body-secondary rounded p-3 h-100">
                                <h6 class="mb-3">Available Modules</h6>
                                <div id="availableModules"
                                    class="d-flex flex-wrap gap-2 module-container">
                                    <?php foreach ($modules as $module): ?>
                                        <?php
                                        $moduleId = (int) $module['id'];
                                        if (in_array($moduleId, $selectedModuleIds, true)) {
                                            continue;
                                        }
                                        ?>
                                        <div class="card shadow-sm cursor-move module-card"
                                            data-id="<?= $moduleId ?>">
                                            <div class="card-body p-2">
                                                <?= ($module['parent_id'] != 0) ? htmlspecialchars(
                                                    $module['parent_name'] . ' - ' . $module['name'] 
                                                ) : htmlspecialchars(
                                                    $module['name'] 
                                                ) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <button type="button"
                                    class="btn btn-primary"
                                    id="moveAllRight"
                                    title="Select All">
                                    <i data-lucide="chevrons-right"></i>
                                </button>
                                <button type="button"
                                    class="btn btn-secondary"
                                    id="moveAllLeft"
                                    title="Remove All">
                                    <i data-lucide="chevrons-left"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="bg-body-secondary rounded p-3 h-100">
                                <h6 class="mb-3">Selected Modules</h6>
                                <div id="selectedModules"
                                    class="d-flex flex-wrap gap-2 module-container">
                                    <?php foreach ($modules as $module): ?>
                                        <?php
                                        $moduleId = (int) $module['id'];
                                        if (!in_array($moduleId, $selectedModuleIds, true)) {
                                            continue;
                                        }
                                        ?>
                                        <div class="card shadow-sm cursor-move module-card"
                                            data-id="<?= $moduleId ?>">
                                            <div class="card-body p-2">
                                                <?= ($module['parent_id'] != 0) ? htmlspecialchars(
                                                    $module['parent_name'] . ' - ' . $module['name'] 
                                                ) : htmlspecialchars(
                                                    $module['name'] 
                                                ) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <label>Application Right</label>
                        <div class="col-md-5">
                            <div class="bg-body-secondary rounded p-3 h-100">
                                <h6 class="mb-3">Available Modules</h6>
                                <div id="availableAppModules"
                                    class="d-flex flex-wrap gap-2 module-container">
                                    <?php foreach ($modules as $module): ?>
                                        <?php
                                        $moduleId = (int) $module['id'];
                                        if (in_array($moduleId, $selectedAppModuleIds, true)) {
                                            continue;
                                        }
                                        ?>
                                        <div class="card shadow-sm cursor-move module-card"
                                            data-id="<?= $moduleId ?>">
                                            <div class="card-body p-2">
                                                <?= ($module['parent_id'] != 0) ? htmlspecialchars(
                                                    $module['parent_name'] . ' - ' . $module['name'] 
                                                ) : htmlspecialchars(
                                                    $module['name'] 
                                                ) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <button type="button"
                                    class="btn btn-primary"
                                    id="moveAllAppRight"
                                    title="Select All">
                                    <i data-lucide="chevrons-right"></i>
                                </button>
                                <button type="button"
                                    class="btn btn-secondary"
                                    id="moveAllAppLeft"
                                    title="Remove All">
                                    <i data-lucide="chevrons-left"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="bg-body-secondary rounded p-3 h-100">
                                <h6 class="mb-3">Selected Modules</h6>
                                <div id="selectedAppModules"
                                    class="d-flex flex-wrap gap-2 module-container">
                                    <?php foreach ($modules as $module): ?>
                                        <?php
                                        $moduleId = (int) $module['id'];
                                        if (!in_array($moduleId, $selectedAppModuleIds, true)) {
                                            continue;
                                        }
                                        ?>
                                        <div class="card shadow-sm cursor-move module-card"
                                            data-id="<?= $moduleId ?>">
                                            <div class="card-body p-2">
                                                <?= ($module['parent_id'] != 0) ? htmlspecialchars(
                                                    $module['parent_name'] . ' - ' . $module['name'] 
                                                ) : htmlspecialchars(
                                                    $module['name'] 
                                                ) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden"
                        name="panel_right"
                        id="panel_right"
                        value="<?= htmlspecialchars($plan['panel_right'] ?? '') ?>">
                    <input type="hidden"
                        name="app_right"
                        id="app_right"
                        value="<?= htmlspecialchars($plan['app_right'] ?? '') ?>">
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    $(document).ready(function() {
        // --- Panel Right ---
        const availableModules = document.getElementById('availableModules');
        const selectedModules = document.getElementById('selectedModules');
        const panelRightInput = document.getElementById('panel_right');

        new Sortable(availableModules, {
            group: 'panel_modules',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: updateSelectedPanelModules,
            onRemove: updateSelectedPanelModules
        });

        new Sortable(selectedModules, {
            group: 'panel_modules',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: updateSelectedPanelModules,
            onRemove: updateSelectedPanelModules,
            onSort: updateSelectedPanelModules
        });

        function updateSelectedPanelModules() {
            let ids = [];
            $('#selectedModules .module-card').each(function() {
                ids.push($(this).data('id'));
            });
            panelRightInput.value = ids.join(',');
        }

        $('#moveAllRight').on('click', function() {
            $('#availableModules .module-card').each(function() {
                $('#selectedModules').append(this);
            });
            updateSelectedPanelModules();
        });

        $('#moveAllLeft').on('click', function() {
            $('#selectedModules .module-card').each(function() {
                $('#availableModules').append(this);
            });
            updateSelectedPanelModules();
        });

        // --- Application Right ---
        const availableAppModules = document.getElementById('availableAppModules');
        const selectedAppModules = document.getElementById('selectedAppModules');
        const appRightInput = document.getElementById('app_right');

        new Sortable(availableAppModules, {
            group: 'app_modules',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: updateSelectedAppModules,
            onRemove: updateSelectedAppModules
        });

        new Sortable(selectedAppModules, {
            group: 'app_modules',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: updateSelectedAppModules,
            onRemove: updateSelectedAppModules,
            onSort: updateSelectedAppModules
        });

        function updateSelectedAppModules() {
            let ids = [];
            $('#selectedAppModules .module-card').each(function() {
                ids.push($(this).data('id'));
            });
            appRightInput.value = ids.join(',');
        }

        $('#moveAllAppRight').on('click', function() {
            $('#availableAppModules .module-card').each(function() {
                $('#selectedAppModules').append(this);
            });
            updateSelectedAppModules();
        });

        $('#moveAllAppLeft').on('click', function() {
            $('#selectedAppModules .module-card').each(function() {
                $('#availableAppModules').append(this);
            });
            updateSelectedAppModules();
        });

        $("#planForm").validate({
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                price: {
                    required: true,
                    number: true,
                    min: 0
                },
                to_date: {
                    required: true
                },
                from_date: {
                    required: true
                },
                max_team_user: {
                    required: true,
                    digits: true,
                    min: 1
                },
                max_customer: {
                    required: true,
                    digits: true,
                    min: 1
                },
                max_inquiry: {
                    required: true,
                    digits: true,
                    min: 1
                }
            },
            messages: {
                name: {
                    required: "Please enter plan name",
                    minlength: "Plan name must be at least 3 characters",
                    maxlength: "Plan name cannot exceed 100 characters"
                },
                price: {
                    required: "Please enter price",
                    number: "Please enter valid price",
                    min: "Price cannot be negative"
                },
                to_date: {
                    required: "Please select To Date"
                },
                from_date: {
                    required: "Please select From Date"
                },
                max_team_user: {
                    required: "Please enter max team users",
                    digits: "Please enter only numbers",
                    min: "Minimum 1 team user is required"
                },
                max_customer: {
                    required: "Please enter max customers",
                    digits: "Please enter only numbers",
                    min: "Minimum 1 customer is required"
                },
                max_inquiry: {
                    required: "Please enter max inquiries",
                    digits: "Please enter only numbers",
                    min: "Minimum 1 inquiry is required"
                }
            },
            errorElement: "span",
            errorClass: "text-danger",
            submitHandler: function(form) {
                let $form = $(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");

                updateSelectedPanelModules();
                updateSelectedAppModules();                

                $.ajax({
                    url: "<?= SITE_URL ?>admin/setting/plan/store.php",
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
                            $form[0].reset();
                            $form.validate().resetForm();
                            $("#selectedModules").html("");
                            $("#selectedAppModules").html("");
                            updateSelectedPanelModules();
                            updateSelectedAppModules();
                            setTimeout(function() {
                                window.location.href = "<?= SITE_URL ?>plan";
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