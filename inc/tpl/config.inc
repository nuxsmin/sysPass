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
                    <select name="sitelang" id="sel-sitelang" size="1">
                        <?php foreach ( $arrLangAvailable as $langName => $langValue ): ?>
                            <option value='<?php echo $langValue; ?>' <?php echo ( $currentLang == $langValue ) ?  "SELECTED" : ""; ?>><?php echo $langName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Timeout de sesión (s)'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="session_timeout" id="session_timeout" value="<?php echo $sessionTimeout; ?>" maxlength="4" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar log de eventos'); ?>
                    <?php echo SP\Common::printHelpButton("config", 20); ?>
                </td>
                <td class="valField">
                    <label for="logenabled"><?php echo ($chkLog) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="log_enabled" id="logenabled" class="checkbox" <?php echo $chkLog,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar depuración'); ?>
                    <?php echo SP\Common::printHelpButton("config", 19); ?>
                </td>
                <td class="valField">
                    <label for="debug"><?php echo ($chkDebug) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="debug" id="debug" class="checkbox" <?php echo $chkDebug,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Modo mantenimiento'); ?>
                    <?php echo SP\Common::printHelpButton("config", 18); ?>
                </td>
                <td class="valField">
                    <label for="maintenance"><?php echo ($chkMaintenance) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="maintenance" id="maintenance"  class="checkbox" <?php echo $chkMaintenance,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Comprobar actualizaciones'); ?>
                    <?php echo SP\Common::printHelpButton("config", 21); ?>
                </td>
                <td class="valField">
                    <label for="updates"><?php echo ($chkUpdates) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="updates" id="updates" class="checkbox" <?php echo $chkUpdates,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre de cuenta como enlace'); ?>
                    <?php echo SP\Common::printHelpButton("config", 3); ?>
                </td>
                <td class="valField">
                    <label for="account_link"><?php echo ($chkAccountLink) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="account_link" id="account_link" class="checkbox" <?php echo $chkAccountLink; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Gestión de archivos'); ?>
                    <?php echo SP\Common::printHelpButton("config", 5); ?>
                </td>
                <td class="valField">
                    <label for="filesenabled"><?php echo ($chkFiles) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="files_enabled" id="filesenabled" class="checkbox" <?php echo $chkFiles,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Búsquedas globales'); ?>
                    <?php echo SP\Common::printHelpButton("config", 24); ?>
                </td>
                <td class="valField">
                    <label for="globalsearch"><?php echo ($chkGlobalSearch) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="globalsearch" id="globalsearch" class="checkbox" <?php echo $chkGlobalSearch,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Extensiones de archivos permitidas'); ?>
                    <?php echo SP\Common::printHelpButton("config", 22); ?>
                </td>
                <td class="valField">
                    <input type="text" name="files_allowed_exts" id="allowed_exts" value="<?php echo $filesAllowedExts; ?>"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Tamaño máximo de archivo'); ?>
                    <?php echo SP\Common::printHelpButton("config", 6); ?>
                </td>
                <td class="valField">
                    <input type="text" name="files_allowed_size" id="files_allowed_size" value="<?php echo $filesAllowedSize; ?>" maxlength="5" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Resultados por página'); ?>
                    <?php echo SP\Common::printHelpButton("config", 4); ?>
                </td>
                <td class="valField">
                    <input type="text" name="account_count" id="sel-account_count" value="<?php echo $accountCount; ?>" maxlength="5" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Resultados en Tarjetas'); ?>
                    <?php echo SP\Common::printHelpButton("config", 25); ?>
                </td>
                <td class="valField">
                    <label for="resultsascards"><?php echo ($chkResultsAsCards) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="resultsascards" id="resultsascards" class="checkbox" <?php echo $chkResultsAsCards,' ',$isDisabled; ?> />
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
                    <label for="wikienabled"><?php echo ($chkWiki) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="wiki_enabled" id="wikienabled" class="checkbox" <?php echo $chkWiki,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de búsqueda Wiki'); ?>
                    <?php echo SP\Common::printHelpButton("config", 8); ?>
                </td>
                <td class="valField">
                    <input type="text" name="wiki_searchurl" class="txtLong" value="<?php echo $wikiSearchUrl; ?>" maxlength="128" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de página en Wiki'); ?>
                    <?php echo SP\Common::printHelpButton("config", 9); ?>
                </td>
                <td class="valField">
                    <input type="text" name="wiki_pageurl" class="txtLong" value="<?php echo $wikiPageUrl; ?>" maxlength="128" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Prefijo para nombre de cuenta'); ?>
                    <?php echo SP\Common::printHelpButton("config", 10); ?>
                </td>
                <td class="valField">
                    <input type="text" name="wiki_filter" id="wikifilter" value="<?php echo $wikiFilter; ?>" />
                </td>
            </tr>
        </table>

        <!--LDAP-->

        <div id="title" class="midroundup titleNormal">
            <?php echo _('LDAP'); ?>
        </div>

        <table id="tblLdap" class="data tblConfig round">
            <?php if ( $ldapIsAvailable || $isDemoMode ): ?>
                <tr>
                    <td class="descField">
                        <?php echo _('Habilitar LDAP'); ?>
                        <?php echo SP\Common::printHelpButton("config", 11); ?>
                    </td>
                    <td class="valField">
                        <label for="ldapenabled"><?php echo ($chkLdap) ? 'ON' : 'OFF'; ?></label>
                        <input type="checkbox" name="ldap_enabled" id="ldapenabled" class="checkbox" <?php echo $chkLdap,' ',$isDisabled; ?> />
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Servidor'); ?>
                        <?php echo SP\Common::printHelpButton("config", 15); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_server" value="<?php echo $ldapServer; ?>" maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Usuario de conexión'); ?>
                        <?php echo SP\Common::printHelpButton("config", 12); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_binduser" value="<?php echo $ldapBindUser; ?>" maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Clave de conexión'); ?>
                        <?php echo SP\Common::printHelpButton("config", 17); ?>
                    </td>
                    <td class="valField">
                        <input type="password" name="ldap_bindpass" value="<?php echo $ldapBindPass; ?>" maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Base de búsqueda'); ?>
                        <?php echo SP\Common::printHelpButton("config", 13); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_base" class="txtLong" value="<?php echo $ldapBase; ?>" maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo'); ?>
                        <?php echo SP\Common::printHelpButton("config", 14); ?>
                    </td>
                    <td class="valField">
                        <input type="text" name="ldap_group" class="txtLong" value="<?php echo $ldapGroup; ?>" maxlength="128" <?php echo $isDisabled; ?>/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Grupo por Defecto'); ?>
                        <?php echo SP\Common::printHelpButton("config", 27); ?>
                    </td>
                    <td class="valField">
                        <?php SP\Html::printSelect($groupsSelData, $groupsSelProp); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Perfil por Defecto'); ?>
                        <?php echo SP\Common::printHelpButton("config", 28); ?>
                    </td>
                    <td class="valField">
                        <?php SP\Html::printSelect($profilesSelData, $profilesSelProp); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Active Directory'); ?>
                        <?php echo SP\Common::printHelpButton("config", 26); ?>
                    </td>
                    <td class="valField">
                        <label for="ldap_ads"><?php echo ($chkLdapADS) ? 'ON' : 'OFF'; ?></label>
                        <input type="checkbox" name="ldap_ads" id="ldap_ads" class="checkbox" <?php echo $chkLdapADS,' ',$isDisabled; ?> />
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo _('Comprobar'); ?>
                    </td>
                    <td class="valField">
                        <img src="imgs/refresh.png" class="inputImg" title="<?php echo _('Comprobar conexión con LDAP'); ?>" onclick="checkLdapConn();"/>
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
                    <label for="mailenabled"><?php echo ($chkMail) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="mail_enabled" id="mailenabled" class="checkbox" <?php echo $chkMail,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_server" size="20" value="<?php echo $mailServer; ?>" maxlength="128" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Puerto'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_port" size="20" value="<?php echo $mailPort; ?>" maxlength="5" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar Autentificación'); ?>
                </td>
                <td class="valField">
                    <label for="mailauthenabled"><?php echo ($chkMailAuth) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="mail_authenabled" id="mailauthenabled" class="checkbox" <?php echo $chkMailAuth,' ',$isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_user" size="20" value="<?php echo $mailUser; ?>" maxlength="50" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <input type="password" name="mail_pass" size="20" value="<?php echo $mailPass; ?>" maxlength="50" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Seguridad'); ?>
                </td>
                <td class="valField">
                    <select name="mail_security" id="sel-mailsecurity" size="1">

                        <?php echo '<option>'._('Deshabilitada').'</option>'; ?>
                        <?php foreach ( $mailSecurity as $security ): ?>
                            <?php echo "<option ",($currentMailSecurity == $security ) ?  "SELECTED" : "", ">$security</option>"; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Dirección de correo de envío'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_from" size="20" value="<?php echo $mailFrom; ?>" maxlength="128" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar peticiones por correo'); ?>
                </td>
                <td class="valField">
                    <label for="mailrequestsenabled"><?php echo ($chkMailRequests) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="mail_requestsenabled" id="mailrequestsenabled" class="checkbox" <?php echo $chkMailRequests,' ',$isDisabled; ?> />
                </td>
            </tr>
        </table>

        <?php if ( $isDemoMode ): ?>
            <input type="hidden" name="log_enabled" value="1" />
            <input type="hidden" name="files_enabled" value="1" />
            <input type="hidden" name="wiki_enabled" value="1" />
        <?php endif; ?>
        <input type="hidden" name="onCloseAction" value="<?php echo $config_onCloseAction; ?>" />
        <input type="hidden" name="activeTab" value="<?php echo $config_tabIndex; ?>" />
        <input type="hidden" name="actionId" value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_GENERAL; ?>" />
        <input type="hidden" name="isAjax" value="1" />
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <ul>
            <li
                ><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" OnClick="configMgmt('saveconfig');" />
            </li>
        </ul>
    </div>
