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

use SP\Auth\Auth;
use SP\Auth\Ldap;
use SP\Core\CryptMasterPass;
use SP\Core\Init;
use SP\Core\Language;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Themes;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Log;
use SP\Mgmt\User\Groups;
use SP\Mgmt\User\Profile;
use SP\Mgmt\User\User;
use SP\Mgmt\User\UserLdap;
use SP\Mgmt\User\UserPass;
use SP\Mgmt\User\UserPassRecover;
use SP\Mgmt\User\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Request::analyze('login', false)) {
    return;
}

$userLogin = Request::analyze('user');
$userPass = Request::analyzeEncrypted('pass');
$masterPass = Request::analyzeEncrypted('mpass');
$urlParams = Request::importUrlParamsToGet();

if (!$userLogin || !$userPass) {
    Response::printJSON(_('Usuario/Clave no introducidos'));
}

$User = new User();
$User->setUserLogin($userLogin);
$User->setUserPass($userPass);

if ($resLdap = Auth::authUserLDAP($userLogin, $userPass)) {
    $User->setUserName(Auth::$userName);
    $User->setUserEmail(Auth::$userEmail);
}

$Log = new Log(_('Inicio sesión'));

// Autentificamos por LDAP
if ($resLdap === true) {
    $Log->addDescription('(LDAP)');
    $Log->addDetails(_('Servidor Login'), Ldap::getLdapServer());

    // Verificamos si el usuario existe en la BBDD
    if (!UserLdap::checkLDAPUserInDB($userLogin)) {
        // Creamos el usuario de LDAP en MySQL
        if (!UserLdap::newUserLDAP($User)) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al guardar los datos de LDAP'));
            $Log->writeLog();

            Response::printJSON(_('Error interno'));
        }
    } else {
        // Actualizamos la clave del usuario en MySQL
        if (!UserLdap::updateLDAPUserInDB($User)) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al actualizar la clave del usuario en la BBDD'));
            $Log->writeLog();

            Response::printJSON(_('Error interno'));
        }
    }
} else if ($resLdap == 49) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Login incorrecto'));
    $Log->addDetails(_('Usuario'), $userLogin);
    $Log->writeLog();

    Response::printJSON(_('Usuario/Clave incorrectos'));
} else if ($resLdap === 701) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Cuenta expirada'));
    $Log->addDetails(_('Usuario'), $userLogin);
    $Log->writeLog();

    Response::printJSON(_('Cuenta expirada'));
} else if ($resLdap === 702) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('El usuario no tiene grupos asociados'));
    $Log->addDetails(_('Usuario'), $userLogin);
    $Log->writeLog();

    Response::printJSON(_('Usuario/Clave incorrectos'));
} else { // Autentificamos por MySQL (ha fallado LDAP)
    $Log->resetDescription();
    $Log->addDescription('(MySQL)');

    // Autentificamos con la BBDD
    if (!Auth::authUserMySQL($userLogin, $userPass)) {
        $Log->addDescription(_('Login incorrecto'));
        $Log->addDetails(_('Usuario'), $userLogin);
        $Log->writeLog();

        Response::printJSON(_('Usuario/Clave incorrectos'));
    }
}

// Comprobar si el usuario está deshabilitado
if (UserUtil::checkUserIsDisabled($userLogin)) {
    $Log->addDescription(_('Usuario deshabilitado'));
    $Log->addDetails(_('Usuario'), $userLogin);
    $Log->writeLog();

    Response::printJSON(_('Usuario deshabilitado'));
}

// Obtenemos los datos del usuario
if (!$User->getUserInfo()) {
    $Log->setLogLevel(Log::ERROR);
    $Log->addDescription(_('Error al obtener los datos del usuario de la BBDD'));
    $Log->writeLog();

    Response::printJSON(_('Error interno'));
}

// Comprobamos que la clave maestra del usuario es correcta y está actualizada
if (!$masterPass
    && (!UserPass::checkUserMPass($User) || !UserPass::checkUserUpdateMPass($userLogin))
) {
    Response::printJSON(_('La clave maestra no ha sido guardada o es incorrecta'), 3);
} elseif ($masterPass) {
    if (CryptMasterPass::checkTempMasterPass($masterPass)) {
        $masterPass = CryptMasterPass::getTempMasterPass($masterPass);
    }

    if (!$User->updateUserMPass($masterPass)) {
        $Log->setLogLevel(Log::NOTICE);
        $Log->addDescription(_('Clave maestra incorrecta'));
        $Log->writeLog();

        Response::printJSON(_('Clave maestra incorrecta'), 4);
    }
}

// Comprobar si se ha forzado un cambio de clave
if ($User->isUserChangePass()) {
    $hash = \SP\Util\Util::generate_random_bytes();

    if (UserPassRecover::addPassRecover($userLogin, $hash)) {
        $url = Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1';
        Response::printJSON($url, 0);
    }
}

// Obtenemos la clave maestra del usuario
if ($User->getUserMPass()) {
    // Actualizar el último login del usuario
    UserUtil::setUserLastLogin($User->getUserId());

    // Cargar las variables de sesión del usuario
    SessionUtil::loadUserSession($User);

    $Log->addDetails(_('Usuario'), $userLogin);
    $Log->addDetails(_('Perfil'), Profile::getProfileNameById($User->getUserProfileId()));
    $Log->addDetails(_('Grupo'), Groups::getGroupNameById($User->getUserGroupId()));
    $Log->writeLog();
} else {
    $Log->setLogLevel(Log::ERROR);
    $Log->addDescription(_('Error al obtener la clave maestra del usuario'));
    $Log->writeLog();

    Response::printJSON(_('Error interno'));
}

$UserPrefs = \SP\Mgmt\User\UserPreferences::getPreferences($User->getUserId());

if ($UserPrefs->isUse2Fa()) {
    Session::set2FApassed(false);
    $url = Init::$WEBURI . '/index.php?a=2fa&i=' . $User->getUserId() . '&t=' . time() . '&f=1';
    Response::printJSON($url, 0);
} else {
    Session::set2FApassed(true);
}

Language::setLanguage(true);
Themes::setTheme(true);
Session::setUserPreferences($UserPrefs);
Session::setSessionType(Session::SESSION_INTERACTIVE);

Response::printJSON('index.php?' . $urlParams, 0);