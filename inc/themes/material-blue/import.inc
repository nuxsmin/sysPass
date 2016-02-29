<?php /** @var $icons \Theme\Icons */ ?>

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
                    <div id="help-import_defaultuser" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-import_defaultuser">
                        <p>
                            <?php echo _('Define el usuario por defecto para las cuentas importadas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <select id="import_defaultuser" name="import_defaultuser"
                            class="select-box sel-chosen-user" required>
                        <option value=""><?php echo _('Seleccionar Usuario'); ?></option>
                        <?php foreach ($users as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($id === \SP\Core\Session::getUserId()) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Grupo por Defecto'); ?>
                    <div id="help-import_defaultgroup" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-import_defaultgroup">
                        <p>
                            <?php echo _('Define el grupo por defecto para las cuentas importadas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <select id="import_defaultgroup" name="import_defaultgroup"
                            class="select-box sel-chosen-usergroup" required>
                        <option value=""><?php echo _('Seleccionar Grupo'); ?></option>
                        <?php foreach ($groups as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($id === \SP\Core\Session::getUserGroupId()) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
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
                        <input id="importPwd" name="importPwd" type="password" class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                               maxlength="255"/>
                        <label class="mdl-textfield__label" for="importPwd"><?php echo _('Clave de Importación'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Delimitador CSV'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="csvDelimiter" name="csvDelimiter" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
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
                <div id="dropzone" class="round active-tooltip"
                     data-files-ext="csv,xml"
                     title="<?php echo _('Soltar archivo aquí o click para seleccionar'); ?>">
                    <i class="material-icons md-60 mdl-color-text--teal-500">cloud_upload</i>
                </div>
                <form method="post" enctype="multipart/form-data" name="upload_form" id="fileUploadForm">
                    <input type="file" id="inFile" multiple name="inFile"/>
                </form>
            </td>
        </tr>
    </table>
    <div class="action">
        <button id="help_import_button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                title="<?php echo $icons->getIconHelp()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
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
                    <div id="help-dbuser" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dbuser">
                        <p>
                            <?php echo _('Indicar el usuario de conexión a la base de datos de phpPMS.') ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dbuser" name="dbuser" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
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
                        <input id="dbpass" name="dbpass" type="password" class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label" for="dbpass"><?php echo _('Clave BBDD'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre BBDD'); ?>
                    <div id="help-dbname" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dbname">
                        <p>
                            <?php echo _('Indicar el nombre de la base de datos de phpPMS.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dbname" name="dbname" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label" for="dbname"><?php echo _('Nombre BBDD'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor BBDD'); ?>
                    <div id="help-dbhost" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dbhost">
                        <p>
                            <?php echo _('Indicar el servidor de la base de datos de phpPMS.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dbhost" name="dbhost" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
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
                    <div class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                    <?php echo _('Los datos actuales serán borrados (excepto el usuario actual)'); ?>
                    <br><br>

                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="chkmigrate">
                        <input type="checkbox" id="chkmigrate" class="mdl-switch__input mdl-color-text--indigo-400" name="chkmigrate"/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $import_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_IMPORT; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>" form="frmMigrate"
                title="<?php echo $icons->getIconPlay()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
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