</div> <!-- End Tab - Config -->

<script>
    $("#sel-sitelang,#sel-account_link,#sel-mailsecurity").chosen({disable_search : true});
    $('#frmConfig').find('.checkbox').button();
    $('#frmConfig').find('.ui-button').click(function(){
        // El cambio de clase se produce durante el evento de click
        // Si tiene la clase significa que el estado anterior era ON y ahora es OFF
        if ( $(this).hasClass('ui-state-active') ){
            $(this).children().html('OFF');
        } else{
            $(this).children().html('ON');
        }
    });
    $('#allowed_exts').tagsInput({
        'width':'350px',
        'defaultText':'<?php echo _('Añadir extensión'); ?>',
        'defaultRemoveText':'<?php echo _('Eliminar extensión'); ?>',
        'removeWithBackspace' : false,
        'tagsToUpper' : true,
        'maxChars' : 4,
        'onAddTag' : function(){
            // Fix scrolling to bottom
            var $tagsbox = $(this).next();
            $tagsbox.animate({scrollTop: $tagsbox.height()});

            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '" />');
            }
        },
        'onRemoveTag' : function(){
            var $tagsbox = $(this).next();

            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '"/>');
            }
        }
    });
    $('#wikifilter').tagsInput({
        'width':'350px',
        'height':'50px',
        'defaultText':'<?php echo _('Añadir filtro'); ?>',
        'defaultRemoveText':'<?php echo _('Eliminar filtro'); ?>',
        'removeWithBackspace' : false,
        onAddTag : function(){
            // Fix scrolling to bottom
            var $tagsbox = $(this).next();
            $tagsbox.animate({scrollTop: $tagsbox.height()});

            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '"/>');
            }
        },
        onRemoveTag : function(){
            var $tagsbox = $(this).next();

            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[5] + '"/>');
            }
        },
        onChange : function(){
            var $tagsbox = $(this).next();
            var last_width = $tagsbox.find("span:last").width() + 10;
            $tagsbox.find(".tag:last").css('width', last_width);
        }
    });
    $("#ldap_defaultgroup").chosen({
        placeholder_text_single: "<?php echo _('Seleccionar Grupo'); ?>",
        disable_search_threshold: 10,
        no_results_text: "<?php echo _('Sin resultados'); ?>"
    });
    $("#ldap_defaultprofile").chosen({
        placeholder_text_single: "<?php echo _('Seleccionar Perfil'); ?>",
        disable_search_threshold: 10,
        no_results_text: "<?php echo _('Sin resultados'); ?>"
    });
    $("#session_timeout").spinner({step: 300, min: 300, numberFormat: "n"});
    $("#files_allowed_size").spinner({step: 1024, min: 1024, numberFormat: "n"});
    $("#sel-account_count").spinner({step: 6, max: 100, min: 6, numberFormat: "n"});
</script>