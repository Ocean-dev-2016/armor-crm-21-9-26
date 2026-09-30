<?php
/**
 * Common Master/Submaster Modal Component
 * 
 * Expected / Optional variables:
 * - $modalId          : string (e.g. 'TaxModal', 'CountryModal')
 * - $formId           : string (e.g. 'taxForm', 'countryForm')
 * - $pageNm           : string (e.g. 'Tax', 'Country')
 * - $modalDialogClass : string (optional, e.g. 'modal-lg', default: '')
 * - $formEnctype      : string (optional, e.g. 'multipart/form-data', default: '')
 * - $modalBodyContent : callable or string - HTML content to put inside the form row
 *                       (If null, you can define form fields between include or wrap)
 */

$modalId          = $modalId ?? ($pageNm ? str_replace(' ', '', $pageNm) . 'Modal' : 'CommonModal');
$formId           = $formId ?? ($pageNm ? lcfirst(str_replace(' ', '', $pageNm)) . 'Form' : 'commonForm');
$pageNm           = $pageNm ?? 'Record';
$modalDialogClass = $modalDialogClass ?? '';
$formEnctype      = !empty($formEnctype) ? ' enctype="' . $formEnctype . '"' : '';
$modalTitleId     = $modalId . 'Label';
$isSuperadmin     = $isSuperadmin ?? (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin');
?>

<div class="modal fade" id="<?= htmlspecialchars($modalId) ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($modalTitleId) ?>" aria-hidden="true">
    <div class="modal-dialog <?= htmlspecialchars($modalDialogClass) ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-16" id="<?= htmlspecialchars($modalTitleId) ?>">
                    Add <?= htmlspecialchars($pageNm); ?>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="<?= htmlspecialchars($formId) ?>" method="POST"<?= $formEnctype ?>>
                <div class="modal-body">
                    <input type="hidden" name="id" id="id">
                    <?php if (isset($hasImageUpload) && $hasImageUpload): ?>
                        <input type="hidden" name="remove_image" id="remove_image" value="0">
                    <?php endif; ?>

                    <div class="row g-3">
                        <?php if (!empty($includeCompanySelect) && $isSuperadmin && !empty($companies)): ?>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Company </label>
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

                        <?php if (empty($hideNameField)): ?>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label"><?= htmlspecialchars($nameLabel ?? 'Name') ?> </label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter <?= htmlspecialchars($pageNm) ?> <?= htmlspecialchars($nameLabel ?? 'Name') ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php
                        if (isset($modalBodyContent)) {
                            if (is_callable($modalBodyContent)) {
                                call_user_func($modalBodyContent);
                            } else {
                                echo $modalBodyContent;
                            }
                        }
                        ?>
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
