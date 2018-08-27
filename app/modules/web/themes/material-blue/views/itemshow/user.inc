<?php
/** @var $icons \SP\Core\UI\ThemeIcons */
/** @var $user \SP\DataModel\UserData */
?>

<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
        <div class="mdl-tabs__tab-bar">
            <a href="#user-panel" class="mdl-tabs__tab is-active"><?php echo __('Usuario'); ?></a>
            <a href="#options-panel" class="mdl-tabs__tab"><?php echo __('Opciones'); ?></a>

            <?php if ($isView): ?>
                <a href="#details-panel" class="mdl-tabs__tab"><?php echo __('Detalles'); ?></a>
            <?php endif; ?>
        </div>

        <form method="post" name="frmUsers" id="frmUsers" class="form-action"
              data-onsubmit="appMgmt/save"
              data-action-route="<?php echo isset($route) ? $route : ''; ?>"
              data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
              data-hash="">

            <div class="mdl-tabs__panel is-active" id="user-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td class="descField"><?php echo __('Nombre') ?></td>
                        <td class="valField">

                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="name" name="name" type="text" required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       value="<?php echo $user->getName(); ?>"
                                       maxlength="80" <?php echo $readonly; ?>>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Nombre de usuario completo'); ?></label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Login'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="login" name="login" type="text" required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       value="<?php echo $user->getLogin(); ?>"
                                       maxlength="80" <?php echo $user->isLdap() ? 'readonly' : $readonly; ?>>
                                <label class="mdl-textfield__label"
                                       for="login"><?php echo __('Login de inicio de sesión'); ?></label>
                            </div>
                            <?php if ($user->isLdap()): ?>
                                <i class="material-icons <?php echo $icons->getIconLdapUser()->getClass(); ?>"
                                   title="<?php echo $icons->getIconLdapUser()->getTitle(); ?>"><?php echo $icons->getIconLdapUser()->getIcon(); ?></i>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ($isUseSSO): ?>
                        <tr>
                            <td class="descField"><?php echo __('Login SSO'); ?></td>
                            <td class="valField">
                                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                    <input id="login_sso" name="login_sso" type="text"
                                           class="mdl-textfield__input mdl-color-text--indigo-400"
                                           value="<?php echo $user->getSsoLogin(); ?>"
                                           maxlength="100" <?php echo $readonly; ?>>
                                    <label class="mdl-textfield__label"
                                           for="login_sso"><?php echo __('Login de inicio de sesión con SSO'); ?></label>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="descField"><?php echo __('Email'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="email" name="email" type="email" required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       value="<?php echo $user->getEmail(); ?>" maxlength="50" <?php echo $readonly; ?>>
                                <label class="mdl-textfield__label"
                                       for="email"><?php echo __('Dirección de correo'); ?></label>
                            </div
                        </td>

                    </tr>

                    <?php if (isset($createAction)): ?>
                        <tr>
                            <td class="descField"><?php echo __('Clave'); ?></td>
                            <td class="valField">
                                <div class="form-control">
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                        <input id="password" name="password" type="password" required
                                               class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                                               maxlength="50">
                                        <label class="mdl-textfield__label"
                                               for="password"><?php echo __('Clave'); ?></label>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Clave (repetir)'); ?></td>
                            <td class="valField">
                                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                    <input id="password_repeat" name="password_repeat" type="password" required
                                           class="mdl-textfield__input mdl-color-text--indigo-400"
                                           maxlength="50">
                                    <label class="mdl-textfield__label"
                                           for="password_repeat"><?php echo __('Clave (repetir)'); ?></label>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="descField"><?php echo __('Perfil'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Perfil'); ?></div>

                            <select id="userprofile_id" name="userprofile_id"
                                    class="select-box" required <?php echo $disabled; ?>>
                                <option value=""><?php echo __('Seleccionar Perfil'); ?></option>
                                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $profiles */
                                foreach ($profiles as $profile): ?>
                                    <option
                                            value="<?php echo $profile->getId(); ?>" <?php echo ($profile->getId() === $user->getUserProfileId()) ? 'selected' : ''; ?>><?php echo $profile->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Grupo'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Grupo'); ?></div>

                            <select id="usergroup_id" name="usergroup_id"
                                    class="select-box" required <?php echo $disabled; ?>>
                                <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $groups */
                                foreach ($groups as $group): ?>
                                    <option
                                            value="<?php echo $group->getId(); ?>" <?php echo ($group->getId() === $user->getUserGroupId()) ? 'selected' : ''; ?>><?php echo $group->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Notas') ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input" rows="3" id="notes" name="notes"
                                  maxlength="1000" <?php echo $readonly; ?>><?php echo $user->getNotes(); ?></textarea>
                                <label class="mdl-textfield__label"
                                       for="notes"><?php echo __('Notas'); ?></label>
                            </div>
                        </td>
                    </tr>

                    <?php if ($customFields): ?>
                        <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="mdl-tabs__panel" id="options-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td>
                            <ul class="user-list-action mdl-list">
                                <?php if ($userIsAdminApp || $isDemo): ?>
                                    <li class="mdl-list__item mdl-list__item--two-line">
                                        <div class="mdl-switch__box">
                                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                                   for="adminapp_enabled">
                                                <input type="checkbox" id="adminapp_enabled"
                                                       class="mdl-switch__input"
                                                       name="adminapp_enabled" <?php echo $user->isAdminApp() ? 'checked' : ' '; ?> <?php echo $disabled; ?>/>
                                            </label>
                                        </div>

                                        <span class="mdl-list__item-primary-content">
                                            <span><?php echo __('Admin Aplicación'); ?></span>
                                            <span class="mdl-list__item-sub-title">
                                                <?php echo __('Administrador de la aplicación'); ?>
                                            </span>
                                        </span>
                                    </li>

                                    <li class="mdl-list__item mdl-list__item--two-line">
                                        <div class="mdl-switch__box">
                                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                                   for="adminacc_enabled">
                                                <input type="checkbox" id="adminacc_enabled"
                                                       class="mdl-switch__input"
                                                       name="adminacc_enabled" <?php echo $user->isAdminAcc() ? 'checked' : ' '; ?> <?php echo $disabled; ?>/>
                                            </label>
                                        </div>

                                        <span class="mdl-list__item-primary-content">
                                            <span><?php echo __('Admin Cuentas'); ?></span>
                                            <span class="mdl-list__item-sub-title">
                                                <?php echo __('Administrador de cuentas'); ?>
                                            </span>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($mailEnabled): ?>
                                    <li class="mdl-list__item mdl-list__item--two-line">
                                        <div class="mdl-switch__box">
                                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                                   for="changepass_enabled">
                                                <input type="checkbox" id="changepass_enabled"
                                                       class="mdl-switch__input"
                                                       name="changepass_enabled" <?php echo $user->isChangePass() ? 'checked' : ' '; ?> <?php echo $disabled; ?>/>
                                            </label>
                                        </div>

                                        <span class="mdl-list__item-primary-content">
                                            <span><?php echo __('Cambio de Clave'); ?></span>
                                            <span class="mdl-list__item-sub-title">
                                                <?php echo __('Forzar cambio de clave'); ?>
                                            </span>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <li class="mdl-list__item mdl-list__item--two-line">
                                    <div class="mdl-switch__box">
                                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                               for="disabled">
                                            <input type="checkbox" id="disabled"
                                                   class="mdl-switch__input"
                                                   name="disabled" <?php echo $user->isDisabled() ? 'checked' : ' '; ?> <?php echo $disabled; ?>/>
                                        </label>
                                    </div>

                                    <span class="mdl-list__item-primary-content">
                                        <span><?php echo __('Deshabilitado'); ?></span>
                                        <span class="mdl-list__item-sub-title"></span>
                                    </span>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <?php if ($isView): ?>
                <div class="mdl-tabs__panel" id="details-panel">
                    <table class="popup-data">
                        <tbody>
                        <tr>
                            <td class="descField"><?php echo __('Entradas'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Entradas'); ?></div>

                                <?php echo $user->getLoginCount(); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Último Acceso'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Último Acceso'); ?></div>

                                <?php echo $user->getLastLogin(); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Última Modificación'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Última Modificación'); ?></div>

                                <?php echo $user->getLastUpdate(); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Fecha Clave Maestra'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Fecha Clave Maestra'); ?></div>

                                <?php echo gmdate('Y-m-d H:i:s', $user->getLastUpdateMPass()); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Usado en'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Usado en'); ?></div>
                                <div class="list-wrap">
                                    <ul class="mdl-list">
                                        <?php foreach ($usage as $item): ?>
                                            <li class="mdl-list__item mdl-list__item" title="<?php echo $item->ref; ?>">
                                                <span class="mdl-list__item-primary-content">
                                                <i class="material-icons mdl-list__item-icon"><?php echo $item->icon; ?></i>
                                                    <?php printf('%s: %s', $item->ref, $item->name ?: $item->id); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!$isView): ?>
                <input type="hidden" name="isLdap" value="<?php echo $user->isLdap(); ?>"/>
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
                <input type="hidden" name="isAjax" value="1">
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$isView): ?>
        <div class="action-in-box">
            <button form="frmUsers"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>
