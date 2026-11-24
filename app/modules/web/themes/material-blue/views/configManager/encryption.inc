<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Modules\Web\Mvc\View\Components\SelectItem;
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

/**
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

?>

<div id="title" class="titleNormal">
    <i class="material-icons">enhanced_encryption</i>
    <?php echo __('Master Password'); ?>
</div>
<?php if ($_getvar('lastUpdateMPass') > 0): ?>
    <p><?php echo __('Last change'); ?>: <?php echo date('r', $_getvar('lastUpdateMPass')); ?></p>
<?php endif; ?>
<p><?php echo __('To change the master password, enable maintenance mode and run the CLI command sp:crypt:update-master-password.'); ?></p>

<div id="title" class="titleNormal">
    <i class="material-icons">enhanced_encryption</i>
    <?php
    echo __('Temporary Password'); ?>
</div>

<form method="post" name="frmTempMasterPass" id="frmTempMasterPass"
      class="form-action"
      data-onsubmit="config/save"
      data-action-route="configEncryption/saveTemp">
    <table class="data tblConfig">
        <tr>
            <td class="descField">
                <?php
                echo __('Last change'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Last change'); ?></div>

                <?php
                echo ($_getvar('tempMasterPassTime') > 0) ? date('r', $_getvar('tempMasterPassTime')) : __(
                    'Not generated'
                ); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Valid until'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Valid until'); ?></div>

                <?php
                $tempMasterMaxTime = $_getvar('tempMasterMaxTime');
                if (time() > $tempMasterMaxTime): ?>
                    <span style="color: red"><?php
                        echo date('r', $tempMasterMaxTime); ?></span>
                <?php
                elseif ($tempMasterMaxTime > 0): echo date('r', $tempMasterMaxTime); ?>
                <?php
                else: echo __('Not generated'); ?>
                <?php
                endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Attempts'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Attempts'); ?></div>

                <?php
                echo $_getvar('tempMasterAttempts'); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Life (s)'); ?>
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
                           for="temporary_masterpass_maxtime"><?php
                        echo __('Life (s)'); ?></label>
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
                                       name="temporary_masterpass_email" <?php
                                echo !$configData->isMailEnabled() ? 'disabled' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php
                                echo __('Send email'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php
                                echo __('Sends an email to all users or only to those whose group is selected.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Group'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Group'); ?></div>

                <select id="temporary_masterpass_group"
                        name="temporary_masterpass_group"
                        class="select-box select-box-deselect" <?php
                echo !$configData->isMailEnabled() ? 'disabled' : ''; ?>>
                    <option value=""><?php
                        echo __('Select Group'); ?></option>
                    <?php
                    /** @var SelectItem $userGroup */
                    foreach ($_getvar('userGroups') as $userGroup): ?>
                        <option
                                value="<?php
                                echo $userGroup->getId(); ?>"><?php
                            echo htmlspecialchars($userGroup->getName(), ENT_QUOTES); ?></option>
                    <?php
                    endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
        if (!empty($_getvar('tempMasterPass'))): ?>
            <tr>
                <td class="descField">
                    <?php
                    echo __('Password'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Password'); ?></div>

                    <?php
                    echo $_getvar('tempMasterPass'); ?>
                </td>
            </tr>
        <?php
        endif; ?>
    </table>
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->back()->getClassButton(); ?>"
                    title="<?php
                    echo __('Back'); ?>">
                <i class="material-icons"><?php
                    echo $icons->back()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button id="help_tmpass_button"
                    class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                    title="<?php
                    echo $icons->help()->getTitle(); ?>"
                    data-help="help-tmpass">
                <i class="material-icons"><?php
                    echo $icons->help()->getIcon(); ?></i>
            </button>


            <div for="help-tmpass" class="help-box"
                 title="<?php
                 echo __('Temporary Password'); ?>">
                <div class="help-container">
                    <p class="help-text"><?php
                        echo __(
                            'The temporary password is used as a Master Password for the users that need to enter it during login. By this way there is no need to tell them the real Master Password.'
                        ); ?></p>
                </div>
            </div>
        </li>
        <li>
            <button form="frmTempMasterPass"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->play()->getClassButton(); ?>"
                    title="<?php
                    echo __('Generate'); ?>">
                <i class="material-icons"><?php
                    echo $icons->play()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
