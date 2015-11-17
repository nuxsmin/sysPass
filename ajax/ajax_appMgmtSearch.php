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

use SP\Controller\ItemsMgmtSearch;
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

$Tpl = new Template();
$Tpl->assign('index', Request::analyze('activeTab', 0));

$Controller = new ItemsMgmtSearch($Tpl);

switch ($actionId) {
    case \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_SEARCH:
        $Controller->getCategories($search);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_SEARCH:
        $Controller->getCustomers($search);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_SEARCH:
        $Controller->getCustomFields($search);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_FILES_SEARCH:
        $Controller->getFiles($search);
        break;
    case \SP\Core\ActionsInterface::ACTION_MGM_ACCOUNTS_SEARCH:
        $Controller->getAccounts($search);
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