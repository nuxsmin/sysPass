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
use SP\Controller\AccItemsMgmtSearch;
use SP\Controller\AppItemsMgmtSearch;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Http\Request;
use SP\Http\Response;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$actionId = Request::analyze('actionId', 0);
$search = Request::analyze('search');
$limitStart = Request::analyze('start', 0);
$limitCount = Request::analyze('count', Config::getConfig()->getAccountCount());

$Tpl = new Template();
$Tpl->assign('index', Request::analyze('activeTab', 0));

switch ($actionId) {
    case \SP\Core\ActionsInterface::ACTION_USR_USERS_SEARCH:
        $Controller = new AccItemsMgmtSearch($Tpl);
        $Controller->getUsers($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_USR_GROUPS_SEARCH:
        $Controller = new AccItemsMgmtSearch($Tpl);
        $Controller->getGroups($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_USR_PROFILES_SEARCH:
        $Controller = new AccItemsMgmtSearch($Tpl);
        $Controller->getProfiles($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_SEARCH:
        $Controller = new AccItemsMgmtSearch($Tpl);
        $Controller->getTokens($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_SEARCH:
        $Controller = new AccItemsMgmtSearch($Tpl);
        $Controller->getPublicLinks($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_SEARCH:
        $Controller = new AppItemsMgmtSearch($Tpl);
        $Controller->getCategories($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_SEARCH:
        $Controller = new AppItemsMgmtSearch($Tpl);
        $Controller->getCustomers($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_SEARCH:
        $Controller = new AppItemsMgmtSearch($Tpl);
        $Controller->getCustomFields($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_FILES_SEARCH:
        $Controller = new AppItemsMgmtSearch($Tpl);
        $Controller->getFiles($search, $limitStart, $limitCount);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_ACCOUNTS_SEARCH:
        $Controller = new AppItemsMgmtSearch($Tpl);
        $Controller->getAccounts($search, $limitStart, $limitCount);
        break;
    default:
        Response::printJSON(_('Acción Inválida'));
        break;
}

$data = array(
    'sk' => SessionUtil::getSessionKey(),
    'html' => $Controller->render()
);

Response::printJSON($data, 0);