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

/**
 * @var callable $_getvar
 * @var \SP\Domain\Core\UI\ThemeIconsInterface $icons
 * @var AccountExtData $accountData
 * @var AccountPermission $accountAcl
 * @var ConfigDataInterface $configData
 */

use SP\DataModel\AccountExtData;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Config\Ports\ConfigDataInterface;

/** @var AccountExtData $accountData */
$accountData = $_getvar('accountData');

/** @var AccountPermission $accountAcl */
$accountAcl = $_getvar('accountAcl');
?>

<div class="data-container">
    <div id="title" class="titleNormal">
        <i class="material-icons"><?php
            echo $_getvar('title')['icon']; ?></i>
        <?php
        echo $_getvar('title')['name']; ?>
    </div>

    <form method="post" name="editpass" id="frmEditPass" class="form-action"
          data-onsubmit="account/save"
          data-action-route="<?php
          echo $_getvar('formRoute', ''); ?>"
          data-item-id="<?php
          echo $_getvar('accountId', ''); ?>"
          data-hash="">
        <table class="data">
            <tr>
                <td class="descField"><?php
                    echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars($accountData->getName(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="name"><?php
                            echo __('Account name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Client'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="client" name="client" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars($accountData->getClientName(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="client"><?php
                            echo __('Client'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('URL / IP'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="url" name="url" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars($accountData->getUrl(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="url"><?php
                            echo __('URL / IP'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('User'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars($accountData->getLogin(), ENT_QUOTES); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="login"><?php
                            echo __('User'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Password'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password" name="password" type="password"
                               required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password"><?php
                            echo __('Password'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Password (repeat)'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password_repeat" name="password_repeat"
                               type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password_repeat"><?php
                            echo __('Password'); ?></label>
                    </div>
                </td>
            </tr>
            <?php
            if ($configData->isAccountExpireEnabled()): ?>
                <tr>
                    <td class="descField"><?php
                        echo __('Password Expiry Date'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="password_date_expire"
                                   name="password_date_expire" type="date"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                                   value="<?php
                                   echo $_getvar('accountPassDateChange'); ?>"
                                   data-dst-unix="password_date_expire_unix">
                            <input type='hidden'
                                   name='password_date_expire_unix' value=""/>
                            <label class="mdl-textfield__label"
                                   for="password_date_expire"><?php
                                echo __('Date'); ?></label>
                        </div>
                        <i class="material-icons btn-action"
                           title="<?php
                           echo __('Select date'); ?>">date_range</i>
                    </td>
                </tr>
            <?php
            endif; ?>
        </table>

        <input type="hidden" name="isAjax" value="1">

        <div class="item-actions">
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
                    <button id="btnSave" name="btnSave" type="submit"
                            form="frmEditPass"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                            echo $icons->save()->getClassButton(); ?>"
                            title="<?php
                            echo __('Save'); ?>">
                        <i class="material-icons"><?php
                            echo $icons->save()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </form>
</div>

<script>
    $('input:password:visible:first').focus();
</script>
