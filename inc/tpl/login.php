<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

?>

    <div id="boxLogin" class="round shadow">
        <div id="boxLogo"><img id="imgLogo" src="imgs/logo.svg" title="sysPass"/></div>
        <div id="boxData">
            <form method="post" name="frmLogin" id="frmLogin" action="" OnSubmit="return doLogin();">
                <?php if (SP_Util::demoIsEnabled()): ?>
                    <input type="text" name="user" id="user" placeholder="<?php echo _('Usuario'); ?>" value=""
                           title="> demo <"/><br/>
                    <input type="password" name="pass" id="pass" placeholder="<?php echo _('Clave'); ?>" value=""
                           title="> syspass <"/><br/>
                    <span id="smpass" style="display: none"><input type="password" name="mpass" id="mpass"
                                                                   placeholder="<?php echo _('Clave Maestra'); ?>"
                                                                   value="" title="> 01234567890 <"
                                                                   disabled/><br/></span>
                <?php else: ?>
                    <input type="text" name="user" id="user" placeholder="<?php echo _('Usuario'); ?>" value=""/><br/>
                    <input type="password" name="pass" id="pass" placeholder="<?php echo _('Clave'); ?>" value=""
                           autocomplete="off"/><br/>
                    <span id="smpass" style="display: none"><input type="password" name="mpass" id="mpass"
                                                                   placeholder="<?php echo _('Clave Maestra'); ?>"
                                                                   value="" autocomplete="off" disabled/><br/></span>
                <?php endif; ?>
                <input type="image" id="btnLogin" src="imgs/login.png" title="<?php echo _('Acceder') ?>"/>
                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="isAjax" value="1"/>
                <?php if (count($_GET) > 0): ?>
                    <?php foreach ($_GET as $param => $value): ?>
                        <input type="hidden" name="g_<?php echo SP_Html::sanitize($param); ?>"
                               value="<?php echo SP_Html::sanitize($value); ?>"/>
                    <?php endforeach; ?>
                <?php endif; ?>
            </form>
        </div>
        <!-- Close boxData -->
        <?php if (SP_Util::mailIsEnabled()): ?>
            <div id="boxActions">
                <a href="index.php?a=passreset"><?php echo _('¿Olvidó su clave?'); ?></a>
            </div>
        <?php endif; ?>
    </div><!-- Close boxLogin -->

<?php if (SP_Common::parseParams('g', 'logout', false, true)): ?>
    <div id="boxLogout" class="round5"><?php echo _('Sesión finalizada'); ?></div>
    <script>$('#boxLogout').fadeOut(1500, function () {
            location.href = 'index.php';
        });</script>
<?php endif; ?>

<?php if (SP_Init::$UPDATED === true): ?>
    <div id="boxUpdated" class="round5"><?php echo _('Aplicación actualizada correctamente'); ?></div>
<?php endif; ?>

<?php
if (SP_Util::demoIsEnabled()) {
    $newFeatures = array(
        _('Nuevo interface de búsqueda con estilo de lista o tipo tarjeta'),
        _('Selección de grupos y usuarios de acceso a cuentas'),
        _('Drag&Drop para subida de archivos'),
        _('Copiar clave al portapapeles'),
        _('Historial de cuentas y restauración'),
        _('Nueva gestión de categorías y clientes'),
        _('Función de olvido de claves para usuarios'),
        _('Integración con Active Directory y LDAP mejorada'),
        _('Autentificación para notificaciones por correo'),
        _('Búsqueda global de cuentas para usuarios sin permisos'),
        _('Solicitudes de modificación de cuentas para usuarios sin permisos'),
        _('Importación de cuentas desde KeePass, KeePassX y CSV'),
        _('Función de copiar cuentas'),
        _('Optimización del código y mayor rapidez de carga'),
        _('Mejoras de seguridad en XSS e inyección SQL')
    );
    echo '<div id="whatsNewIcon">';
    echo '<img src="imgs/gearscolorful.png" title="' . _('Nuevas Características') . '" alt="' . _('Nuevas Características') . '" onclick="$(\'#whatsNew\').show(500);"/>';
    echo '<h2>' . _('Nuevas Características') . '</h2>';
    echo '</div>';

    echo '<div id="whatsNew" class="round5 shadow">';
    echo '<ul>';
    foreach ($newFeatures as $feature) {
        echo '<li>' . $feature . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
?>