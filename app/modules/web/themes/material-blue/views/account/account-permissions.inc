<?php
/** @var \SP\Account\AccountAcl $accountAcl */
/** @var \SP\DataModel\AccountExtData $accountData */
/** @var $icons \SP\Core\UI\ThemeIcons */
?>

<div id="data-accesses">
    <table class="data">
        <?php if ($accountAcl->isShowPermission()): ?>
            <tr>
                <td class="descField"><?php echo __('Usuarios'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <select id="other_users" name="other_users[]" multiple="multiple">
                            <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                            <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUsers */
                            foreach ($otherUsers as $otherUser): ?>
                                <?php if ($otherUser->isSkip()): continue; endif; ?>
                                <option
                                        value="<?php echo $otherUser->getId(); ?>"
                                    <?php echo $otherUser->isSelected() ? 'selected' : '' ?>><?php echo $otherUser->getName(); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="other_user_edit_enabled">
                            <input name="other_user_edit_enabled" type="checkbox" id="other_user_edit_enabled"
                                   class="mdl-switch__input" <?php echo ($gotData && $accountData->getOtherUserEdit()) ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Hablitar edición'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Grupos'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <select id="other_usergroups" name="other_usergroups[]" multiple="multiple">
                            <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                            <?php /** @var \SP\Mvc\View\Components\SelectItem[] $otherUserGroups */
                            foreach ($otherUserGroups as $otherUserGroup): ?>
                                <?php if ($otherUserGroup->isSkip()): continue; endif; ?>
                                <option
                                        value="<?php echo $otherUserGroup->getId(); ?>"
                                    <?php echo $otherUserGroup->isSelected() ? 'selected' : '' ?>><?php echo $otherUserGroup->getName(); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="other_usergroup_edit_enabled">
                            <input name="other_usergroup_edit_enabled" type="checkbox"
                                   id="other_usergroup_edit_enabled"
                                   class="mdl-switch__input" <?php echo ($gotData && $accountData->getOtherUserGroupEdit()) ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Hablitar edición'); ?></span>
                        </label
                    </div>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($userIsAdminApp || $userIsAdminAcc): ?>
            <tr>
                <td class="descField"><?php echo __('Grupo Principal'); ?></td>
                <td class="valField">
                    <select id="main_usergroup_id" name="main_usergroup_id" class="select-box">
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

        <?php if ($allowPrivate === true): ?>
            <tr>
                <td class="descField"><?php echo __('Cuenta Privada'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_enabled">
                        <input name="private_enabled" type="checkbox" id="private_enabled"
                               class="mdl-switch__input" <?php echo ($gotData && $accountData->getIsPrivate()) ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Privada'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($allowPrivateGroup === true): ?>
            <tr>
                <td class="descField"><?php echo __('Cuenta Privada Grupo'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_group_enabled">
                        <input name="private_group_enabled" type="checkbox" id="private_group_enabled"
                               class="mdl-switch__input" <?php echo ($gotData && $accountData->getIsPrivateGroup()) ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Privada Grupo'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

