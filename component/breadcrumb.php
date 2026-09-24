<?php

$pageNm = $pageNm ?? 'Page';
$breadcrumbType = $breadcrumbType ?? 'list';
$parentUrl = $parentUrl ?? SITE_URL;
$isEdit = $isEdit ?? false;
$addUrl = $addUrl ?? '';
$addType = $addType ?? 'redirect';
$customName = $customName ?? '';
$tbl = $tbl ?? '';
$fields = $fields ?? 'name';
$module = $module ?? $tbl;
?>
<div class="card mb-2">
    <div class="card-body breadcrumb-body">
        <div class="page-header">
            <!-- Breadcrumb -->
            <nav style="--bs-breadcrumb-divider: '>'; "
                 aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <!-- Home -->
                    <li class="breadcrumb-item">
                        <a href="<?= SITE_URL ?>">
                            Home
                        </a>
                    </li>
                    <?php if ($breadcrumbType === 'list'): ?>
                        <!-- LIST PAGE -->
                        <li class="breadcrumb-item active"
                            aria-current="page">
                            <?= htmlspecialchars($pageNm) ?>
                        </li>
                    <?php else: ?>
                        <!-- FORM PAGE -->
                        <li class="breadcrumb-item">
                            <a href="<?= htmlspecialchars($parentUrl) ?>">
                                <?= htmlspecialchars($pageNm) ?>
                            </a>
                        </li>
                        <?php if($customName != '') : ?>
                        <li class="breadcrumb-item active"
                            aria-current="page">
                            <?= htmlspecialchars($customName) ?>
                        </li>
                        <?php else: ?>
                        <li class="breadcrumb-item active"
                            aria-current="page">
                            <?= $isEdit ? 'Edit ' : 'Add ' ?>
                            <?= htmlspecialchars($pageNm) ?>
                        </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ol>
            </nav>
            <div class="page-actions">
                <?php if ($breadcrumbType === 'list'): ?>
                    <?php if (!empty($showAdd)): ?>
                        <?php if ($addType === 'popup'): ?>
                            <a href="javascript:void(0);" class="btn btn-primary open_modal">
                                <i data-lucide="plus" class="me-1"></i>
                                Add
                            </a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($addUrl) ?>" class="btn btn-primary">
                                <i data-lucide="plus" class="me-1"></i>Add
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                
                    <?php
                    $permModule = $moduleKey ?? $module ?? $tbl;
                    $canPrint = hasPermission($permModule, 'print');
                    $canExcel = hasPermission($permModule, 'excel');
                    if (!$canPrint && function_exists('hasPermission')) {
                        // Also fallback to $tbl or $module if different
                        if ($tbl && hasPermission($tbl, 'print')) $canPrint = true;
                        if ($module && hasPermission($module, 'print')) $canPrint = true;
                    }
                    if (!$canExcel && function_exists('hasPermission')) {
                        if ($tbl && hasPermission($tbl, 'excel')) $canExcel = true;
                        if ($module && hasPermission($module, 'excel')) $canExcel = true;
                    }
                    ?>
                    <?php if ($canPrint || $canExcel): ?>
                    <div class="dropdown">
                        <button type="button"
                                class="btn btn-primary"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            <i data-lucide="settings"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border mb-2 pb-0 pt-2 rounded-3">
                            <?php if ($canPrint): ?>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" id="btnPrintRecord"
                                   data-tbl="<?= htmlspecialchars($tbl) ?>"
                                   data-fields="<?= htmlspecialchars($fields) ?>"
                                   data-page-name="<?= htmlspecialchars($pageNm) ?>"
                                   data-module="<?= htmlspecialchars($module) ?>">
                                    <i data-lucide="file-text" class="me-2"></i>
                                    Print
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($canExcel): ?>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" id="btnExportExcel"
                                   data-tbl="<?= htmlspecialchars($tbl) ?>"
                                   data-fields="<?= htmlspecialchars($fields) ?>"
                                   data-page-name="<?= htmlspecialchars($pageNm) ?>"
                                   data-module="<?= htmlspecialchars($module) ?>">
                                    <i data-lucide="save" class="me-2"></i>
                                    Excel
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($parentUrl) ?>"
                       class="btn btn-primary">
                        <i data-lucide="arrow-left" class="me-1"></i>
                        Back
                    </a>
                <?php endif; ?> 
            </div>
        </div>
    </div>
</div>