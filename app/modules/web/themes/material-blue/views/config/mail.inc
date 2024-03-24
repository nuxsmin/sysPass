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

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\TemplateInterface;

?>

<div id="title" class="titleNormal">
    <i class="material-icons">mail</i>
    <?php
    echo __('Mail'); ?>
</div>

<form method="post" name="frmMail" id="frmMail" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configMail/save"
      data-hash="">
    <table id="tblMail" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-mail-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="mail_enabled">
                                <input type="checkbox" id="mail_enabled"
                                       class="mdl-switch__input"
                                       name="mail_enabled" <?php
                                echo $configData->isMailEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php
                                echo __('Enable email notifications'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php
                                echo __('Enable email notifications'); ?></span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="mail_requests_enabled">
                                <input type="checkbox"
                                       id="mail_requests_enabled"
                                       class="mdl-switch__input"
                                       name="mail_requests_enabled" <?php
                                echo $configData->isMailRequestsEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php
                                echo __('Enable requests by email'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php
                                echo __(
                                    'Allows users to request for either modifications to existing accounts or access to accounts which they have no rights on.'
                                ); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Server'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_server" name="mail_server" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128"
                           value="<?php
                           echo htmlspecialchars($configData->getMailServer(), ENT_QUOTES); ?>"/>
                    <label class="mdl-textfield__label"
                           for="mail_server"><?php
                        echo __('Server'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Port'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_port" name="mail_port" type="number"
                           pattern="[0-9]{1,5}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php
                           echo $configData->getMailPort(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="mail_port"><?php
                        echo __('Port'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-mail-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="mail_auth_enabled">
                                <input type="checkbox" id="mail_auth_enabled"
                                       class="mdl-switch__input"
                                       name="mail_auth_enabled" <?php
                                echo $configData->isMailAuthenabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php
                                echo __('Enable Authentication'); ?></span>
                            <span class="mdl-list__item-sub-title"></span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('User'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_user" name="mail_user" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="50"
                           value="<?php
                           echo htmlspecialchars($configData->getMailUser(), ENT_QUOTES); ?>"/>
                    <label class="mdl-textfield__label"
                           for="mail_user"><?php
                        echo __('User'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Password'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_pass" name="mail_pass" type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           maxlength="128"
                           value="<?php
                           echo $configData->getMailPass() ? '***' : ''; ?>"/>
                    <label class="mdl-textfield__label"
                           for="mail_pass"><?php
                        echo __('Password'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Security'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Security'); ?></div>

                <select name="mail_security" id="sel-mailsecurity" size="1"
                        class="select-box select-box-deselect">
                    <option value=""><?php
                        echo __('Disabled'); ?></option>
                    <?php
                    foreach ($_getvar('mailSecurity') as $security): ?>
                        <option
                                value="<?php
                                echo $security; ?>" <?php
                        echo ($configData->getMailSecurity() === $security) ? 'selected' : ''; ?>><?php
                            echo $security; ?></option>
                    <?php
                    endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Sender email address'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_from" name="mail_from" type="email"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128"
                           value="<?php
                           echo htmlspecialchars($configData->getMailFrom(), ENT_QUOTES); ?>"/>
                    <label class="mdl-textfield__label"
                           for="mail_from"><?php
                        echo __('Sender email address'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Recipients'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Recipients'); ?></div>

                <input type="text" name="mail_recipients" id="mail_recipients"
                       class="select-items-tag"
                       value="<?php
                       echo implode(',', $configData->getMailRecipients()); ?>"/>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php
                echo __('Events'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Events'); ?></div>

                <select id="mail_events" name="mail_events[]"
                        multiple="multiple" class="select-box"
                        data-create="true">
                    <?php
                    /** @var SelectItem $mailEvent */
                    foreach ($_getvar('mailEvents') as $mailEvent): ?>
                        <option
                                value="<?php
                                echo $mailEvent->getName(); ?>"
                            <?php
                            echo $mailEvent->isSelected() ? 'selected' : '' ?>><?php
                            echo $mailEvent->getName(); ?></option>
                    <?php
                    endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button type="button"
                    class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->check()->getClassButton(); ?>"
                    data-onclick="config/mailCheck"
                    data-action-route="configMail/check"
                    data-src="#frmMail"
                    title="<?php
                    echo $icons->check()->getTitle(); ?>">
                <i class="material-icons"><?php
                    echo $icons->check()->getIcon(); ?></i>
            </button>
        </li>
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
            <button form="frmMail"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->save()->getClassButton(); ?>"
                    title="<?php
                    echo $icons->save()->getTitle(); ?>">
                <i class="material-icons"><?php
                    echo $icons->save()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
