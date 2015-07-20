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
        <ul>
            <li>
                <img src="imgs/backup.png" title="<?php echo _('Realizar Backup'); ?>" class="inputImg"
                     OnClick="configMgmt('backup');"/>
            </li>
            <li>
                <img id="help_backup_button" src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImg"/>

                <div id="help_backup" class="help-box" title="<?php echo _('Ayuda'); ?>">
                    <p class="help-text"><?php echo _('La copia de seguridad permite guardar y descargar tanto la base de datos de sysPass como los archivos de la aplicación y su configuración.'); ?></p>

                    <p class="help-text"><?php echo _('Con este método es posible guardar todos los datos de sysPass en otro lugar o utilizarlos para hacer la aplicación portable.'); ?></p>
                </div>
            </li>
        </ul>
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
                    <input type="password" name="exportPwd" maxlength="255">
                    <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave de Exportación (repetir)'); ?>
                </td>
                <td class="valField">
                    <input type="password" name="exportPwdR" maxlength="255">
                </td>
            </tr>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $backup_tabIndex; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="actionId" value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_EXPORT; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <ul>
            <li>
                <img src="imgs/backup.png" title="<?php echo _('Exportar Cuentas'); ?>" class="inputImg"
                     OnClick="configMgmt('export');"/>
            </li>
            <li>
                <img id="help_export_button" src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImg"/>

                <div id="help_export" class="help-box" title="<?php echo _('Ayuda'); ?>">
                    <p class="help-text"><?php echo _('La exportación de cuentas permite guardar las cuentas y sus datos en formato XML para posteriormente poder ser importados en otras instancias de sysPass.'); ?></p>

                    <p class="help-text"><?php echo _('Los elementos exportados son cuentas, clientes y categorías.'); ?></p>

                    <p class="help-text"><?php echo _('Las claves de las cuentas son exportadas de forma encriptada para mayor seguridad.'); ?></p>

                    <p class="help-text"><?php echo _('Es posible establecer una clave de exportación para guardar todos los datos encriptados en el archivo XML.'); ?></p>
                </div>
            </li>
        </ul>
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