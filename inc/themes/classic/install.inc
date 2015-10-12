<div id="actions" class="installer" align="center">
    <div id="logo">
        <img src="<?php echo $logo; ?>" alt="sysPass logo"/>
        <span ID="pageDesc"><?php echo _('Instalación ') . ' ' . $version; ?></span>
    </div>

    <?php if (count($errors) > 0): ?>
        <ul class="errors round">
            <?php foreach ($errors as $err): ?>
                <?php if (is_array($err)): ?>
                    <li class="err_<?php echo \SP\Core\SPException::getExceptionTypeName($err['type']); ?>">
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
                <p>
                    <input type="text" name="adminlogin" id="adminlogin" title="<?php echo _('Usuario'); ?>"
                           placeholder="<?php echo _('Usuario'); ?>"
                           value="<?php echo $adminlogin; ?>" autocomplete="off" autofocus
                           required/>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Login del usuario administrador de sysPass'); ?>"/>
                </p>

                <p>
                    <input type="password" name="adminpass" id="adminpass"
                           class="passwordfield__input"
                           title="<?php echo _('Clave'); ?>"
                           placeholder="<?php echo _('Clave'); ?>"
                           value="<?php echo $adminpass; ?>" autocomplete="off"
                           onKeyUp="checkPassLevel(this.value,'adminaccount')" required/>
                </p>
            </fieldset>

            <fieldset id="masterpwd">
                <legend><?php echo _('Clave Maestra'); ?></legend>
                <p>
                    <input type="password" name="masterpassword" id="masterpassword"
                           class="passwordfield__input"
                           title="<?php echo _('Clave Maestra'); ?>  "
                           placeholder="<?php echo _('Clave Maestra'); ?>"
                           value="<?php echo $masterpassword; ?>" autocomplete="off"
                           onKeyUp="checkPassLevel(this.value,'masterpwd')" required/>
                </p>
            </fieldset>

            <fieldset id='databaseField'>
                <legend><?php echo _('Configurar BBDD') . " (MySQL)"; ?></legend>
                <input type='hidden' id='hasMySQL' value='true'/>
                <input type="hidden" id="dbtype" name="dbtype" value="mysql"/>

                <p>
                    <input type="text" name="dbuser" id="dbuser" title="<?php echo _('Usuario BBDD'); ?>"
                           placeholder="<?php echo _('Usuario BBDD'); ?>"
                           value="<?php echo $dbuser; ?>" autocomplete=off" required/>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Login de usuario con permisos de administrador de MySQL'); ?>"/>
                </p>

                <p>
                    <input type="password" name="dbpass" id="dbpass" title="<?php echo _('Clave BBDD'); ?>"
                           class="passwordfield__input-show"
                           placeholder="<?php echo _('Clave BBDD'); ?>"
                           value="<?php echo $dbpass; ?>" required/>
                </p>

                <p>
                    <input type="text" name="dbname" id="dbname" title="<?php echo _('Nombre BBDD'); ?>"
                           placeholder="<?php echo _('Nombre BBDD'); ?>"
                           value="<?php echo $dbname; ?>" autocomplete=off"
                           pattern="[0-9a-zA-Z$_-]+" required/>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Nombre de la base de datos para sysPass'); ?>"/>
                </p>

                <p>
                    <input type="text" name="dbhost" id="dbhost" title="<?php echo _('Servidor BBDD'); ?>"
                           placeholder="<?php echo _('Servidor BBDD'); ?>"
                           value="<?php echo $dbhost; ?>" required/>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Nombre del servidor de la base de datos de sysPass'); ?>"/>
                </p>

                <br>

                <p>
                    <span class="option-text"><?php echo _('Modo Hosting'); ?></span>
                    <label
                        for="hostingmode"><?php echo ($hostingmode) ? _('SI') : _('NO'); ?></label>
                    <input type="checkbox" name="hostingmode"
                           id="hostingmode"
                           class="checkbox" <?php echo ($hostingmode) ? 'checked' : ''; ?>/>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('No crea ni verifica los permisos del usuario sobre la BBDD'); ?>"/>
                </p>
            </fieldset>

            <div class="buttons"><input type="submit" class="button" value="<?php echo _('Instalar'); ?>"/></div>
        </form>
    <?php endif; ?>
</div>
<script>
    window.onload = function () {
        sysPassUtil.passwordDetect();

        sysPassUtil.Common.checkboxDetect('#actions');
    };
</script>