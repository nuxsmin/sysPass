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
 * @var UserData $user
 * @var \SP\Domain\Core\UI\ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\DataModel\UserData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\TemplateInterface;

$user = $_getvar('user');
?>

<div id="box-popup">
    <h2 class="center"><?php
        echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="updUsrPass" id="frmUpdUsrPass" class="form-action"
          data-onsubmit="user/savePassword"
          data-action-route="<?php
          echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tr>
                <td class="descField"><?php
                    echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars($user->getName(), ENT_QUOTES); ?>" readonly
                               disabled/>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php
                    echo __('Login'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars($user->getLogin(), ENT_QUOTES); ?>"
                               readonly disabled/>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php
                    echo __('Password'); ?></td>
                <td class="valField">
                    <div class="form-control">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="password" name="password" type="password"
                                   required
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                                   maxlength="50">
                            <label class="mdl-textfield__label"
                                   for="password"><?php
                                echo __('Password'); ?></label>
                        </div>
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
                               maxlength="50">
                        <label class="mdl-textfield__label"
                               for="password_repeat"><?php
                            echo __('Password (repeat)'); ?></label>
                    </div>
                </td>
            </tr>
        </table>
    </form>

    <div class="action-in-box">
        <button form="frmUpdUsrPass"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                echo $icons->save()->getClassButton(); ?>"
                title="<?php
                echo $icons->save()->getTitle(); ?>">
            <i class="material-icons"><?php
                echo $icons->save()->getIcon(); ?></i>
        </button>
    </div>
</div>
