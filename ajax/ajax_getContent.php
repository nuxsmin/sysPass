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
use SP\Controller\AccountC;
use SP\Controller\SearchC;
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\Template;
use SP\Core\Themes;
use SP\Http\Request;
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
    die('<div class="error">' . _('Parámetros incorrectos') . '</DIV>');
}

$actionId = Request::analyze('actionId');
$itemId = Request::analyze('itemId', 0);
$lastAction = Request::analyze('lastAction', ActionsInterface::ACTION_ACC_SEARCH);

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
$Tpl->assign('themeUri', Themes::$themeUri);

// Control de ruta de acciones
if ($actionId != ActionsInterface::ACTION_ACC_SEARCH) {
    $actionsPath = &$_SESSION['actionsPath'];
    $actionsPath[] = $actionId;
    $actions = count($actionsPath);

    // Se eliminan las acciones ya realizadas
    if ($actions > 2 && $actionsPath[$actions - 3] == $actionId) {
        unset($actionsPath[$actions - 3]);
        unset($actionsPath[$actions - 2]);
        $actionsPath = array_values($actionsPath);
        $actions = count($actionsPath);
    }

    $Tpl->assign('lastAction', $actionsPath[$actions - 2]);
}

switch ($actionId) {
    case ActionsInterface::ACTION_ACC_SEARCH:
        $_SESSION['actionsPath'] = array(ActionsInterface::ACTION_ACC_SEARCH);

        $Tpl->assign('lastAction', $lastAction);

        $Controller = new SearchC($Tpl);
        $Controller->getSearchBox();
        $Controller->getSearch();
        break;
    case ActionsInterface::ACTION_ACC_NEW:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getNewAccount();
        break;
    case ActionsInterface::ACTION_ACC_COPY:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getCopyAccount();
        break;
    case ActionsInterface::ACTION_ACC_EDIT:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getEditAccount();
        break;
    case ActionsInterface::ACTION_ACC_EDIT_PASS:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getEditPassAccount();
        break;
    case ActionsInterface::ACTION_ACC_VIEW:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getViewAccount();
        break;
    case ActionsInterface::ACTION_ACC_VIEW_HISTORY:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getViewHistoryAccount();
        break;
    case ActionsInterface::ACTION_ACC_DELETE:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getDeleteAccount();
        break;
    case ActionsInterface::ACTION_ACC_REQUEST:
        $Controller = new AccountC($Tpl, null, $itemId);
        $Controller->getRequestAccountAccess();
        break;
    case ActionsInterface::ACTION_USR:
    case ActionsInterface::ACTION_MGM_APITOKENS:
    case ActionsInterface::ACTION_MGM_PUBLICLINKS:
        $Controller = new \SP\Controller\UsersMgmtC($Tpl);
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
        $Controller = new \SP\Controller\AccountsMgmtC($Tpl);
        $Controller->useTabs();
        $Controller->getCategories();
        $Controller->getCustomers();
        $Controller->getCustomFields();
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
        $Tpl->assign('onCloseAction', $actionId);
        $Tpl->addTemplate('tabs-start');

        $Controller = new \SP\Controller\ConfigC($Tpl);
        $Controller->getGeneralTab();
        $Controller->getWikiTab();
        $Controller->getLdapTab();
        $Controller->getMailTab();
        $Controller->getEncryptionTab();
        $Controller->getBackupTab();
        $Controller->getImportTab();
        $Controller->getInfoTab();

        $Tpl->addTemplate('tabs-end');
        break;
    case ActionsInterface::ACTION_EVL:
        $Controller = new \SP\Controller\EventlogC($Tpl);
        $Controller->getEventlog();
        break;
    case ActionsInterface::ACTION_USR_PREFERENCES:
    case ActionsInterface::ACTION_USR_PREFERENCES_GENERAL:
    case ActionsInterface::ACTION_USR_PREFERENCES_SECURITY:
        $Tpl->addTemplate('tabs-start');

        $Controller = new \SP\Controller\UsersPrefsC($Tpl);
        $Controller->getPreferencesTab();
        $Controller->getSecurityTab();

        $Tpl->addTemplate('tabs-end');
        break;
}

// Se comprueba si se debe de mostrar la vista de depuración
if (Session::getUserIsAdminApp() && Config::getValue('debug')) {
    $Controller->getDebug();
}

$Tpl->addTemplate('js-common');
$Controller->view();
