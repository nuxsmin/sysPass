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
use SP\SessionUtil;
use SP\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    SP\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = SP\Request::analyze('actionId', 0);
$itemId = SP\Request::analyze('itemId', 0);
$activeTab = SP\Request::analyze('activeTab', 0);

// Acción al cerrar la vista
$doActionOnClose = "sysPassUtil.Common.doAction($actionId,'',$activeTab);";

if ($actionId === SP\Controller\ActionsInterface::ACTION_USR_PREFERENCES_GENERAL) {
    $userLang = SP\Request::analyze('userlang');
    $userTheme = SP\Request::analyze('usertheme', 'material-blue');
    $resultsPerPage = SP\Request::analyze('resultsperpage', 12);
    $accountLink = SP\Request::analyze('account_link', false, false, true);
    $sortViews = SP\Request::analyze('sort_views', false, false, true);
    $topNavbar = SP\Request::analyze('top_navbar', false, false, true);

    // No se instancia la clase ya que es necesario guardar los atributos ya guardados
    $UserPrefs = \SP\UserPreferences::getPreferences($itemId);
    $UserPrefs->setId($itemId);
    $UserPrefs->setLang($userLang);
    $UserPrefs->setTheme($userTheme);
    $UserPrefs->setResultsPerPage($resultsPerPage);
    $UserPrefs->setAccountLink($accountLink);
    $UserPrefs->setSortViews($sortViews);
    $UserPrefs->setTopNavbar($topNavbar);

    if (!$UserPrefs->updatePreferences()) {
        SP\Response::printJSON(_('Error al actualizar preferencias'));
    }

    // Forzar la detección del lenguaje tras actualizar
    SP\Language::setLanguage(true);
    SP\Themes::setTheme(true);
    // Actualizar las preferencias en la sesión y recargar la página
    SP\Session::setUserPreferences($UserPrefs);
    SP\Util::reload();

    SP\Response::printJSON(_('Preferencias actualizadas'), 0, $doActionOnClose);
} else if ($actionId === SP\Controller\ActionsInterface::ACTION_USR_PREFERENCES_SECURITY) {
    if (SP\Util::demoIsEnabled() && \SP\Session::getUserLogin() === 'demo') {
        SP\Response::printJSON(_('Ey, esto es una DEMO!!'));
    }

    // Variables POST del formulario
    $twoFaEnabled = SP\Request::analyze('security_2faenabled', 0, false, 1);
    $pin = SP\Request::analyze('security_pin', 0);

    $userLogin = UserUtil::getUserLoginById($itemId);
    $twoFa = new \SP\Auth\Auth2FA($itemId, $userLogin);

    if (!$twoFa->verifyKey($pin)) {
        SP\Response::printJSON(_('Código incorrecto'));
    }

    // No se instancia la clase ya que es necesario guardar los atributos ya guardados
    $UserPrefs = \SP\UserPreferences::getPreferences($itemId);
    $UserPrefs->setId($itemId);
    $UserPrefs->setUse2Fa(\SP\Util::boolval($twoFaEnabled));

    if (!$UserPrefs->updatePreferences()) {
        SP\Response::printJSON(_('Error al actualizar preferencias'));
    }

    SP\Response::printJSON(_('Preferencias actualizadas'), 0, $doActionOnClose);
} else {
    SP\Response::printJSON(_('Acción Inválida'));
}