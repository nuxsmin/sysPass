<!-- Start Tab - Backup -->
<div id="tabs-<?php echo $backup_tabIndex; ?>">
    <table class="data round">
        <tr>
            <td class="descField">s
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
                    <a href="<?php echo $backupDbFile['relative']; ?>">Backup BBDD</a>
                    -
                    <a href="<?php echo $backupFile['relative']; ?>">Backup <?php echo $siteName; ?></a>
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
                <img id="help_backup_button" src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImg" />
                <div id="help_backup" class="help-box" title="<?php echo _('Ayuda'); ?>">
                    <p class="help-text"><?php echo _('La copia de seguridad permite guardar y descargar tanto la base de datos de sysPass como los archivos de la aplicación y su configuración.'); ?></p>
                    <p class="help-text"><?php echo _('Con este método es posible guardar todos los datos de sysPass en otro lugar o utilizarlos para hacer la aplicación portable.'); ?></p>
                </div>
            </li>
        </ul>
    </div>

    <script>
        $(".help-box").dialog({autoOpen: false, title: '<?php echo _('Ayuda'); ?>'});
        $("#help_backup_button").click(function() {
            $("#help_backup").dialog("open");
        });
    </script>
</div> <!-- End Tab - Backup -->