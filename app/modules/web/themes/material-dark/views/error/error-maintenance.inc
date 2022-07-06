<?php
/**
 * @var ThemeIcons $icons
 * @var Template   $this
 */

use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>
<div id="actions" align="center">
    <?php include $this->includePartial('error-list'); ?>

    <div class="buttons">
        <button id="btnBack"
                class="btn-back mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
            <i class="material-icons"
               title="<?php echo __('Back'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            <?php echo __('Back'); ?>
        </button>
    </div>
</div>