<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 * @var callable               $_getvar
 * @var \SP\Mvc\View\Template  $this
 */
?>

<?php if (!$_getvar('curlIsAvailable')): ?>
    <div class="msg-option-unvailable">
        <?php printf(__('La extensión \'%s\' no está disponible'), 'curl'); ?>
        <i id="help-curl" class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
        <div class="mdl-tooltip mdl-tooltip--large" for="help-curl">
            <p>
                <?php echo __('Esta extensión es necesaria comprobar las actualizaciones y notificaciones'); ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<div id="title" class="titleNormal">
    <i class="material-icons">home</i>
    <?php echo __('Sitio'); ?>
</div>

<table id="tblSite" class="data tblConfig">
    <tr>
        <td class="descField">
            <?php echo __('Idioma'); ?>
            <div id="help-sitelang"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-sitelang">
                <p>
                    <?php echo __('Establece el idioma global de la aplicación. Es posible establecer un idioma por usuario en las preferencias.'); ?>
                </p>

                <p>
                    <?php echo __('Para mejorar/añadir las traducciones, puede colaborar en https://poeditor.com/join/project/fmlsBuugyv'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php echo __('Idioma'); ?></div>

            <select name="sitelang" id="sel-sitelang" size="1" class="select-box sel-chosen-ns">
                <?php /** @var \SP\Mvc\View\Components\SelectItem $lang */
                foreach ($_getvar('langs') as $lang): ?>
                    <option
                            value='<?php echo $lang->getId(); ?>' <?php echo $lang->isSelected() ? 'selected' : ''; ?>><?php echo $lang->getName(); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Tema Visual'); ?>
            <div id="help-sitetheme"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-sitetheme">
                <p>
                    <?php echo __('Establece el tema visual global de la aplicación. Es posible establecer un tema visual por usuario en las preferencias.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php echo __('Tema Visual'); ?></div>

            <select name="sitetheme" id="sel-sitetheme" size="1"
                    class="select-box sel-chosen-ns">
                <?php /** @var \SP\Mvc\View\Components\SelectItem $theme */
                foreach ($_getvar('themes') as $theme): ?>
                    <option
                            value='<?php echo $theme->getId(); ?>' <?php echo $theme->isSelected() ? 'selected' : ""; ?>><?php echo $theme->getName(); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Timeout de sesión (s)'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="session_timeout" name="session_timeout" type="number" step="300"
                       pattern="[0-9]{2,4}" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="5"
                       value="<?php echo $configData->getSessionTimeout(); ?>" required/>
                <label class="mdl-textfield__label"
                       for="session_timeout"><?php echo __('Timeout de sesión (s)'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="config-site-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="https_enabled">
                            <input type="checkbox" id="https_enabled"
                                   class="mdl-switch__input"
                                   name="https_enabled" <?php echo $configData->isHttpsEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Forzar HTTPS'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Fuerza para que todas las conexiones sean a través de HTTPS.'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="debug_enabled">
                            <input type="checkbox" id="debug_enabled"
                                   class="mdl-switch__input"
                                   name="debug_enabled" <?php echo $configData->isDebug() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Habilitar depuración'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Muestra información relativa a la configuración de la aplicación y rendimiento.'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="maintenance_enabled">
                            <input type="checkbox" id="maintenance_enabled"
                                   class="mdl-switch__input"
                                   name="maintenance_enabled" <?php echo $configData->isMaintenance() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Modo mantenimiento'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('En este modo no se puede acceder a la aplicación. Para deshabilitarlo es necesario modificar el archivo de configuración.'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="check_updates_enabled">
                            <input type="checkbox" id="check_updates_enabled"
                                   class="mdl-switch__input"
                                   name="check_updates_enabled" <?php echo $configData->isCheckUpdates() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Comprobar actualizaciones'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Comprobar actualizaciones de la aplicación (sólo para los usuarios administradores).'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="check_notices_enabled">
                            <input type="checkbox" id="check_notices_enabled"
                                   class="mdl-switch__input"
                                   name="check_notices_enabled" <?php echo $configData->isChecknotices() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Comprobar notificaciones'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Comprobar si existen notificaciones de seguridad o avisos de sysPass (sólo para los usuarios administradores).'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="encrypt_session_enabled">
                            <input type="checkbox" id="encrypt_session_enabled"
                                   class="mdl-switch__input"
                                   name="encrypt_session_enabled" <?php echo $configData->isEncryptSession() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Encriptar Sesión'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php echo __('Encriptar los datos de la sesión de PHP.'); ?>
                            <?php echo __('Esta funcionalidad incrementa la seguridad de las sesiones de PHP ya que los datos almacenados no serán legibles.'); ?>
                        </span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
</table>