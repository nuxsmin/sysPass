<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 */ ?>

<form method="post" name="frmAccounts" id="frmAccounts" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configAccount/save"
      data-reload="1"
      data-hash="">

    <div id="title" class="titleNormal">
        <i class="material-icons">search</i>
        <?php echo __('Búsqueda'); ?>
    </div>

    <table id="tblAccounts" class="data tblConfig">
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
                           value="<?php echo $configData->getAccountCount(); ?>" required/>
                    <label class="mdl-textfield__label"
                           for="account_count"><?php echo __('Número de resultados por página'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-accounts-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_expire_enabled">
                                <input type="checkbox" id="account_expire_enabled"
                                       class="mdl-switch__input"
                                       name="account_expire_enabled" <?php echo $configData->isAccountExpireEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Caducidad clave cuentas'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Habilita la caducidad de la clave de cuentas.'); ?></span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Tiempo caducidad clave'); ?>
                <div id="help-account_expire_time"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-account_expire_time">
                    <p>
                        <?php echo __('Número de días para la caducidad de la clave de cuenta.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="account_expire_time" name="account_expire_time" type="number"
                           pattern="[0-9]{1,3}" class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="<?php echo $configData->getAccountExpireTime() / 24 / 3600; ?>"/>
                    <label class="mdl-textfield__label"
                           for="account_expire_time"><?php echo __('Tiempo caducidad (días)'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-accounts-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link_enabled">
                                <input type="checkbox" id="account_link_enabled"
                                       class="mdl-switch__input"
                                       name="account_link_enabled" <?php echo $configData->isAccountLink() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Nombre de cuenta como enlace'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?></span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_globalsearch_enabled">
                                <input type="checkbox" id="account_globalsearch_enabled"
                                       class="mdl-switch__input"
                                       name="account_globalsearch_enabled" <?php echo $configData->isGlobalSearch() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Búsquedas globales'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Permite que todos los usuarios puedan realizar búsquedas en todas las cuentas, pero no pueden ver el contenido de las que no tienen permisos.'); ?></span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_passtoimage_enabled">
                                <input type="checkbox" id="account_passtoimage_enabled"
                                       class="mdl-switch__input"
                                       name="account_passtoimage_enabled" <?php echo $configData->isAccountPassToImage() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Imagen para mostrar clave'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Generar una imagen con el texto de la clave de la cuenta.'); ?>
                                <br>
                                <?php echo __('Util para entornos donde copiar la clave supone un riesgo de seguridad.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_resultsascards_enabled">
                                <input type="checkbox" id="account_resultsascards_enabled"
                                       class="mdl-switch__input"
                                       name="account_resultsascards_enabled" <?php echo $configData->isResultsAsCards() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Resultados en Tarjetas'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Muestra los resultados de búsqueda de cuentas en formato tarjeta.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_fullgroup_access_enabled">
                                <input type="checkbox" id="account_fullgroup_access_enabled"
                                       class="mdl-switch__input"
                                       name="account_fullgroup_access_enabled" <?php echo $configData->isAccountFullGroupAccess() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Acceso Grupos Secundarios'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Habilita el acceso a los usuarios que estén incluidos en los grupos secundarios.'); ?>
                                <br>
                                <?php echo __('Por defecto el usuario de un grupo secundario es permitido si el grupo secundario está establecido como el primario del usuario.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
    </table>

    <div id="title" class="titleNormal">
        <i class="material-icons">link</i>
        <?php echo __('Enlaces Públicos'); ?>
    </div>

    <table id="tblFiles" class="data tblConfig round">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-publiclinks-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="publiclinks_enabled">
                                <input type="checkbox" id="publiclinks_enabled"
                                       class="mdl-switch__input"
                                       name="publiclinks_enabled" <?php echo $configData->isPublinksEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Habilitar Enlaces Públicos'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Habilita la posibilidad de generar enlaces públicos para ver los detalles de una cuenta.'); ?>
                                <br>
                                <?php echo __('Las cuentas enlazadas serán visibles por cualquiera que disponga del enlace.'); ?>
                                <br>
                                <?php echo __('Para crear enlaces, los usuarios tienen que tener activada la opción en su perfl.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="publiclinks_image_enabled">
                                <input type="checkbox" id="publiclinks_image_enabled"
                                       class="mdl-switch__input"
                                       name="publiclinks_image_enabled" <?php echo $configData->isPublinksImageEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Usar imagen para clave'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('La clave de la cuenta es visualizada como una imagen.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Tiempo de caducidad'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="publiclinks_maxtime" name="publiclinks_maxtime" type="number" step="5"
                           pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $configData->getPublinksMaxTime() / 60; ?>"/>
                    <label class="mdl-textfield__label"
                           for="publiclinks_maxtime"><?php echo __('Tiempo de caducidad'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Número máximo  de visitas'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="publiclinks_maxviews" name="publiclinks_maxviews" type="number" step="1"
                           pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $configData->getPublinksMaxViews(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="publiclinks_maxviews"><?php echo __('Número máximo  de visitas'); ?></label>
                </div>
            </td>
        </tr>
    </table>

    <div id="title" class="titleNormal">
        <i class="material-icons">attachment</i>
        <?php echo __('Archivos'); ?>
    </div>

    <table id="tblFiles" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-accounts-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="files_enabled">
                                <input type="checkbox" id="files_enabled"
                                       class="mdl-switch__input"
                                       name="files_enabled" <?php echo $configData->isFilesEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Gestión de archivos'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Habilita la subida/descarga de archivos para las cuentas.'); ?></span>
                        </span>
                    </li>
                </ul>
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
                        <?php echo __('Escribir extensión y pulsar intro para añadir. Es necesario guardar la configuración.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Extensiones de archivos permitidas'); ?></div>

                <input type="text" name="files_allowed_exts" id="allowed_exts"
                       value="<?php echo implode(',', $configData->getFilesAllowedExts()); ?>"/>
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
                           value="<?php echo $configData->getFilesAllowedSize(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="files_allowed_size"><?php echo __('Tamaño máximo de archivo en kilobytes'); ?></label>
                </div>
            </td>
        </tr>
    </table>
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
