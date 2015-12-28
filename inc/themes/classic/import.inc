<!-- Start Tab - Import -->
<div id="tabs-<?php echo $import_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Importar CSV/XML'); ?>
    </div>

    <table class="data round">
        <form method="post" name="frmImport" id="frmImport">
            <tr>
                <td class="descField">
                    <?php echo _('Usuario por Defecto'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-import_defaultuser" style="display: none;">
                        <p>
                            <?php echo _('Define el usuario por defecto para las cuentas importadas.'); ?>
                        </p>
                    </div>
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
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-import_defaultuser" style="display: none;">
                        <p>
                            <?php echo _('Define el grupo por defecto para las cuentas importadas.'); ?>
                        </p>
                    </div>
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
                    <input type="password" name="importPwd" id="importPwd" class="passwordfield__input-show" maxlength="255">
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Delimitador CSV'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="csvDelimiter" maxlength="1" value=";">
                </td>
            </tr>
        </form>
        <tr>
            <td class="descField">
                <?php echo _('Archivo'); ?>
            </td>
            <td class="valField">
                <div id="dropzone" class="round active-tooltip"
                     data-files-ext="csv,xml"
                     title="<?php echo _('Soltar archivo aquí o click para seleccionar'); ?>">
                    <img src="imgs/upload.png" alt="upload" class="opacity50"/>
                </div>
                <form method="post" enctype="multipart/form-data" name="upload_form" id="fileUploadForm">
                    <input type="file" id="inFile" multiple name="inFile"/>
                </form>
            </td>
        </tr>
    </table>

    <div class="action">
        <button id="help_import_button" type="button" title="<?php echo _('Ayuda'); ?>"
                class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/help.png" alt="save"/>
        </button>
        <div id="help_import" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('Permite realizar la importación de Cuentas, Categorías y Clientes desde archivos XML y CSV.'); ?></p>

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

    <form method="post" name="frmMigrate" id="frmMigrate" onsubmit="sysPassUtil.Common.configMgmt('import', this); return false;">
        <table class="data round">
            <tr>
                <td class="descField">
                    <?php echo _('Usuario BBDD'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dbuser" style="display:none;">
                        <p>
                            <?php echo _('Indicar el usuario de conexión a la base de datos de phpPMS.') ?>
                        </p>
                    </div>
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
                    <input type="password" name="dbpass" class="passwordfield__input-show" value=""/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre BBDD'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dbname" style="display:none;">
                        <p>
                            <?php echo _('Indicar el nombre de la base de datos de phpPMS.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="dbname" value="phppms"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor BBDD'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dbhost" style="display:none;">
                        <p>
                            <?php echo _('Indicar el servidor de la base de datos de phpPMS.'); ?>
                        </p>
                    </div>
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
        <button type="submit" form="frmMigrate" title="<?php echo _('Iniciar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/start.png" alt="save"/>
        </button>
    </div>
</div> <!-- End Tab - Import -->

<script>
    $(function () {
        var upload = sysPassUtil.Common.fileUpload({targetId: 'dropzone', url: '/ajax/ajax_import.php'});
        upload.setBeforeSendAction(function(){
            upload.setRequestData({
                sk: '<?php echo $sk; ?>',
                csvDelimiter: $('#csvDelimiter').val(),
                importPwd: $('#importPwd').val(),
                import_defaultuser: $('#import_defaultuser').val(),
                import_defaultgroup: $('#import_defaultgroup').val()
            });
        });

        $("#help_import_button").click(function () {
            $("#help_import").dialog("open");
        });
    });
</script>