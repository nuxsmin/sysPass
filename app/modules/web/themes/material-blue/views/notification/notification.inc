<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 * @var  Notification $notification
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Notification\Models\Notification;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\TemplateInterface;
use SP\Util\Date;

$notification = $_getvar('notification');
?>
<div id="box-popup">
    <h2 class="center"><?php
        echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmNotices" id="frmNotices" class="form-action"
          data-onsubmit="notification/save"
          data-route="<?php
          echo $_getvar('route', ''); ?>"
          data-action-next="<?php
          echo $_getvar('nextAction'); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php
                    echo __('Type'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notification_type" name="notification_type"
                               type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo $notification->getType(); ?>" <?php
                        echo $_getvar('disabled'); ?>>
                        <label class="mdl-textfield__label"
                               for="notification_type"><?php
                            echo __('Type'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Component'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notification_component"
                               name="notification_component" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo $notification->getComponent(); ?>" <?php
                        echo $_getvar('disabled'); ?>>
                        <label class="mdl-textfield__label"
                               for="notification_component"><?php
                            echo __('Component'); ?></label>
                    </div>
                </td>
            </tr>
            <?php
            if ($_getvar('isView')): ?>
                <tr>
                    <td class="descField"><?php
                        echo __('Date'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="notification_date"
                                   name="notification_date" type="text"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   value="<?php
                                   echo Date::getDateFromUnix($notification->getDate()); ?>"
                                <?php
                                echo $_getvar('disabled'); ?>>
                            <label class="mdl-textfield__label"
                                   for="notification_date"><?php
                                echo __('Date'); ?></label>
                        </div>
                    </td>
                </tr>
            <?php
            endif; ?>
            <tr>
                <td class="descField"><?php
                    echo __('Description'); ?></td>
                <td class="valField">
                    <?php
                    if ($_getvar('isView') === false): ?>
                        <div class="mdl-textfield mdl-js-textfield">
                            <textarea
                                    class="mdl-textfield__input mdl-color-text--indigo-400"
                                    type="text" rows="3"
                                    id="notification_description"
                                    name="notification_description" <?php
                            echo $_getvar('readonly'); ?>>
                                <?php
                                echo htmlspecialchars($notification->getDescription(), ENT_QUOTES); ?>
                            </textarea>
                            <label class="mdl-textfield__label"
                                   for="notification_description"><?php
                                echo __('Description'); ?></label>
                        </div>
                    <?php
                    else: ?>
                        <div class="notification-description">
                            <?php
                            echo htmlspecialchars($notification->getDescription(), ENT_QUOTES); ?>
                        </div>
                    <?php
                    endif; ?>
                </td>
            </tr>

            <?php
            if (!$_getvar('isView') && $_getvar('ctx_userIsAdminApp')): ?>
                <tr>
                    <td class="descField"><?php
                        echo __('User'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('User'); ?></div>

                        <select id="notification_user" name="notification_user"
                                class="select-box select-box-deselect" <?php
                        echo $_getvar('disabled'); ?>>
                            <option value=""><?php
                                echo __('Select User'); ?></option>
                            <?php
                            /** @var SelectItem $user */
                            foreach ($_getvar('users') as $user): ?>
                                <option value="<?php
                                echo $user->getId(); ?>"
                                    <?php
                                    echo $user->isSelected() ? 'selected' : ''; ?>>
                                    <?php
                                    echo htmlspecialchars($user->getName(), ENT_QUOTES); ?>
                                </option>
                            <?php
                            endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php
                        echo __('Options'); ?></td>
                    <td class="valField checkbox">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="notification_sticky"
                               title="<?php
                               echo __('Global notification'); ?>">
                            <input type="checkbox" id="notification_sticky"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notification_sticky" <?php
                            echo $notification->isSticky() ? 'checked'
                                : ' '; ?> <?php
                            echo $_getvar('disabled'); ?>/>
                            <span class="mdl-switch__label"><?php
                                echo __('Global'); ?></span>
                        </label>

                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="notification_onlyadmin"
                               title="<?php
                               echo __('Only for application administrators'); ?>">
                            <input type="checkbox" id="notification_onlyadmin"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notification_onlyadmin"
                                <?php
                                echo $notification->isOnlyAdmin() ? 'checked' : ' '; ?>
                                <?php
                                echo $_getvar('disabled'); ?>/>
                            <span class="mdl-switch__label"><?php
                                echo __('Only Admins'); ?></span>
                        </label>

                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="notification_checkout"
                               title="<?php
                               echo __('Read'); ?>">
                            <input type="checkbox" id="notification_checkout"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notification_checkout"
                                <?php
                                echo $notification->isChecked() ? 'checked' : ' '; ?>
                                <?php
                                echo $_getvar('disabled'); ?>/>
                            <span class="mdl-switch__label"><?php
                                echo __('Read'); ?></span>
                        </label>
                    </td>
                </tr>
            <?php
            endif; ?>

            </tbody>
        </table>
    </form>
    <?php
    if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->save()->getClassButton(); ?>"
                    form="frmNotices"
                    title="<?php
                    echo $icons->save()->getTitle(); ?>">
                <i class="material-icons"><?php
                    echo $icons->save()->getIcon(); ?></i>
            </button>
        </div>
    <?php
    endif; ?>
</div>
