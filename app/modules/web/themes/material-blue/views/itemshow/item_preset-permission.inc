<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"><?php echo __('Usuarios'); ?></td>
        <td class="valField">
            <div class="account-permissions">
                <div class="tag-list-box">
                    <div class="tag-list-header"><?php echo __('Ver'); ?></div>

                    <select id="users_view" name="users_view[]" multiple="multiple"
                            class="select-box">
                        <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $usersView */
                        foreach ($usersView as $user): ?>
                            <?php if ($user->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $user->getId(); ?>"
                                <?php echo $user->isSelected() ? 'selected' : '' ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="tag-list-box">
                    <div class="tag-list-header"><?php echo __('Editar'); ?></div>

                    <select id="users_edit" name="users_edit[]" multiple="multiple"
                            class="select-box">
                        <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $usersEdit */
                        foreach ($usersEdit as $user): ?>
                            <?php if ($user->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $user->getId(); ?>"
                                <?php echo $user->isSelected() ? 'selected' : '' ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td class="descField"><?php echo __('Grupos'); ?></td>
        <td class="valField">
            <div class="account-permissions">
                <div class="tag-list-box">
                    <div class="tag-list-header"><?php echo __('Ver'); ?></div>

                    <select id="user_groups_view" name="user_groups_view[]" multiple="multiple"
                            class="select-box">
                        <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userGroupsView */
                        foreach ($userGroupsView as $userGroup): ?>
                            <?php if ($userGroup->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $userGroup->getId(); ?>"
                                <?php echo $userGroup->isSelected() ? 'selected' : '' ?>><?php echo $userGroup->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="tag-list-box">
                    <div class="tag-list-header"><?php echo __('Editar'); ?></div>

                    <select id="user_groups_edit" name="user_groups_edit[]" multiple="multiple"
                            class="select-box">
                        <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userGroupsEdit */
                        foreach ($userGroupsEdit as $userGroup): ?>
                            <?php if ($userGroup->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $userGroup->getId(); ?>"
                                <?php echo $userGroup->isSelected() ? 'selected' : '' ?>><?php echo $userGroup->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </td>
    </tr>
    </tbody>
</table>