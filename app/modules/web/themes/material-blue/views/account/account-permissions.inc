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
                        <select id="otherUsers" name="otherUsers[]" multiple="multiple">
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
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="otherUserEditEnabled">
                            <input name="otherUserEditEnabled" type="checkbox" id="otherUserEditEnabled"
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
                        <select id="otherUserGroups" name="otherUserGroups[]" multiple="multiple">
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
                               for="otherUserGroupEditEnabled">
                            <input name="otherUserGroupEditEnabled" type="checkbox"
                                   id="otherUserGroupEditEnabled"
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
                    <select id="mainUserGroupId" name="mainUserGroupId" class="select-box">
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
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="privateEnabled">
                        <input name="privateEnabled" type="checkbox" id="privateEnabled"
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
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="privateGroupEnabled">
                        <input name="privateGroupEnabled" type="checkbox" id="privateGroupEnabled"
                               class="mdl-switch__input" <?php echo ($gotData && $accountData->getIsPrivateGroup()) ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Privada Grupo'); ?></span>
                    </label>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

