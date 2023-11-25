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
 * @var AccountPrivate $private
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\TemplateInterface;

$private = $_getvar('private');
?>

<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="private-account-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="private_user_enabled">
                            <input type="checkbox" id="private_user_enabled"
                                   class="mdl-switch__input"
                                   name="private_user_enabled" <?php
                            echo $private->isPrivateUser() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php
                            echo __('Private Account'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php
                            echo __('Accounts will be only visible by the user.'); ?>
                            <br>
                            <?php
                            echo __('Administrators won\'t be able to display the accounts.'); ?>
                        </span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="private_group_enabled">
                            <input type="checkbox" id="private_group_enabled"
                                   class="mdl-switch__input"
                                   name="private_group_enabled" <?php
                            echo $private->isPrivateGroup() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php
                            echo __('Private Account for Group'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php
                            echo __(
                                'Accounts will be only visible by the user and users belonging to the main group.'
                            ); ?>
                            <br>
                            <?php
                            echo __('Administrators won\'t be able to display the accounts.'); ?>
                        </span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    </tbody>
</table>
