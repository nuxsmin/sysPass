<?php
/**
 * @var $icons \SP\Core\UI\ThemeIconsBase
 */
?>
<div id="actions" align="center">
    <?php include $this->includeTemplate('error-list'); ?>

    <div class="buttons">
        <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored"
                onclick="window.location.href='index.php'">
            <i class="material-icons"
               title="<?php echo _('Volver'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            <?php echo _('Volver'); ?>
        </button>
    </div>
</div>
