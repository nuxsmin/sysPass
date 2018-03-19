<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Mvc\View\Template  $this
 */
?>
<div id="actions" align="center">
    <?php include $this->includePartial('error-list'); ?>

    <?php if (!isset($skipBackButton)): ?>
        <div class="buttons">
            <button id="btnBack" class="btn-back mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
                <i class="material-icons"
                   title="<?php echo __('Volver'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                <?php echo __('Volver'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
