<?php
/**
 * @var $InstallData \SP\DataModel\InstallData
 */
?>
<main class="mdl-layout__content">
    <div id="actions" class="installer" align="center">
        <div id="page-title">
            <h1><?php printf(__('Instalación  %s'), $appVersion); ?></h1>
        </div>

        <?php include $this->includeTemplate('error-list'); ?>

        <form id="frmInstall" method="post" class="form-action" data-onsubmit="main/install">
            <input type="hidden" name="install" value="true"/>
            <input type="hidden" name="isAjax" value="1"/>

            <fieldset id="adminaccount">
                <legend><?php echo __('Admin de sysPass'); ?></legend>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="adminlogin" name="adminlogin" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="admin" maxlength="80" autocomplete="off" tabindex="0"
                           autofocus>
                    <label class="mdl-textfield__label"
                           for="adminlogin"><?php echo __('Usuario administrador de sysPass'); ?></label>
                </div>

                <div id="help-login"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-login">
                    <?php echo __('Login del usuario administrador de sysPass'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="adminpass" name="adminpass" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           value="" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="adminlogin"><?php echo __('Clave'); ?></label>
                </div>
            </fieldset>

            <fieldset id="masterpwd">
                <legend><?php echo __('Clave Maestra'); ?></legend>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="masterpassword" name="masterpassword" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           value="" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="masterpassword"><?php echo __('Clave Maestra'); ?></label>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="masterpasswordR" name="masterpasswordr" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="masterpasswordR"><?php echo __('Clave (repetir)'); ?></label>
                </div>
            </fieldset>

            <fieldset id='databaseField'>
                <legend><?php echo __('Configurar BBDD'), ' (MySQL)'; ?></legend>

                <input type='hidden' id='hasMySQL' value='true'/>
                <input type="hidden" id="dbtype" name="dbtype" value="mysql"/>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbuser" name="dbuser" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="root" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="dbuser"><?php echo __('Usuario acceso BBDD'); ?></label>
                </div>

                <div id="help-dblogin"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-dblogin">
                    <?php echo __('Usuario con permisos de administrador de MySQL'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbpass" name="dbpass" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                           value="" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="dbpass"><?php echo __('Clave acceso BBDD'); ?></label>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbname" name="dbname" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="syspass" autocomplete="off" autofocus
                           pattern="[0-9a-zA-Z$_-]+">
                    <label class="mdl-textfield__label"
                           for="dbuser"><?php echo __('Nombre BBDD para sysPass'); ?></label>
                </div>

                <div id="help-dbname"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-dbname">
                    <?php echo __('Nombre de la base de datos para sysPass'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbhost" name="dbhost" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="localhost" autocomplete="off" autofocus
                           pattern="([0-9a-zA-Z._-]+|([0-9]{1,3}.)+)(:[0-9]{1,5})?|unix:.*">
                    <label class="mdl-textfield__label"
                           for="dbhost"><?php echo __('Servidor BBDD para sysPass'); ?></label>
                </div>

                <div id="help-dbhost"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-dbhost">
                    <?php echo __('Nombre del servidor para instalar la base de datos de sysPass'); ?>
                </div>
            </fieldset>

            <fieldset id="site">
                <legend><?php echo __('General'); ?></legend>

                <div class="form-field">
                    <label for="sel-sitelang"><?php echo __('Idioma'); ?></label>
                    <select name="sitelang" id="sel-sitelang" size="1" class="select-box" title="<?php echo __('Idioma'); ?>">
                        <?php foreach ($langsAvailable as $langName => $langValue): ?>
                            <option value="<?php echo $langValue; ?>" <?php echo ($langBrowser === $langValue) ? 'selected="selected"' : ''; ?>><?php echo $langName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="hostingmode">
                        <?php echo __('Modo Hosting'); ?>
                        <div id="help-hostingmode"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                            <?php echo $icons->getIconHelp()->getIcon(); ?>
                        </div>

                        <div class="mdl-tooltip" for="help-hostingmode">
                            <?php echo __('No crea ni verifica los permisos del usuario sobre la BBDD'); ?>
                        </div>
                    </label>

                    <div>
                        <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="hostingmode">
                            <input type="checkbox" id="hostingmode" class="mdl-checkbox__input" name="hostingmode">
                        </label>
                    </div>
                </div>
            </fieldset>

            <div class="buttons">
                <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent" type="submit">
                    <?php echo __('Instalar'); ?>
                    <i class="material-icons"
                       title="<?php echo __('Instalar'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</main>