<div id="actions" class="installer" align="center">
    <div id="logo">
        <img src="imgs/logo_full.png" alt="sysPass logo"/>
        <span ID="pageDesc"><?php echo _('Instalación ') . ' ' . $version; ?></span>
    </div>

    <?php if (count($errors) > 0): ?>
        <ul class="errors round">
            <?php foreach ($errors as $err): ?>
                <?php if (is_array($err)): ?>
                    <li class="err_<?php echo \SP\SPException::getExceptionTypeName($err['type']); ?>">
                        <strong><?php echo $err['description']; ?></strong>
                        <?php echo (isset($err['hint'])) ? '<p class="hint">' . $err['hint'] . '</p>' : ''; ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($isCompleted === false): ?>
        <form id="frmInstall" action="index.php" method="post">
            <input type="hidden" name="install" value="true"/>

            <fieldset id="adminaccount">
                <legend><?php echo _('Crear cuenta de admin de sysPass'); ?></legend>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="adminlogin" name="adminlogin" type="text" required class="mdl-textfield__input blue100"
                           value="<?php echo $adminlogin; ?>" maxlength="80" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="adminlogin"><?php echo _('Usuario administrador de sysPass'); ?></label>
                </div>

                <div id="help-login" class="icon material-icons blue80">help_outline</div>
                <div class="mdl-tooltip" for="help-login">
                    <?php echo _('Login del usuario administrador de sysPass'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="adminpass" name="adminpass" type="password" required class="mdl-textfield__input blue100"
                           value="<?php echo $adminpass; ?>" maxlength="255" autocomplete="off"
                           onKeyUp="checkPassLevel(this.value,'adminaccount')">
                    <label class="mdl-textfield__label"
                           for="adminlogin"><?php echo _('Clave'); ?></label>
                </div>
                <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                <i id="passGen" class="material-icons" title="<?php echo _('Generar clave aleatoria'); ?>">settings</i>
                <span class="passLevel passLevel-float fullround"
                      title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>

            </fieldset>

            <fieldset id="masterpwd">
                <legend><?php echo _('Clave Maestra'); ?></legend>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="masterpassword" name="masterpassword" type="password" required
                           class="mdl-textfield__input blue100"
                           value="<?php echo $masterpassword; ?>" maxlength="255" autocomplete="off"
                           onKeyUp="checkPassLevel(this.value,'masterpwd')">
                    <label class="mdl-textfield__label"
                           for="masterpassword"><?php echo _('Clave Maestra'); ?></label>
                </div>
                <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                <i id="passGen" class="material-icons" title="<?php echo _('Generar clave aleatoria'); ?>">settings</i>
                <span class="passLevel passLevel-float fullround"
                      title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="masterpasswordr" name="masterpasswordr" type="password" required
                           class="mdl-textfield__input blue100"
                           value="<?php echo $masterpasswordr; ?>" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="masterpassword"><?php echo _('Clave (repetir)'); ?></label>
                </div>
            </fieldset>

            <fieldset id='databaseField'>
                <legend><?php echo _('Configurar BBDD') . " (MySQL)"; ?></legend>

                <input type='hidden' id='hasMySQL' value='true'/>
                <input type="hidden" id="dbtype" name="dbtype" value="mysql"/>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbuser" name="dbuser" type="text" required class="mdl-textfield__input blue100"
                           value="<?php echo $dbuser; ?>" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="dbuser"><?php echo _('Usuario acceso BBDD'); ?></label>
                </div>

                <div id="help-dblogin" class="icon material-icons blue80">help_outline</div>
                <div class="mdl-tooltip" for="help-dblogin">
                    <?php echo _('Usuario con permisos de administrador de MySQL'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbpass" name="dbpass" type="password" required class="mdl-textfield__input blue100"
                           value="<?php echo $dbpass; ?>" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="dbpass"><?php echo _('Clave acceso BBDD'); ?></label>
                </div>
                <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbname" name="dbname" type="text" required class="mdl-textfield__input blue100"
                           value="<?php echo $dbname; ?>" autocomplete="off" autofocus pattern="[0-9a-zA-Z$_-]+">
                    <label class="mdl-textfield__label"
                           for="dbuser"><?php echo _('Nombre BBDD para sysPass'); ?></label>
                </div>

                <div id="help-dbname" class="icon material-icons blue80">help_outline</div>
                <div class="mdl-tooltip" for="help-dbname">
                    <?php echo _('Nombre de la base de datos para sysPass'); ?>
                </div>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dbhost" name="dbhost" type="text" required class="mdl-textfield__input blue100"
                           value="<?php echo $dbhost; ?>" autocomplete="off" autofocus pattern="[0-9a-zA-Z$_-]+">
                    <label class="mdl-textfield__label"
                           for="dbhost"><?php echo _('Servidor BBDD para sysPass'); ?></label>
                </div>

                <div id="help-dbhost" class="icon material-icons blue80">help_outline</div>
                <div class="mdl-tooltip" for="help-dbhost">
                    <?php echo _('Nombre del servidor para instalar la base de datos de sysPass'); ?>
                </div>

                <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="hostingmode">
                    <input type="checkbox" id="hostingmode" class="mdl-switch__input blue100"
                           name="hostingmode" <?php echo ($hostingmode) ? 'checked' : ''; ?>/>
                    <span class="mdl-switch__label"><?php echo _('Modo Hosting'); ?>
                        &nbsp;
                        <div id="help-hostingmode" class="icon material-icons blue80">help_outline</div>
                    </span>
                </label>

                <div class="mdl-tooltip" for="help-hostingmode">
                    <?php echo _('No crea ni verifica los permisos del usuario sobre la BBDD'); ?>
                </div>

            </fieldset>

            <div class="buttons">
                <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php echo _('Instalar'); ?>
                    <i class="material-icons" title="<?php echo _('Instalar'); ?>">play_circle_filled</i>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
<script>
    $(function(){
       $('#btnInstall').click(function(){
            $('#frmInstall').submit();
       });
    });
</script>