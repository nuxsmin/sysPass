<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
$errors = SP_Util::checkModules();
$resInstall = array();
$isCompleted = 0;

if (isset($_POST['install']) AND $_POST['install'] == 'true') {
    $resInstall = SP_Installer::install($_POST);

    if (count($resInstall) == 0) {
        $resInstall[] = array('type' => 'ok',
            'description' => _('Instalación finalizada'),
            'hint' => 'Pulse <a href="index.php" title="Acceder"> aquí</a> para acceder');
        $isCompleted = 1;
    }
}
?>

<div id="install" align="center">
    <div id="logo">
        <img src="imgs/logo_full.png" alt="sysPass logo"/>
        <span ID="pageDesc"><?php echo _('Instalación ') . ' ' . SP_Util::getVersionString(); ?></span>
    </div>

    <form action="index.php" method="post">
        <input type="hidden" name="install" value="true" />

<?php
if (@file_exists(__FILE__ . "\0Nullbyte")) {
    $errors[] = array('type' => 'warning',
        'description' => _('La version de PHP es vulnerable al ataque NULL Byte (CVE-2006-7243)'),
        'hint' => _('Actualice la versión de PHP para usar sysPass de forma segura'));
}
if (!SP_Util::secureRNG_available()) {
    $errors[] = array('type' => 'warning',
        'description' => _('No se encuentra el generador de números aleatorios.'),
        'hint' => _('Sin esta función un atacante puede utilizar su cuenta al resetear la clave'));
}

$errors = array_merge($errors, $resInstall);

if (count($errors) > 0) {
    echo '<ul class="errors round">';

    foreach ($errors as $err) {
        if (is_array($err)) {
            echo '<li class="err_' . $err["type"] . '">';
            echo '<strong>' . $err['description'] . '</strong>';
            echo ( $err['hint'] ) ? '<p class="hint">' . $err['hint'] . '</p>' : '';
            echo '</li>';
        }
    }
    echo '</ul>';
}

if ($isCompleted == 0):
    ?>

            <fieldset id="adminaccount">
                <legend><?php echo _('Crear cuenta de admin'); ?></legend>
                <p>
                    <input type="text" name="adminlogin" id="adminlogin" placeholder="<?php echo _('Usuario'); ?>  " value="<?php echo SP_Util::init_var('adminlogin'); ?>" autocomplete="off" autofocus required />
                    <img class="svg" src="<?php //p(image_path('', 'actions/user.svg'));  ?>" alt="" />
                </p>
                <p>
                    <input type="password" name="adminpass" id="adminpass" placeholder="<?php echo _('Clave'); ?>" value="<?php echo SP_Util::init_var('adminpass'); ?>" required/>
                    <img class="svg" id="adminpass-icon" src="<?php //print_unescaped(image_path('', 'actions/password.svg'));  ?>" alt="" />
                </p>
            </fieldset>

            <fieldset id="masterpwd">
                <legend><?php echo _('Clave Maestra'); ?></legend>
                <p>
                    <input type="password" name="masterpassword" id="masterpassword" placeholder="<?php echo _('Clave Maestra'); ?>  " value="<?php echo SP_Util::init_var('masterpassword'); ?>" autocomplete="off" required />
                    <img class="svg" src="<?php //p(image_path('', 'actions/user.svg'));  ?>" alt="" />
                </p>
            </fieldset>    

            <fieldset id='databaseField'>
                <legend><?php echo _('Configurar BBDD') . " (MySQL)"; ?></legend>
                <input type='hidden' id='hasMySQL' value='true'/>
                <input type="hidden" id="dbtype" name="dbtype" value="mysql" />
                <p>
                    <input type="text" name="dbuser" id="dbuser" placeholder="<?php echo _('Usuario BBDD'); ?>" value="<?php echo SP_Util::init_var('dbuser', 'root'); ?>" autocomplete=off" required/>
                </p>
                <p>
                    <input type="password" name="dbpass" id="dbpass" placeholder="<?php echo _('Clave BBDD'); ?>" value="<?php echo SP_Util::init_var('dbpass'); ?>" required/>
                </p>
                <p>
                    <input type="text" name="dbname" id="dbname" placeholder="<?php echo _('Nombre BBDD'); ?>" value="<?php echo SP_Util::init_var('dbname', 'syspass'); ?>" autocomplete=off" pattern="[0-9a-zA-Z$_-]+" />
                </p>
                <p>
                    <input type="text" name="dbhost" id="dbhost" placeholder="<?php echo _('Servidor BBDD'); ?>" value="<?php echo SP_Util::init_var('dbhost', 'localhost'); ?>" />
                </p>
                <p>
                    <label for="hostingmode"><?php echo _('Modo Hosting'); ?></label>
                    <img src="imgs/help.png" class="iconMini" title="<?php echo _('No crea ni verifica los permisos del usuario sobre la BBDD'); ?>" />
                    <input type="checkbox" name="hostingmode" id="hostingmode" <?php echo SP_Util::init_var('hostingmode', ''); ?> />
                </p>
            </fieldset>

            <div class="buttons"><input type="submit" class="button" value="<?php echo _('Instalar'); ?>" /></div>
        </form>
<?php endif; ?>
</div>