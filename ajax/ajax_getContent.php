<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Controller\AccountController;
use SP\Controller\AccountSearchController;
use SP\Controller\ConfigController;
use SP\Controller\EventlogController;
use SP\Controller\ItemListController;
use SP\Controller\NoticesController;
use SP\Controller\UserPreferencesController;
use SP\Core\Acl\ActionsInterface;
use SP\Core\SessionFactory;
use SP\Core\Template;
use SP\Http\Request;
use SP\Http\Response;
use SP\Util\Util;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('GET');

/** @var \SP\Storage\Database $db */
$db = $dic->get(\SP\Storage\Database::class);
/** @var SessionFactory $session */
$session = $dic->get(SessionFactory::class);
/** @var \SP\Core\UI\Theme $theme */
$theme = $dic->get(\SP\Core\UI\Theme::class);

if (!Util::isLoggedIn($session)) {
    Util::logout();
}

Util::checkReload();

if (!Request::analyze('actionId', 0, true)) {
    Response::printHtmlError(__('Parámetros incorrectos'));
}

$actionId = Request::analyze('actionId', 0);
$itemId = Request::analyze('itemId', 0);

$UserData = SessionFactory::getUserData();

$Tpl = new Template();
$Tpl->assign('actionId', $actionId);
$Tpl->assign('id', $itemId);
$Tpl->assign('activeTabId', $itemId);
$Tpl->assign('queryTimeStart', microtime());
$Tpl->assign('userId', $UserData->getUserId());
$Tpl->assign('userGroupId', $UserData->getUserGroupId());
$Tpl->assign('userIsAdminApp', $UserData->isUserIsAdminApp());
$Tpl->assign('userIsAdminAcc', $UserData->isUserIsAdminAcc());
$Tpl->assign('themeUri', $theme->getThemeUri());

switch ($actionId) {
    case ActionsInterface::ACCOUNT_SEARCH:
        $Controller = new AccountSearchController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::ACCOUNT_CREATE:
    case ActionsInterface::ACCOUNT_COPY:
    case ActionsInterface::ACCOUNT_EDIT:
    case ActionsInterface::ACCOUNT_EDIT_PASS:
    case ActionsInterface::ACCOUNT_VIEW:
    case ActionsInterface::ACCOUNT_VIEW_HISTORY:
    case ActionsInterface::ACCOUNT_DELETE:
    case ActionsInterface::ACCOUNT_REQUEST:
        $Controller = new AccountController($Tpl, $itemId);
        $Controller->doAction($actionId);
        break;
    case ActionsInterface::ACCESS_MANAGE:
    case ActionsInterface::USER:
    case ActionsInterface::GROUP:
    case ActionsInterface::PROFILE:
    case ActionsInterface::APITOKEN:
    case ActionsInterface::PUBLICLINK:
        $Controller = new ItemListController($Tpl);
        $Controller->doAction(ItemListController::TYPE_ACCESSES);
        break;
    case ActionsInterface::ITEMS_MANAGE:
    case ActionsInterface::CATEGORY:
    case ActionsInterface::CLIENT:
    case ActionsInterface::CUSTOMFIELD:
    case ActionsInterface::FILE:
    case ActionsInterface::ACCOUNTMGR:
    case ActionsInterface::TAG:
        $Controller = new ItemListController($Tpl);
        $Controller->doAction(ItemListController::TYPE_ACCOUNTS);
        break;
    case ActionsInterface::CONFIG:
    case ActionsInterface::CONFIG_GENERAL:
    case ActionsInterface::WIKI_CONFIG:
    case ActionsInterface::LDAP_CONFIG:
    case ActionsInterface::MAIL_CONFIG:
    case ActionsInterface::ENCRYPTION_CONFIG:
    case ActionsInterface::ENCRYPTION_TEMPPASS:
    case ActionsInterface::BACKUP_CONFIG:
    case ActionsInterface::EXPORT_CONFIG:
    case ActionsInterface::IMPORT_CONFIG:
        $Controller = new ConfigController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::EVENTLOG:
        $Controller = new EventlogController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::PREFERENCE:
    case ActionsInterface::PREFERENCE_GENERAL:
    case ActionsInterface::PREFERENCE_SECURITY:
        $Controller = new UserPreferencesController($Tpl);
        $Controller->doAction();
        break;
    case ActionsInterface::NOTICE:
    case ActionsInterface::NOTICE_USER:
        $Controller = new NoticesController($Tpl);
        $Controller->doAction();
        break;
}

/** @var \SP\Config\ConfigData $ConfigData */
$ConfigData = $dic->get(\SP\Config\ConfigData::class);

// Se comprueba si se debe de mostrar la vista de depuración
if ($UserData->isUserIsAdminApp() && $ConfigData->isDebug()) {
    $Controller->getDebug();
}

$Controller->view();