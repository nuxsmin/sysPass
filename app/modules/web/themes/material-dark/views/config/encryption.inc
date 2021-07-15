<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

$disabled = $configData->isMaintenance() ? '' : 'disabled';
?>

<form method="post" name="frmCrypt" id="frmCrypt" class="form-action"
      data-onsubmit="config/masterpass"
      data-action-route="configEncryption/save"
      data-hash="">

    <div id="title" class="titleNormal">
        <i class="material-icons">enhanced_encryption</i>
        <?php echo __('Master Password'); ?>
    </div>

    <table class="data tblConfig round">
        <?php if ($_getvar('lastUpdateMPass') > 0): ?>
            <tr>
                <td class="descField">
                    <?php echo __('Last change'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Last change'); ?></div>

                    <?php echo date('r', $_getvar('lastUpdateMPass')); ?>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="descField">
                <?php echo __('Current Master Password'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="current_masterpass" name="current_masterpass"
                           type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                           maxlength="255" required <?php echo $disabled; ?> />
                    <label class="mdl-textfield__label"
                           for="current_masterpass"><?php echo __('Current Master Password'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('New Master Password'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="new_masterpass" name="new_masterpass"
                           type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           maxlength="255" required <?php echo $disabled; ?> />
                    <label class="mdl-textfield__label"
                           for="new_masterpass"><?php echo __('New Master Password'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('New Master Password (repeat)'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="new_masterpass_repeat"
                           name="new_masterpass_repeat" type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255" required <?php echo $disabled; ?> />
                    <label class="mdl-textfield__label"
                           for="new_masterpass_repeat"><?php echo __('New Master Password (repeat)'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <?php if (!$configData->isMaintenance()): ?>
                    <div
                            class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                    <?php echo __('Maintenance mode not enabled'); ?>. <?php echo __('Please, enable it to avoid unwanted behavior from other sessions'); ?>
                    <br>
                <?php endif; ?>
                <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                <?php echo __('You should save the new password on a secure place'); ?>
                <br>
                <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                <?php echo __('All accounts passwords will be encrypted again.'); ?>
                <br>
                <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                <?php echo __('Users will need to enter the new Master Password.'); ?>
                <br>
                <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                <strong><?php printf(__('It will be updated %s accounts. This process could take some time long.'), $_getvar('numAccounts')); ?></strong>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-masterpass-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="no_account_change">
                                <input type="checkbox" id="no_account_change"
                                       class="mdl-switch__input"
                                       name="no_account_change"/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Do not modify accounts'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Sets a new Master Password without re-encrypting the accounts.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="confirm_masterpass_change">
                                <input type="checkbox"
                                       id="confirm_masterpass_change"
                                       class="mdl-switch__input"
                                       name="confirm_masterpass_change"/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Confirm change'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Confirm the master password change after you have been read all the above warnings.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
    </table>

    <?php if ($_getvar('taskId')): ?>
        <input type="hidden" name="taskId"
               value="<?php echo $_getvar('taskId'); ?>">
        <input type="hidden" name="lock" value="masterpass">
    <?php endif; ?>
</form>
<div class="tab-actions">
    <ul>
        <li>
            <button id="help_mpass_button" type="button"
                    class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                    title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                    data-help="help-mpass">
                <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
            </button>

            <div for="help-mpass" class="help-box"
                 title="<?php echo __('Master Password'); ?>">
                <div class="help-container">
                    <p class="help-text"><?php echo __('The Master Password is used to encrypt sysPass accounts passwords and keep them safe.'); ?></p>

                    <p class="help-text"><?php echo __('It\'s advisable to change it from time to time and use a complex password that includes numbers, letters and symbols.'); ?></p>

                    <p class="help-text"><?php printf(__('You could get more info on: %s'), '<a target="_blank" href="https://doc.syspass.org">https://doc.syspass.org</a>'); ?></p>
                </div>
            </div>
        </li>
        <?php if ($_getvar('ctx_userIsAdminApp')): ?>
            <li>
                <button type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                        data-onclick="config/refreshMpass"
                        data-action-route="configEncryption/refresh"
                        title="<?php echo __('Update master password hash'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconRefresh()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>

        <?php if ($configData->isMaintenance() && $_getvar('numAccounts') > 0): ?>
            <li>
                <button form="frmCrypt"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                        title="<?php echo $icons->getIconPlay()->getTitle(); ?>">
                    <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>
    </ul>
</div>

<div id="title" class="titleNormal">
    <i class="material-icons">enhanced_encryption</i>
    <?php echo __('Temporary Password'); ?>
</div>

<form method="post" name="frmTempMasterPass" id="frmTempMasterPass"
      class="form-action"
      data-onsubmit="config/save"
      data-action-route="configEncryption/saveTemp">
    <table class="data tblConfig">
        <tr>
            <td class="descField">
                <?php echo __('Last change'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Last change'); ?></div>

                <?php echo ($_getvar('tempMasterPassTime') > 0) ? date('r', $_getvar('tempMasterPassTime')) : __('Not generated'); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Valid until'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Valid until'); ?></div>

                <?php
                $tempMasterMaxTime = $_getvar('tempMasterMaxTime');
                if (time() > $tempMasterMaxTime): ?>
                    <span style="color: red"><?php echo date('r', $tempMasterMaxTime); ?></span>
                <?php elseif ($tempMasterMaxTime > 0): echo date('r', $tempMasterMaxTime); ?>
                <?php else: echo __('Not generated'); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Attempts'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Attempts'); ?></div>

                <?php echo $_getvar('tempMasterAttempts'); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Life (s)'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="temporary_masterpass_maxtime"
                           name="temporary_masterpass_maxtime" type="number"
                           step="600"
                           pattern="[0-9]{2,6}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="3600"
                           required/>
                    <label class="mdl-textfield__label"
                           for="temporary_masterpass_maxtime"><?php echo __('Life (s)'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-masterpass-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="temporary_masterpass_email">
                                <input type="checkbox"
                                       id="temporary_masterpass_email"
                                       class="mdl-switch__input"
                                       name="temporary_masterpass_email" <?php echo !$configData->isMailEnabled() ? 'disabled' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Send email'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Sends an email to all users or only to those whose group is selected.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Group'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Group'); ?></div>

                <select id="temporary_masterpass_group"
                        name="temporary_masterpass_group"
                        class="select-box select-box-deselect" <?php echo !$configData->isMailEnabled() ? 'disabled' : ''; ?>>
                    <option value=""><?php echo __('Select Group'); ?></option>
                    <?php /** @var SelectItem $userGroup */
                    foreach ($_getvar('userGroups') as $userGroup): ?>
                        <option
                                value="<?php echo $userGroup->getId(); ?>"><?php echo $userGroup->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php if (!empty($_getvar('tempMasterPass'))): ?>
            <tr>
                <td class="descField">
                    <?php echo __('Password'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Password'); ?></div>

                    <?php echo $_getvar('tempMasterPass'); ?>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Back'); ?>">
                <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button id="help_tmpass_button"
                    class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                    title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                    data-help="help-tmpass">
                <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
            </button>


            <div for="help-tmpass" class="help-box"
                 title="<?php echo __('Temporary Password'); ?>">
                <div class="help-container">
                    <p class="help-text"><?php echo __('The temporary password is used as a Master Password for the users that need to enter it during login. By this way there is no need to tell them the real Master Password.'); ?></p>
                </div>
            </div>
        </li>
        <li>
            <button form="frmTempMasterPass"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                    title="<?php echo __('Generate'); ?>">
                <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
