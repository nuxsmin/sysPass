<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Common\Adapters\Date;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Notification\Models\Notification;
use SP\Modules\Web\Mvc\View\Components\SelectItem;
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

$notification = $_getvar('notification');
$isView = (bool)$_getvar('isView');
?>
<div id="box-popup"
     class="sp-dialog <?php echo $isView ? 'sp-dialog--view' : ''; ?>"
     role="dialog"
     aria-modal="true"
     aria-labelledby="box-popup-title">
    <h2 class="center" id="box-popup-title"><?php
        echo $_getvar('header'); ?></h2>

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
                <td class="descField"><label for="notification_type"><?php
                    echo __('Type'); ?></label></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notification_type" name="notification_type"
                               type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars((string)$notification->getType(), ENT_QUOTES); ?>" <?php
                        echo $_getvar('disabled'); ?>>
                        <label class="mdl-textfield__label"
                               for="notification_type"><?php
                            echo __('Type'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><label for="notification_component"><?php
                    echo __('Component'); ?></label></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notification_component"
                               name="notification_component" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo htmlspecialchars((string)$notification->getComponent(), ENT_QUOTES); ?>" <?php
                        echo $_getvar('disabled'); ?>>
                        <label class="mdl-textfield__label"
                               for="notification_component"><?php
                            echo __('Component'); ?></label>
                    </div>
                </td>
            </tr>
            <?php
            if ($isView): ?>
                <tr>
                    <td class="descField"><label for="notification_date"><?php
                        echo __('Date'); ?></label></td>
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
                <td class="descField"><label for="notification_description"><?php
                    echo __('Description'); ?></label></td>
                <td class="valField">
                    <?php
                    if ($isView === false): ?>
                        <div class="mdl-textfield mdl-js-textfield">
                            <textarea
                                    class="mdl-textfield__input mdl-color-text--indigo-400"
                                    type="text" rows="3"
                                    id="notification_description"
                                    name="notification_description" <?php
                            echo $_getvar('readonly'); ?>><?php
                                echo htmlspecialchars((string)$notification->getDescription(), ENT_QUOTES); ?></textarea>
                            <label class="mdl-textfield__label"
                                   for="notification_description"><?php
                                echo __('Description'); ?></label>
                        </div>
                    <?php
                    else: ?>
                        <div class="notification-description sp-readonly-value">
                            <?php
                            echo htmlspecialchars((string)$notification->getDescription(), ENT_QUOTES); ?>
                        </div>
                    <?php
                    endif; ?>
                </td>
            </tr>

            <?php
            if (!$isView && $_getvar('ctx_userIsAdminApp')): ?>
                <tr>
                    <td class="descField"><label for="notification_user"><?php
                        echo __('User'); ?></label></td>
                    <td class="valField">
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
                        <ul class="user-list-action mdl-list">
                            <li class="mdl-list__item mdl-list__item--two-line">
                                <div class="mdl-switch__box">
                                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                           for="notification_sticky"
                                           title="<?php echo htmlspecialchars(__('Global notification'), ENT_QUOTES); ?>">
                                        <input type="checkbox" id="notification_sticky"
                                               class="mdl-switch__input mdl-color-text--indigo-400"
                                               name="notification_sticky"
                                               aria-label="<?php echo htmlspecialchars(__('Global'), ENT_QUOTES); ?>"
                                               aria-describedby="desc-notification_sticky"
                                            <?php echo $notification->isSticky() ? 'checked' : ' '; ?>
                                            <?php echo $_getvar('disabled'); ?>/>
                                    </label>
                                </div>
                                <span class="mdl-list__item-primary-content">
                                    <span><?php echo __('Global'); ?></span>
                                    <span id="desc-notification_sticky" class="mdl-list__item-sub-title"><?php
                                        echo __('Global notification'); ?></span>
                                </span>
                            </li>

                            <li class="mdl-list__item mdl-list__item--two-line">
                                <div class="mdl-switch__box">
                                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                           for="notification_onlyadmin"
                                           title="<?php echo htmlspecialchars(__('Only for application administrators'), ENT_QUOTES); ?>">
                                        <input type="checkbox" id="notification_onlyadmin"
                                               class="mdl-switch__input mdl-color-text--indigo-400"
                                               name="notification_onlyadmin"
                                               aria-label="<?php echo htmlspecialchars(__('Only Admins'), ENT_QUOTES); ?>"
                                               aria-describedby="desc-notification_onlyadmin"
                                            <?php echo $notification->isOnlyAdmin() ? 'checked' : ' '; ?>
                                            <?php echo $_getvar('disabled'); ?>/>
                                    </label>
                                </div>
                                <span class="mdl-list__item-primary-content">
                                    <span><?php echo __('Only Admins'); ?></span>
                                    <span id="desc-notification_onlyadmin" class="mdl-list__item-sub-title"><?php
                                        echo __('Only for application administrators'); ?></span>
                                </span>
                            </li>

                            <li class="mdl-list__item mdl-list__item--two-line">
                                <div class="mdl-switch__box">
                                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                           for="notification_checkout"
                                           title="<?php echo htmlspecialchars(__('Read'), ENT_QUOTES); ?>">
                                        <input type="checkbox" id="notification_checkout"
                                               class="mdl-switch__input mdl-color-text--indigo-400"
                                               name="notification_checkout"
                                               aria-label="<?php echo htmlspecialchars(__('Read'), ENT_QUOTES); ?>"
                                            <?php echo $notification->isChecked() ? 'checked' : ' '; ?>
                                            <?php echo $_getvar('disabled'); ?>/>
                                    </label>
                                </div>
                                <span class="mdl-list__item-primary-content">
                                    <span><?php echo __('Read'); ?></span>
                                </span>
                            </li>
                        </ul>
                    </td>
                </tr>
            <?php
            endif; ?>

            </tbody>
        </table>
    </form>

    <div class="action-in-box sp-dialog-footer">
        <?php if ($isView): ?>
            <button type="button"
                    class="btn-popup-close mdl-button mdl-js-button sp-btn-secondary">
                <?php echo __('Close'); ?>
            </button>
        <?php else: ?>
            <button type="button"
                    class="btn-popup-close mdl-button mdl-js-button sp-btn-secondary">
                <?php echo __('Cancel'); ?>
            </button>
            <button type="submit"
                    form="frmNotices"
                    class="mdl-button mdl-js-button sp-btn-primary <?php
                    echo $icons->save()->getClassButton(); ?>"
                    title="<?php
                    echo $icons->save()->getTitle(); ?>">
                <?php echo __('Save'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

