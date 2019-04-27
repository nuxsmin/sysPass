<?php
/**
 * @var ClientData $client
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\ClientData;
use SP\Mvc\View\Template;

$client = $_getvar('client');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmClients" id="frmClients" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo $client->getName(); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Client name'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Description'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo $client->getDescription(); ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php echo __('Client description'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <div id="help-isglobal"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large"
                         for="help-isglobal">
                        <p>
                            <?php echo __('Sets whether the client is visible by all users'); ?>
                        </p>
                        <p>
                            <?php echo __('By default, clients assigned to accounts only will be visible by users who are granted to access to the accounts.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="isglobal">
                        <input type="checkbox" id="isglobal"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="isglobal" <?php echo $client->getIsGlobal() ? 'checked' : ' '; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Global'); ?></span>
                    </label>
                </td>
            </tr>

            <?php if ($_getvar('customFields')): ?>
                <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
            <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmClients"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>