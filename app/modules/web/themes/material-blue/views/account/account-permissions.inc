<?php
/**
 * @var \SP\Services\Account\AccountAcl         $accountAcl
 * @var \SP\DataModel\AccountExtData            $accountData
 * @var \SP\Core\UI\ThemeIcons                  $icons
 * @var \SP\DataModel\ItemPreset\AccountPrivate $accountPrivate
 */
?>

<div id="data-accesses">
    <table class="data">
        <?php if ($accountAcl->isShowPermission()): ?>
            <tr>
                <td class="descField"><?php echo __('Usuarios'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <div class="tag-list-box">
                            <?php if (!$isView): ?>
                                <select id="other_users_view" name="other_users_view[]" multiple="multiple"
                                        class="select-box-tags"
                                        data-current-item-id="<?php echo $ctx_userId; ?>" <?php echo $disabled; ?>>
                                    <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUsersView */
                                    foreach ($otherUsersView as $otherUser): ?>
                                        <?php if ($otherUser->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUser->getId(); ?>"
                                            <?php echo $otherUser->isSelected() ? 'selected' : '' ?>><?php echo $otherUser->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('Ver'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Ver'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUsersView */
                                        foreach ($otherUsersView as $otherUser): ?>
                                            <?php if ($otherUser->isSelected()): ?>
                                                <span class="tag"><?php echo $otherUser->getName(); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tag-list-box">
                            <?php if (!$isView): ?>
                                <select id="other_users_edit" name="other_users_edit[]" multiple="multiple"
                                        class="select-box-tags"
                                        data-current-item-id="<?php echo $ctx_userId; ?>" <?php echo $disabled; ?>>
                                    <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUsersEdit */
                                    foreach ($otherUsersEdit as $otherUser): ?>
                                        <?php if ($otherUser->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUser->getId(); ?>"
                                            <?php echo $otherUser->isSelected() ? 'selected' : '' ?>><?php echo $otherUser->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('Editar'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Editar'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUsersEdit */
                                        foreach ($otherUsersEdit as $otherUser): ?>
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
                <td class="descField"><?php echo __('Grupos'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <div class="tag-list-box">
                            <?php if (!$isView): ?>
                                <select id="other_usergroups_view" name="other_usergroups_view[]" multiple="multiple"
                                        class="select-box-tags"
                                        data-current-item-id="<?php echo $ctx_userGroupId; ?>" <?php echo $disabled; ?>>
                                    <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUserGroupsView */
                                    foreach ($otherUserGroupsView as $otherUserGroup): ?>
                                        <?php if ($otherUserGroup->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUserGroup->getId(); ?>"
                                            <?php echo $otherUserGroup->isSelected() ? 'selected' : '' ?>><?php echo $otherUserGroup->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('Ver'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Ver'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUserGroupsView */
                                        foreach ($otherUserGroupsView as $otherGroup): ?>
                                            <?php if ($otherGroup->isSelected()): ?>
                                                <span class="tag"><?php echo $otherGroup->getName(); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tag-list-box">
                            <?php if (!$isView): ?>
                                <select id="other_usergroups_edit" name="other_usergroups_edit[]" multiple="multiple"
                                        class="select-box-tags"
                                        data-current-item-id="<?php echo $ctx_userGroupId; ?>" <?php echo $disabled; ?>>
                                    <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUserGroupsEdit */
                                    foreach ($otherUserGroupsEdit as $otherUserGroup): ?>
                                        <?php if ($otherUserGroup->isSkip()): continue; endif; ?>
                                        <option
                                                value="<?php echo $otherUserGroup->getId(); ?>"
                                            <?php echo $otherUserGroup->isSelected() ? 'selected' : '' ?>><?php echo $otherUserGroup->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="material-icons select-icon"
                                   title="<?php echo __('Editar'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                            <?php else: ?>
                                <div class="tag-list-body">
                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Editar'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                                    <div class="tags-list-items">
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUserGroupsEdit */
                                        foreach ($otherUserGroupsEdit as $otherGroup): ?>
                                            <?php if ($otherGroup->isSelected()): ?>
                                                <span class="tag"><?php echo $otherGroup->getName(); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endif; ?>

        <?php if (isset($editAction) && ($ctx_userIsAdminApp || $ctx_userIsAdminAcc || $ctx_userId === $accountData->getUserId())): ?>
            <tr>
                <td class="descField"><?php echo __('Creador'); ?></td>
                <td class="valField">
                    <select id="owner_id" name="owner_id" class="select-box" <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $users */
                        foreach ($users as $user): ?>
                            <option
                                    value="<?php echo $user->getId(); ?>"
                                <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($ctx_userIsAdminApp || $ctx_userIsAdminAcc): ?>
            <tr>
                <td class="descField"><?php echo __('Grupo Principal'); ?></td>
                <td class="valField">
                    <select id="main_usergroup_id" name="main_usergroup_id" class="select-box" <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userGroups */
                        foreach ($userGroups as $userGroup): ?>
                            <option
                                    value="<?php echo $userGroup->getId(); ?>"
                                <?php echo $userGroup->isSelected() ? 'selected' : ''; ?>><?php echo $userGroup->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($allowPrivate === true):
            $privateUserCheck = (($gotData && $accountData->getIsPrivate())
                || (isset($accountPrivate) && $accountPrivate->isPrivateUser())) ? 'checked' : '';
            ?>
            <tr>
                <td class="descField"><?php echo __('Cuenta Privada'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_enabled">
                        <input name="private_enabled" type="checkbox" id="private_enabled"
                               class="mdl-switch__input" <?php echo $privateUserCheck;
                        echo $disabled; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Privada'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($allowPrivateGroup === true):
            $privateUserGroupCheck = (($gotData && $accountData->getIsPrivateGroup())
                || (isset($accountPrivate) && $accountPrivate->isPrivateGroup())) ? 'checked' : '';
            ?>
            <tr>
                <td class="descField"><?php echo __('Cuenta Privada Grupo'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_group_enabled">
                        <input name="private_group_enabled" type="checkbox" id="private_group_enabled"
                               class="mdl-switch__input" <?php echo $privateUserGroupCheck;
                        echo $disabled; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Privada Grupo'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

