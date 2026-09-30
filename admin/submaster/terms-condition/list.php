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
<?php
$modalId = 'TermsConditionModal';
$formId = 'termsConditionForm';
$includeCompanySelect = true;
$modalBodyContent = function() { ?>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Terms & Condition</label>
            <div id="terms-editor" class="editor-container"></div>
            <input type="hidden" name="terms_condition" id="terms_condition" value="">
        </div>
    </div>
<?php };
include BASE_PATH . '/component/modal.php';
?>

<?php
$ajaxUrl = SITE_URL . 'admin/submaster/terms-condition/ajax.php';
$tableHeaders = ['Sr No.'];
if ($isSuperadmin) {
    $tableHeaders[] = 'Company Name';
}
$tableHeaders = array_merge($tableHeaders, ['Name', 'Status', 'Action']);
include BASE_PATH . '/component/datatable.php';
?>

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

        initMasterModalCrud({
            modalId: '#TermsConditionModal',
            formId: '#termsConditionForm',
            storeUrl: SITE_URL + 'admin/submaster/terms-condition/store.php',
            pageNm: '<?= $pageNm ?>',
            editBtn: '.terms_condition_edit',
            rules: validationRules,
            messages: validationMessages,
            beforeSubmit: function($form, form) {
                if (termsQuill) {
                    let htmlContent = termsQuill.root.innerHTML;
                    if (htmlContent === '<p><br></p>') htmlContent = '';
                    $('#terms_condition').val(htmlContent);
                }
            },
            onReset: function() {
                if (isSuperadmin) {
                    setSelectVal('#select-company', '');
                }
                setQuillContent('');
                $('#terms_condition').val('');
            },
            onEditPopulate: function(data) {
                if (isSuperadmin) {
                    setSelectVal('#select-company', data.company_id);
                }
                setQuillContent(data.terms_condition || '');
                $('#terms_condition').val(data.terms_condition || '');
            },
            onSuccess: function() {
                setQuillContent('');
                $('#terms_condition').val('');
            }
        });
    });
</script>
</body>
</html>
