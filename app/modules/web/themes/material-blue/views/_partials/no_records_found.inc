<?php if (isset($message)): ?>
    <div class="noRes round"><?php echo $message; ?></div>
<?php else: ?>
    <div class="noRes round"><?php echo __('No se encontraron registros'); ?></div>
<?php endif; ?>