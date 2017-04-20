<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

use SP\Config\Config;
use SP\Controller\AccountController;
use SP\Controller\AccountSearchController;
use SP\Controller\ConfigController;
use SP\Controller\EventlogController;
use SP\Controller\ItemListController;
use SP\Controller\NoticesController;
use SP\Controller\UserPreferencesController;
use SP\Core\ActionsInterface;
use SP\Core\DiFactory;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\Template;
use SP\Http\Request;
use SP\Http\Response;
use SP\Util\Util;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('GET');

if (!Init::isLoggedIn()) {
    Util::logout();
}

Util::checkReload();

if (!Request::analyze('actionId', 0, true)) {
    Response::printHtmlError(__('Parámetros incorrectos'));
}

$actionId = Request::analyze('actionId', 0);
$itemId = Request::analyze('itemId', 0);

$Tpl = new Template();
$Tpl->assign('actionId', $actionId);
$Tpl->assign('id', $itemId);
$Tpl->assign('activeTabId', $itemId);
$Tpl->assign('queryTimeStart', microtime());
$Tpl->assign('userId', Session::getUserData()->getUserId());
$Tpl->assign('userGroupId', Session::getUserData()->getUserGroupId());
$Tpl->assign('userIsAdminApp', Session::getUserData()->isUserIsAdminApp());
$Tpl->assign('userIsAdminAcc', Session::getUserData()->isUserIsAdminAcc());
$Tpl->assign('themeUri', DiFactory::getTheme()->getThemeUri());

switch ($actionId) {
    case ActionsInterface::ACTION_ACC_SEARCH:
        $Controller = new AccountSearchController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::ACTION_ACC_NEW:
    case ActionsInterface::ACTION_ACC_COPY:
    case ActionsInterface::ACTION_ACC_EDIT:
    case ActionsInterface::ACTION_ACC_EDIT_PASS:
    case ActionsInterface::ACTION_ACC_VIEW:
    case ActionsInterface::ACTION_ACC_VIEW_HISTORY:
    case ActionsInterface::ACTION_ACC_DELETE:
    case ActionsInterface::ACTION_ACC_REQUEST:
        $Controller = new AccountController($Tpl, $itemId);
        $Controller->doAction($actionId);
        break;
    case ActionsInterface::ACTION_USR:
    case ActionsInterface::ACTION_USR_USERS:
    case ActionsInterface::ACTION_USR_GROUPS:
    case ActionsInterface::ACTION_USR_PROFILES:
    case ActionsInterface::ACTION_MGM_APITOKENS:
    case ActionsInterface::ACTION_MGM_PUBLICLINKS:
        $Controller = new ItemListController($Tpl);
        $Controller->doAction(ItemListController::TYPE_ACCESSES);
        break;
    case ActionsInterface::ACTION_MGM:
    case ActionsInterface::ACTION_MGM_CATEGORIES:
    case ActionsInterface::ACTION_MGM_CUSTOMERS:
    case ActionsInterface::ACTION_MGM_CUSTOMFIELDS:
    case ActionsInterface::ACTION_MGM_FILES:
    case ActionsInterface::ACTION_MGM_ACCOUNTS:
    case ActionsInterface::ACTION_MGM_TAGS:
        $Controller = new ItemListController($Tpl);
        $Controller->doAction(ItemListController::TYPE_ACCOUNTS);
        break;
    case ActionsInterface::ACTION_CFG:
    case ActionsInterface::ACTION_CFG_GENERAL:
    case ActionsInterface::ACTION_CFG_WIKI:
    case ActionsInterface::ACTION_CFG_LDAP:
    case ActionsInterface::ACTION_CFG_MAIL:
    case ActionsInterface::ACTION_CFG_ENCRYPTION:
    case ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS:
    case ActionsInterface::ACTION_CFG_BACKUP:
    case ActionsInterface::ACTION_CFG_EXPORT:
    case ActionsInterface::ACTION_CFG_IMPORT:
        $Controller = new ConfigController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::ACTION_EVL:
        $Controller = new EventlogController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::ACTION_USR_PREFERENCES:
    case ActionsInterface::ACTION_USR_PREFERENCES_GENERAL:
    case ActionsInterface::ACTION_USR_PREFERENCES_SECURITY:
        $Controller = new UserPreferencesController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::ACTION_NOT:
    case ActionsInterface::ACTION_NOT_USER:
        $Controller = new NoticesController($Tpl);
        $Controller->doAction();
        break;
}

// Se comprueba si se debe de mostrar la vista de depuración
if (Session::getUserData()->isUserIsAdminApp() && Config::getConfig()->isDebug()) {
    $Controller->getDebug();
}

$Controller->view();