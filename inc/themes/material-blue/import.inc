<!-- Start Tab - Import -->
<div id="tabs-<?php use SP\Acl;
use SP\Common;

echo $import_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Importar CSV/XML'); ?>
    </div>


    <table class="data round">
        <form method="post" name="frmImport" id="frmImport">
            <tr>
                <td class="descField">
                    <?php echo _('Usuario por Defecto'); ?>
                    <?php echo SP\Common::printHelpButton("config", 29); ?>
                </td>
                <td class="valField">
                    <select id="import_defaultuser" name="import_defaultuser"
                            class="select-box sel-chosen-user" <?php echo $isDisabled; ?> required>
                        <option value="0"></option>
                        <?php foreach ($users as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($id === \SP\Session::getUserId()) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Grupo por Defecto'); ?>
                    <?php echo SP\Common::printHelpButton("config", 30); ?>
                </td>
                <td class="valField">
                    <select id="import_defaultgroup" name="import_defaultgroup"
                            class="select-box sel-chosen-usergroup" <?php echo $isDisabled; ?> required>
                        <option value="0"></option>
                        <?php foreach ($groups as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($id === \SP\Session::getUserGroupId()) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave de Importación'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="importPwd" name="importPwd" type="password" class="mdl-textfield__input blue100"
                               maxlength="255"/>
                        <label class="mdl-textfield__label" for="importPwd"><?php echo _('Clave de Importación'); ?></label>
                    </div>
                    <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Delimitador CSV'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="csvDelimiter" name="csvDelimiter" type="text" class="mdl-textfield__input blue100"
                               maxlength="1" value=";"/>
                        <label class="mdl-textfield__label" for="csvDelimiter"><?php echo _('Delimitador CSV'); ?></label>
                    </div>
                </td>
            </tr>
        </form>
        <tr>
            <td class="descField">
                <?php echo _('Archivo'); ?>
            </td>
            <td class="valField">
                <form method="post" enctype="multipart/form-data" name="upload_form" id="fileUpload">
                    <input type="file" id="inFile" name="inFile"/>
                </form>
                <div id="dropzone" class="round"
                     title="<?php echo _('Soltar archivo aquí o click para seleccionar'); ?>">
                    <img src="imgs/upload.png" alt="upload" class="opacity50"/>
                </div>
            </td>
        </tr>
    </table>
    <div class="action">
        <button id="help_import_button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                title="<?php echo _('Ayuda'); ?>">
            <i class="material-icons">help_outline</i>
        </button>

        <div id="help_import" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('Permite realizar la importación de Cuentas, Categorías y Clientes desde arhcivos XML y CSV.'); ?></p>

            <p class="help-text"><?php echo _('Los formatos de archivos XML soportados son: sysPass, KeePass y KeePassX'); ?></p>

            <p class="help-text"><?php echo _('Arrastar el archivo a importar a la zona indicada o hacer click sobre la flecha.'); ?></p>

            <p class="help-text"><?php echo _('Para archivos de KeePass o KeePassX, el nombre del cliente será igual a KeePass o KeePassX y la categoría igual al nombre de los grupos.'); ?></p>

            <p class="help-text"><?php echo _('La importación de archivos CSV se realiza con el siguiente formato:'); ?></p>

            <p class="help-text"><?php echo _('nombre_de_cuenta;cliente;categoría;url;usuario;clave;notas'); ?></p>

            <p class="help-text"><?php echo _('En todos los casos, si el cliente o la categoría no están creados, se crean automáticamente.'); ?></p>
        </div>
    </div>

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
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dbuser" name="dbuser" type="text" class="mdl-textfield__input blue100"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label" for="dbuser"><?php echo _('Usuario BBDD'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave BBDD'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dbpass" name="dbpass" type="password" class="mdl-textfield__input blue100"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label" for="dbpass"><?php echo _('Clave BBDD'); ?></label>
                    </div>
                    <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre BBDD'); ?>
                    <?php echo Common::printHelpButton("config", 1); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dbname" name="dbname" type="text" class="mdl-textfield__input blue100"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label" for="dbname"><?php echo _('Nombre BBDD'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor BBDD'); ?>
                    <?php echo Common::printHelpButton("config", 2); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dbhost" name="dbhost" type="text" class="mdl-textfield__input blue100"
                               maxlength="255" value="localhost" required/>
                        <label class="mdl-textfield__label" for="dbhost"><?php echo _('Servidor BBDD'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Confirmar'); ?>
                </td>
                <td class="valField">
                    <img src="imgs/warning.png" ALT="<?php echo _('Atención'); ?>" class="iconMini"/>
                    <?php echo _('Los datos actuales serán borrados (excepto el usuario actual)'); ?>
                    <br><br>

                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="chkmigrate">
                        <input type="checkbox" id="chkmigrate" class="mdl-switch__input blue100" name="chkmigrate"/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $import_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_IMPORT; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="configMgmt('migrate');" title="<?php echo _('Realizar'); ?>">
            <i class="material-icons">play_circle_filled</i>
        </button>
    </div>
</div> <!-- End Tab - Import -->

<script>
    $(function () {
        importFile('<?php echo Common::getSessionKey(true); ?>');
        $("#help_import_button").click(function () {
            $("#help_import").dialog("open");
        });
    });
</script>