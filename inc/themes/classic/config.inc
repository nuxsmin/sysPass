<!-- Start Tab - Config -->
<div id="tabs-<?php echo $config_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Sitio'); ?>
    </div>

    <form method="post" name="frmConfig" id="frmConfig" onsubmit="sysPassUtil.Common.configMgmt('config', this); return false;">
        <table id="tblSite" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Idioma'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-sitelang" style="display: none;">
                        <p>
                            <?php echo _('Establece el idioma global de la aplicación. Es posible establecer un idioma por usuario en las preferencias.'); ?>
                        </p>
                        <p>
                            <?php echo _('Para mejorar/añadir las traducciones, puede colaborar en http:://translate.syspass.org'); ?>
                        </p>
                    </div>
                </td>
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
                <td class="descField">
                    <?php echo _('Tema Visual'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-sitetheme" style="display: none;">
                        <p>
                            <?php echo _('Establece el tema visual global de la aplicación. Es posible establecer un tema visual por usuario en las preferencias.'); ?>
                        </p>
                    </div>
                </td>
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
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-eventlog" style="display: none;">
                        <p>
                            <?php echo _('Guarda las acciones realizadas en la aplicación.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="logenabled"><?php echo ($chkLog) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="log_enabled" id="logenabled"
                           class="checkbox" <?php echo $chkLog, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar depuración'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-debug" style="display:none;">
                        <p>
                            <?php echo _('Muestra información relativa a la configuración de la aplicación y rendimiento.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="debug"><?php echo ($chkDebug) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="debug" id="debug"
                           class="checkbox" <?php echo $chkDebug, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Modo mantenimiento'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-maintenance" style="display:none;">
                        <p>
                            <?php echo _('En este modo no se puede acceder a la aplicación. Para deshabilitarlo es necesario modificar el archivo de configuración.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="maintenance"><?php echo ($chkMaintenance) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="maintenance" id="maintenance"
                           class="checkbox" <?php echo $chkMaintenance, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Comprobar actualizaciones'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-updates" style="display: none;">
                        <p>
                            <?php echo _('Comprobar actualizaciones de la aplicación (sólo para los usuarios administradores).'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="updates"><?php echo ($chkUpdates) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="updates" id="updates"
                           class="checkbox" <?php echo $chkUpdates; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Comprobar notificaciones'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-notices" style="display: none;">
                        <p>
                            <?php echo _('Comprobar si existen notificaciones de seguridad o avisos de sysPass (sólo para los usuarios administradores).'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="notices"><?php echo ($chkNotices) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="notices" id="notices"
                           class="checkbox" <?php echo $chkNotices; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre de cuenta como enlace'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-account_link" style="display: none;">
                        <p>
                            <?php echo _('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="account_link"><?php echo ($chkAccountLink) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="account_link" id="account_link"
                           class="checkbox" <?php echo $chkAccountLink; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Gestión de archivos'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-files" style="display:none;">
                        <p>
                            <?php echo _('Habilita la subida/descarga de archivos para las cuentas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="filesenabled"><?php echo ($chkFiles) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="files_enabled" id="filesenabled"
                           class="checkbox" <?php echo $chkFiles, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Búsquedas globales'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-globalsearch" style="display:none;">
                        <p>
                            <?php echo _('Permite que todos los usuarios puedan realizar búsquedas en todas las cuentas, pero no pueden ver el contenido de las que no tienen permisos.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="globalsearch"><?php echo ($chkGlobalSearch) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="globalsearch" id="globalsearch"
                           class="checkbox" <?php echo $chkGlobalSearch; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Extensiones de archivos permitidas'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-allowed_exts" style="display:none;">
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
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-files_allowed_size" style="display:none;">
                        <p>
                            <?php echo _('Establece el tamaño máximo para subir archivos.'); ?>
                        </p>

                        <p>
                            <?php echo _('El máximo absuluto es de 16MB.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="files_allowed_size" id="files_allowed_size"
                           value="<?php echo $filesAllowedSize; ?>" maxlength="5" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Resultados por página'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-account_count" style="display:none;">
                        <p>
                            <?php echo _('Número de resultados por página a mostrar, al realizar una búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="account_count" id="sel-account_count" value="<?php echo $accountCount; ?>"
                           maxlength="5" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Resultados en Tarjetas'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" style="display:none;">
                        <p>
                            <?php echo _('Muestra los resultados de búsqueda de cuentas en formato tarjeta.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="resultsascards"><?php echo ($chkResultsAsCards) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="resultsascards" id="resultsascards"
                           class="checkbox" <?php echo $chkResultsAsCards, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Imagen para mostrar clave'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" style="display:none;">
                        <p>
                            <?php echo _('Generar una imagen con el texto de la clave de la cuenta.'); ?>
                        </p>
                        <p>
                            <?php echo _('Util para entornos donde copiar la clave supone un riesgo de seguridad.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="account_passtoimage"><?php echo ($chkAccountPassToImage) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="account_passtoimage" id="account_passtoimage"
                           class="checkbox" <?php echo $chkAccountPassToImage; ?> />
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
               value="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_GENERAL; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button type="submit" form="frmConfig" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Core\Init::$WEBURI; ?>imgs/check.png" alt="save" />
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
        $("#session_timeout").spinner({step: 300, min: 300, numberFormat: "n"});
        $("#files_allowed_size").spinner({step: 1024, min: 1024, numberFormat: "n"});
        $("#sel-account_count").spinner({step: 6, max: 100, min: 6, numberFormat: "n"});
    })
</script>