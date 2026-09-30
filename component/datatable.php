<?php

$pageNm          = $pageNm ?? 'List';
$ajaxUrl         = $ajaxUrl ?? '';
$tableHeaders    = $tableHeaders ?? [];
$cardHeaderRight = $cardHeaderRight ?? '';
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><?= htmlspecialchars($pageNm); ?></h6>
                <?php if (!empty($cardHeaderRight)): ?>
                    <div>
                        <?= is_callable($cardHeaderRight) ? $cardHeaderRight() : $cardHeaderRight; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle table-striped mb-0 data-table" data-ajaxurl="<?= htmlspecialchars($ajaxUrl) ?>" data-tbl="<?= htmlspecialchars($tbl ?? '') ?>">
                        <thead>
                            <tr>
                                <?php foreach ($tableHeaders as $header): ?>
                                    <th><?= $header; ?></th>
                                <?php endforeach; ?>
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
