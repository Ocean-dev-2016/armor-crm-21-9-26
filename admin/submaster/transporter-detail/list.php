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
<?php
$modalId = 'TransporterDetailModal';
$formId = 'transporterDetailForm';
$includeCompanySelect = true;
$hideNameField = true;
$modalBodyContent = function() use ($pageNm, $isSuperadmin, $transportByList) { ?>
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
<?php };
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/submaster/transporter-detail/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, [
    'Transport By',
    'Transport Name',
    'Status',
    'Action'
]);
include BASE_PATH . '/component/datatable.php';
?>

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

        initMasterModalCrud({
            modalId: '#TransporterDetailModal',
            formId: '#transporterDetailForm',
            storeUrl: SITE_URL + 'admin/submaster/transporter-detail/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.transporter_detail_edit',
            rules: validationRules,
            messages: validationMessages,
            onReset: function() {
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
            },
            onEditPopulate: function(data) {
                if (isSuperadmin) {
                    setSelectVal('#select-company', data.company_id);
                    loadTransportByOptions(data.company_id, data.transport_by_id);
                } else {
                    setSelectVal('#select-transport-by', data.transport_by_id);
                }
            }
        });
    });
</script>
</body>
</html>
