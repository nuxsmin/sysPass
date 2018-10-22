<?php
/**
 * @var callable              $_getvar
 * @var \SP\Mvc\View\Template $this
 */
?>
<?php if ($_getvar('message')): ?>
    <div class="noRes round"><?php echo $_getvar('message'); ?></div>
<?php else: ?>
    <div class="noRes round"><?php echo __('No se encontraron registros'); ?></div>
<?php endif; ?>