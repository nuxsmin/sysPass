<?php /** @var $icons \Theme\Icons */ ?>

<div id="tabs-<?php echo $accounts_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <form method="post" name="frmAccounts" id="frmAccounts" class="form-action"
              data-onsubmit="config/save"
              data-type="general"
              data-reload="1"
              data-hash="">

            <div id="title" class="midroundup titleNormal">
                <?php echo __('Búsqueda'); ?>
            </div>

            <table id="tblAccounts" class="data tblConfig round">
                <tr>
                    <td class="descField">
                        <?php echo __('Resultados por página'); ?>
                        <div id="help-account_count"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-account_count">
                            <p>
                                <?php echo __('Número de resultados por página a mostrar, al realizar una búsqueda.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="account_count" name="account_count" type="number" step="6"
                                   pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="5"
                                   value="<?php echo $accountCount; ?>" required/>
                            <label class="mdl-textfield__label"
                                   for="account_count"><?php echo __('Número de resultados por página'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-account_link"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-account_link">
                            <p>
                                <?php echo __('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link">
                            <input type="checkbox" id="account_link"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="account_link" <?php echo $chkAccountLink; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Nombre de cuenta como enlace'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-globalsearch"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-globalsearch">
                            <p>
                                <?php echo __('Permite que todos los usuarios puedan realizar búsquedas en todas las cuentas, pero no pueden ver el contenido de las que no tienen permisos.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="globalsearch">
                            <input type="checkbox" id="globalsearch"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="globalsearch" <?php echo $chkGlobalSearch; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Búsquedas globales'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-account_passtoimage"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-account_passtoimage">
                            <p>
                                <?php echo __('Generar una imagen con el texto de la clave de la cuenta.'); ?>
                            </p>

                            <p>
                                <?php echo __('Util para entornos donde copiar la clave supone un riesgo de seguridad.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_passtoimage">
                            <input type="checkbox" id="account_passtoimage"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="account_passtoimage"
                                <?php echo $chkAccountPassToImage; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Imagen para mostrar clave'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-resultsascards"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-resultsascards">
                            <p>
                                <?php echo __('Muestra los resultados de búsqueda de cuentas en formato tarjeta.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="resultsascards">
                            <input type="checkbox" id="resultsascards"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="resultsascards"
                                <?php echo $chkResultsAsCards; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Resultados en Tarjetas'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-account_fullgroup_access"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-account_fullgroup_access">
                            <p>
                                <?php echo __('Habilita el acceso a los usuarios que estén incluidos en los grupos secundarios.'); ?>
                            </p>
                            <p>
                                <?php echo __('Por defecto el usuario de un grupo secundario es permitido si el grupo secundario está establecido como el primario del usuario.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_fullgroup_access">
                            <input type="checkbox" id="account_fullgroup_access"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="account_fullgroup_access"
                                <?php echo $chkAccountFullGroupAccess; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Acceso Grupos Secundarios'); ?></span>
                        </label>
                    </td>
                </tr>
            </table>

            <div id="title" class="midroundup titleNormal">
                <?php echo __('Enlaces Públicos'); ?>
            </div>

            <table id="tblFiles" class="data tblConfig round">
                <tr>
                    <td class="descField">
                        <div id="help-publinks"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-publinks">
                            <p>
                                <?php echo __('Habilita la posibilidad de generar enlaces públicos para ver los detalles de una cuenta.'); ?>
                            </p>

                            <p>
                                <?php echo __('Las cuentas enlazadas serán visibles por cualquiera que disponga del enlace.'); ?>
                            </p>

                            <p>
                                <?php echo __('Para crear enlaces, los usuarios tienen que tener activada la opción en su perfl.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="publinks_enabled">
                            <input type="checkbox" id="publinks_enabled"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="publinks_enabled"
                                <?php echo $chkPubLinks; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Habilitar Enlaces Públicos'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Tiempo de caducidad'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="publinks_maxtime" name="publinks_maxtime" type="number" step="5"
                                   pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="5"
                                   value="<?php echo $pubLinksMaxTime; ?>"/>
                            <label class="mdl-textfield__label"
                                   for="publinks_maxtime"><?php echo __('Tiempo de caducidad'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Número máximo  de visitas'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="publinks_maxviews" name="publinks_maxviews" type="number" step="1"
                                   pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="5"
                                   value="<?php echo $pubLinksMaxViews; ?>"/>
                            <label class="mdl-textfield__label"
                                   for="publinks_maxviews"><?php echo __('Número máximo  de visitas'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-publinksimage"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-publinksimage">
                            <p>
                                <?php echo __('La clave de la cuenta es visualizada como una imagen.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="publinks_image_enabled">
                            <input type="checkbox" id="publinks_image_enabled"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="publinks_image_enabled"
                                <?php echo $chkPubLinksImage; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Usar imagen para clave'); ?></span>
                        </label>
                    </td>
                </tr>
            </table>

            <div id="title" class="midroundup titleNormal">
                <?php echo __('Archivos'); ?>
            </div>

            <table id="tblFiles" class="data tblConfig round">
                <tr>
                    <td class="descField">
                        <div id="help-files"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-files">
                            <p>
                                <?php echo __('Habilita la subida/descarga de archivos para las cuentas.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="files_enabled">
                            <input type="checkbox" id="files_enabled"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="files_enabled"
                                <?php echo $chkFiles; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Gestión de archivos'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Extensiones de archivos permitidas'); ?>
                        <div id="help-allowed_exts"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-allowed_exts">
                            <p>
                                <?php echo __('Extensiones permitidas para la subida de archivos.'); ?>
                            </p>

                            <p>
                                <?php echo __('Se permite un máximo de 4 caracteres.'); ?>
                            </p>

                            <p>
                                <?php echo __('Escribir extensión y pulsar intro para añadir. Es necesario guardar la configuración.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Extensiones de archivos permitidas'); ?></div>

                        <input type="text" name="files_allowed_exts" id="allowed_exts"
                               value="<?php echo $filesAllowedExts; ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Tamaño máximo de archivo'); ?>
                        <div id="help-files_allowed_size"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-files_allowed_size">
                            <p>
                                <?php echo __('Establece el tamaño máximo para subir archivos.'); ?>
                            </p>

                            <p>
                                <?php echo __('El máximo absuluto es de 16MB.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="files_allowed_size" name="files_allowed_size" type="number" step="512"
                                   pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="5"
                                   value="<?php echo $filesAllowedSize; ?>"/>
                            <label class="mdl-textfield__label"
                                   for="files_allowed_size"><?php echo __('Tamaño máximo de archivo en kilobytes'); ?></label>
                        </div>
                    </td>
                </tr>
            </table>

            <input type="hidden" name="activeTab" value="<?php echo $accounts_tabIndex; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $accounts_actionId; ?>"/>
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
                    <button form="frmAccounts"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                            title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                        <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>