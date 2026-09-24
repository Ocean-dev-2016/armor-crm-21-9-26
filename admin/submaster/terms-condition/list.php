<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

$moduleKey = 'terms-condition';
checkPermissionOrDeny($moduleKey, 'views');

$pageNm = 'Terms & Condition';
$tbl = 'terms_condition';
$fields = 'name:Name,terms_condition:Terms & Condition';
$module = 'terms-condition';
$showAdd = hasPermission($moduleKey, 'adds');
$breadcrumbType = 'list';
$addType = 'popup';
include BASE_PATH . '/component/breadcrumb.php';

$isSuperadmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin';
$companies = [];
if ($isSuperadmin) {
    $companies = db_rows("SELECT id, name FROM company WHERE status = 1 ORDER BY name ASC");
}
?>

<!-- Terms & Condition Modal -->
<div class="modal fade" id="TermsConditionModal" tabindex="-1" aria-labelledby="TermsConditionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="TermsConditionModalLabel">
                    Add <?= $pageNm; ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="termsConditionForm" method="POST">
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
                                <label class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= $pageNm ?> Name">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Terms & Condition</label>
                                <div id="terms-editor" class="editor-container"></div>
                                <input type="hidden" name="terms_condition" id="terms_condition" value="">
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
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= SITE_URL ?>admin/submaster/terms-condition/ajax.php">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <?php if ($isSuperadmin): ?>
                                    <th>Company Name</th>
                                <?php endif; ?>
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

        let isSuperadmin = <?= $isSuperadmin ? 'true' : 'false' ?>;

        function setSelectVal(selector, val) {
            let el = $(selector)[0];
            if (el && el.tomselect) {
                el.tomselect.setValue(val ? String(val) : '');
            } else {
                $(selector).val(val ? String(val) : '').trigger('change');
            }
        }

        if (isSuperadmin && typeof TomSelect !== 'undefined' && $('#select-company').length) {
            new TomSelect('#select-company', {
                create: false,
                allowEmptyOption: true
            });
        }

        // Initialize Quill Editor
        let quillToolbarOptions = [
            [{ 'font': [] }, { 'size': [] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'script': 'super' }, { 'script': 'sub' }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'indent': '-1' }, { 'indent': '+1' }],
            [{ 'align': [] }],
            ['link', 'clean']
        ];

        let termsQuill = null;
        if (typeof Quill !== 'undefined' && document.querySelector('#terms-editor')) {
            termsQuill = new Quill('#terms-editor', {
                theme: 'snow',
                modules: { toolbar: quillToolbarOptions }
            });
        }

        function setQuillContent(html) {
            if (!termsQuill) return;
            if (!html || html.trim() === '') {
                termsQuill.setContents([]);
                termsQuill.root.innerHTML = '';
            } else {
                termsQuill.clipboard.dangerouslyPasteHTML(html);
            }
        }

        // Open Add Modal
        $(document).on('click', '.open_modal', function() {
            $('#id').val('');
            $('#name').val('');
            if (isSuperadmin) {
                setSelectVal('#select-company', '');
            }
            setQuillContent('');
            $('#terms_condition').val('');
            $('#TermsConditionModalLabel').text('Add <?= $pageNm ?>');
            $('#termsConditionForm').validate().resetForm();
            $("#TermsConditionModal").modal("show");
        });

        // Form Validation & Submission
        let validationRules = {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            }
        };
        let validationMessages = {
            name: {
                required: "Please enter name",
                minlength: "Name must be at least 2 characters",
                maxlength: "Name cannot exceed 100 characters"
            }
        };

        if (isSuperadmin) {
            validationRules.company_id = { required: true };
            validationMessages.company_id = { required: "Please select a company" };
        }

        $("#termsConditionForm").validate({
            rules: validationRules,
            messages: validationMessages,
            errorElement: "span",
            errorClass: "text-danger",
            submitHandler: function(form) {
                // Sync Quill editor content to hidden input
                if (termsQuill) {
                    let htmlContent = termsQuill.root.innerHTML;
                    if (htmlContent === '<p><br></p>') htmlContent = '';
                    $('#terms_condition').val(htmlContent);
                }

                let $form = $(form);
                let $button = $("#submitBtn");
                let $text = $("#submitText");
                let $loader = $("#submitLoader");

                $.ajax({
                    url: SITE_URL + "admin/submaster/terms-condition/store.php",
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
                            }
                            setQuillContent('');
                            $('#terms_condition').val('');
                            form.reset();
                            $(form).validate().resetForm();
                            $("#TermsConditionModal").modal("hide");
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

        // Edit Terms & Condition
        $(document).on('click', '.terms_condition_edit', function() {
            var id = $(this).data('id');
            $.ajax({
                url: SITE_URL + 'admin/submaster/terms-condition/store.php',
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
                        $('#TermsConditionModalLabel').text('Edit <?= $pageNm ?>');

                        if (isSuperadmin) {
                            setSelectVal('#select-company', data.company_id);
                        }

                        setQuillContent(data.terms_condition || '');
                        $('#terms_condition').val(data.terms_condition || '');

                        $("#TermsConditionModal").modal("show");
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
