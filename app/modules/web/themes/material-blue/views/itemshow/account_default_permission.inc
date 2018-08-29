<?php
/** @var $icons \SP\Core\UI\ThemeIcons */
/** @var $permission \SP\DataModel\AccountDefaultPermissionData */
?>

<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmAccountDefaultGroup" id="frmAccountDefaultGroup" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo isset($route) ? $route : ''; ?>"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Usuario'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Usuario'); ?></div>

                    <select id="selUser" name="user_id" class="select-box select-box-deselect"
                            title="<?php echo __('Usuario'); ?>" <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $users */
                        foreach ($users as $user): ?>
                            <option
                                    value="<?php echo $user->getId(); ?>" <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Grupo'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Grupo'); ?></div>

                    <select id="selUserGroup" name="user_group_id" class="select-box select-box-deselect"
                            title="<?php echo __('Grupo'); ?>" <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userGroups */
                        foreach ($userGroups as $userGroup): ?>
                            <option
                                    value="<?php echo $userGroup->getId(); ?>" <?php echo $userGroup->isSelected() ? 'selected' : ''; ?>><?php echo $userGroup->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Perfil'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Perfil'); ?></div>

                    <select id="selUserProfile" name="user_profile_id" class="select-box select-box-deselect"
                            title="<?php echo __('Perfil'); ?>" <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Perfil'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userProfiles */
                        foreach ($userProfiles as $userProfile): ?>
                            <option
                                    value="<?php echo $userProfile->getId(); ?>" <?php echo $userProfile->isSelected() ? 'selected' : ''; ?>><?php echo $userProfile->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo __('Prioridad'); ?>

                    <div id="help-priority"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-priority">
                        <p>
                            <?php echo __('Prioridad de asignación en caso de coincidir con otros permisos asignados por usuario, grupo o perfil.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="priority" name="priority" type="number" step="1"
                               pattern="[0-9]{1,3}" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="3" min="0" max="128"
                               value="<?php echo $permission->getPriority(); ?>"/>
                        <label class="mdl-textfield__label"
                               for="priority"><?php echo __('Prioridad de asignación'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"></td>
                <td class="valField">
                    <ul class="mdl-list">
                        <li class="mdl-list__item mdl-list__item--two-line">
                            <div class="mdl-switch__box">
                                <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="fixed_enabled">
                                    <input type="checkbox" id="fixed_enabled"
                                           class="mdl-switch__input"
                                           name="fixed_enabled" <?php echo $permission->getFixed() ? 'checked' : ''; ?>/>
                                </label>
                            </div>

                            <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Forzado'); ?></span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Indica si los permisos serán forzados al crear o modificar la cuenta.'); ?>
                                    <br>
                                    <?php echo __('Los permisos serán añadidos a los existentes.'); ?>
                                </span>
                            </span>
                        </li>
                    </ul>
                </td>
            </tr>

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

        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmAccountDefaultGroup" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>