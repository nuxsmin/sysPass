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

if ( SP_Common::parseParams('p', 'id', FALSE, TRUE) && SP_Common::parseParams('p', 'type', FALSE, TRUE) ) {
    $tplvars['itemid'] = SP_Common::parseParams('p', 'id', 0);
    $itemType = $tplvars['itemtype'] = SP_Common::parseParams('p', 'type', 0);
    $tplvars['active'] = SP_Common::parseParams('p', 'active', 0);
    $tplvars['view'] = SP_Common::parseParams('p', 'view', 0);
} else {
    return;
}

switch ($itemType) {
    case 1:
        $tplvars['header'] = _('Editar Usuario');
        $template = 'users';
        break;
    case 2:
        $tplvars['header'] = _('Nuevo Usuario');
        $template = 'users';
        break;
    case 3:
        $tplvars['header'] = _('Editar Grupo');
        $template = 'groups';
        break;
    case 4:
        $tplvars['header'] = _('Nuevo Grupo');
        $template = 'groups';
        break;
    case 5:
        $tplvars['header'] = _('Editar Perfil');
        $template = 'profiles';
        break;
    case 6:
        $tplvars['header'] = _('Nuevo Perfil');
        $template = 'profiles';
        break;
    default :
        break;
}

SP_Html::getTemplate($template, $tplvars);