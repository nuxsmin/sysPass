<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></h2>

    <form method="post" name="frmUsers" id="frmUsers" onsubmit="appMgmtSave('frmUsers'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre') ?></td>
                <td class="valField">
                    <?php if (!$isView): ?>
                        <input type="text" id="usrname" name="name"
                               title="<?php echo _('Nombre de usuario completo'); ?>" class="txtuser" maxlength="80"
                               value="<?php echo $User['user_name']; ?>"/>
                    <?php else: ?>
                        <?php echo $User['user_name']; ?>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Login'); ?></td>
                <td class="valField">
                    <?php if (!$isView): ?>
                        <input type="text" id="usrlogin" name="login"
                               title="<?php echo _('Login de inicio de sesión'); ?>" class="txtlogin" maxlength="30"
                               value="<?php echo $User['user_login'] ?>" <?php echo $ro; ?> />
                        <?php if ($ro): ?>
                            <img src="imgs/ldap.png" title="<?php echo _('Usuario de LDAP'); ?>" class="iconMini"/>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo $User['user_login']; ?>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Perfil'); ?></td>
                <td class="valField">
                    <select id="selProfile" name="profileid" class="select-box sel-chosen-profile" <?php echo $isDisabled; ?> required>
                        <option value="0"></option>
                        <?php foreach ($profiles as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($id == $User['user_profileId']) ? 'selected' : ''; ?>><?php echo $name; ?></option>
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
                            <option value="<?php echo $id; ?>" <?php echo ($id == $User['user_groupId']) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Email'); ?></td>
                <td class="valField">
                    <?php if (!$isView): ?>
                        <input type="text" id="usremail" name="email" title="<?php echo _('Dirección de correo'); ?>"
                               class="txtemail" maxlength="50" value="<?php echo $User['user_email']; ?>"/>
                    <?php else: ?>
                        <?php echo $User['user_email']; ?>
                    <?php endif; ?>
                </td>

            </tr>

            <?php if ($User['action'] === 1 && !$isView): ?>
                <tr>
                    <td class="descField"><?php echo _('Clave'); ?></td>
                    <td class="valField">
                        <input type="password" id="usrpass" name="pass" class="txtpass passwordfield__input" maxlength="50" OnFocus="$('#passLevel').show();
                                    $('#resFancyAccion').hide();" OnKeyUp="checkPassLevel(this.value)"/>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                    <td class="valField">
                        <input type="password" id="usrpassR" name="passR" class="txtpassv" maxlength="50"/>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <td class="descField"><?php echo _('Notas') ?></td>
                <td class="valField">
                    <textarea name="notes" id="usrnotes" rows="4"><?php echo $User['user_notes']; ?></textarea>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Opciones'); ?></td>
                <td class="valField checkbox">
                    <div id="btnUserOptions" class="btn-checks round5">
                        <?php if (\SP\Session::getUserIsAdminApp() || $isDemo): ?>
                            <label for="usradminapp"
                                   title="<?php echo _('Administrador de la aplicación'); ?>"><?php echo _('Admin. Aplicación'); ?></label>
                            <input type="checkbox" id="usradminapp"
                                   name="adminapp" <?php echo $User['checks']['user_isAdminApp'] . ' ' . $isDisabled; ?>/>
                            <label for="usradminacc"
                                   title="<?php echo _('Administrador de cuentas') ?>"><?php echo _('Admin. Cuentas') ?></label>
                            <input type="checkbox" id="usradminacc"
                                   name="adminacc" <?php echo $User['checks']['user_isAdminAcc'] . ' ' . $isDisabled; ?> />
                        <?php endif; ?>
                        <br>
                        <label for="usrdisabled"
                               title="<?php echo _('Deshabilitado'); ?>"><?php echo _('Deshabilitado'); ?></label>
                        <input type="checkbox" id="usrdisabled"
                               name="disabled" <?php echo $User['checks']['user_isDisabled'] . ' ' . $isDisabled; ?>/>
                        <label for="usrchangepass"
                               title="<?php echo _('Forzar cambio de clave'); ?>"><?php echo _('Cambio de Clave'); ?></label>
                        <input type="checkbox" id="usrchangepass"
                               name="changepass" <?php echo $User['checks']['user_isChangePass'] . ' ' . $isDisabled; ?>/>
                    </div>
                </td>
            </tr>
            <?php if ($isView): ?>
                <tr>
                    <td class="descField"><?php echo _('Entradas'); ?></td>
                    <td class="valField"> <?php echo $User['user_count']; ?></td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Último Acceso'); ?></td>
                    <td class="valField"> <?php echo $User['user_lastLogin']; ?></td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Última Modificación'); ?></td>
                    <td class="valField"> <?php echo $User['user_lastUpdate']; ?></td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Fecha Clave Maestra'); ?></td>
                    <td class="valField"> <?php echo $User['user_lastUpdateMPass']; ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php if (!$isView): ?>
            <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
            <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
            <input type="hidden" name="isLdap" value="<?php echo $User['user_isLdap']; ?>"/>
            <input type="hidden" name="itemId" value="<?php echo $User['user_id']; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
            <input type="hidden" name="isAjax" value="1">
        <?php endif; ?>
    </form>

    <?php if (!$isView): ?>
        <div id="resCheck"><span id="resFancyAccion"></span></div>
        <div class="action-in-box">
            <button type="submit" form="frmUsers" title="<?php echo _('Guardar'); ?>" class="button-action">
                <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
            </button>
        </div>
    <?php endif; ?>
</div>
<script>
    $(function() {
        $("#btnUserOptions").buttonset();

        passwordDetect();

        chosenDetect();
    });
</script>