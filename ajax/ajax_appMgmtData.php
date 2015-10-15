<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Controller\AccountsMgmtC;
use SP\Controller\UsersMgmtC;
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\Template;
use SP\Http\Request;
use SP\Util\Util;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Util::logout();
}

if (!Request::analyze('itemId', false, true)
    || !Request::analyze('actionId', false, true)
) {
    exit();
}

$actionId = Request::analyze('actionId', 0);

$Tpl = new Template();
$Tpl->assign('itemId', Request::analyze('itemId', 0));
$Tpl->assign('activeTab', Request::analyze('activeTab', 0));
$Tpl->assign('actionId', $actionId);
$Tpl->assign('isView', false);

switch ($actionId) {
    case ActionsInterface::ACTION_USR_USERS_VIEW:
        $Tpl->assign('header', _('Ver Usuario'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Tpl->assign('isView', true);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getUser();
        break;
    case ActionsInterface::ACTION_USR_USERS_EDIT:
        $Tpl->assign('header', _('Editar Usuario'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getUser();
        break;
    case ActionsInterface::ACTION_USR_USERS_NEW:
        $Tpl->assign('header', _('Nuevo Usuario'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getUser();
        break;
    case ActionsInterface::ACTION_USR_GROUPS_VIEW:
        $Tpl->assign('header', _('Ver Grupo'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Tpl->assign('isView', true);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getGroup();
        break;
    case ActionsInterface::ACTION_USR_GROUPS_EDIT:
        $Tpl->assign('header', _('Editar Grupo'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getGroup();
        break;
    case ActionsInterface::ACTION_USR_GROUPS_NEW:
        $Tpl->assign('header', _('Nuevo Grupo'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getGroup();
        break;
    case ActionsInterface::ACTION_USR_PROFILES_VIEW:
        $Tpl->assign('header', _('Ver Perfil'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Tpl->assign('isView', true);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getProfile();
        break;
    case ActionsInterface::ACTION_USR_PROFILES_EDIT:
        $Tpl->assign('header', _('Editar Perfil'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getProfile();
        break;
    case ActionsInterface::ACTION_USR_PROFILES_NEW:
        $Tpl->assign('header', _('Nuevo Perfil'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getProfile();
        break;
    case ActionsInterface::ACTION_MGM_CUSTOMERS_VIEW:
        $Tpl->assign('header', _('Ver Cliente'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Tpl->assign('isView', true);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCustomer();
        break;
    case ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT:
        $Tpl->assign('header', _('Editar Cliente'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCustomer();
        break;
    case ActionsInterface::ACTION_MGM_CUSTOMERS_NEW:
        $Tpl->assign('header', _('Nuevo Cliente'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCustomer();
        break;
    case ActionsInterface::ACTION_MGM_CATEGORIES_VIEW:
        $Tpl->assign('header', _('Ver Categoría'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Tpl->assign('isView', true);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCategory();
        break;
    case ActionsInterface::ACTION_MGM_CATEGORIES_EDIT:
        $Tpl->assign('header', _('Editar Categoría'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCategory();
        break;
    case ActionsInterface::ACTION_MGM_CATEGORIES_NEW:
        $Tpl->assign('header', _('Nueva Categoría'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCategory();
        break;
    case ActionsInterface::ACTION_MGM_APITOKENS_VIEW:
        $Tpl->assign('header', _('Ver Autorización'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Tpl->assign('isView', true);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getToken();
        break;
    case ActionsInterface::ACTION_MGM_APITOKENS_NEW:
        $Tpl->assign('header', _('Nueva Autorización'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getToken();
        break;
    case ActionsInterface::ACTION_MGM_APITOKENS_EDIT:
        $Tpl->assign('header', _('Editar Autorización'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_USR);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getToken();
        break;
    case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW:
        $Tpl->assign('header', _('Nuevo Campo'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCustomField();
        break;
    case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT:
        $Tpl->assign('header', _('Editar Campo'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM);
        $Controller = new AccountsMgmtC($Tpl);
        $Controller->getCustomField();
        break;
    case ActionsInterface::ACTION_MGM_PUBLICLINKS_VIEW:
        $Tpl->assign('header', _('Ver Enlace Público'));
        $Tpl->assign('onCloseAction', ActionsInterface::ACTION_MGM_PUBLICLINKS);
        $Tpl->assign('isView', true);
        $Controller = new UsersMgmtC($Tpl);
        $Controller->getPublicLink();
        break;
    default :
        exit();
        break;
}

$Controller->view();