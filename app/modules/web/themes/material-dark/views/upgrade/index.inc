<?php
/**
 * @var Template   $this
 * @var ThemeIcons $icons
 */

use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>
<div id="actions" class="installer" align="center">
    <ul class="errors round">
        <li class="msg-warning">
            <i class="material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
            <strong><?php echo __('The application needs to be updated'); ?></strong>
            <p class="hint">
                <?php echo __('Please contact to the administrator'); ?>
            </p>
            <p class="hint">
                <?php echo __('Please enter the security code to start the upgrade'); ?>
            </p>
        </li>
    </ul>

    <form id="frmUpgrade" method="get" class="form-action"
          data-onsubmit="main/upgrade"
          data-action-route="upgrade/upgrade">
        <fieldset>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="key" name="key" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       autocomplete="off" autofocus>
                <label class="mdl-textfield__label"
                       for="key"><?php echo __('Security Code'); ?></label>
            </div>

            <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                   for="chkConfirm">
                <input type="checkbox" id="chkConfirm"
                       class="mdl-checkbox__input" name="chkConfirm" required>
                <span class="mdl-checkbox__label"><?php echo __('I\'ve done a full sysPass backup'); ?></span>
            </label>
        </fieldset>

        <div>
            <ul class="errors">
                <li class="msg-warning">
                    <i class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
                    <?php echo __('Please, wait while the process is running'); ?>
                </li>
            </ul>
        </div>

        <div class="buttons">
            <button id="btnChange"
                    class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent"
                    type="submit">
                <?php echo __('Update'); ?>
                <i class="material-icons"
                   title="<?php echo __('Start Update'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>