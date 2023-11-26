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
 * @var  AuthTokenData $authToken
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\DataModel\AuthTokenData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\TemplateInterface;

$authToken = $_getvar('authToken');
?>
<div id="box-popup">
    <h2 class="center"><?php
        echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmTokens" id="frmTokens" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php
          echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php
                    echo __('User'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('User'); ?></div>

                    <select id="selUsers" name="users" class="select-box"
                            required <?php
                    echo $_getvar('disabled'); ?>>
                        <option value=""><?php
                            echo __('Select User'); ?></option>
                        <?php
                        /** @var SelectItem $user */
                        foreach ($_getvar('users') as $user): ?>
                            <option value="<?php
                            echo $user->getId(); ?>" <?php
                            echo $user->isSelected() ? 'selected' : ''; ?>><?php
                                printf(
                                    '%s (%s)',
                                    htmlspecialchars($user->getName(), ENT_QUOTES),
                                    htmlspecialchars($user->getItemProperty('login'), ENT_QUOTES)
                                ); ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Action'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Action'); ?></div>

                    <select id="selActions" name="actions"
                            class="select-box"
                            required <?php
                    echo $_getvar('disabled'); ?>>
                        <option value=""><?php
                            echo __('Select Action'); ?></option>
                        <?php
                        /** @var SelectItem $action */
                        foreach ($_getvar('actions') as $action): ?>
                            <option value="<?php
                            echo $action->getId(); ?>" <?php
                            echo $action->isSelected() ? 'selected' : ''; ?>><?php
                                echo htmlspecialchars($action->getName(), ENT_QUOTES); ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php
            if (!$_getvar('isView')): ?>
                <tr>
                    <td class="descField"><?php
                        echo __('Password'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="pass" name="pass" type="password"
                                   class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                                   maxlength="50">
                            <label class="mdl-textfield__label"
                                   for="pass"><?php
                                echo __('Password'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php
                        echo __('Options'); ?></td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="refreshtoken"
                               title="<?php
                               echo __('Refresh Authorization'); ?>">
                            <input type="checkbox" id="refreshtoken"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="refreshtoken"/>
                            <span class="mdl-switch__label"><?php
                                echo __('Refresh Authorization'); ?></span>
                        </label>
                    </td>
                </tr>
            <?php
            else: ?>
                <tr>
                    <td class="descField"><?php
                        echo __('Token'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('Token'); ?></div>

                        <?php
                        echo $authToken->getToken(); ?>
                    </td>
                </tr>
            <?php
            endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                echo $icons->save()->getClassButton(); ?>"
                form="frmTokens"
                title="<?php
                echo $icons->save()->getTitle(); ?>">
            <i class="material-icons"><?php
                echo $icons->save()->getIcon(); ?></i>
        </button>
    </div>
</div>
