<?php
/**
 * @var callable                                $_getvar
 * @var \SP\Core\UI\ThemeIcons                  $icons
 * @var \SP\DataModel\AccountExtData            $accountData
 * @var \SP\Services\Account\AccountAcl         $accountAcl
 * @var \SP\Config\ConfigData                   $configData
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
                            <?php if (!$_getvar('isView')): ?>
                                <select id="other_users_view" name="other_users_view[]" multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUser */
                                    foreach ($_getvar('otherUsersView') as $otherUser): ?>
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
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUser */
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
                                <select id="other_users_edit" name="other_users_edit[]" multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUser */
                                    foreach ($_getvar('otherUsersEdit') as $otherUser): ?>
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
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUser */
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
                <td class="descField"><?php echo __('Grupos'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <div class="tag-list-box">
                            <?php if (!$_getvar('isView')): ?>
                                <select id="other_usergroups_view" name="other_usergroups_view[]" multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUserGroup */
                                    foreach ($_getvar('otherUserGroupsView') as $otherUserGroup): ?>
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
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUserGroup */
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
                                <select id="other_usergroups_edit" name="other_usergroups_edit[]" multiple="multiple"
                                        class="select-box-tags" <?php echo $_getvar('disabled'); ?>>
                                    <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUserGroup */
                                    foreach ($_getvar('otherUserGroupsEdit') as $otherUserGroup): ?>
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
                                        <?php /** @var \SP\Mvc\View\Components\SelectItem $otherUserGroup */
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
        <?php endif; ?>

        <?php if ($_getvar('editAction')
            && ($_getvar('ctx_userIsAdminApp')
                || $_getvar('ctx_userIsAdminAcc')
                || $_getvar('ctx_userId') === $accountData->getUserId())): ?>
            <tr>
                <td class="descField"><?php echo __('Propietario'); ?></td>
                <td class="valField">
                    <select id="owner_id" name="owner_id" class="select-box" <?php echo $_getvar('disabled'); ?>>
                        <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $user */
                        foreach ($_getvar('users') as $user): ?>
                            <option
                                    value="<?php echo $user->getId(); ?>"
                                <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($_getvar('ctx_userIsAdminApp') || $_getvar('ctx_userIsAdminAcc')): ?>
            <tr>
                <td class="descField"><?php echo __('Grupo Principal'); ?></td>
                <td class="valField">
                    <select id="main_usergroup_id" name="main_usergroup_id"
                            class="select-box" <?php echo $_getvar('disabled'); ?>>
                        <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $userGroup */
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
            $accountPrivate = $_getvar('accountPrivate');

            $privateUserCheck = (($gotData && $accountData->getIsPrivate())
                || (is_object($accountPrivate) && $accountPrivate->isPrivateUser())) ? 'checked' : '';
            ?>
            <tr>
                <td class="descField"><?php echo __('Cuenta Privada'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_enabled">
                        <input name="private_enabled" type="checkbox" id="private_enabled"
                               class="mdl-switch__input" <?php echo $_getvar('privateUserCheck');
                        echo $_getvar('disabled'); ?>/>
                        <span class="mdl-switch__label"><?php echo __('Privada'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($_getvar('allowPrivateGroup') === true):
            $accountPrivate = $_getvar('accountPrivate');

            $privateUserGroupCheck = (($gotData && $accountData->getIsPrivateGroup())
                || (is_object($accountPrivate) && $accountPrivate->isPrivateGroup())) ? 'checked' : '';
            ?>
            <tr>
                <td class="descField"><?php echo __('Cuenta Privada Grupo'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_group_enabled">
                        <input name="private_group_enabled" type="checkbox" id="private_group_enabled"
                               class="mdl-switch__input" <?php echo $_getvar('privateUserGroupCheck');
                        echo $_getvar('disabled'); ?>/>
                        <span class="mdl-switch__label"><?php echo __('Privada Grupo'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

