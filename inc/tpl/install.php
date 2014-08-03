<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
$modulesErrors = SP_Util::checkModules();
$versionErrors = SP_Util::checkPhpVersion();
$resInstall = array();
$isCompleted = 0;

if (isset($_POST['install']) AND $_POST['install'] == 'true') {
    $resInstall = SP_Installer::install($_POST);

    if (count($resInstall) == 0) {
        $resInstall[] = array('type' => 'ok',
            'description' => _('Instalación finalizada'),
            'hint' => _('Pulse <a href="index.php" title="Acceder">aquí</a> para acceder'));
        $isCompleted = 1;
    }
}
?>


<div id="actions" class="installer" align="center">
    <div id="logo">
        <img src="imgs/logo_full.png" alt="sysPass logo"/>
        <span ID="pageDesc"><?php echo _('Instalación ') . ' ' . SP_Util::getVersionString(); ?></span>
    </div>

    <form id="frmInstall" action="index.php" method="post">
        <input type="hidden" name="install" value="true"/>

        <?php
        $securityErrors = array();

        if (@file_exists(__FILE__ . "\0Nullbyte")) {
            $securityErrors[] = array('type' => 'warning',
                'description' => _('La version de PHP es vulnerable al ataque NULL Byte (CVE-2006-7243)'),
                'hint' => _('Actualice la versión de PHP para usar sysPass de forma segura'));
        }
        if (!SP_Util::secureRNG_available()) {
            $securityErrors[] = array('type' => 'warning',
                'description' => _('No se encuentra el generador de números aleatorios.'),
                'hint' => _('Sin esta función un atacante puede utilizar su cuenta al resetear la clave'));
        }

        $errors = array_merge($modulesErrors, $securityErrors, $resInstall);

        if (count($errors) > 0) {
            echo '<ul class="errors round">';

            foreach ($errors as $err) {
                if (is_array($err)) {
                    echo '<li class="err_' . $err["type"] . '">';
                    echo '<strong>' . $err['description'] . '</strong>';
                    echo ($err['hint']) ? '<p class="hint">' . $err['hint'] . '</p>' : '';
                    echo '</li>';
                }
            }
            echo '</ul>';
        }

        if ($isCompleted === 0):
        ?>
        <fieldset id="adminaccount">
            <legend><?php echo _('Crear cuenta de admin de sysPass'); ?></legend>
            <p>
                <img src="imgs/help.png" class="inputImg"
                     title="<?php echo _('Login del usuario administrador de sysPass'); ?>"/>
                <input type="text" name="adminlogin" id="adminlogin" title="<?php echo _('Usuario'); ?>"
                       placeholder="<?php echo _('Usuario'); ?>"
                       value="<?php echo SP_Util::init_var('adminlogin'); ?>" autocomplete="off" autofocus
                       required/>
            </p>

            <p>
                <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                     alt="<?php echo _('Mostrar Clave'); ?>"/>
                <input type="password" name="adminpass" id="adminpass"
                       title="<?php echo _('Clave'); ?>"
                       placeholder="<?php echo _('Clave'); ?>"
                       value="<?php echo SP_Util::init_var('adminpass'); ?>" autocomplete="off"
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
                       value="<?php echo SP_Util::init_var('masterpassword'); ?>" autocomplete="off"
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
                       value="<?php echo SP_Util::init_var('dbuser', 'root'); ?>" autocomplete=off" required/>
            </p>

            <p>
                <img class="showpass inputImg " src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                     alt="<?php echo _('Mostrar Clave'); ?>"/>
                <input type="password" name="dbpass" id="dbpass" title="<?php echo _('Clave BBDD'); ?>"
                       placeholder="<?php echo _('Clave BBDD'); ?>"
                       value="<?php echo SP_Util::init_var('dbpass'); ?>" required/>
            </p>

            <p>
                <img src="imgs/help.png" class="inputImg"
                     title="<?php echo _('Nombre de la base de datos para sysPass'); ?>"/>
                <input type="text" name="dbname" id="dbname" title="<?php echo _('Nombre BBDD'); ?>"
                       placeholder="<?php echo _('Nombre BBDD'); ?>"
                       value="<?php echo SP_Util::init_var('dbname', 'syspass'); ?>" autocomplete=off"
                       pattern="[0-9a-zA-Z$_-]+" required/>
            </p>

            <p>
                <img src="imgs/help.png" class="inputImg"
                     title="<?php echo _('Nombre del servidor de la base de datos de sysPass'); ?>"/>
                <input type="text" name="dbhost" id="dbhost" title="<?php echo _('Servidor BBDD'); ?>"
                       placeholder="<?php echo _('Servidor BBDD'); ?>"
                       value="<?php echo SP_Util::init_var('dbhost', 'localhost'); ?>" required/>
            </p>

            <br>

            <p>
                <img src="imgs/help.png" class="inputImg"
                     title="<?php echo _('No crea ni verifica los permisos del usuario sobre la BBDD'); ?>"/>
                <label
                    for="hostingmode"><?php echo (SP_Util::init_var('hostingmode')) ? _('Modo Hosting') . ' ON' : _('Modo Hosting') . ' OFF'; ?></label>
                <input type="checkbox" name="hostingmode"
                       id="hostingmode"
                       class="checkbox" <?php echo (SP_Util::init_var('hostingmode')) ? 'checked' : ''; ?> />
            </p>
        </fieldset>

        <div class="buttons"><input type="submit" class="button" value="<?php echo _('Instalar'); ?>"/></div>
    </form>
    <?php endif; ?>
</div>

<script>
    $('#frmInstall').find('.checkbox').button();
    $('#frmInstall').find('.ui-button').click(function () {
        // El cambio de clase se produce durante el evento de click
        // Si tiene la clase significa que el estado anterior era ON y ahora es OFF
        if ($(this).hasClass('ui-state-active')) {
            $(this).children().html('<?php echo _('Modo Hosting');?> OFF');
        } else {
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