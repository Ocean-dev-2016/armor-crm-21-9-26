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
$fields = 'name:Name';
$module = 'team-role';
$tbl = 'role';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Team Role Modal -->
<?php
$modalId = 'TeamRoleModal';
$formId = 'TeamRole';
$hideNameField = true;
$modalBodyContent = function() use ($company, $pageNm) { ?>
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
<?php };
include BASE_PATH . '/component/modal.php';
?>
<?php
$ajaxUrl = SITE_URL . 'admin/setting/teamrole/ajax.php';
$tableHeaders = [
    'Sr No.',
    'Company Name',
    'Parent Company',
    'Name',
    'Status',
    'Action'
];
include BASE_PATH . '/component/datatable.php';
?>

<?php
include BASE_PATH . '/include/footer.php';
?>
<script>

    $(document).ready(function() {
        function setCompanyValue(val) {
            $('#select-single').each(function() {
                if (this.tomselect) {
                    this.tomselect.setValue(val || '');
                } else {
                    $(this).val(val || '').trigger('change');
                }
            });
        }

        let sessionCompanyId = '<?= (isset($_SESSION['company_id']) && (int)$_SESSION['company_id'] > 0) ? (int)$_SESSION['company_id'] : '' ?>';

        initMasterModalCrud({
            modalId: '#TeamRoleModal',
            formId: '#TeamRole',
            storeUrl: 'admin/setting/teamrole/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.team_role_edit',
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
                    required: "Please enter name",
                    minlength: "Team Role name must be at least 2 characters",
                    maxlength: "Team Role name cannot exceed 100 characters"
                },
                company_id: {
                    required: "Please select company",
                },
                parent_id: {
                    required: "Please select parent company",
                }
            },
            onReset: function() {
                if (sessionCompanyId) {
                    setCompanyValue(sessionCompanyId);
                    loadParentCompanies(sessionCompanyId);
                } else {
                    setCompanyValue('');
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
            },
            onEditPopulate: function(rec) {
                let companyToSet = rec.company_id || sessionCompanyId;
                setCompanyValue(companyToSet);
                loadParentCompanies(companyToSet, rec.parent_id);
            }
        });

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