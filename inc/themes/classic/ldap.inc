<!-- Start Tab - LDAP -->
<div id="tabs-<?php echo $ldap_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('LDAP'); ?>
    </div>

    <form method="post" name="frmLdap" id="frmLdap" onsubmit="sysPassUtil.Common.configMgmt('config', this); return false;">
        <table id="tblLdap" class="data tblConfig round">
            <?php if ($ldapIsAvailable || $isDemoMode): ?>
                <tr>
                    <td class="descField">
                        <?php echo _('Habilitar LDAP'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_enabled" style="display:none;">
                            <p>
                                <?php echo _('Habilita de autentificación mediante servidor LDAP.'); ?>
                            </p>

                            <p>
                                <?php echo _('Este método utilizará MySQL en caso de fallo.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label for="ldapenabled"><?php echo ($chkLdap) ? _('SI') : _('NO'); ?></label>
                        <input type="checkbox" name="ldap_enabled" id="ldapenabled"
                               class="checkbox" <?php echo $chkLdap, ' ', $isDisabled; ?> />
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Servidor'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_server" style="display:none;">
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
                        <input type="text" name="ldap_server" value="<?php echo $ldapServer; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Usuario de conexión'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_binduser" style="display:none;">
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
                        <input type="text" name="ldap_binduser" value="<?php echo $ldapBindUser; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Clave de conexión'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    </td>
                    <td class="valField">
                        <input type="password" id="ldap_bindpass" name="ldap_bindpass" value="<?php echo $ldapBindPass; ?>"
                               class="passwordfield__input-show"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Base de búsqueda'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_base" style="display:none;">
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
                        <input type="text" name="ldap_base" class="txtLong" value="<?php echo $ldapBase; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_group" style="display:none;">
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
                        <input type="text" name="ldap_group" class="txtLong" value="<?php echo $ldapGroup; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo por Defecto'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_defaultgroup" style="display:none;">
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
                                <option
                                    value="<?php echo $id; ?>" <?php echo ($id === $ldapDefaultGroup) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Perfil por Defecto'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_defaultprofile" style="display:none;">
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
                                <option
                                    value="<?php echo $id; ?>" <?php echo ($id === $ldapDefaultProfile) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Active Directory'); ?>
                        <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                        <div class="tooltip" for="help-ldap_ads" style="display:none;">
                            <p>
                                <?php echo _('Habilita el modo de conexión con LDAP de Active Directory.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label for="ldap_ads"><?php echo ($chkLdapADS) ? _('SI') : _('NO'); ?></label>
                        <input type="checkbox" name="ldap_ads" id="ldap_ads"
                               class="checkbox" <?php echo $chkLdapADS, ' ', $isDisabled; ?> />
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Comprobar'); ?>
                    </td>
                    <td class="valField">
                        <img src="imgs/refresh.png" class="inputImg"
                             title="<?php echo _('Comprobar conexión con LDAP'); ?>" onclick="sysPassUtil.Common.checkLdapConn('frmLdap');"/>
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
        <button type="submit" form="frmLdap" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save" />
        </button>
    </div>
</div> <!-- End Tab - LDAP -->