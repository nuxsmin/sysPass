<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></h2>

    <form method="post" name="frmUsers" id="frmUsers" onsubmit="appMgmtSave('frmUsers'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre') ?></td>
                <td class="valField">
                    <?php if (!$isView): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="name" name="name" type="text" required class="mdl-textfield__input fg-blue100"
                                   value="<?php echo $user["user_name"]; ?>" maxlength="80">
                            <label class="mdl-textfield__label"
                                   for="name"><?php echo _('Nombre de usuario completo'); ?></label>
                        </div>
                    <?php else: ?>
                        <?php echo $user['user_name']; ?>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Login'); ?></td>
                <td class="valField">
                    <?php if (!$isView): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="login" name="login" type="text" required class="mdl-textfield__input fg-blue100"
                                   value="<?php echo $user["user_login"]; ?>" maxlength="80" <?php echo $ro; ?>>
                            <label class="mdl-textfield__label"
                                   for="login"><?php echo _('Login de inicio de sesión'); ?></label>
                        </div>
                        <?php if ($ro): ?>
                            <img src="imgs/ldap.png" title="<?php echo _('Usuario de LDAP'); ?>" class="iconMini"/>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo $user['user_login']; ?>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Perfil'); ?></td>
                <td class="valField">
                    <select id="selProfile" name="profileid" class="select-box sel-chosen-profile" <?php echo $isDisabled; ?> required>
                        <option value="0"></option>
                        <?php foreach ($profiles as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($id == $user['user_profileId']) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Grupo'); ?></td>
                <td class="valField">
                    <select id="selGroup" name="groupid" class="select-box sel-chosen-usergroup" <?php echo $isDisabled; ?> required>
                        <option value="0"></option>
                        <?php foreach ($groups as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($id == $user['user_groupId']) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Email'); ?></td>
                <td class="valField">
                    <?php if (!$isView): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="email" name="email" type="email" required class="mdl-textfield__input fg-blue100"
                                   value="<?php echo $user["user_email"]; ?>" maxlength="50">
                            <label class="mdl-textfield__label"
                                   for="email"><?php echo _('Dirección de correo'); ?></label>
                        </div
                    <?php else: ?>
                        <?php echo $user['user_email']; ?>
                    <?php endif; ?>
                </td>

            </tr>

            <?php if ($user['action'] === 1 && !$isView): ?>
                <tr>
                    <td class="descField"><?php echo _('Clave'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="userpass" name="pass" type="password" required class="mdl-textfield__input passwordfield__input fg-blue100"
                                   maxlength="50" OnKeyUp="checkPassLevel(this.value)">
                            <label class="mdl-textfield__label"
                                   for="userpass"><?php echo _('Clave'); ?></label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="userpassR" name="passR" type="password" required class="mdl-textfield__input fg-blue100"
                                   maxlength="50">
                            <label class="mdl-textfield__label"
                                   for="userpassR"><?php echo _('Clave (repetir)'); ?></label>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <td class="descField"><?php echo _('Notas') ?></td>
                <td class="valField">
                    <?php if (!$isView): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input" rows="3" id="notes" name="notes"
                                  maxlength="1000" <?php echo $user['user_notes']; ?>></textarea>
                        <label class="mdl-textfield__label"
                               for="notes"><?php echo _('Notas sobre la cuenta'); ?></label>
                    </div>
                    <?php else: ?>
                        <?php echo $user['user_notes']; ?>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Opciones'); ?></td>
                <td class="valField checkbox">
                    <div id="btnUserOptions" class="btn-checks round5">
                        <?php if (\SP\Session::getUserIsAdminApp() || $isDemo): ?>
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="adminapp" title="<?php echo _('Administrador de la aplicación'); ?>">
                                <input type="checkbox" id="adminapp" class="mdl-switch__input fg-blue100"
                                       name="adminapp" <?php echo $user['checks']['user_isAdminApp'] . ' ' . $isDisabled; ?>/>
                                <span class="mdl-switch__label"><?php echo _('Admin. Aplicación'); ?></span>
                            </label>

                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="adminacc" title="<?php echo _('Administrador de cuentas'); ?>">
                                <input type="checkbox" id="adminacc" class="mdl-switch__input fg-blue100"
                                       name="adminacc" <?php echo $user['checks']['user_isAdminAcc'] . ' ' . $isDisabled; ?>/>
                                <span class="mdl-switch__label"><?php echo _('Admin. Cuentas'); ?></span>
                            </label>
                        <?php endif; ?>
                        <br>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="disabled" title="<?php echo _('Deshabilitado'); ?>">
                            <input type="checkbox" id="disabled" class="mdl-switch__input fg-blue100"
                                   name="disabled" <?php echo $user['checks']['user_isDisabled'] . ' ' . $isDisabled; ?>/>
                            <span class="mdl-switch__label"><?php echo _('Deshabilitado'); ?></span>
                        </label>

                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="changepass" title="<?php echo _('Forzar cambio de clave'); ?>">
                            <input type="checkbox" id="changepass" class="mdl-switch__input fg-blue100"
                                   name="changepass" <?php echo $user['checks']['user_isChangePass'] . ' ' . $isDisabled; ?>/>
                            <span class="mdl-switch__label"><?php echo _('Cambio de Clave'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <?php if ($isView): ?>
                <tr>
                    <td class="descField"><?php echo _('Entradas'); ?></td>
                    <td class="valField"> <?php echo $user['user_count']; ?></td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Último Acceso'); ?></td>
                    <td class="valField"> <?php echo $user['user_lastLogin']; ?></td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Última Modificación'); ?></td>
                    <td class="valField"> <?php echo $user['user_lastUpdate']; ?></td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Fecha Clave Maestra'); ?></td>
                    <td class="valField"> <?php echo $user['user_lastUpdateMPass']; ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php if (!$isView): ?>
            <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
            <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
            <input type="hidden" name="isLdap" value="<?php echo $user['user_isLdap']; ?>"/>
            <input type="hidden" name="itemId" value="<?php echo $user['user_id']; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
            <input type="hidden" name="isAjax" value="1">
        <?php endif; ?>
    </form>

    <?php if (!$isView): ?>
        <div id="resCheck"><span id="resFancyAccion"></span></div>
        <div class="action-in-box">
            <button form="frmUsers" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                    title="<?php echo _('Guardar'); ?>">
                <i class="material-icons">save</i>
            </button>
        </div>
    <?php endif; ?>
</div>
<script>
    $(function () {
        chosenDetect();

        passwordDetect();
    });
</script>