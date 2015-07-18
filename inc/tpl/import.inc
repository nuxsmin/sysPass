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
                    <input type="text" name="dbuser" value=""/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave BBDD'); ?>
                </td>
                <td class="valField">
                    <input type="password" name="dbpass" value=""/>
                    <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre BBDD'); ?>
                    <?php echo Common::printHelpButton("config", 1); ?>
                </td>
                <td class="valField">
                    <input type="text" name="dbname" value="phppms"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor BBDD'); ?>
                    <?php echo Common::printHelpButton("config", 2); ?>
                </td>
                <td class="valField">
                    <input type="text" name="dbhost" value="localhost"/>
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
                    <label for="chkmigrate"><?php echo _('NO'); ?></label>
                    <input type="checkbox" name="chkmigrate" id="chkmigrate" class="checkbox"/>
                </td>
            </tr>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $import_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_IMPORT; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <ul>
            <li>
                <img src="imgs/start.png" title="<?php echo _('Iniciar'); ?>" class="inputImg"
                     OnClick="configMgmt('migrate');"/>
            </li>
        </ul>
    </div>

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
                            class="select-box" <?php echo $isDisabled; ?> required>
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
                            class="select-box" <?php echo $isDisabled; ?> required>
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
                    <input type="password" name="importPwd" maxlength="255">
                    <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
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
        <ul>
            <li>
                <img id="help_import_button" src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImg"/>

                <div id="help_import" class="help-box" title="<?php echo _('Ayuda'); ?>">
                    <p class="help-text"><?php echo _('Permite realizar la importación de Cuentas, Categorías y Clientes desde arhcivos XML y CSV.'); ?></p>

                    <p class="help-text"><?php echo _('Los formatos de archivos XML soportados son: sysPass, KeePass y KeePassX'); ?></p>

                    <p class="help-text"><?php echo _('Arrastar el archivo a importar a la zona indicada o hacer click sobre la flecha.'); ?></p>

                    <p class="help-text"><?php echo _('Para archivos de KeePass o KeePassX, el nombre del cliente será igual a KeePass o KeePassX y la categoría igual al nombre de los grupos.'); ?></p>

                    <p class="help-text"><?php echo _('La importación de archivos CSV se realiza con el siguiente formato:'); ?></p>

                    <p class="help-text"><?php echo _('nombre_de_cuenta;cliente;categoría;url;usuario;clave;notas'); ?></p>

                    <p class="help-text"><?php echo _('En todos los casos, si el cliente o la categoría no están creados, se crean automáticamente.'); ?></p>
                </div>
            </li>
        </ul>
    </div>
</div> <!-- End Tab - Import -->

<script>
    $(function () {
        importFile('<?php echo Common::getSessionKey(true); ?>');
        $("#import_defaultgroup").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Grupo'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
        });
        $("#import_defaultuser").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Usuario'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
        });
        $("#help_import_button").click(function () {
            $("#help_import").dialog("open");
        });
    });
</script>