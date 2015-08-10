<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.or
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

define('APP_ROOT', '.');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

$userLogin = \SP\Request::analyze('u');
$userPass = \SP\Request::analyze('up');
$authToken = \SP\Request::analyze('t');
$actionId = \SP\Request::analyze('a', 0);

if (!$userLogin || !$authToken || !$actionId){
    \SP\Common::printJSON(_('Parámetros incorrectos'));
}

try {
    switch ($actionId) {
        case \SP\Controller\ActionsInterface::ACTION_ACC_VIEW:
            $itemId = \SP\Request::analyze('i', 0);

            $Api = new \SP\Api($userLogin, $actionId, $authToken);
            $out = $Api->getAccountData($itemId);
            break;
        case \SP\Controller\ActionsInterface::ACTION_ACC_VIEW_PASS:
            $itemId = \SP\Request::analyze('i', 0);

            $Api = new \SP\Api($userLogin, $actionId, $authToken, $userPass);
            $out = $Api->getAccountPassword($itemId);
            break;
        case \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH:
            $search = \SP\Request::analyze('s');
            $count = \SP\Request::analyze('c', 10);

            $Api = new \SP\Api($userLogin, $actionId, $authToken);
            $out = $Api->getAccountSearch($search, $count);
            break;
        default:
            throw new Exception(_('Acción Inválida'));
    }
} catch (Exception $e) {
    \SP\Common::printJSON($e->getMessage(), 1, $actionId);
}

header('Content-type: application/json');
echo $out;