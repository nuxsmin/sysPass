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
use SP\Core\Exceptions\SPException;
use SP\Core\Themes;
use SP\DataModel\UserData;
use SP\DataModel\UserPassRecoverData;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Log;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserLdap;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserPassRecover;
use SP\Mgmt\Users\UserPreferences;
use SP\Mgmt\Users\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Request::analyze('login', false)) {
    return;
}

$userLogin = Request::analyze('user');
$userPass = Request::analyzeEncrypted('pass');
$masterPass = Request::analyzeEncrypted('mpass');

if (!$userLogin || !$userPass) {
    Response::printJSON(_('Usuario/Clave no introducidos'));
}

$UserData = new UserData();
$UserData->setUserLogin($userLogin);
$UserData->setUserPass($userPass);

if ($resLdap = Auth::authUserLDAP($userLogin, $userPass)) {
    $UserData->setUserName(Auth::$userName);
    $UserData->setUserEmail(Auth::$userEmail);
}

$Log = new Log(_('Inicio sesión'));

// Autentificamos por LDAP
if ($resLdap === true) {
    $Log->addDescription('(LDAP)');
    $Log->addDetails(_('Servidor Login'), Ldap::getLdapServer());

    try {
        // Verificamos si el usuario existe en la BBDD
        if (!UserLdap::checkLDAPUserInDB($UserData->getUserLogin())) {
            // Creamos el usuario de LDAP en MySQL
            UserLdap::getItem($UserData)->add();
        } else {
            UserLdap::getItem($UserData)->update();
        }
    } catch (SPException $e) {
        $Log->setLogLevel(Log::ERROR);
        $Log->addDescription($e->getMessage());
        $Log->writeLog();

        Response::printJSON(_('Error interno'));
    }
} else if ($resLdap == 49) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Login incorrecto'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->writeLog();

    Response::printJSON(_('Usuario/Clave incorrectos'));
} else if ($resLdap === 701) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Cuenta expirada'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->writeLog();

    Response::printJSON(_('Cuenta expirada'));
} else if ($resLdap === 702) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('El usuario no tiene grupos asociados'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->writeLog();

    Response::printJSON(_('El usuario no tiene grupos asociados'));
} else { // Autentificamos por MySQL (ha fallado LDAP)
    $Log->resetDescription();
    $Log->addDescription('(MySQL)');

    // Autentificamos con la BBDD
    if (!Auth::authUserMySQL($UserData->getUserLogin(), $UserData->getUserPass())) {
        $Log->addDescription(_('Login incorrecto'));
        $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
        $Log->writeLog();

        Response::printJSON(_('Usuario/Clave incorrectos'));
    }
}

// Comprobar si concide el login con la autentificación del servidor web
if (!Auth::checkServerAuthUser($UserData->getUserLogin())) {
    $Log->addDescription(_('Login incorrecto'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->addDetails(_('Autentificación'), sprintf('%s (%s)', Auth::getServerAuthType(), Auth::getServerAuthUser()));
    $Log->writeLog();

    Response::printJSON(_('Usuario/Clave incorrectos'));
}

// Obtenemos los datos del usuario
try {
    $User = User::getItem($UserData)->getByLogin($UserData->getUserLogin());
    $User->getItemData()->setUserPass($userPass);
} catch (SPException $e) {
    $Log->setLogLevel(Log::ERROR);
    $Log->addDescription(_('Error al obtener los datos del usuario de la BBDD'));
    $Log->writeLog();

    Response::printJSON(_('Error interno'));
}

// Comprobar si el usuario está deshabilitado
if ($User->getItemData()->isUserIsDisabled()) {
    $Log->addDescription(_('Usuario deshabilitado'));
    $Log->addDetails(_('Usuario'), $User->getItemData()->getUserLogin());
    $Log->writeLog();

    Response::printJSON(_('Usuario deshabilitado'));
}

$UserPass = UserPass::getItem($User->getItemData());

// Comprobamos que la clave maestra del usuario es correcta y está actualizada
if (!$masterPass
    && (!$UserPass->loadUserMPass() || !UserPass::checkUserUpdateMPass($User->getItemData()->getUserId()))
) {
    Response::printJSON(_('La clave maestra no ha sido guardada o es incorrecta'), 3);
} elseif ($masterPass) {
    if (CryptMasterPass::checkTempMasterPass($masterPass)) {
        $masterPass = CryptMasterPass::getTempMasterPass($masterPass);
    }

    if (!$UserPass->updateUserMPass($masterPass)) {
        $Log->addDescription(_('Clave maestra incorrecta'));
        $Log->writeLog();

        Response::printJSON(_('Clave maestra incorrecta'), 4);
    }
}

// Comprobar si se ha forzado un cambio de clave
if ($User->getItemData()->isUserIsChangePass()) {
    $hash = \SP\Util\Util::generate_random_bytes();

    $UserPassRecoverData = new UserPassRecoverData();
    $UserPassRecoverData->setUserpassrUserId($User->getItemData()->getUserId());
    $UserPassRecoverData->setUserpassrHash($hash);

    if (UserPassRecover::getItem($UserPassRecoverData)->add()) {
        $url = Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1';
        Response::printJSON($url, 0);
    }
}

// Obtenemos la clave maestra del usuario
if ($UserPass->getClearUserMPass()) {
    // Actualizar el último login del usuario
    UserUtil::setUserLastLogin($User->getItemData()->getUserId());

    // Cargar las variables de sesión del usuario
    SessionUtil::loadUserSession($User->getItemData());

    $Log->addDetails(_('Usuario'), $User->getItemData()->getUserLogin());
    $Log->addDetails(_('Perfil'), Profile::getItem()->getById($User->getItemData()->getUserProfileId())->getItemData()->getUserprofileName());
    $Log->addDetails(_('Grupo'), Group::getItem()->getById($User->getItemData()->getUserGroupId())->getItemData()->getUsergroupName());
    $Log->writeLog();
} else {
    $Log->setLogLevel(Log::ERROR);
    $Log->addDescription(_('Error al obtener la clave maestra del usuario'));
    $Log->writeLog();

    Response::printJSON(_('Error interno'));
}

$UserPreferencesData = UserPreferences::getItem()->getById($User->getItemData()->getUserId())->getItemData();
Language::setLanguage(true);
Themes::setTheme(true);
Session::setUserPreferences($UserPreferencesData);
Session::setSessionType(Session::SESSION_INTERACTIVE);

if ($UserPreferencesData->isUse2Fa()) {
    Session::set2FApassed(false);
    $url = Init::$WEBURI . '/index.php?a=2fa&i=' . $User->getItemData()->getUserId() . '&t=' . time() . '&f=1';
    Response::printJSON($url, 0);
} else {
    Session::set2FApassed(true);
}

$urlParams = Request::importUrlParamsToGet();
Response::printJSON('index.php?' . $urlParams, 0);