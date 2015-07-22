<!-- Start Tab - Backup -->
<div id="tabs-<?php echo $backup_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Copia de Seguridad'); ?>
    </div>

    <table class="data round">
        <tr>
            <td class="descField">
                <?php echo _('Resultado'); ?>
            </td>
            <td class="valField">
                <?php echo $lastBackupTime; ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('Descargar Actual'); ?>
            </td>
            <td class="valField">
                <?php if (file_exists($backupFile['absolute']) && file_exists($backupDbFile['absolute'])): ?>
                    <a href="<?php echo $backupDbFile['relative']; ?>"
                       download="<?php echo $backupDbFile['filename']; ?>" class="download">
                        Backup BBDD
                    </a>

                    <a href="<?php echo $backupFile['relative']; ?>"
                       download="<?php echo $backupFile['filename']; ?>" class="download">
                        Backup <?php echo $siteName; ?>
                    </a>
                <?php else: echo _('No hay backups para descargar'); ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <form method="post" name="frmBackup" id="frmBackup">
        <input type="hidden" name="activeTab" value="<?php echo $backup_tabIndex; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="actionId" value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_BACKUP; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button id="help_backup_button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                title="<?php echo _('Ayuda'); ?>">
            <i class="material-icons">help_outline</i>
        </button>

        <div id="help_backup" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('La copia de seguridad permite guardar y descargar tanto la base de datos de sysPass como los archivos de la aplicación y su configuración.'); ?></p>

            <p class="help-text"><?php echo _('Con este método es posible guardar todos los datos de sysPass en otro lugar o utilizarlos para hacer la aplicación portable.'); ?></p>
        </div>

        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="configMgmt('backup');" title="<?php echo _('Realizar'); ?>">
            <i class="material-icons">play_circle_filled</i>
        </button>
    </div>

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Exportar Cuentas'); ?>
    </div>

    <form method="post" name="frmExport" id="frmExport">
        <table class="data round">
            <tr>
                <td class="descField">
                    <?php echo _('Resultado'); ?>
                </td>
                <td class="valField">
                    <?php echo $lastExportTime; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Descargar Actual'); ?>
                </td>
                <td class="valField">
                    <?php if (file_exists($exportFile['absolute'])): ?>
                        <a href="<?php echo $exportFile['relative']; ?>"
                           download="<?php echo $exportFile['filename']; ?>"
                           class="download">
                           XML <?php echo $siteName; ?></>
                       </a>
                    <?php else: echo _('No hay archivos XML para descargar'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave de Exportación'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="exportPwd" name="exportPwd" type="password" class="mdl-textfield__input fg-blue100"
                               maxlength="255"/>
                        <label class="mdl-textfield__label"
                               for="exportPwd"><?php echo _('Clave de Exportación'); ?></label>
                    </div>
                    <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                    <i id="passGen" class="material-icons" title="<?php echo _('Generar clave aleatoria'); ?>">settings</i>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave de Exportación (repetir)'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="exportPwdR" name="exportPwdR" type="password" class="mdl-textfield__input fg-blue100"
                               maxlength="255"/>
                        <label class="mdl-textfield__label"
                               for="exportPwdR"><?php echo _('Clave de Exportación (repetir)'); ?></label>
                    </div>
                </td>
            </tr>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $backup_tabIndex; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="actionId" value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_EXPORT; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button id="help_export_button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                title="<?php echo _('Ayuda'); ?>">
            <i class="material-icons">help_outline</i>
        </button>

        <div id="help_export" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('La exportación de cuentas permite guardar las cuentas y sus datos en formato XML para posteriormente poder ser importados en otras instancias de sysPass.'); ?></p>

            <p class="help-text"><?php echo _('Los elementos exportados son cuentas, clientes y categorías.'); ?></p>

            <p class="help-text"><?php echo _('Las claves de las cuentas son exportadas de forma encriptada para mayor seguridad.'); ?></p>

            <p class="help-text"><?php echo _('Es posible establecer una clave de exportación para guardar todos los datos encriptados en el archivo XML.'); ?></p>
        </div>

        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="configMgmt('export');" title="<?php echo _('Realizar'); ?>">
            <i class="material-icons">play_circle_filled</i>
        </button>

    </div>

    <script>
        $(function () {
            $("#help_backup_button").click(function () {
                $("#help_backup").dialog("open");
            });
            $("#help_export_button").click(function () {
                $("#help_export").dialog("open");
            });
        })
    </script>
</div> <!-- End Tab - Backup -->