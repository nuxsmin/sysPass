<!-- Start Tab - Import -->
<div id="tabs-<?php use SP\Acl;
use SP\Common;

echo $import_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Importar phpPMS'); ?>
    </div>

    <form method="post" name="frmMigrate" id="frmMigrate">
        <table class="data round">
            <tr>
                <td class="descField">
                    <?php echo _('Usuario BBDD'); ?>
                    <?php echo Common::printHelpButton("config", 0); ?>
                </td>
                <td class="valField">
                    <input type="text" name="dbuser" value="" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave BBDD'); ?>
                </td>
                <td class="valField">
                    <input type="password" name="dbpass" value=""/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre BBDD'); ?>
                    <?php echo Common::printHelpButton("config", 1); ?>
                </td>
                <td class="valField">
                    <input type="text" name="dbname" value="phppms" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor BBDD'); ?>
                    <?php echo Common::printHelpButton("config", 2); ?>
                </td>
                <td class="valField">
                    <input type="text" name="dbhost" value="localhost" />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Confirmar'); ?>
                </td>
                <td class="valField">
                    <img src="imgs/warning.png" ALT="<?php echo _('Atención'); ?>" class="iconMini" />
                    <?php echo _('Los datos actuales serán borrados (excepto el usuario actual)'); ?>
                    <br><br>
                    <label for="chkmigrate"><?php echo _('NO'); ?></label>
                    <input type="checkbox" name="chkmigrate" id="chkmigrate" class="checkbox" />
                </td>
            </tr>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $import_activeTab; ?>" />
        <input type="hidden" name="onCloseAction" value="<?php echo $import_onCloseAction; ?>" />
        <input type="hidden" name="actionId" value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_IMPORT; ?>" />
        <input type="hidden" name="isAjax" value="1" />
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <ul>
            <li>
                <img src="imgs/start.png" title="<?php echo _('Iniciar'); ?>" class="inputImg" OnClick="configMgmt('migrate');" />
            </li>
        </ul>
    </div>

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Importar CSV/XML'); ?>
    </div>


    <table class="data round">
        <tr>
            <td class="descField">
                <?php echo _('Archivo'); ?>
                <?php echo Common::printHelpButton("config", 23); ?>
            </td>
            <td class="valField">
                <form method="post" enctype="multipart/form-data" name="upload_form" id="fileUpload">
                    <input type="file" id="inFile" name="inFile" />
                </form>
                <div id="dropzone" class="round" title="<?php echo _('Soltar archivo aquí o click para seleccionar'); ?>">
                    <img src="imgs/upload.png" alt="upload" class="opacity50"/>
                </div>
            </td>
        </tr>
    </table>
</div> <!-- End Tab - Import -->

<script>
    $('#frmMigrate .checkbox').button();
    $('#frmMigrate .ui-button').click(function(){
        // El cambio de clase se produce durante el evento de click
        // Si tiene la clase significa que el estado anterior era ON y ahora es OFF
        if ( $(this).hasClass('ui-state-active') ){
            $(this).children().html('<?php echo _('NO'); ?>');
        } else{
            $(this).children().html('<?php echo _('SI'); ?>');
        }
    });
    importFile('<?php echo Common::getSessionKey(true); ?>');
</script>