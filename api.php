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

require APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

try {
    $ApiRequest = new \SP\Api\ApiRequest();

    switch ($ApiRequest->getAction()) {
        case \SP\Core\ActionsInterface::ACTION_ACC_VIEW:
            $itemId = \SP\Http\Request::analyze(\SP\Api\ApiRequest::ITEM, 0);

            $out = $ApiRequest->getApi()->getAccountData($itemId);
            break;
        case \SP\Core\ActionsInterface::ACTION_ACC_VIEW_PASS:
            $ApiRequest->addVar('userPass', \SP\Api\ApiRequest::analyze(\SP\Api\ApiRequest::USER_PASS));

            $itemId = \SP\Http\Request::analyze(\SP\Api\ApiRequest::ITEM, 0);

            $out = $ApiRequest->getApi()->getAccountPassword($itemId);
            break;
        case \SP\Core\ActionsInterface::ACTION_ACC_SEARCH:
            $search = \SP\Http\Request::analyze(\SP\Api\ApiRequest::SEARCH);
            $count = \SP\Http\Request::analyze(\SP\Api\ApiRequest::SEARCH_COUNT, 10);

            $out = $ApiRequest->getApi()->getAccountSearch($search, $count);
            break;
        default:
            throw new Exception(_('Acción Inválida'));
    }
} catch (Exception $e) {
    \SP\Http\Response::printJSON(array($e->getMessage(), _('Ayuda Parámetros') => \SP\Api\ApiRequest::getHelp()));
}

header('Content-type: application/json');
echo $out;