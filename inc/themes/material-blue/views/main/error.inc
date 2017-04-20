<?php
/**
 * @var $icons \SP\Core\UI\ThemeIconsBase
 */
?>
<div id="actions" align="center">
    <?php include $this->includeTemplate('error-list', 'main'); ?>

    <div class="buttons">
        <button id="btnBack" class="btn-back mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
            <i class="material-icons"
               title="<?php echo __('Volver'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            <?php echo __('Volver'); ?>
        </button>
    </div>
</div>
