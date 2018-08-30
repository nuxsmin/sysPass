<?php
/** @var $icons \SP\Core\UI\ThemeIcons */
/** @var $preset \SP\DataModel\ItemPresetData */
/** @var $this \SP\Mvc\View\Template */
?>

<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmItemPreset" id="frmItemPreset" class="form-action"
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
                            <?php echo __('Prioridad de asignación en caso de coincidir con otros valores asignados por usuario, grupo o perfil.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="priority" name="priority" type="number" step="1"
                               pattern="[0-9]{1,3}" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="3" min="0" max="128"
                               value="<?php echo $preset->getPriority(); ?>"/>
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
                                           name="fixed_enabled" <?php echo $preset->getFixed() ? 'checked' : ''; ?>/>
                                </label>
                            </div>

                            <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Forzado'); ?></span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Indica si los valores serán forzados al crear o modificar los elementos.'); ?>
                                    <br>
                                    <?php echo __('Los valores serán añadidos a los existentes.'); ?>
                                </span>
                            </span>
                        </li>
                    </ul>
                </td>
            </tr>

            <?php if (isset($typeTemplate)) : include $this->includeTemplate($typeTemplate, 'itemshow'); endif; ?>

            </tbody>
        </table>

        <input type="hidden" name="type" value="<?php echo $preset->getType(); ?>">
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmItemPreset" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>