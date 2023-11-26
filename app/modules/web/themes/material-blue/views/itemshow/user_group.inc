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
 * @var UserToUserGroupData $groupUsers
 * @var UserGroupData $group
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\DataModel\UserGroupData;
use SP\DataModel\UserToUserGroupData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\TemplateInterface;

$group = $_getvar('group');
?>

<div id="box-popup">
    <h2 class="center"><?php
        echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmGroups" id="frmGroups" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php
          echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php
                    echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php
                               echo htmlspecialchars($group->getName(), ENT_QUOTES); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php
                            echo __('Group name'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php
                    echo __('Description'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php
                               echo htmlspecialchars($group->getDescription(), ENT_QUOTES); ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php
                            echo __('Group description'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php
                    echo __('Users'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Users'); ?></div>

                    <?php
                    if (!$_getvar('isView')): ?>
                        <select id="selUsers" name="users[]" multiple="multiple"
                                class="select-box"
                                title="<?php
                                echo __('Users'); ?>" <?php
                        echo $_getvar('disabled'); ?>>
                            <option value=""><?php
                                echo __('Select Users'); ?></option>
                            <?php
                            /** @var SelectItem $user */
                            foreach ($_getvar('users') as $user): ?>
                                <option
                                        value="<?php
                                        echo $user->getId(); ?>"
                                    <?php
                                    echo $user->isSelected() ? 'selected' : ''; ?>>
                                    <?php
                                    echo htmlspecialchars($user->getName(), ENT_QUOTES); ?>
                                </option>
                            <?php
                            endforeach; ?>
                        </select>
                    <?php
                    else: ?>
                        <div class="tag-list-box">
                            <div class="tag-list-body">
                                <div class="tags-list-items">
                                    <?php
                                    /** @var SelectItem $user */
                                    foreach ($_getvar('users') as $user): ?>
                                        <?php
                                        if ($user->isSelected()): ?>
                                            <span class="tag">
                                                <?php
                                                echo htmlspecialchars($user->getName(), ENT_QUOTES); ?>
                                            </span>
                                        <?php
                                        endif; ?>
                                    <?php
                                    endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    endif; ?>
                </td>
            </tr>

            <?php
            if ($_getvar('customFields')): ?>
                <?php
                include $this->includeTemplate('aux-customfields', 'common'); ?>
            <?php
            endif; ?>

            <?php
            if ($_getvar('usedBy')): ?>
                <tr>
                    <td class="descField"><?php
                        echo __('Used by'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('Used by'); ?></div>
                        <div class="list-wrap">
                            <ul class="mdl-list">
                                <?php
                                /** @var stdClass $user */
                                foreach ($_getvar('usedBy') as $user): ?>
                                    <li class="mdl-list__item mdl-list__item">
                                        <span class="mdl-list__item-primary-content">
                                            <i class="material-icons mdl-list__item-icon"
                                               title="<?php
                                               echo __('User'); ?>">person</i>
                                            <?php
                                            if ($user->ref === 'UserGroup'): ?>
                                                <?php
                                                printf(
                                                    '%s (%s)*',
                                                    htmlspecialchars($user->name, ENT_QUOTES),
                                                    htmlspecialchars($user->login, ENT_QUOTES)
                                                ); ?>
                                            <?php
                                            else: ?>
                                                <?php
                                                printf(
                                                    '%s (%s)',
                                                    htmlspecialchars($user->name, ENT_QUOTES),
                                                    htmlspecialchars($user->login, ENT_QUOTES)
                                                ); ?>
                                            <?php
                                            endif; ?>
                                        </span>
                                    </li>
                                <?php
                                endforeach; ?>
                            </ul>
                        </div>
                        <div><?php
                            echo __('(*) Listed in group'); ?></div>
                    </td>
                </tr>
            <?php
            endif; ?>

            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <?php
    if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->save()->getClassButton(); ?>"
                    form="frmGroups"
                    title="<?php
                    echo $icons->save()->getTitle(); ?>">
                <i class="material-icons"><?php
                    echo $icons->save()->getIcon(); ?></i>
            </button>
        </div>
    <?php
    endif; ?>
</div>
