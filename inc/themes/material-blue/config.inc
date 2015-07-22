<!-- Start Tab - Config -->
<div id="tabs-<?php echo $config_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Sitio'); ?>
    </div>

    <form method="post" name="frmConfig" id="frmConfig">

        <table id="tblSite" class="data tblConfig round">

            <tr>
                <td class="descField"><?php echo _('Idioma'); ?></td>
                <td class="valField">
                    <select name="sitelang" id="sel-sitelang" size="1" class="sel-chosen-ns">
                        <?php foreach ($langsAvailable as $langName => $langValue): ?>
                            <option
                                value='<?php echo $langValue; ?>' <?php echo ($currentLang == $langValue) ? "SELECTED" : ""; ?>><?php echo $langName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Tema Visual'); ?></td>
                <td class="valField">
                    <select name="sitetheme" id="sel-sitetheme" size="1" class="sel-chosen-ns">
                        <?php foreach ($themesAvailable as $themeDir => $themeName): ?>
                            <option
                                value='<?php echo $themeDir; ?>' <?php echo ($currentTheme == $themeDir) ? "SELECTED" : ""; ?>><?php echo $themeName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Timeout de sesión (s)'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="session_timeout" name="session_timeout" type="number" step="300"
                               pattern="[0-9]{2,4}" class="mdl-textfield__input fg-blue100" maxlength="5"
                               value="<?php echo $sessionTimeout; ?>" <?php echo $isDisabled; ?> required/>
                        <label class="mdl-textfield__label"
                               for="session_timeout"><?php echo _('Timeout de sesión (s)'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar log de eventos'); ?>
                    <div id="help-eventlog" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-eventlog">
                        <p>
                            <?php echo _('Guarda las acciones realizadas en la aplicación.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="log_enabled">
                        <input type="checkbox" id="log_enabled" class="mdl-switch__input fg-blue100" name="log_enabled"
                            <?php echo $chkLog, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar depuración'); ?>
                    <div id="help-debug" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-debug">
                        <p>
                            <?php echo _('Muestra información relativa a la configuración de la aplicación y rendimiento.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="debug">
                        <input type="checkbox" id="debug" class="mdl-switch__input fg-blue100" name="debug"
                            <?php echo $chkDebug, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Modo mantenimiento'); ?>
                    <div id="help-maintenance" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-maintenance">
                        <p>
                            <?php echo _('En este modo no se puede acceder a la aplicación. Para deshabilitarlo es necesario modificar el archivo de configuración.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="maintenance">
                        <input type="checkbox" id="maintenance" class="mdl-switch__input fg-blue100" name="maintenance"
                            <?php echo $chkMaintenance, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Comprobar actualizaciones'); ?>
                    <div id="help-updates" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-updates">
                        <p>
                            <?php echo _('Comprobar actualizaciones de la aplicación (sólo para los usuarios administradores).'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="updates">
                        <input type="checkbox" id="updates" class="mdl-switch__input fg-blue100" name="updates"
                            <?php echo $chkUpdates, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre de cuenta como enlace'); ?>
                    <div id="help-account_link" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-account_link">
                        <p>
                            <?php echo _('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link">
                        <input type="checkbox" id="account_link" class="mdl-switch__input fg-blue100"
                               name="account_link"
                            <?php echo $chkAccountLink, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Gestión de archivos'); ?>
                    <div id="help-files" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-files">
                        <p>
                            <?php echo _('Habilita la subida/descarga de archivos para las cuentas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="files_enabled">
                        <input type="checkbox" id="files_enabled" class="mdl-switch__input fg-blue100"
                               name="files_enabled"
                            <?php echo $chkFiles, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Búsquedas globales'); ?>
                    <div id="help-globalsearch" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-globalsearch">
                        <p>
                            <?php echo _('Permite que todos los usuarios puedan realizar búsquedas en todas las cuentas, pero no pueden ver el contenido de las que no tienen permisos.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="globalsearch">
                        <input type="checkbox" id="globalsearch" class="mdl-switch__input fg-blue100"
                               name="globalsearch"
                            <?php echo $chkGlobalSearch, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Extensiones de archivos permitidas'); ?>
                    <div id="help-allowed_exts" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-allowed_exts">
                        <p>
                            <?php echo _('Extensiones permitidas para la subida de archivos.'); ?>
                        </p>

                        <p>
                            <?php echo _('Se permite un máximo de 4 caracteres.'); ?>
                        </p>

                        <p>
                            <?php echo _('Escribir extensión y pulsar intro para añadir. Es necesario guardar la configuración.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="files_allowed_exts" id="allowed_exts"
                           value="<?php echo $filesAllowedExts; ?>"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Tamaño máximo de archivo'); ?>
                    <div id="help-files_allowed_size" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-files_allowed_size">
                        <p>
                            <?php echo _('Establece el tamaño máximo para subir archivos.'); ?>
                        </p>

                        <p>
                            <?php echo _('El máximo absuluto es de 16MB.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="files_allowed_size" name="files_allowed_size" type="number" step="512"
                               pattern="[0-9]{1,5}" class="mdl-textfield__input fg-blue100" maxlength="5"
                               value="<?php echo $filesAllowedSize; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label"
                               for="files_allowed_size"><?php echo _('Tamaño máximo de archivo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Resultados por página'); ?>
                    <div id="help-account_count" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-account_count">
                        <p>
                            <?php echo _('Número de resultados por página a mostrar, al realizar una búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="account_count" name="account_count" type="number" step="6"
                               pattern="[0-9]{1,5}" class="mdl-textfield__input fg-blue100" maxlength="5"
                               value="<?php echo $accountCount; ?>" <?php echo $isDisabled; ?> required/>
                        <label class="mdl-textfield__label"
                               for="account_count"><?php echo _('Tamaño máximo de archivo'); ?></label>
                    </div>
                </td>
            </tr>
        </table>

        <!--WIKI-->
        <div id="title" class="midroundup titleNormal">
            <?php echo _('Wiki'); ?>
        </div>

        <table id="tblWiki" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar enlaces Wiki'); ?>
                    <div id="help-wiki_enabled" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_enabled">
                        <p>
                            <?php echo _('Habilita la opción de añadir un enlace a Wiki externa para los resultados de la búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wiki_enabled">
                        <input type="checkbox" id="wiki_enabled" class="mdl-switch__input fg-blue100"
                               name="wiki_enabled"
                            <?php echo $chkWiki, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de búsqueda Wiki'); ?>
                    <div id="help-wiki_searchurl" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_searchurl">
                        <p>
                            <?php echo _('URL que utiliza la wiki para realizar una búsqueda de una página.'); ?>
                        </p>

                        <p>
                            <?php echo _('Como parámetro se utiliza el nombre del cliente.'); ?>
                        </p>

                        <p>
                            <?php _('Ejemplo:'); ?>
                        </p>

                        <p>
                            https://wiki.example.org/search.php?phrase=
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="wiki_searchurl" name="wiki_searchurl" type="text"
                               class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $wikiSearchUrl; ?>">
                        <label class="mdl-textfield__label"
                               for="wiki_searchurl"><?php echo _('URL de búsqueda Wiki'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de página en Wiki'); ?>
                    <div id="help-wiki_pageurl" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_pageurl">
                        <p>
                            <?php echo _('URL que utiliza la wiki para acceder a los detalles de una página.'); ?>
                        </p>

                        <p>
                            <?php echo _('El nombre de la cuenta se utiliza como parámetro de la variable de búsqueda de la Wiki.'); ?>
                        </p>

                        <p>
                            <?php echo _('Ejemplo:'); ?>
                        </p>

                        <p>
                            https://wiki.example.org/show.php?name=
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="wiki_pageurl" name="wiki_pageurl" type="text" class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $wikiPageUrl; ?>">
                        <label class="mdl-textfield__label"
                               for="wiki_pageurl"><?php echo _('URL de página en Wiki'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Prefijo para nombre de cuenta'); ?>
                    <div id="help-wikifilter" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wikifilter">
                        <p>
                            <?php echo _('Prefijo para determinar qué cuentas tienen un enlace a una página de la Wiki.'); ?>
                        </p>

                        <p>
                            <?php echo _('Ejemplos:') . ' serv- | srv- | vm-'; ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="wiki_filter" id="wikifilter" value="<?php echo $wikiFilter; ?>"/>
                </td>
            </tr>
        </table>

        <!--LDAP-->

        <div id="title" class="midroundup titleNormal">
            <?php echo _('LDAP'); ?>
        </div>

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
                                   class="mdl-textfield__input fg-blue100"
                                   maxlength="128" value="<?php echo $ldapBindPass; ?>" <?php echo $isDisabled; ?>/>
                            <label class="mdl-textfield__label" for="ldap_bindpass"><?php echo _('Clave'); ?></label>
                        </div>
                        <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                        <i id="passGen" class="material-icons" title="<?php echo _('Generar clave aleatoria'); ?>">settings</i>
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
                                <option
                                    value="<?php echo $id; ?>" <?php echo ($id === $ldapDefaultGroup) ? 'selected' : ''; ?>><?php echo $name; ?></option>
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
                                <option
                                    value="<?php echo $id; ?>" <?php echo ($id === $ldapDefaultProfile) ? 'selected' : ''; ?>><?php echo $name; ?></option>
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

        <!--MAIL-->
        <div id="title" class="midroundup titleNormal">
            <?php echo _('Correo'); ?>
        </div>

        <table id="tblMail" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar notificaciones de correo'); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_enabled">
                        <input type="checkbox" id="mail_enabled" class="mdl-switch__input fg-blue100"
                               name="mail_enabled"
                            <?php echo $chkMail, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_server" name="mail_server" type="text" class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $mailServer; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_server"><?php echo _('Servidor'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Puerto'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_port" name="mail_port" type="number" pattern="[0-9]{1,5}"
                               class="mdl-textfield__input fg-blue100" maxlength="5"
                               value="<?php echo $mailPort; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_port"><?php echo _('Puerto'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar Autentificación'); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_authenabled">
                        <input type="checkbox" id="mail_authenabled" class="mdl-switch__input fg-blue100"
                               name="mail_authenabled" <?php echo $chkMailAuth, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_user" name="mail_user" type="email" class="mdl-textfield__input fg-blue100"
                               maxlength="50" value="<?php echo $mailUser; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_user"><?php echo _('Usuario'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_pass" name="mail_pass" type="password" class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $mailPass; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_pass"><?php echo _('Clave'); ?></label>
                    </div>
                    <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                    <i id="passGen" class="material-icons"
                       title="<?php echo _('Generar clave aleatoria'); ?>">settings</i>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Seguridad'); ?>
                </td>
                <td class="valField">
                    <select name="mail_security" id="sel-mailsecurity" size="1" class="sel-chosen-ns">

                        <?php echo '<option>' . _('Deshabilitada') . '</option>'; ?>
                        <?php foreach ($mailSecurity as $security): ?>
                            <?php echo "<option ", ($currentMailSecurity == $security) ? "SELECTED" : "", ">$security</option>"; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Dirección de correo de envío'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_from" name="mail_from" type="email" class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $mailFrom; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label"
                               for="mail_from"><?php echo _('Dirección de correo de envío'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar peticiones por correo'); ?>

                    <div id="help-mail_requestsenabled" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-mail_requestsenabled">
                        <p>
                            <?php echo _('Habilita que los usuarios puedan solicitar modificaciones o acceso a las cuentas sin permisos.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_requestsenabled">
                        <input type="checkbox" id="mail_requestsenabled" class="mdl-switch__input fg-blue100"
                               name="mail_requestsenabled" <?php echo $chkMailRequests, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
        </table>

        <?php if ($isDemoMode): ?>
            <input type="hidden" name="log_enabled" value="1"/>
            <input type="hidden" name="files_enabled" value="1"/>
            <input type="hidden" name="wiki_enabled" value="1"/>
        <?php endif; ?>
        <input type="hidden" name="activeTab" value="<?php echo $config_tabIndex; ?>"/>
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_GENERAL; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="configMgmt('saveconfig');" title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div> <!-- End Tab - Config -->

<script>
    $(function () {
        $('#allowed_exts').tagsInput({
            'width': '350px',
            'defaultText': '<?php echo _('Añadir extensión'); ?>',
            'defaultRemoveText': '<?php echo _('Eliminar extensión'); ?>',
            'removeWithBackspace': false,
            'tagsToUpper': true,
            'maxChars': 4,
            'onAddTag': function () {
                // Fix scrolling to bottom
                var $tagsbox = $(this).next();
                $tagsbox.animate({scrollTop: $tagsbox.height()});

                if (!$tagsbox.find('div').hasClass('icon')) {
                    $tagsbox.find('div:last').prev().append('<div class="icon material-icons fg-orange80" title="' + LANG[5] + '">warning</div>');
                }
            },
            'onRemoveTag': function () {
                var $tagsbox = $(this).next();

                if (!$tagsbox.find('div').hasClass('icon')) {
                    $tagsbox.find('div:last').prev().append('<div class="icon material-icons fg-orange80" title="' + LANG[5] + '">warning</div>');
                }
            }
        });
        $('#wikifilter').tagsInput({
            'width': '350px',
            'height': '50px',
            'defaultText': '<?php echo _('Añadir filtro'); ?>',
            'defaultRemoveText': '<?php echo _('Eliminar filtro'); ?>',
            'removeWithBackspace': false,
            onAddTag: function () {
                // Fix scrolling to bottom
                var $tagsbox = $(this).next();
                $tagsbox.animate({scrollTop: $tagsbox.height()});

                if (!$tagsbox.find('div').hasClass('icon')) {
                    $tagsbox.find('div:last').prev().append('<div class="icon material-icons fg-orange80" title="' + LANG[5] + '">warning</div>');
                }
            },
            onRemoveTag: function () {
                var $tagsbox = $(this).next();

                if (!$tagsbox.find('div').hasClass('icon')) {
                    $tagsbox.find('div:last').prev().append('<div class="icon material-icons fg-orange80" title="' + LANG[5] + '">warning</div>');
                }
            },
            onChange: function () {
                var $tagsbox = $(this).next();
                var last_width = $tagsbox.find("span:last").width() + 10;
                $tagsbox.find(".tag:last").css('width', last_width);
            }
        });
    })
</script>