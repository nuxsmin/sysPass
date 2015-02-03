<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

$startTime = microtime();

$adminApp = ( isset($_SESSION["uisadminapp"]) && $_SESSION["uisadminapp"] == 1 ) ? "<span title=\""._('Admin Aplicación')."\">(A+)</span>" : "";
$userId = ( isset($_SESSION["uid"]) ) ? $_SESSION["uid"] : 0;
$userLogin = (isset($_SESSION["ulogin"]) && !empty($_SESSION["ulogin"])) ? strtoupper($_SESSION["ulogin"]) : '';
$userName = (isset($_SESSION["uname"]) && !empty($_SESSION["uname"])) ? $_SESSION["uname"] : strtoupper($userLogin);
$userGroup = ( isset($_SESSION["ugroupn"]) ) ? $_SESSION["ugroupn"] : '';

$strUser = "$userName ($userGroup) " . $adminApp;
$chpass = ( ! isset($_SESSION['uisldap']) || $_SESSION['uisldap'] == 0 ) ? '<img src="imgs/key.png" class="iconMini" title="' . _('Cambiar clave de usuario') . '" Onclick="usrUpdPass(' . $userId . ',\'' . $userLogin . '\')" />' : '';

?>

<div id="header">
    <div id="session" class="midround shadow">
        <?php echo $strUser . $chpass ?>
        <img src="imgs/exit.png" title="<?php echo _('Salir') ?>" OnClick="doLogout();" />
    </div>
</div>

<div id="actionsBar" class="round">
    <ul>
        <?php
        $actions = array(
            array('name' => 'accsearch', 'title' => _('Buscar'), 'img' => 'search.png', 'checkaccess' => 0),
            array('name' => 'accnew', 'title' => _('Nueva Cuenta'), 'img' => 'add.png', 'checkaccess' => 1),
            array('name' => 'usersmenu', 'title' => _('Gestión de Usuarios'), 'img' => 'users.png', 'checkaccess' => 1),
            array('name' => 'appmgmtmenu', 'title' => _('Gestión de Clientes y Categorías'), 'img' => 'appmgmt.png', 'checkaccess' => 1),
            array('name' => 'configmenu', 'title' => _('Configuración'), 'img' => 'config.png', 'checkaccess' => 1),
            array('name' => 'eventlog', 'title' => _('Registro de Eventos'), 'img' => 'log.png', 'checkaccess' => 1)
        );

        foreach ($actions as $action) {
            if ($action['checkaccess']) {
                if (!SP_ACL::checkUserAccess($action['name'])) {
                    continue;
                }
            }
            if ($action['name'] == 'eventlog' && !SP_Util::logIsEnabled()) {
                continue;
            }

            echo '<li class="round"><img src="' . SP_Init::$WEBROOT . '/imgs/' . $action['img'] . '" title="' . _($action['title']) . '" OnClick="doAction(\'' . $action['name'] . '\')" /></li>';
        }
        ?>
    </ul>
</div>
<div id="content"></div>
