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
                    <input type="text" name="session_timeout" id="session_timeout"
                           value="<?php echo $sessionTimeout; ?>" maxlength="4" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar log de eventos'); ?>
                    <?php echo SP\Common::printHelpButton("config", 20); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="log_enabled">
                        <input type="checkbox" id="log_enabled" class="mdl-switch__input" name="log_enabled" <?php echo $chkLog, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar depuración'); ?>
                    <?php echo SP\Common::printHelpButton("config", 19); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="debug">
                        <input type="checkbox" id="debug" class="mdl-switch__input" name="debug" <?php echo $chkDebug, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Modo mantenimiento'); ?>
                    <?php echo SP\Common::printHelpButton("config", 18); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="maintenance">
                        <input type="checkbox" id="maintenance" class="mdl-switch__input" name="maintenance" <?php echo $chkMaintenance, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Comprobar actualizaciones'); ?>
                    <?php echo SP\Common::printHelpButton("config", 21); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="updates">
                        <input type="checkbox" id="updates" class="mdl-switch__input" name="updates" <?php echo $chkUpdates, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre de cuenta como enlace'); ?>
                    <?php echo SP\Common::printHelpButton("config", 3); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link">
                        <input type="checkbox" id="account_link" class="mdl-switch__input" name="account_link" <?php echo $chkAccountLink, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Gestión de archivos'); ?>
                    <?php echo SP\Common::printHelpButton("config", 5); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="files_enabled">
                        <input type="checkbox" id="files_enabled" class="mdl-switch__input" name="files_enabled" <?php echo $chkFiles, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Búsquedas globales'); ?>
                    <?php echo SP\Common::printHelpButton("config", 24); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="globalsearch">
                        <input type="checkbox" id="globalsearch" class="mdl-switch__input" name="globalsearch" <?php echo $chkGlobalSearch, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Extensiones de archivos permitidas'); ?>
                    <?php echo SP\Common::printHelpButton("config", 22); ?>
                </td>
                <td class="valField">
                    <input type="text" name="files_allowed_exts" id="allowed_exts"
                           value="<?php echo $filesAllowedExts; ?>"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Tamaño máximo de archivo'); ?>
                    <?php echo SP\Common::printHelpButton("config", 6); ?>
                </td>
                <td class="valField">
                    <input type="text" name="files_allowed_size" id="files_allowed_size"
                           value="<?php echo $filesAllowedSize; ?>" maxlength="5" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Resultados por página'); ?>
                    <?php echo SP\Common::printHelpButton("config", 4); ?>
                </td>
                <td class="valField">
                    <input type="text" name="account_count" id="sel-account_count" value="<?php echo $accountCount; ?>"
                           maxlength="5" <?php echo $isDisabled; ?> />
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
                    <?php echo SP\Common::printHelpButton("config", 7); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wiki_enabled">
                        <input type="checkbox" id="wiki_enabled" class="mdl-switch__input" name="wiki_enabled" <?php echo $chkWiki, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de búsqueda Wiki'); ?>
                    <?php echo SP\Common::printHelpButton("config", 8); ?>
                </td>
                <td class="valField">
                    <input type="text" name="wiki_searchurl" class="txtLong" value="<?php echo $wikiSearchUrl; ?>"
                           maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de página en Wiki'); ?>
                    <?php echo SP\Common::printHelpButton("config", 9); ?>
                </td>
                <td class="valField">
                    <input type="text" name="wiki_pageurl" class="txtLong" value="<?php echo $wikiPageUrl; ?>"
                           maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Prefijo para nombre de cuenta'); ?>
                    <?php echo SP\Common::printHelpButton("config", 10); ?>
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
                        <?php echo SP\Common::printHelpButton("config", 11); ?>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_enabled">
                            <input type="checkbox" id="ldap_enabled" class="mdl-switch__input" name="ldap_enabled" <?php echo $chkLdap, ' ', $isDisabled; ?>/>
                            <span class="mdl-switch__label"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Servidor'); ?>
                        <?php echo SP\Common::printHelpButton("config", 15); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_server" value="<?php echo $ldapServer; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Usuario de conexión'); ?>
                        <?php echo SP\Common::printHelpButton("config", 12); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_binduser" value="<?php echo $ldapBindUser; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Clave de conexión'); ?>
                        <?php echo SP\Common::printHelpButton("config", 17); ?>
                    </td>
                    <td class="valField">
                        <input type="password" name="ldap_bindpass" value="<?php echo $ldapBindPass; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                        <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                             alt="<?php echo _('Mostrar Clave'); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Base de búsqueda'); ?>
                        <?php echo SP\Common::printHelpButton("config", 13); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_base" class="txtLong" value="<?php echo $ldapBase; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo'); ?>
                        <?php echo SP\Common::printHelpButton("config", 14); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_group" class="txtLong" value="<?php echo $ldapGroup; ?>"
                               maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo por Defecto'); ?>
                        <?php echo SP\Common::printHelpButton("config", 27); ?>
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
                        <?php echo SP\Common::printHelpButton("config", 28); ?>
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
                        <?php echo SP\Common::printHelpButton("config", 26); ?>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_ads">
                            <input type="checkbox" id="ldap_ads" class="mdl-switch__input" name="ldap_ads" <?php echo $chkLdapADS, ' ', $isDisabled; ?>/>
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
                        <input type="checkbox" id="mail_enabled" class="mdl-switch__input" name="mail_enabled" <?php echo $chkMail, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_server" size="20" value="<?php echo $mailServer; ?>" maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Puerto'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_port" size="20" value="<?php echo $mailPort; ?>" maxlength="5"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar Autentificación'); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_authenabled">
                        <input type="checkbox" id="mail_authenabled" class="mdl-switch__input" name="mail_authenabled" <?php echo $chkMailAuth, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_user" size="20" value="<?php echo $mailUser; ?>" maxlength="50"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <input type="password" name="mail_pass" size="20" value="<?php echo $mailPass; ?>" maxlength="50"/>
                    <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
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
                    <input type="text" name="mail_from" size="20" value="<?php echo $mailFrom; ?>" maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar peticiones por correo'); ?>
                </td>
                <td class="valField">
                    <label for="mailrequestsenabled"><?php echo ($chkMailRequests) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="mail_requestsenabled" id="mailrequestsenabled"
                           class="checkbox" <?php echo $chkMailRequests, ' ', $isDisabled; ?> />
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
        <ul>
            <li
                ><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg"
                      OnClick="configMgmt('saveconfig');"/>
            </li>
        </ul>
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

                if ($tagsbox.find('img:last').attr('alt') != 'warning') {
                    $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '" />');
                }
            },
            'onRemoveTag': function () {
                var $tagsbox = $(this).next();

                if ($tagsbox.find('img:last').attr('alt') != 'warning') {
                    $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '"/>');
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

                if ($tagsbox.find('img:last').attr('alt') != 'warning') {
                    $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '"/>');
                }
            },
            onRemoveTag: function () {
                var $tagsbox = $(this).next();

                if ($tagsbox.find('img:last').attr('alt') != 'warning') {
                    $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '"/>');
                }
            },
            onChange: function () {
                var $tagsbox = $(this).next();
                var last_width = $tagsbox.find("span:last").width() + 10;
                $tagsbox.find(".tag:last").css('width', last_width);
            }
        });
        $("#session_timeout").spinner({step: 300, min: 300, numberFormat: "n"});
        $("#files_allowed_size").spinner({step: 1024, min: 1024, numberFormat: "n"});
        $("#sel-account_count").spinner({step: 6, max: 100, min: 6, numberFormat: "n"});
    })
</script>