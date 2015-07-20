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

use SP\Request;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Util::logout();
}

SP\Util::checkReload();

if (!SP\Request::analyze('actionId', 0, true)) {
    die('<div class="error">' . _('Parámetros incorrectos') . '</DIV>');
}

$actionId = SP\Request::analyze('actionId');
$itemId = SP\Request::analyze('itemId', 0);
$lastAction = filter_var(SP\Request::analyze('lastAction', \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH, false, false, false), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$tpl = new SP\Template();
$tpl->assign('actionId', $actionId);
$tpl->assign('id', $itemId);
$tpl->assign('activeTabId', $itemId);
$tpl->assign('queryTimeStart', microtime());
$tpl->assign('userId', SP\Session::getUserId());
$tpl->assign('userGroupId', SP\Session::getUserGroupId());
$tpl->assign('userIsAdminApp', SP\Session::getUserIsAdminApp());
$tpl->assign('userIsAdminAcc', SP\Session::getUserIsAdminAcc());
$tpl->assign('themeUri', $themeUri);

// Control de ruta de acciones
if ($actionId != \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH) {
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

    $tpl->assign('lastAction', $actionsPath[$actions - 2]);
}

switch ($actionId) {
    case \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH:
        $_SESSION['actionsPath'] = array(\SP\Controller\ActionsInterface::ACTION_ACC_SEARCH);

        $tpl->assign('lastAction', $lastAction);

        $controller = new SP\Controller\SearchC($tpl);
        $controller->getSearchBox();
        $controller->getSearch();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_NEW:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getNewAccount();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_COPY:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getCopyAccount();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getEditAccount();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getEditPassAccount();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_VIEW:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getViewAccount();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_VIEW_HISTORY:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getViewHistoryAccount();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_DELETE:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getDeleteAccount();
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_REQUEST:
        $controller = new SP\Controller\AccountC($tpl, null, $itemId);
        $controller->getRequestAccountAccess();
        break;
    case \SP\Controller\ActionsInterface::ACTION_USR:
        $controller = new SP\Controller\UsersMgmtC($tpl);
        $controller->useTabs();
        $controller->getUsersList();
        $controller->getGroupsList();
        $controller->getProfilesList();
        break;
    case \SP\Controller\ActionsInterface::ACTION_MGM:
        $controller = new SP\Controller\AccountsMgmtC($tpl);
        $controller->useTabs();
        $controller->getCategories();
        $controller->getCustomers();
        break;
    case \SP\Controller\ActionsInterface::ACTION_CFG:
    case \SP\Controller\ActionsInterface::ACTION_CFG_GENERAL:
    case \SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION:
    case \SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS:
    case \SP\Controller\ActionsInterface::ACTION_CFG_BACKUP:
    case \SP\Controller\ActionsInterface::ACTION_CFG_EXPORT:
    case \SP\Controller\ActionsInterface::ACTION_CFG_IMPORT:
        $tpl->assign('onCloseAction', $actionId);
        $tpl->addTemplate('tabs-start');

        $controller = new SP\Controller\ConfigC($tpl);
        $controller->getConfigTab();
        $controller->getEncryptionTab();
        $controller->getBackupTab();
        $controller->getImportTab();
        $controller->getInfoTab();

        $tpl->addTemplate('tabs-end');
        break;
    case \SP\Controller\ActionsInterface::ACTION_EVL:
        $controller = new SP\Controller\EventlogC($tpl);
        $controller->getEventlog();
        break;
}

// Se comprueba si se debe de mostrar la vista de depuración
if (\SP\Session::getUserIsAdminApp() && SP\Config::getValue('debug')) {
    $controller->getDebug();
}

// Se comprueba si hay actualizaciones.
// Es necesario que se haga al final de obtener el contenido ya que la 
// consulta ajax detiene al resto si se ejecuta antes
if (\SP\Session::getUserIsAdminApp()
    && SP\Config::getValue('checkupdates') === true
    && !SP\Session::getUpdated()
) {
    echo '<script>checkUpds();</script>';
}

$tpl->addTemplate('js-common');
$controller->view();
