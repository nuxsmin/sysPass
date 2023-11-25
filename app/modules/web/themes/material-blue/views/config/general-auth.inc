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
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\TemplateInterface;

?>

<div id="title" class="titleNormal">
    <i class="material-icons">verified_user</i>
    <?php
    echo __('Authentication'); ?>
</div>

<table id="tblAuth" class="data tblConfig">
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="config-auth-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="authbasic_enabled">
                            <input type="checkbox" id="authbasic_enabled"
                                   class="mdl-switch__input"
                                   name="authbasic_enabled" <?php
                            echo $configData->isAuthBasicEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php
                            echo __('Use Auth Basic'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php
                            echo __('Use Auth Basic for authorizing accesses to the application'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="authbasicautologin_enabled">
                            <input type="checkbox"
                                   id="authbasicautologin_enabled"
                                   class="mdl-switch__input"
                                   name="authbasicautologin_enabled" <?php
                            echo $configData->isAuthBasicAutoLoginEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php
                            echo __('Use Auth Basic SSO'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php
                            echo __('Use auto-login through Auth Basic'); ?></span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Domain'); ?>
            <div id="help-domain"
                 class="icon material-icons <?php
                 echo $icons->help()->getClass(); ?>"><?php
                echo $icons->help()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-domain">
                <p>
                    <?php
                    echo __(
                        'The domain will be added to sysPass user name for checking against the Auth Basic one.'
                    ); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="authbasic_domain" name="authbasic_domain" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128"
                       value="<?php
                       echo htmlspecialchars($configData->getAuthBasicDomain(), ENT_QUOTES); ?>">
                <label class="mdl-textfield__label"
                       for="authbasic_domain"><?php
                    echo __('Domain name'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Default Group'); ?>
            <div id="help-sso_defaultgroup"
                 class="icon material-icons <?php
                 echo $icons->help()->getClass(); ?>"><?php
                echo $icons->help()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large"
                 for="help-sso_defaultgroup">
                <p>
                    <?php
                    echo __('Sets the default users group for newly created SSO users'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Default Group'); ?></div>

            <select id="sso_defaultgroup" name="sso_defaultgroup"
                    class="select-box sel-chosen-usergroup">
                <option value=""><?php
                    echo __('Select Group'); ?></option>
                <?php
                /** @var SelectItem $userGroup */
                foreach ($_getvar('userGroups') as $userGroup): ?>
                    <option
                            value="<?php
                            echo $userGroup->getId(); ?>" <?php
                    echo $userGroup->getId() === $configData->getSsoDefaultGroup() ? 'selected' : ''; ?>><?php
                        echo htmlspecialchars($userGroup->getName(), ENT_QUOTES); ?></option>
                <?php
                endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Default Profile'); ?>
            <div id="help-sso_defaultprofile"
                 class="icon material-icons <?php
                 echo $icons->help()->getClass(); ?>"><?php
                echo $icons->help()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large"
                 for="help-sso_defaultprofile">
                <p>
                    <?php
                    echo __('Sets the default profile for the newly created SSO users.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Default Profile'); ?></div>

            <select id="sso_defaultprofile" name="sso_defaultprofile"
                    class="select-box sel-chosen-profile">
                <option value=""><?php
                    echo __('Select User'); ?></option>
                <?php
                /** @var SelectItem $userProfile */
                foreach ($_getvar('userProfiles') as $userProfile): ?>
                    <option
                            value="<?php
                            echo $userProfile->getId(); ?>" <?php
                    echo ($userProfile->getId() === $configData->getSsoDefaultProfile()) ? 'selected' : ''; ?>><?php
                        echo htmlspecialchars($userProfile->getName(), ENT_QUOTES); ?></option>
                <?php
                endforeach; ?>
            </select>
        </td>
    </tr>
</table>
