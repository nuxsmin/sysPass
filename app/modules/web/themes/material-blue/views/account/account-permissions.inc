<?php
/**
 * @var callable       $_getvar
 * @var ThemeIcons     $icons
 * @var AccountExtData $accountData
 * @var AccountAcl     $accountAcl
 * @var ConfigData     $configData
 * @var AccountPrivate $accountPrivate
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\Mvc\View\Components\SelectItem;
use SP\Services\Account\AccountAcl;

?>

<div id="data-accesses">
    <table class="data">
        <?php if ($accountAcl->isShowPermission()): ?>
            <tr>
                <td class="descField"><?php echo __('Users'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <div class="tag-list-box">
                            <?php if (!$_getvar('isView')): ?>
                                <select id="other_users_view"
                                        name="other_users_view[]"
                                        multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Select Users'); ?></option>
                                    <?php /** @var SelectItem $otherUser */
                                    foreach ($_getvar('otherUsersView') as $otherUser): ?>
                                        <?php if ($otherUser->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUser->getId(); ?>"
                                            <?php echo $otherUser->isSelected() ? 'selected' : '' ?>><?php echo $otherUser->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('View'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('View'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var SelectItem $otherUser */
                                        foreach ($_getvar('otherUsersView') as $otherUser): ?>
                                            <?php if ($otherUser->isSelected()): ?>
                                                <span class="tag"><?php echo $otherUser->getName(); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tag-list-box">
                            <?php if (!$_getvar('isView')): ?>
                                <select id="other_users_edit"
                                        name="other_users_edit[]"
                                        multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Select Users'); ?></option>
                                    <?php /** @var SelectItem $otherUser */
                                    foreach ($_getvar('otherUsersEdit') as $otherUser): ?>
                                        <?php if ($otherUser->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUser->getId(); ?>"
                                            <?php echo $otherUser->isSelected() ? 'selected' : '' ?>><?php echo $otherUser->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('Edit'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Edit'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var SelectItem $otherUser */
                                        foreach ($_getvar('otherUsersEdit') as $otherUser): ?>
                                            <?php if ($otherUser->isSelected()): ?>
                                                <span class="tag"><?php echo $otherUser->getName(); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Groups'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <div class="tag-list-box">
                            <?php if (!$_getvar('isView')): ?>
                                <select id="other_usergroups_view"
                                        name="other_usergroups_view[]"
                                        multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Select Groups'); ?></option>
                                    <?php /** @var SelectItem $otherUserGroup */
                                    foreach ($_getvar('otherUserGroupsView') as $otherUserGroup): ?>
                                        <?php if ($otherUserGroup->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUserGroup->getId(); ?>"
                                            <?php echo $otherUserGroup->isSelected() ? 'selected' : '' ?>><?php echo $otherUserGroup->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('View'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('View'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var SelectItem $otherUserGroup */
                                        foreach ($_getvar('otherUserGroupsView') as $otherUserGroup): ?>
                                            <?php if ($otherUserGroup->isSelected()): ?>
                                                <span class="tag"><?php echo $otherUserGroup->getName(); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tag-list-box">
                            <?php if (!$_getvar('isView')): ?>
                                <select id="other_usergroups_edit"
                                        name="other_usergroups_edit[]"
                                        multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Select Groups'); ?></option>
                                    <?php /** @var SelectItem $otherUserGroup */
                                    foreach ($_getvar('otherUserGroupsEdit') as $otherUserGroup): ?>
                                        <?php if ($otherUserGroup->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUserGroup->getId(); ?>"
                                            <?php echo $otherUserGroup->isSelected() ? 'selected' : '' ?>><?php echo $otherUserGroup->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('Edit'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Edit'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var SelectItem $otherUserGroup */
                                        foreach ($_getvar('otherUserGroupsEdit') as $otherUserGroup): ?>
                                            <?php if ($otherUserGroup->isSelected()): ?>
                                                <span class="tag"><?php echo $otherUserGroup->getName(); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>

            <?php if ($_getvar('copyAction')): // Forces an update of permissions when copying from another account ?>
                <input type="hidden" name="other_users_view_update" value="1">
                <input type="hidden" name="other_users_edit_update" value="1">
                <input type="hidden" name="other_usergroups_view_update"
                       value="1">
                <input type="hidden" name="other_usergroups_edit_update"
                       value="1">
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$_getvar('isView')
            && ($_getvar('ctx_userIsAdminApp')
                || $_getvar('ctx_userIsAdminAcc')
                || ($gotData && $_getvar('ctx_userId') === $accountData->getUserId()))
        ): ?>
            <tr>
                <td class="descField"><?php echo __('Owner'); ?></td>
                <td class="valField">
                    <select id="owner_id" name="owner_id"
                            class="select-box" <?php echo $_getvar('disabled'); ?>>
                        <option value=""><?php echo __('Select User'); ?></option>
                        <?php /** @var SelectItem $user */
                        foreach ($_getvar('users') as $user): ?>
                            <option
                                    value="<?php echo $user->getId(); ?>"
                                <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <?php if (!$_getvar('isView')
            && ($_getvar('ctx_userIsAdminApp')
                || $_getvar('ctx_userIsAdminAcc')
                || ($gotData
                    && ($_getvar('ctx_userId') === $accountData->getUserId()
                        || $_getvar('ctx_userGroupId') === $accountData->getUserGroupId())))
        ): ?>
            <tr>
                <td class="descField"><?php echo __('Main Group'); ?></td>
                <td class="valField">
                    <select id="main_usergroup_id" name="main_usergroup_id"
                            class="select-box" <?php echo $_getvar('disabled'); ?>>
                        <option value=""><?php echo __('Select Group'); ?></option>
                        <?php /** @var SelectItem $userGroup */
                        foreach ($_getvar('userGroups') as $userGroup): ?>
                            <option
                                    value="<?php echo $userGroup->getId(); ?>"
                                <?php echo $userGroup->isSelected() ? 'selected' : ''; ?>><?php echo $userGroup->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($_getvar('allowPrivate') === true):
            $checked = ($gotData && $accountData->getIsPrivate()) || $_getvar('privateUserCheck');
            ?>
            <tr>
                <td class="descField"><?php echo __('Private Account'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="private_enabled">
                        <input name="private_enabled" type="checkbox"
                               id="private_enabled"
                               class="mdl-switch__input" <?php echo $checked ? 'checked' : '';
                        echo $_getvar('disabled'); ?>/>
                        <span class="mdl-switch__label"><?php echo __('Private'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($_getvar('allowPrivateGroup') === true):
            $checked = ($gotData && $accountData->getIsPrivateGroup()) || $_getvar('privateUserGroupCheck');
            ?>
            <tr>
                <td class="descField"><?php echo __('Private Account for Group'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="private_group_enabled">
                        <input name="private_group_enabled" type="checkbox"
                               id="private_group_enabled"
                               class="mdl-switch__input" <?php echo $checked ? 'checked' : '';
                        echo $_getvar('disabled'); ?>/>
                        <span class="mdl-switch__label"><?php echo __('Private for Group'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

