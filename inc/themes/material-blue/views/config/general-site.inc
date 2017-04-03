<?php /** @var $icons \Theme\Icons */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo __('Sitio'); ?>
</div>

<table id="tblSite" class="data tblConfig round">
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
                <?php foreach ($langsAvailable as $langName => $langValue): ?>
                    <option
                        value='<?php echo $langValue; ?>' <?php echo ($currentLang === $langValue) ? 'SELECTED' : ''; ?>><?php echo $langName; ?></option>
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
                    class="select-box sel-chosen-ns" >
                <?php foreach ($themesAvailable as $themeDir => $themeName): ?>
                    <option
                        value='<?php echo $themeDir; ?>' <?php echo ($currentTheme == $themeDir) ? "SELECTED" : ""; ?>><?php echo $themeName; ?></option>
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
                       value="<?php echo $sessionTimeout; ?>"  required/>
                <label class="mdl-textfield__label"
                       for="session_timeout"><?php echo __('Timeout de sesión (s)'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <div id="help-https"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-https">
                <p>
                    <?php echo __('Fuerza para que todas las conexiones sean a través de HTTPS.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="https_enabled">
                <input type="checkbox" id="https_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                       name="https_enabled"
                    <?php echo $chkHttps; ?>/>
                <span class="mdl-switch__label"><?php echo __('Forzar HTTPS'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <div id="help-debug"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-debug">
                <p>
                    <?php echo __('Muestra información relativa a la configuración de la aplicación y rendimiento.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="debug">
                <input type="checkbox" id="debug" class="mdl-switch__input mdl-color-text--indigo-400" name="debug"
                    <?php echo $chkDebug; ?>/>
                <span class="mdl-switch__label"><?php echo __('Habilitar depuración'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <div id="help-maintenance"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-maintenance">
                <p>
                    <?php echo __('En este modo no se puede acceder a la aplicación. Para deshabilitarlo es necesario modificar el archivo de configuración.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="maintenance">
                <input type="checkbox" id="maintenance" class="mdl-switch__input mdl-color-text--indigo-400" name="maintenance"
                    <?php echo $chkMaintenance; ?>/>
                <span class="mdl-switch__label"><?php echo __('Modo mantenimiento'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <div id="help-updates"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-updates">
                <p>
                    <?php echo __('Comprobar actualizaciones de la aplicación (sólo para los usuarios administradores).'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="updates">
                <input type="checkbox" id="updates" class="mdl-switch__input mdl-color-text--indigo-400" name="updates"
                    <?php echo $chkUpdates; ?>/>
                <span class="mdl-switch__label"><?php echo __('Comprobar actualizaciones'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <div id="help-notices"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-notices">
                <p>
                    <?php echo __('Comprobar si existen notificaciones de seguridad o avisos de sysPass (sólo para los usuarios administradores).'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="notices">
                <input type="checkbox" id="notices" class="mdl-switch__input mdl-color-text--indigo-400" name="notices"
                    <?php echo $chkNotices; ?>/>
                <span class="mdl-switch__label"><?php echo __('Comprobar notificaciones'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <div id="help-encryptsession"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-encryptsession">
                <p>
                    <?php echo __('Encriptar los datos de la sesión de PHP.'); ?>
                </p>
                <p>
                    <?php echo __('Esta funcionalidad incrementa la seguridad de las sesiones de PHP ya que los datos almacenados no serán legibles.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="encryptsession">
                <input type="checkbox" id="encryptsession" class="mdl-switch__input mdl-color-text--indigo-400" name="encryptsession"
                    <?php echo $chkEncryptSession; ?>/>
                <span class="mdl-switch__label"><?php echo __('Encriptar Sesión'); ?></span>
            </label>
        </td>
    </tr>
</table>