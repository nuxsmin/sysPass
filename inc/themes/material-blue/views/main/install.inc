<?php
/**
 * @var $InstallData \SP\DataModel\InstallData
 */
?>
<main class="mdl-layout__content">
    <div id="actions" class="installer" align="center">
        <div id="page-title">
            <h1><?php printf(_('Instalación  %s'), $appVersion); ?></h1>
        </div>

        <?php include $this->includeTemplate('error-list'); ?>

        <form id="frmInstall" method="post" class="form-action" data-onsubmit="main/install">
            <input type="hidden" name="install" value="true"/>
            <input type="hidden" name="isAjax" value="1"/>

            <fieldset id="adminaccount">
                <legend><?php echo _('Admin de sysPass'); ?></legend>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="adminlogin" name="adminlogin" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="admin" maxlength="80" autocomplete="off" tabindex="0"
                           autofocus>
                    <label class="mdl-textfield__label"
                           for="adminlogin"><?php echo _('Usuario administrador de sysPass'); ?></label>
                </div>

                <div id="help-login"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-login">
                    <?php echo _('Login del usuario administrador de sysPass'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="adminpass" name="adminpass" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           value="" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="adminlogin"><?php echo _('Clave'); ?></label>
                </div>
            </fieldset>

            <fieldset id="masterpwd">
                <legend><?php echo _('Clave Maestra'); ?></legend>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="masterpassword" name="masterpassword" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           value="" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="masterpassword"><?php echo _('Clave Maestra'); ?></label>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="masterpasswordR" name="masterpasswordr" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="masterpasswordR"><?php echo _('Clave (repetir)'); ?></label>
                </div>
            </fieldset>

            <fieldset id='databaseField'>
                <legend><?php echo _('Configurar BBDD'), ' (MySQL)'; ?></legend>

                <input type='hidden' id='hasMySQL' value='true'/>
                <input type="hidden" id="dbtype" name="dbtype" value="mysql"/>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbuser" name="dbuser" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="root" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="dbuser"><?php echo _('Usuario acceso BBDD'); ?></label>
                </div>

                <div id="help-dblogin"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-dblogin">
                    <?php echo _('Usuario con permisos de administrador de MySQL'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbpass" name="dbpass" type="password" required
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                           value="" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="dbpass"><?php echo _('Clave acceso BBDD'); ?></label>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbname" name="dbname" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="syspass" autocomplete="off" autofocus
                           pattern="[0-9a-zA-Z$_-]+">
                    <label class="mdl-textfield__label"
                           for="dbuser"><?php echo _('Nombre BBDD para sysPass'); ?></label>
                </div>

                <div id="help-dbname"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-dbname">
                    <?php echo _('Nombre de la base de datos para sysPass'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbhost" name="dbhost" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="localhost" autocomplete="off" autofocus
                           pattern="[0-9a-zA-Z._-]+|([0-9]{1,3}.)+">
                    <label class="mdl-textfield__label"
                           for="dbhost"><?php echo _('Servidor BBDD para sysPass'); ?></label>
                </div>

                <div id="help-dbhost"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-dbhost">
                    <?php echo _('Nombre del servidor para instalar la base de datos de sysPass'); ?>
                </div>
            </fieldset>

            <fieldset id="site">
                <legend><?php echo _('General'); ?></legend>

                <div class="form-field">
                    <label for="sel-sitelang"><?php echo _('Idioma'); ?></label>
                    <select name="sitelang" id="sel-sitelang" size="1" class="select-box" title="<?php echo _('Idioma'); ?>">
                        <?php foreach ($langsAvailable as $langName => $langValue): ?>
                            <option value="<?php echo $langValue; ?>" <?php echo ($langBrowser === $langValue) ? 'selected="selected"' : ''; ?>><?php echo $langName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="hostingmode">
                        <?php echo _('Modo Hosting'); ?>
                        <div id="help-hostingmode"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                            <?php echo $icons->getIconHelp()->getIcon(); ?>
                        </div>

                        <div class="mdl-tooltip" for="help-hostingmode">
                            <?php echo _('No crea ni verifica los permisos del usuario sobre la BBDD'); ?>
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
                    <?php echo _('Instalar'); ?>
                    <i class="material-icons"
                       title="<?php echo _('Instalar'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</main>