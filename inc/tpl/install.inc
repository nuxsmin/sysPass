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
                <p>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Login del usuario administrador de sysPass'); ?>"/>
                    <input type="text" name="adminlogin" id="adminlogin" title="<?php echo _('Usuario'); ?>"
                           placeholder="<?php echo _('Usuario'); ?>"
                           value="<?php echo $adminlogin; ?>" autocomplete="off" autofocus
                           required/>
                </p>

                <p>
                    <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
                    <input type="password" name="adminpass" id="adminpass"
                           title="<?php echo _('Clave'); ?>"
                           placeholder="<?php echo _('Clave'); ?>"
                           value="<?php echo $adminpass; ?>" autocomplete="off"
                           onKeyUp="checkPassLevel(this.value,'adminaccount')" required/>
                <span class="passLevel passLevel-float fullround"
                      title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                </p>
            </fieldset>

            <fieldset id="masterpwd">
                <legend><?php echo _('Clave Maestra'); ?></legend>
                <p>
                    <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
                    <input type="password" name="masterpassword" id="masterpassword"
                           title="<?php echo _('Clave Maestra'); ?>  "
                           placeholder="<?php echo _('Clave Maestra'); ?>"
                           value="<?php echo $masterpassword; ?>" autocomplete="off"
                           onKeyUp="checkPassLevel(this.value,'masterpwd')" required/>
                <span class="passLevel passLevel-float fullround"
                      title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                </p>
            </fieldset>

            <fieldset id='databaseField'>
                <legend><?php echo _('Configurar BBDD') . " (MySQL)"; ?></legend>
                <input type='hidden' id='hasMySQL' value='true'/>
                <input type="hidden" id="dbtype" name="dbtype" value="mysql"/>

                <p>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Login de usuario con permisos de administrador de MySQL'); ?>"/>
                    <input type="text" name="dbuser" id="dbuser" title="<?php echo _('Usuario BBDD'); ?>"
                           placeholder="<?php echo _('Usuario BBDD'); ?>"
                           value="<?php echo $dbuser; ?>" autocomplete=off" required/>
                </p>

                <p>
                    <img class="showpass inputImg " src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
                    <input type="password" name="dbpass" id="dbpass" title="<?php echo _('Clave BBDD'); ?>"
                           placeholder="<?php echo _('Clave BBDD'); ?>"
                           value="<?php echo $dbpass; ?>" required/>
                </p>

                <p>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Nombre de la base de datos para sysPass'); ?>"/>
                    <input type="text" name="dbname" id="dbname" title="<?php echo _('Nombre BBDD'); ?>"
                           placeholder="<?php echo _('Nombre BBDD'); ?>"
                           value="<?php echo $dbname; ?>" autocomplete=off"
                           pattern="[0-9a-zA-Z$_-]+" required/>
                </p>

                <p>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('Nombre del servidor de la base de datos de sysPass'); ?>"/>
                    <input type="text" name="dbhost" id="dbhost" title="<?php echo _('Servidor BBDD'); ?>"
                           placeholder="<?php echo _('Servidor BBDD'); ?>"
                           value="<?php echo $dbhost; ?>" required/>
                </p>

                <br>

                <p>
                    <img src="imgs/help.png" class="inputImg"
                         title="<?php echo _('No crea ni verifica los permisos del usuario sobre la BBDD'); ?>"/>
                    <label
                        for="hostingmode"><?php echo ($hostingmode) ? _('Modo Hosting') . ' ON' : _('Modo Hosting') . ' OFF'; ?></label>
                    <input type="checkbox" name="hostingmode"
                           id="hostingmode"
                           class="checkbox" <?php echo ($hostingmode) ? 'checked' : ''; ?> />
                </p>
            </fieldset>

            <div class="buttons"><input type="submit" class="button" value="<?php echo _('Instalar'); ?>"/></div>
        </form>
    <?php endif; ?>
</div>

<script>
    var hostingmode = <?php echo ($hostingmode === true) ? 1 : 0; ?>;

    $('#frmInstall').find('.checkbox').button();
    $('#frmInstall').find('.ui-button').click(function () {
        if (hostingmode == 1) {
            hostingmode = 0;
            $(this).children().html('<?php echo _('Modo Hosting');?> OFF');
        } else {
            hostingmode = 1;
            $(this).children().html('<?php echo _('Modo Hosting');?> ON');
        }
    });
    $('.showpass').click(function () {
        var passInput = $(this).next();

        if (passInput.attr('type') == 'password') {
            passInput.get(0).type = 'text';
        } else {
            passInput.get(0).type = 'password';
        }
    })
</script>