<?php
/**
 * @var callable $_getvar
 * @var Template $this
 */

use SP\Mvc\View\Template;

?>
<div id="actions" align="center">
    <?php include $this->includePartial('error-list'); ?>

    <?php if (!$_getvar('skipBackButton')): ?>
        <div class="buttons">
            <button id="btnBack"
                    class="btn-back mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                <i class="material-icons"
                   title="<?php echo __('Back'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                <?php echo __('Back'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
