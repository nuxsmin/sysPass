<!-- Start Tab - LDAP -->
<div id="tabs-<?php echo $ldap_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('LDAP'); ?>
    </div>

    <form method="post" name="frmLdap" id="frmLdap" onsubmit="configMgmt('config', this); return false;">
        <table id="tblLdap" class="data tblConfig round">
            <?php if ($ldapIsAvailable || $isDemoMode): ?>
                <tr>
                    <td class="descField">
                        <?php echo _('Habilitar LDAP'); ?>
                        <div id="help-ldap_enabled" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_enabled">
                            <p>
                                <?php echo _('Habilita de autentificación mediante servidor LDAP.'); ?>
                            </p>

                            <p>
                                <?php echo _('Este método utilizará MySQL en caso de fallo.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_enabled">
                            <input type="checkbox" id="ldap_enabled" class="mdl-switch__input fg-blue100"
                                   name="ldap_enabled"
                                <?php echo $chkLdap, ' ', $isDisabled; ?>/>
                            <span class="mdl-switch__label"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Servidor'); ?>
                        <div id="help-ldap_server" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_server">
                            <p>
                                <?php echo _('Nombre o dirección IP del servidor de LDAP.'); ?>
                            </p>

                            <p>
                                <?php echo _('Ejemplos:'); ?>
                            </p>

                            <ul>
                                <li>ldap.example.org</li>
                                <li>ldap://ldap.example.org</li>
                                <li>ldaps://ldap.example.org</li>
                            </ul>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="ldap_server" name="ldap_server" type="text"
                                   class="mdl-textfield__input fg-blue100"
                                   maxlength="128" value="<?php echo $ldapServer; ?>" <?php echo $isDisabled; ?>/>
                            <label class="mdl-textfield__label" for="ldap_server"><?php echo _('Servidor'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Usuario de conexión'); ?>
                        <div id="help-ldap_binduser" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_binduser">
                            <p>
                                <?php echo _('Usuario para conectar con el servicio de LDAP.'); ?>
                            </p>

                            <p>
                                <?php echo _('Ejemplos:'); ?>
                            </p>

                            <ul>
                                <li>cn=syspass,ou=Users,dc=syspass,o=org</li>
                                <li>syspass</li>
                            </ul>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="ldap_binduser" name="ldap_binduser" type="text"
                                   class="mdl-textfield__input fg-blue100"
                                   maxlength="128" value="<?php echo $ldapBindUser; ?>" <?php echo $isDisabled; ?>/>
                            <label class="mdl-textfield__label" for="ldap_binduser"><?php echo _('Servidor'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Clave de conexión'); ?>
                        <div id="help-ldap_bindpass" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_bindpass">
                            <p>
                                <?php echo _('Clave del usuario de conexión a LDAP.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="ldap_bindpass" name="ldap_bindpass" type="password"
                                   class="mdl-textfield__input passwordfield__input fg-blue100"
                                   maxlength="128" value="<?php echo $ldapBindPass; ?>" <?php echo $isDisabled; ?>/>
                            <label class="mdl-textfield__label" for="ldap_bindpass"><?php echo _('Clave'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Base de búsqueda'); ?>
                        <div id="help-ldap_base" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_base">
                            <p>
                                <?php echo _('Base en la que realizar la búsqueda de usuarios de LDAP.'); ?>
                            </p>

                            <p>
                                <?php echo _('Ejemplos:'); ?>
                            </p>

                            <ul>
                                <li>cn=Users,dc=example,dc=org</li>
                                <li>ou=AdminUsers,dc=example,o=org</li>
                            </ul>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="ldap_base" name="ldap_base" type="text" class="mdl-textfield__input fg-blue100"
                                   maxlength="128" value="<?php echo $ldapBase; ?>" <?php echo $isDisabled; ?>/>
                            <label class="mdl-textfield__label"
                                   for="ldap_base"><?php echo _('Base de búsqueda'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo'); ?>
                        <div id="help-ldap_group" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_group">
                            <p>
                                <?php echo _('Grupo de LDAP al que debe de pertenecer el usuario para permitir el acceso.'); ?>
                            </p>

                            <p>
                                <?php echo _('Este grupo debe de estar ubicado en la base de búsquedas de LDAP.'); ?>
                            </p>

                            <p>
                                <?php echo _('Ejemplos:'); ?>
                            </p>

                            <ul>
                                <li>cn=GRP_SPUSERS,cn=Users,dc=example,dc=org</li>
                                <li>GRP_SPUSERS</li>
                            </ul>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="ldap_group" name="ldap_group" type="text" class="mdl-textfield__input fg-blue100"
                                   maxlength="128" value="<?php echo $ldapGroup; ?>" <?php echo $isDisabled; ?>/>
                            <label class="mdl-textfield__label" for="ldap_group"><?php echo _('Grupo'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo por Defecto'); ?>
                        <div id="help-ldap_defaultgroup" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_defaultgroup">
                            <p>
                                <?php echo _('Define el grupo de usuarios por defecto para los nuevos usuarios de LDAP.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <select id="ldap_defaultgroup" name="ldap_defaultgroup"
                                class="select-box sel-chosen-usergroup" <?php echo $isDisabled; ?> required>
                            <option value="0"></option>
                            <?php foreach ($groups as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($id === $ldapDefaultGroup) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Perfil por Defecto'); ?>
                        <div id="help-ldap_defaultprofile" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_defaultprofile">
                            <p>
                                <?php echo _('Define el perfil de usuarios por defecto para los nuevos usuarios de LDAP.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <select id="ldap_defaultprofile" name="ldap_defaultprofile"
                                class="select-box sel-chosen-profile" <?php echo $isDisabled; ?> required>
                            <option value="0"></option>
                            <?php foreach ($profiles as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($id === $ldapDefaultProfile) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Active Directory'); ?>
                        <div id="help-ldap_ads" class="icon material-icons fg-blue80">help_outline</div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_ads">
                            <p>
                                <?php echo _('Habilita el modo de conexión con LDAP de Active Directory.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_ads">
                            <input type="checkbox" id="ldap_ads" class="mdl-switch__input fg-blue100" name="ldap_ads"
                                <?php echo $chkLdapADS, ' ', $isDisabled; ?>/>
                            <span class="mdl-switch__label"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Comprobar'); ?>
                    </td>
                    <td class="valField">
                        <img src="imgs/refresh.png" class="inputImg"
                             title="<?php echo _('Comprobar conexión con LDAP'); ?>" onclick="checkLdapConn();"/>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td class="option-disabled">
                        <?php echo _('Módulo no disponible'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <?php if ($isDemoMode): ?>
            <input type="hidden" name="ldap_enabled" value="0"/>
        <?php endif; ?>

        <input type="hidden" name="activeTab" value="<?php echo $ldap_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $ldap_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button form="frmLdap"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div> <!-- End Tab - LDAP -->