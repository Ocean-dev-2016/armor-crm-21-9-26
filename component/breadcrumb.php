<?php

$pageNm = $pageNm ?? 'Page';
$breadcrumbType = $breadcrumbType ?? 'list';
$parentUrl = $parentUrl ?? SITE_URL;
$isEdit = $isEdit ?? false;
$addUrl = $addUrl ?? '';
$addType = $addType ?? 'redirect';
$customName = $customName ?? '';
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