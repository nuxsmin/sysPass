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
use SP\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = SP\Request::analyze('actionId', 0);
$itemId = SP\Request::analyze('itemId', 0);
$activeTab = SP\Request::analyze('activeTab', 0);

// Acción al cerrar la vista
$doActionOnClose = "sysPassUtil.Common.doAction($actionId,'',$activeTab);";

if (SP\Util::demoIsEnabled() && \SP\Session::getUserLogin() === 'demo') {
    SP\Common::printJSON(_('Ey, esto es una DEMO!!'));
}

if ($actionId === SP\Controller\ActionsInterface::ACTION_USR_PREFERENCES_GENERAL) {
    $userLang = SP\Request::analyze('userlang');
    $userTheme = SP\Request::analyze('usertheme', 'material-blue');
    $resultsPerPage = SP\Request::analyze('resultsperpage', 12);
    $accountLink = SP\Request::analyze('account_link', false, false, true);

    // No se instancia la clase ya que es necesario guardar los atributos ya guardados
    $UserPrefs = \SP\UserPreferences::getPreferences($itemId);
    $UserPrefs->setId($itemId);
    $UserPrefs->setLang($userLang);
    $UserPrefs->setTheme($userTheme);
    $UserPrefs->setResultsPerPage($resultsPerPage);
    $UserPrefs->setAccountLink($accountLink);

    if (!$UserPrefs->updatePreferences()) {
        SP\Common::printJSON(_('Error al actualizar preferencias'));
    }

    // Forzar la detección del lenguaje tras actualizar
    SP\Language::setLanguage(true);
    SP\Themes::setTheme(true);
    // Actualizar las preferencias en la sesión y recargar la página
    SP\Session::setUserPreferences($UserPrefs);
    SP\Util::reload();

    SP\Common::printJSON(_('Preferencias actualizadas'), 0, $doActionOnClose);
} else if ($actionId === SP\Controller\ActionsInterface::ACTION_USR_PREFERENCES_SECURITY) {
    // Variables POST del formulario
    $twoFaEnabled = SP\Request::analyze('security_2faenabled', 0, false, 1);
    $pin = SP\Request::analyze('security_pin', 0);

    $userLogin = UserUtil::getUserLoginById($itemId);
    $twoFa = new \SP\Auth\Auth2FA($itemId, $userLogin);

    if (!$twoFa->verifyKey($pin)) {
        SP\Common::printJSON(_('Código incorrecto'));
    }

    // No se instancia la clase ya que es necesario guardar los atributos ya guardados
    $UserPrefs = \SP\UserPreferences::getPreferences($itemId);
    $UserPrefs->setId($itemId);
    $UserPrefs->setUse2Fa(\SP\Util::boolval($twoFaEnabled));

    if (!$UserPrefs->updatePreferences()) {
        SP\Common::printJSON(_('Error al actualizar preferencias'));
    }

    SP\Common::printJSON(_('Preferencias actualizadas'), 0, $doActionOnClose);
} else {
    SP\Common::printJSON(_('Acción Inválida'));
}