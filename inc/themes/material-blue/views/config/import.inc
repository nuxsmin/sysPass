<?php /** @var $icons \Theme\Icons */
use SP\Core\Session; ?>

<!-- Start Tab - Import -->
<div id="tabs-<?php echo $import_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo __('Importar CSV/XML'); ?>
        </div>

        <table class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo __('Usuario por Defecto'); ?>
                    <div id="help-import_defaultuser"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-import_defaultuser">
                        <p>
                            <?php echo __('Define el usuario por defecto para las cuentas importadas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Usuario por Defecto'); ?></div>

                    <select id="import_defaultuser" name="import_defaultuser"
                            class="select-box sel-chosen-user" required>
                        <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                        <?php foreach ($users as $user): ?>
                            <option
                                    value="<?php echo $user->id; ?>" <?php echo ($user->id === Session::getUserData()->getUserId()) ? 'selected' : ''; ?>>
                                <?php echo $user->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Grupo por Defecto'); ?>
                    <div id="help-import_defaultgroup"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-import_defaultgroup">
                        <p>
                            <?php echo __('Define el grupo por defecto para las cuentas importadas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Grupo por Defecto'); ?></div>

                    <select id="import_defaultgroup" name="import_defaultgroup"
                            class="select-box sel-chosen-usergroup" required>
                        <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                        <?php foreach ($groups as $group): ?>
                            <option
                                    value="<?php echo $group->id; ?>" <?php echo ($group->id === $SessionUserData->getUserGroupId()) ? 'selected' : ''; ?>>
                                <?php echo $group->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Archivo'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Archivo'); ?></div>

                    <div id="drop-import-files" class="round active-tooltip dropzone"
                         data-files-ext="csv,xml"
                         data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_IMPORT; ?>"
                         title="<?php echo __('Soltar archivo aquí o click para seleccionar'); ?>">
                        <i class="material-icons md-60 mdl-color-text--teal-500">cloud_upload</i>
                    </div>
                    <form method="post" enctype="multipart/form-data" name="upload_form" id="fileUploadForm">
                        <input type="file" id="inFile" multiple name="inFile"/>
                    </form>
                </td>
            </tr>
        </table>

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
                    <button id="help_import_button"
                            class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                            title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                            data-help="help_import">
                        <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
                    </button>

                    <div id="help_import" class="help-box" title="<?php echo __('Ayuda'); ?>">
                        <p class="help-text"><?php echo __('Permite realizar la importación de Cuentas, Categorías y Clientes desde archivos XML y CSV.'); ?></p>

                        <p class="help-text"><?php echo __('Los formatos de archivos XML soportados son: sysPass, KeePass y KeePassX'); ?></p>

                        <p class="help-text"><?php echo __('Arrastar el archivo a importar a la zona indicada o hacer click sobre la flecha.'); ?></p>

                        <p class="help-text"><?php echo __('Para archivos de KeePass o KeePassX, el nombre del cliente será igual a KeePass o KeePassX y la categoría igual al nombre de los grupos.'); ?></p>

                        <p class="help-text"><?php echo __('La importación de archivos CSV se realiza con el siguiente formato:'); ?></p>

                        <p class="help-text"><?php echo __('"nombre_de_cuenta";"cliente";"categoría";"url";"usuario";"clave";"notas"'); ?></p>

                        <p class="help-text"><?php echo __('En todos los casos, si el cliente o la categoría no están creados, se crean automáticamente.'); ?></p>
                    </div>
                </li>
            </ul>
        </div>

        <div id="title" class="midroundup titleNormal">
            <?php echo __('XML'); ?>
        </div>

        <table class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo __('Clave de Importación'); ?>
                    <div id="help-import_pwd"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-import_pwd">
                        <p>
                            <?php echo __('Sólo es necesaria para archivos XML de sysPass encriptados.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="importPwd" name="importPwd" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                               maxlength="255"/>
                        <label class="mdl-textfield__label"
                               for="importPwd"><?php echo __('Clave de Importación'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Clave Maestra'); ?>
                    <div id="help-import_masterpwd"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-import_masterpwd">
                        <p>
                            <?php echo __('Sólo es necesaria para archivos XML de sysPass con clave maestra distinta de la actual.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="importMasterPwd" name="importMasterPwd" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                               maxlength="255"/>
                        <label class="mdl-textfield__label"
                               for="importMasterPwd"><?php echo __('Clave Maestra'); ?></label>
                    </div>
                </td>
            </tr>
        </table>

        <div id="title" class="midroundup titleNormal">
            <?php echo __('CSV'); ?>
        </div>

        <table class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo __('Delimitador CSV'); ?>
                    <div id="help-import_csvdelimiter"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-import_csvdelimiter">
                        <p>
                            <?php echo __('Sólo es necesario para archivos CSV.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="csvDelimiter" name="csvDelimiter" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="1" value=";"/>
                        <label class="mdl-textfield__label"
                               for="csvDelimiter"><?php echo __('Delimitador CSV'); ?></label>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div> <!-- End Tab - Import -->