<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

/**
 * @var callable       $_getvar
 * @var ThemeIcons     $icons
 * @var AccountExtData $accountData
 * @var AccountAcl     $accountAcl
 * @var ConfigData     $configData
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\Services\Account\AccountAcl;

/** @var AccountExtData $accountData */
$accountData = $_getvar('accountData');
?>

<div id="actions" class="public-link" align="center">
    <div id="content" class="public-link">
        <div id="title" class="titleNormal">
            <i class="material-icons"><?php echo $_getvar('title')['icon']; ?></i>
            <?php echo $_getvar('title')['name']; ?>
        </div>
        <table class="data">
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo htmlspecialchars($accountData->getName(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Account name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Client'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="client" name="client" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo htmlspecialchars($accountData->getClientName(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="client"><?php echo __('Client'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Category'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="category" name="category" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo htmlspecialchars($accountData->getCategoryName(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="category"><?php echo __('Category'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('URL / IP'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="url" name="url" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo htmlspecialchars($accountData->getUrl(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="url"><?php echo __('URL / IP'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('User'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo htmlspecialchars($accountData->getLogin(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="login"><?php echo __('User'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Notes'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea
                                class="mdl-textfield__input mdl-color-text--indigo-400"
                                rows="3" id="notes"
                                name="notes"
                                maxlength="1000"
                                readonly><?php echo htmlspecialchars($accountData->getNotes(), ENT_QUOTES); ?></textarea>
                        <label class="mdl-textfield__label"
                               for="notes"><?php echo __('Notes about the account'); ?></label>
                    </div>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Password'); ?></td>
                <td class="valField">
                    <?php if ($_getvar('useImage')): ?>
                        <div class="lowres-title"><?php echo __('Password'); ?></div>

                        <img class="account-pass-image"
                             src="data:image/png;base64,<?php echo $_getvar('accountPassImage'); ?>"/>
                    <?php else: ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="password" name="password" type="text"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   value="<?php echo $accountData->getPass(); ?>"
                                   readonly>
                            <label class="mdl-textfield__label"
                                   for="password"><?php echo __('Password'); ?></label>
                        </div>
                    <?php endif; ?>
            </tr>
        </table>

        <div class="item-actions">
            <ul>
                <?php if (!$_getvar('useImage')): ?>
                    <li>
                        <button id="btnClipPass"
                                class="clip-pass-icon mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-color--indigo-A200"
                                data-clipboard-target="#password"
                                data-item-id="<?php echo $accountData->getId(); ?>"
                                data-sk="<?php echo $_getvar('sk'); ?>"
                                data-history="0"
                                data-onclick="account/copypass">
                            <i class="material-icons">content_paste</i>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>