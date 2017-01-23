<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

use Plugins\Authenticator\Authenticator;
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\Language;
use SP\Core\Session;
use SP\Core\Exceptions\SPException;
use SP\Core\DiFactory;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Core\SessionUtil;
use SP\Mgmt\Users\UserPreferences;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

$Json = new JsonResponse();

if (!Init::isLoggedIn()) {
    $Json->setStatus(10);
    $Json->setDescription(__('La sesión no se ha iniciado o ha caducado'));
    Json::returnJson($Json);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    $Json->setDescription(__('CONSULTA INVÁLIDA'));
    Json::returnJson($Json);
}

// Variables POST del formulario
$actionId = Request::analyze('actionId', 0);
$itemId = Request::analyze('itemId', 0);

if ($actionId === ActionsInterface::ACTION_USR_PREFERENCES_GENERAL) {
    $UserPreferencesData = UserPreferences::getItem()->getById($itemId);

    $UserPreferencesData->setUserId($itemId);
    $UserPreferencesData->setLang(Request::analyze('userlang'));
    $UserPreferencesData->setTheme(Request::analyze('usertheme', 'material-blue'));
    $UserPreferencesData->setResultsPerPage(Request::analyze('resultsperpage', 12));
    $UserPreferencesData->setAccountLink(Request::analyze('account_link', false, false, true));
    $UserPreferencesData->setSortViews(Request::analyze('sort_views', false, false, true));
    $UserPreferencesData->setTopNavbar(Request::analyze('top_navbar', false, false, true));
    $UserPreferencesData->setOptionalActions(Request::analyze('optional_actions', false, false, true));
    $UserPreferencesData->setResultsAsCards(Request::analyze('resultsascards', false, false, true));

    try {
        UserPreferences::getItem($UserPreferencesData)->update();
        // Forzar la detección del lenguaje tras actualizar
        Language::setLanguage(true);
        DiFactory::getTheme()->initTheme(true);

        // Actualizar las preferencias en la sesión y recargar la página
        Session::setUserPreferences($UserPreferencesData);
        Util::reload();

        $Json->setStatus(0);
        $Json->setDescription(__('Preferencias actualizadas'));
    } catch (SPException $e) {
        $Json->setDescription($e->getMessage());
    }

    Json::returnJson($Json);
} else {
    $Json->setDescription(__('Acción Inválida'));
    Json::returnJson($Json);
}