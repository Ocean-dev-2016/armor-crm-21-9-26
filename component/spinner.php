<?php

function spinner($color = 'primary', $size = '')
{
    $sizeClass = $size === 'sm' ? 'spinner-border-sm' : '';
?>

    <div class="spinner-border text-<?= htmlspecialchars($color) ?> <?= $sizeClass ?>"
        role="status">
        <span class="visually-hidden">Loading...</span>
    </div>

<?php
}
