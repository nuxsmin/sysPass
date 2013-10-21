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

define('APP_ROOT', '..');
include_once (APP_ROOT."/inc/init.php");

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Util::logout();
}

if ( isset($_POST["id"]) && isset($_POST["type"])) {
    $tplvars['itemid'] = (int)$_POST["id"];
    $itemType = $tplvars['itemtype'] = (int)$_POST["type"];
    $tplvars['active'] = (int)$_POST["active"];
} else {
    return;
}

switch ($itemType) {
    case 1:
        $tplvars['header'] = _('Editar Usuario');
        break;
    case 2:
        $tplvars['header'] = _('Nuevo Usuario');
        break;
    case 3:
        $tplvars['header'] = _('Editar Grupo');
        break;
    case 4:
        $tplvars['header'] = _('Nuevo Grupo');
        break;
    case 5:
        $tplvars['header'] = _('Editar Perfil');
        break;
    case 6:
        $tplvars['header'] = _('Nuevo Perfil');
        break;
    default :
        break;
}

if ($itemType == 1 || $itemType == 2) {
    SP_Html::getTemplate('users', $tplvars);
} elseif ($itemType == 3 || $itemType == 4) {
    SP_Html::getTemplate('groups', $tplvars);
} elseif ($itemType == 5 || $itemType == 6) {
    SP_Html::getTemplate('profiles', $tplvars);
}