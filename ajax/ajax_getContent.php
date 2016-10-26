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

use SP\Config\Config;
use SP\Controller\AccountController;
use SP\Controller\AccountSearchController;
use SP\Core\ActionsInterface;
use SP\Core\DiFactory;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\Template;
use SP\Http\Request;
use SP\Http\Response;
use SP\Util\Checks;
use SP\Util\Util;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Util::logout();
}

Util::checkReload();

if (!Request::analyze('actionId', 0, true)) {
    Response::printHtmlError(_('Parámetros incorrectos'));
}

$actionId = Request::analyze('actionId', 0);
$itemId = Request::analyze('itemId', 0);

$Tpl = new Template();
$Tpl->assign('actionId', $actionId);
$Tpl->assign('id', $itemId);
$Tpl->assign('activeTabId', $itemId);
$Tpl->assign('lastAccountId', Session::getLastAcountId());
$Tpl->assign('queryTimeStart', microtime());
$Tpl->assign('userId', Session::getUserId());
$Tpl->assign('userGroupId', Session::getUserGroupId());
$Tpl->assign('userIsAdminApp', Session::getUserIsAdminApp());
$Tpl->assign('userIsAdminAcc', Session::getUserIsAdminAcc());
$Tpl->assign('themeUri', DiFactory::getTheme()->getThemeUri());

switch ($actionId) {
    case ActionsInterface::ACTION_ACC_SEARCH:
        $_SESSION['actionsPath'] = array(ActionsInterface::ACTION_ACC_SEARCH);

        $Controller = new AccountSearchController($Tpl);
        $Controller->getSearchBox();
        $Controller->getSearch();
        break;
    case ActionsInterface::ACTION_ACC_NEW:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getNewAccount();
        break;
    case ActionsInterface::ACTION_ACC_COPY:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getCopyAccount();
        break;
    case ActionsInterface::ACTION_ACC_EDIT:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getEditAccount();
        break;
    case ActionsInterface::ACTION_ACC_EDIT_PASS:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getEditPassAccount();
        break;
    case ActionsInterface::ACTION_ACC_VIEW:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getViewAccount();
        break;
    case ActionsInterface::ACTION_ACC_VIEW_HISTORY:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getViewHistoryAccount();
        break;
    case ActionsInterface::ACTION_ACC_DELETE:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getDeleteAccount();
        break;
    case ActionsInterface::ACTION_ACC_REQUEST:
        $Controller = new AccountController($Tpl, null, $itemId);
        $Controller->getRequestAccountAccess();
        break;
    case ActionsInterface::ACTION_USR:
    case ActionsInterface::ACTION_USR_USERS:
    case ActionsInterface::ACTION_USR_GROUPS:
    case ActionsInterface::ACTION_USR_PROFILES:
    case ActionsInterface::ACTION_MGM_APITOKENS:
    case ActionsInterface::ACTION_MGM_PUBLICLINKS:
        $Controller = new \SP\Controller\AccItemsController($Tpl);
        $Controller->useTabs();
        $Controller->getUsersList();
        $Controller->getGroupsList();
        $Controller->getProfilesList();
        $Controller->getAPITokensList();
        if (Checks::publicLinksIsEnabled()) {
            $Controller->getPublicLinksList();
        }
        break;
    case ActionsInterface::ACTION_MGM:
    case ActionsInterface::ACTION_MGM_CATEGORIES:
    case ActionsInterface::ACTION_MGM_CUSTOMERS:
    case ActionsInterface::ACTION_MGM_CUSTOMFIELDS:
    case ActionsInterface::ACTION_MGM_FILES:
    case ActionsInterface::ACTION_MGM_ACCOUNTS:
    case ActionsInterface::ACTION_MGM_TAGS:
        $Controller = new \SP\Controller\AppItemsController($Tpl);
        $Controller->useTabs();
        $Controller->getCategories();
        $Controller->getCustomers();
        $Controller->getCustomFields();
        $Controller->getFiles();
        $Controller->getAccounts();
        $Controller->getTags();
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
        $Tpl->addTemplate('tabs-start', 'common');

        $Controller = new \SP\Controller\ConfigController($Tpl);
        $Controller->getGeneralTab();
        $Controller->getWikiTab();
        $Controller->getLdapTab();
        $Controller->getMailTab();
        $Controller->getEncryptionTab();
        $Controller->getBackupTab();
        $Controller->getImportTab();
        $Controller->getInfoTab();

        $Tpl->addTemplate('tabs-end', 'common');
        break;
    case ActionsInterface::ACTION_EVL:
        $Controller = new \SP\Controller\EventlogController($Tpl);
        $Controller->getEventlog();
        break;
    case ActionsInterface::ACTION_USR_PREFERENCES:
    case ActionsInterface::ACTION_USR_PREFERENCES_GENERAL:
    case ActionsInterface::ACTION_USR_PREFERENCES_SECURITY:
        $Tpl->addTemplate('tabs-start', 'common');

        $Controller = new \SP\Controller\UserPreferencesController($Tpl);
        $Controller->getPreferencesTab();
        $Controller->getSecurityTab();

        $Tpl->addTemplate('tabs-end', 'common');
        break;
}

// Se comprueba si se debe de mostrar la vista de depuración
if (Session::getUserIsAdminApp() && Config::getConfig()->isDebug()) {
    $Controller->getDebug();
}

$Tpl->addTemplate('js-common', 'common');
$Controller->view();