<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Backup -->
<div id="tabs-<?php echo $backup_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo __('Copia de Seguridad'); ?>
        </div>

        <table class="data round">
            <tr>
                <td class="descField">
                    <?php echo __('Resultado'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Resultado'); ?></div>

                    <?php echo $lastBackupTime; ?>
                </td>
            </tr>
            <?php if ($isAdminApp): ?>
                <tr>
                    <td class="descField">
                        <?php echo __('Descargar Actual'); ?>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Descargar Actual'); ?></div>

                        <?php if (file_exists($backupFile['absolute']) && file_exists($backupDbFile['absolute'])): ?>
                            <a href="<?php echo $backupDbFile['relative']; ?>"
                               download="<?php echo $backupDbFile['filename']; ?>" class="download">
                                <button type="button" class="mdl-button mdl-js-button">
                                    <?php echo __('Copia BBDD'); ?>
                                </button>
                            </a>

                            <a href="<?php echo $backupFile['relative']; ?>"
                               download="<?php echo $backupFile['filename']; ?>" class="download">
                                <button type="button" class="mdl-button mdl-js-button">
                                    <?php echo __('Copia sysPass'); ?>
                                </button>
                            </a>
                        <?php else: echo __('No hay backups para descargar'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <form method="post" name="frmBackup" id="frmBackup" class="form-action"
              data-onsubmit="config/backup"
              data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_BACKUP; ?>"
              data-activetab="<?php echo $backup_tabIndex; ?>">

            <input type="hidden" name="isAjax" value="1"/>
            <input type="hidden" name="actionId" value="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_BACKUP; ?>"/>
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        </form>

        <div class="tab-actions">
            <ul>
                <li>
                    <button id="help_backup_button" type="button"
                            class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                            title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                            data-help="help_backup">
                        <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
                    </button>

                    <div id="help_backup" class="help-box" title="<?php echo __('Ayuda'); ?>">
                        <p class="help-text"><?php echo __('La copia de seguridad permite guardar y descargar tanto la base de datos de sysPass como los archivos de la aplicación y su configuración.'); ?></p>

                        <p class="help-text"><?php echo __('Con este método es posible guardar todos los datos de sysPass en otro lugar o utilizarlos para hacer la aplicación portable.'); ?></p>
                    </div>
                </li>
                <li>

                    <button form="frmBackup"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                            title="<?php echo $icons->getIconPlay()->getTitle(); ?>">
                        <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>

        <div id="title" class="midroundup titleNormal">
            <?php echo __('Exportar Cuentas'); ?>
        </div>

        <form method="post" name="frmExport" id="frmExport" class="form-action"
              data-onsubmit="config/export"
              data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_EXPORT; ?>"
              data-activetab="<?php echo $backup_tabIndex; ?>">
            <table class="data round">
                <tr>
                    <td class="descField">
                        <?php echo __('Resultado'); ?>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Resultado'); ?></div>

                        <?php echo $lastExportTime; ?>
                    </td>
                </tr>
                <?php if ($isAdminApp): ?>
                    <tr>
                        <td class="descField">
                            <?php echo __('Descargar Actual'); ?>
                        </td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Descargar Actual'); ?></div>

                            <?php if (file_exists($exportFile['absolute'])): ?>
                                <a href="<?php echo $exportFile['relative']; ?>"
                                   download="<?php echo $exportFile['filename']; ?>"
                                   class="download">
                                    <button type="button" class="mdl-button mdl-js-button">
                                        XML <?php echo $siteName; ?>
                                    </button>
                                </a>
                            <?php else: echo __('No hay archivos XML para descargar'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="descField">
                        <?php echo __('Clave de Exportación'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="exportPwd" name="exportPwd" type="password"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                                   maxlength="255"/>
                            <label class="mdl-textfield__label"
                                   for="exportPwd"><?php echo __('Clave de Exportación'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Clave de Exportación (repetir)'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="exportPwdR" name="exportPwdR" type="password"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="255"/>
                            <label class="mdl-textfield__label"
                                   for="exportPwdR"><?php echo __('Clave de Exportación (repetir)'); ?></label>
                        </div>
                    </td>
                </tr>
            </table>

            <input type="hidden" name="actionId" value="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_EXPORT; ?>"/>
            <input type="hidden" name="sk" value="">
            <input type="hidden" name="isAjax" value="1"/>
        </form>

        <div class="tab-actions">
            <ul>
                <li>
                    <button id="btnBack" type="button"
                            class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                            title="<?php echo __('Atrás'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    </button>
                </li>
                <li>
                    <button id="help_export_button" type="button"
                            class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                            title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                            data-help="help_export">
                        <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
                    </button>

                    <div id="help_export" class="help-box" title="<?php echo $icons->getIconHelp()->getTitle(); ?>">
                        <p class="help-text"><?php echo __('La exportación de cuentas permite guardar las cuentas y sus datos en formato XML para posteriormente poder ser importados en otras instancias de sysPass.'); ?></p>

                        <p class="help-text"><?php echo __('Los elementos exportados son cuentas, clientes, categorías y etiquetas.'); ?></p>

                        <p class="help-text"><?php echo __('Las claves de las cuentas son exportadas de forma encriptada para mayor seguridad.'); ?></p>

                        <p class="help-text"><?php echo __('Es posible establecer una clave de exportación para guardar todos los datos encriptados en el archivo XML.'); ?></p>
                    </div>
                </li>
                <li>
                    <button form="frmExport"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                            title="<?php echo $icons->getIconPlay()->getTitle(); ?>">
                        <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div> <!-- End Tab - Backup -->