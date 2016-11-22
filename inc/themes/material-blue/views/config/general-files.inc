<?php /** @var $icons \Theme\Icons */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo _('Archivos'); ?>
</div>

<table id="tblFiles" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Gestión de archivos'); ?>
            <div id="help-files"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-files">
                <p>
                    <?php echo _('Habilita la subida/descarga de archivos para las cuentas.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="files_enabled">
                <input type="checkbox" id="files_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                       name="files_enabled"
                    <?php echo $chkFiles; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Extensiones de archivos permitidas'); ?>
            <div id="help-allowed_exts"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
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
            <div id="help-files_allowed_size"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
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
                       pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="5"
                       value="<?php echo $filesAllowedSize; ?>"/>
                <label class="mdl-textfield__label"
                       for="files_allowed_size"><?php echo _('Tamaño máximo de archivo en bytes'); ?></label>
            </div>
        </td>
    </tr>
</table>