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

use function SP\__;

?>
<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"><?php
            echo __('Users'); ?></td>
        <td class="valField">
            <div class="account-permissions">
                <div class="tag-list-box">
                    <select id="users_view" name="users_view[]"
                            multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php
                            echo __('Select Users'); ?></option>
                        <?php
                        /** @var SelectItem $user */
                        foreach ($_getvar('usersView') as $user): ?>
                            <?php
                            if ($user->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php
                                    echo $user->getId(); ?>"
                                <?php
                                echo $user->isSelected() ? 'selected' : '' ?>><?php
                                echo htmlspecialchars($user->getName(), ENT_QUOTES); ?></option>
                        <?php
                        endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php
                       echo __('View'); ?>"><?php
                        echo $icons->view()->getIcon(); ?></i>
                </div>

                <div class="tag-list-box">
                    <select id="users_edit" name="users_edit[]"
                            multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php
                            echo __('Select Users'); ?></option>
                        <?php
                        /** @var SelectItem $user */
                        foreach ($_getvar('usersEdit') as $user): ?>
                            <?php
                            if ($user->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php
                                    echo $user->getId(); ?>"
                                <?php
                                echo $user->isSelected() ? 'selected' : '' ?>><?php
                                echo htmlspecialchars($user->getName(), ENT_QUOTES); ?></option>
                        <?php
                        endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php
                       echo __('Edit'); ?>"><?php
                        echo $icons->edit()->getIcon(); ?></i>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td class="descField"><?php
            echo __('Groups'); ?></td>
        <td class="valField">
            <div class="account-permissions">
                <div class="tag-list-box">
                    <select id="user_groups_view" name="user_groups_view[]"
                            multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php
                            echo __('Select Groups'); ?></option>
                        <?php
                        /** @var SelectItem $userGroup */
                        foreach ($_getvar('userGroupsView') as $userGroup): ?>
                            <?php
                            if ($userGroup->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php
                                    echo $userGroup->getId(); ?>"
                                <?php
                                echo $userGroup->isSelected() ? 'selected' : '' ?>><?php
                                echo htmlspecialchars($userGroup->getName(), ENT_QUOTES); ?></option>
                        <?php
                        endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php
                       echo __('View'); ?>"><?php
                        echo $icons->view()->getIcon(); ?></i>
                </div>

                <div class="tag-list-box">
                    <select id="user_groups_edit" name="user_groups_edit[]"
                            multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php
                            echo __('Select Groups'); ?></option>
                        <?php
                        /** @var SelectItem $userGroup */
                        foreach ($_getvar('userGroupsEdit') as $userGroup): ?>
                            <?php
                            if ($userGroup->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php
                                    echo $userGroup->getId(); ?>"
                                <?php
                                echo $userGroup->isSelected() ? 'selected' : '' ?>><?php
                                echo htmlspecialchars($userGroup->getName(), ENT_QUOTES); ?></option>
                        <?php
                        endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php
                       echo __('Edit'); ?>"><?php
                        echo $icons->edit()->getIcon(); ?></i>
                </div>
            </div>
        </td>
    </tr>
    </tbody>
</table>
