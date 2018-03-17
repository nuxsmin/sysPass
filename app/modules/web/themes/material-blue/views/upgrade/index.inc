<?php
/**
 * @var \SP\Mvc\View\Template $this
 */
?>
<div id="actions" class="installer" align="center">
    <ul class="errors round">
        <li class="msg-warning">
            <i class="material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
            <strong><?php echo __('La aplicación necesita actualizarse'); ?></strong>
            <p class="hint">
                <?php echo __('Consulte con el administrador'); ?>
            </p>
        </li>
    </ul>
</div>