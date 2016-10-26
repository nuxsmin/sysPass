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

use SP\Config\Config;
use SP\Controller\AccItemsSearchController;
use SP\Controller\AppItemsSearchController;
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Http\Response;

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJson(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJson(_('CONSULTA INVÁLIDA'));
}

$actionId = Request::analyze('actionId', 0);

$ItemSearchData = new ItemSearchData();
$ItemSearchData->setSeachString(Request::analyze('search'));
$ItemSearchData->setLimitStart(Request::analyze('start', 0));
$ItemSearchData->setLimitCount(Request::analyze('count', Config::getConfig()->getAccountCount()));

$Tpl = new Template();
$Tpl->assign('index', Request::analyze('activeTab', 0));

switch ($actionId) {
    case ActionsInterface::ACTION_USR_USERS_SEARCH:
        $Controller = new AccItemsSearchController($Tpl);
        $Controller->getUsers($ItemSearchData);
        break;
    case ActionsInterface::ACTION_USR_GROUPS_SEARCH:
        $Controller = new AccItemsSearchController($Tpl);
        $Controller->getGroups($ItemSearchData);
        break;
    case ActionsInterface::ACTION_USR_PROFILES_SEARCH:
        $Controller = new AccItemsSearchController($Tpl);
        $Controller->getProfiles($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_APITOKENS_SEARCH:
        $Controller = new AccItemsSearchController($Tpl);
        $Controller->getTokens($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_PUBLICLINKS_SEARCH:
        $Controller = new AccItemsSearchController($Tpl);
        $Controller->getPublicLinks($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_CATEGORIES_SEARCH:
        $Controller = new AppItemsSearchController($Tpl);
        $Controller->getCategories($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_CUSTOMERS_SEARCH:
        $Controller = new AppItemsSearchController($Tpl);
        $Controller->getCustomers($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_SEARCH:
        $Controller = new AppItemsSearchController($Tpl);
        $Controller->getCustomFields($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_FILES_SEARCH:
        $Controller = new AppItemsSearchController($Tpl);
        $Controller->getFiles($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_ACCOUNTS_SEARCH:
        $Controller = new AppItemsSearchController($Tpl);
        $Controller->getAccounts($ItemSearchData);
        break;
    case ActionsInterface::ACTION_MGM_TAGS_SEARCH:
        $Controller = new AppItemsSearchController($Tpl);
        $Controller->getTags($ItemSearchData);
        break;
    default:
        Response::printJson(_('Acción Inválida'));
        break;
}

$data = array(
    'sk' => SessionUtil::getSessionKey(),
    'html' => $Controller->render()
);

Response::printJson($data, 0);