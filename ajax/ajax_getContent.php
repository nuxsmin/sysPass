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

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Init.php';

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Util::logout();
}

SP_Util::checkReload();

if (!SP_Common::parseParams('p', 'action', 0, true)) {
    die('<div class="error">' . _('Parámetros incorrectos') . '</DIV>');
}

$actionId = SP_Common::parseParams('p', 'action');
$lastAction = filter_var(SP_Common::parseParams('p', 'lastAction', \Controller\ActionsInterface::ACTION_ACC_SEARCH, false, false, false), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$tpl = new SP_Template();
// FIXME: cambiar action por actionId
$tpl->assign('action', $actionId);
$tpl->assign('actionId', $actionId);
$tpl->assign('id', SP_Common::parseParams('p', 'id', 0));
$tpl->assign('queryTimeStart', microtime());
$tpl->assign('userId', SP_Common::parseParams('s', 'uid', 0));
$tpl->assign('userGroupId', SP_Common::parseParams('s', 'ugroup', 0));
$tpl->assign('userIsAdminApp', SP_Common::parseParams('s', 'uisadminapp', 0));
$tpl->assign('userIsAdminAcc', SP_Common::parseParams('s', 'uisadminacc', 0));

// Control de ruta de acciones
if ($action != \Controller\ActionsInterface::ACTION_ACC_SEARCH) {
    $actionsPath = &$_SESSION['actionsPath'];
    $actionsPath[] = $action;
    $actions = count($actionsPath);

    // Se eliminan las acciones ya realizadas
    if ($actions > 2 && $actionsPath[$actions - 3] == $action) {
        unset($actionsPath[$actions - 3]);
        unset($actionsPath[$actions - 2]);
        $actionsPath = array_values($actionsPath);
        $actions = count($actionsPath);
    }

    $tpl->assign('lastAction', $actionsPath[$actions - 2]);
}

switch ($actionId) {
    case \Controller\ActionsInterface::ACTION_ACC_SEARCH:
        $_SESSION['actionsPath'] = array(\Controller\ActionsInterface::ACTION_ACC_SEARCH);

        $tpl->assign('lastAction', $lastAction);

        $controller = new \Controller\SearchC($tpl);
        $controller->getSearchBox();
        $controller->getSearch();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_NEW:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getNewAccount();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_COPY:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getCopyAccount();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_EDIT:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getEditAccount();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_EDIT_PASS:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getEditPassAccount();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_VIEW:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getViewAccount();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_VIEW_HISTORY:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getViewHistoryAccount();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_DELETE:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getDeleteAccount();
        break;
    case \Controller\ActionsInterface::ACTION_ACC_REQUEST:
        $controller = new Controller\AccountC($tpl, null, $itemId);
        $controller->getRequestAccountAccess();
        break;
    case \Controller\ActionsInterface::ACTION_USR:
        $controller = new Controller\UsersMgmtC($tpl);
        $controller->useTabs();
        $controller->getUsersList();
        $controller->getGroupsList();
        $controller->getProfilesList();
        break;
    case \Controller\ActionsInterface::ACTION_MGM:
        $controller = new Controller\AccountsMgmtC($tpl);
        $controller->useTabs();
        $controller->getCategories();
        $controller->getCustomers();
        break;
    case \Controller\ActionsInterface::ACTION_CFG:
        $tpl->assign('onCloseAction', $action);
        $tpl->addTemplate('tabs-start');

        $controller = new Controller\ConfigC($tpl);
        $controller->getConfigTab();
        $controller->getEncryptionTab();
        $controller->getBackupTab();
        $controller->getImportTab();
        $controller->getInfoTab();

        $tpl->addTemplate('tabs-end');
        break;
    case \Controller\ActionsInterface::ACTION_EVL:
        $controller = new Controller\EventlogC($tpl);
        $controller->getEventlog();
        break;
}

// Se comprueba si se debe de mostrar la vista de depuración
if (isset($_SESSION["uisadminapp"]) && SP_Config::getValue('debug')) {
    $controller->getDebug();
}

// Se comprueba si hay actualizaciones.
// Es necesario que se haga al final de obtener el contenido ya que la 
// consulta ajax detiene al resto si se ejecuta antes
if ($_SESSION['uisadminapp'] && SP_Config::getValue('checkupdates') === true && !SP_Common::parseParams('s', 'UPDATED', false, true)) {
    echo '<script>checkUpds();</script>';
}

$controller->view();