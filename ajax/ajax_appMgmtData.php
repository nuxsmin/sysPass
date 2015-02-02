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

define('APP_ROOT', '..');
require_once APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'init.php';

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Util::logout();
}

if ( SP_Common::parseParams('p', 'id', false, true) && SP_Common::parseParams('p', 'type', false, true) ) {
    $tplvars['itemid'] = SP_Common::parseParams('p', 'id', 0);
    $itemType = $tplvars['itemtype'] = SP_Common::parseParams('p', 'type', 0);
    $tplvars['activeTab'] = SP_Common::parseParams('p', 'active', 0);
    $tplvars['view'] = SP_Common::parseParams('p', 'view', 0);
} else {
    return;
}

switch ($itemType) {
    case 1:
        $tplvars['header'] = _('Editar Usuario');
        $tplvars['onCloseAction'] = 'usersmenu';
        $template = 'users';
        break;
    case 2:
        $tplvars['header'] = _('Nuevo Usuario');
        $tplvars['onCloseAction'] = 'usersmenu';
        $template = 'users';
        break;
    case 3:
        $tplvars['header'] = _('Editar Grupo');
        $tplvars['onCloseAction'] = 'usersmenu';
        $template = 'groups';
        break;
    case 4:
        $tplvars['header'] = _('Nuevo Grupo');
        $tplvars['onCloseAction'] = 'usersmenu';
        $template = 'groups';
        break;
    case 5:
        $tplvars['header'] = _('Editar Perfil');
        $tplvars['onCloseAction'] = 'usersmenu';
        $template = 'profiles';
        break;
    case 6:
        $tplvars['header'] = _('Nuevo Perfil');
        $tplvars['onCloseAction'] = 'usersmenu';
        $template = 'profiles';
        break;
    case 7:
        $tplvars['header'] = _('Editar Cliente');
        $tplvars['onCloseAction'] = 'appmgmtmenu';
        $template = 'customers';
        break;
    case 8:
        $tplvars['header'] = _('Nuevo Cliente');
        $tplvars['onCloseAction'] = 'appmgmtmenu';
        $template = 'customers';
        break;
    case 9:
        $tplvars['header'] = _('Editar Categoría');
        $tplvars['onCloseAction'] = 'appmgmtmenu';
        $template = 'categories';
        break;
    case 10:
        $tplvars['header'] = _('Nueva Categoría');
        $tplvars['onCloseAction'] = 'appmgmtmenu';
        $template = 'categories';
        break;
    default :
        break;
}

SP_Html::getTemplate($template, $tplvars);