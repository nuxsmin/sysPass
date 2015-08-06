<!-- Start Tab - Config -->
<div id="tabs-<?php echo $config_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Sitio'); ?>
    </div>

    <form method="post" name="frmConfig" id="frmConfig" onsubmit="configMgmt('config', this); return false;">

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
                    <?php echo _('Comprobar notificaciones'); ?>
                    <div id="help-notices" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-notices">
                        <p>
                            <?php echo _('Comprobar si existen notificaciones de seguridad o avisos de sysPass (sólo para los usuarios administradores).'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="notices">
                        <input type="checkbox" id="notices" class="mdl-switch__input fg-blue100" name="notices"
                            <?php echo $chkNotices, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
        </table>

        <div id="title" class="midroundup titleNormal">
            <?php echo _('Cuentas'); ?>
        </div>

        <table id="tblAccounts" class="data tblConfig round">
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
                               for="account_count"><?php echo _('Número de resultados por página'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Imagen para mostrar clave'); ?>
                    <div id="help-account_passtoimage" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-account_passtoimage">
                        <p>
                            <?php echo _('Generar una imagen con el texto de la clave de la cuenta.'); ?>
                        </p>
                        <p>
                            <?php echo _('Util para entornos donde copiar la clave supone un riesgo de seguridad.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_passtoimage">
                        <input type="checkbox" id="account_passtoimage" class="mdl-switch__input fg-blue100"
                               name="account_passtoimage"
                            <?php echo $chkAccountPassToImage, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
        </table>

        <div id="title" class="midroundup titleNormal">
            <?php echo _('Archivos'); ?>
        </div>

        <table id="tblFiles" class="data tblConfig round">
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
                               for="files_allowed_size"><?php echo _('Tamaño máximo de archivo en bytes'); ?></label>
                    </div>
                </td>
            </tr>
        </table>

        <div id="title" class="midroundup titleNormal">
            <?php echo _('Proxy'); ?>
        </div>

        <table id="tblProxy" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Usar Proxy'); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="proxy_enabled">
                        <input type="checkbox" id="proxy_enabled" class="mdl-switch__input fg-blue100"
                               name="proxy_enabled"
                            <?php echo $chkProxy, ' ', $isDisabled; ?>/>
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
                        <input id="proxy_server" name="proxy_server" type="text"
                               class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $proxyServer; ?>">
                        <label class="mdl-textfield__label"
                               for="proxy_server"><?php echo _('Servidor proxy'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Puerto'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="proxy_port" name="proxy_port" type="number"
                               class="mdl-textfield__input fg-blue100" pattern="[0-9]{1,5}"
                               maxlength="128" value="<?php echo $proxyPort; ?>">
                        <label class="mdl-textfield__label"
                               for="proxy_port"><?php echo _('Puerto del servidor proxy'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="proxy_user" name="proxy_user" type="text"
                               class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $proxyUser; ?>">
                        <label class="mdl-textfield__label"
                               for="proxy_user"><?php echo _('Usuario del servidor proxy'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="proxy_pass" name="proxy_pass" type="password"
                               class="mdl-textfield__input passwordfield__input fg-blue100"
                               maxlength="128" value="<?php echo $proxyPass; ?>">
                        <label class="mdl-textfield__label"
                               for="proxy_pass"><?php echo _('Clave del servidor proxy'); ?></label>
                    </div>
                </td>
            </tr>

        </table>


        <?php if ($isDemoMode): ?>
            <input type="hidden" name="log_enabled" value="1"/>
            <input type="hidden" name="files_enabled" value="1"/>
        <?php endif; ?>

        <input type="hidden" name="activeTab" value="<?php echo $config_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $config_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button form="frmConfig"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                title="<?php echo _('Guardar'); ?>">
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
    })
</script>