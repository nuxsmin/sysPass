<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData $configData
 */
?>

<div id="title" class="titleNormal">
    <i class="material-icons">verified_user</i>
    <?php echo __('Autentificación'); ?>
</div>

<table id="tblAuth" class="data tblConfig">
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="config-auth-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="authbasic_enabled">
                            <input type="checkbox" id="authbasic_enabled"
                                   class="mdl-switch__input"
                                   name="authbasic_enabled" <?php echo $configData->isAuthBasicEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Usar Auth Basic'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Usar Auth Basic para autorizar los accesos a la aplicación.'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="authbasicautologin_enabled">
                            <input type="checkbox" id="authbasicautologin_enabled"
                                   class="mdl-switch__input"
                                   name="authbasicautologin_enabled" <?php echo $configData->isAuthBasicAutoLoginEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Usar Auth Basic SSO'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Usar auto-login con Auth Basic.'); ?></span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Dominio'); ?>
            <div id="help-domain"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-domain">
                <p>
                    <?php echo __('El dominio será añadido al nombre de usuario de sysPass para comprobarlo con el de Auth Basic.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="authbasic_domain" name="authbasic_domain" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128" value="<?php echo $configData->getAuthBasicDomain(); ?>">
                <label class="mdl-textfield__label"
                       for="authbasic_domain"><?php echo __('Nombre de dominio'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Grupo por Defecto'); ?>
            <div id="help-sso_defaultgroup"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-sso_defaultgroup">
                <p>
                    <?php echo __('Define el grupo de usuarios por defecto para los nuevos usuarios de SSO.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php echo __('Grupo por Defecto'); ?></div>

            <select id="sso_defaultgroup" name="sso_defaultgroup"
                    class="select-box sel-chosen-usergroup">
                <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userGroups */
                foreach ($userGroups as $userGroup): ?>
                    <option
                            value="<?php echo $userGroup->getId(); ?>" <?php echo $userGroup->getId() === $configData->getSsoDefaultGroup() ? 'selected' : ''; ?>><?php echo $userGroup->getName(); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Perfil por Defecto'); ?>
            <div id="help-sso_defaultprofile"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-sso_defaultprofile">
                <p>
                    <?php echo __('Define el perfil de usuarios por defecto para los nuevos usuarios de SSO.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php echo __('Perfil por Defecto'); ?></div>

            <select id="sso_defaultprofile" name="sso_defaultprofile"
                    class="select-box sel-chosen-profile">
                <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userProfiles */
                foreach ($userProfiles as $userProfile): ?>
                    <option
                            value="<?php echo $userProfile->getId(); ?>" <?php echo ($userProfile->getId() === $configData->getSsoDefaultProfile()) ? 'selected' : ''; ?>><?php echo $userProfile->getName(); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
</table>