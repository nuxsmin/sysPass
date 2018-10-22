<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 * @var callable               $_getvar
 * @var \SP\Mvc\View\Template  $this
 */
?>
<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"><?php echo __('Usuarios'); ?></td>
        <td class="valField">
            <div class="account-permissions">
                <div class="tag-list-box">
                    <select id="users_view" name="users_view[]" multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $user */
                        foreach ($_getvar('usersView') as $user): ?>
                            <?php if ($user->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $user->getId(); ?>"
                                <?php echo $user->isSelected() ? 'selected' : '' ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php echo __('Ver'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                </div>

                <div class="tag-list-box">
                    <select id="users_edit" name="users_edit[]" multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $user */
                        foreach ($_getvar('usersEdit') as $user): ?>
                            <?php if ($user->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $user->getId(); ?>"
                                <?php echo $user->isSelected() ? 'selected' : '' ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php echo __('Editar'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td class="descField"><?php echo __('Grupos'); ?></td>
        <td class="valField">
            <div class="account-permissions">
                <div class="tag-list-box">
                    <select id="user_groups_view" name="user_groups_view[]" multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $userGroup */
                        foreach ($_getvar('userGroupsView') as $userGroup): ?>
                            <?php if ($userGroup->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $userGroup->getId(); ?>"
                                <?php echo $userGroup->isSelected() ? 'selected' : '' ?>><?php echo $userGroup->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php echo __('Ver'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                </div>

                <div class="tag-list-box">
                    <select id="user_groups_edit" name="user_groups_edit[]" multiple="multiple"
                            class="select-box-tags">
                        <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $userGroup */
                        foreach ($_getvar('userGroupsEdit') as $userGroup): ?>
                            <?php if ($userGroup->isSkip()): continue; endif; ?>
                            <option
                                    value="<?php echo $userGroup->getId(); ?>"
                                <?php echo $userGroup->isSelected() ? 'selected' : '' ?>><?php echo $userGroup->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <i class="material-icons select-icon"
                       title="<?php echo __('Editar'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                </div>
            </div>
        </td>
    </tr>
    </tbody>
</table>