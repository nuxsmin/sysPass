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
use SP\Core\DiFactory;
use SP\DataModel\UserData;
use SP\DataModel\UserPassRecoverData;
use SP\Http\JsonResponse;
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
use SP\Util\Json;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Request::analyze('login', false)) {
    return;
}

$Json = new JsonResponse();

$userLogin = Request::analyze('user');
$userPass = Request::analyzeEncrypted('pass');
$masterPass = Request::analyzeEncrypted('mpass');

if (!$userLogin || !$userPass) {
    $Json->setDescription(_('Usuario/Clave no introducidos'));
    Json::returnJson($Json);
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

        $Json->setDescription(_('Error interno'));
        Json::returnJson($Json);
    }
} else if ($resLdap === 49) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Login incorrecto'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->writeLog();

    $Json->setDescription(_('Usuario/Clave incorrectos'));
    Json::returnJson($Json);
} else if ($resLdap === 701) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Cuenta expirada'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->writeLog();

    $Json->setDescription(_('Cuenta expirada'));
    Json::returnJson($Json);
} else if ($resLdap === 702) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('El usuario no tiene grupos asociados'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->writeLog();

    $Json->setDescription(_('El usuario no tiene grupos asociados'));
    Json::returnJson($Json);
} else { // Autentificamos por MySQL (ha fallado LDAP)
    $Log->resetDescription();
    $Log->addDescription('(MySQL)');

    // Autentificamos con la BBDD
    if (!Auth::authUserMySQL($UserData->getUserLogin(), $UserData->getUserPass())) {
        $Log->addDescription(_('Login incorrecto'));
        $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
        $Log->writeLog();

        $Json->setDescription(_('Usuario/Clave incorrectos'));
        Json::returnJson($Json);
    }
}

// Comprobar si concide el login con la autentificación del servidor web
if (!Auth::checkServerAuthUser($UserData->getUserLogin())) {
    $Log->addDescription(_('Login incorrecto'));
    $Log->addDetails(_('Usuario'), $UserData->getUserLogin());
    $Log->addDetails(_('Autentificación'), sprintf('%s (%s)', Auth::getServerAuthType(), Auth::getServerAuthUser()));
    $Log->writeLog();

    $Json->setDescription(_('Usuario/Clave incorrectos'));
    Json::returnJson($Json);
}

// Obtenemos los datos del usuario
try {
    $User = User::getItem($UserData)->getByLogin($UserData->getUserLogin());
    $User->getItemData()->setUserPass($userPass);
} catch (SPException $e) {
    $Log->setLogLevel(Log::ERROR);
    $Log->addDescription(_('Error al obtener los datos del usuario de la BBDD'));
    $Log->writeLog();

    $Json->setDescription(_('Error interno'));
    Json::returnJson($Json);
}

// Comprobar si el usuario está deshabilitado
if ($User->getItemData()->isUserIsDisabled()) {
    $Log->addDescription(_('Usuario deshabilitado'));
    $Log->addDetails(_('Usuario'), $User->getItemData()->getUserLogin());
    $Log->writeLog();

    $Json->setDescription(_('Usuario deshabilitado'));
    Json::returnJson($Json);
}

$UserPass = UserPass::getItem($User->getItemData());

// Comprobamos que la clave maestra del usuario es correcta y está actualizada
if (!$masterPass
    && (!$UserPass->loadUserMPass() || !UserPass::checkUserUpdateMPass($User->getItemData()->getUserId()))
) {
    $Json->setStatus(2);
    $Json->setDescription(_('La clave maestra no ha sido guardada o es incorrecta'));
    Json::returnJson($Json);
} elseif ($masterPass) {
    if (CryptMasterPass::checkTempMasterPass($masterPass)) {
        $masterPass = CryptMasterPass::getTempMasterPass($masterPass);
    }

    if (!$UserPass->updateUserMPass($masterPass)) {
        $Log->addDescription(_('Clave maestra incorrecta'));
        $Log->writeLog();

        $Json->setStatus(2);
        $Json->setDescription($Log->getDescription());
        Json::returnJson($Json);
    }
}

// Comprobar si se ha forzado un cambio de clave
if ($User->getItemData()->isUserIsChangePass()) {
    $hash = \SP\Util\Util::generateRandomBytes();

    $UserPassRecoverData = new UserPassRecoverData();
    $UserPassRecoverData->setUserpassrUserId($User->getItemData()->getUserId());
    $UserPassRecoverData->setUserpassrHash($hash);

    if (UserPassRecover::getItem($UserPassRecoverData)->add()) {
        $data = ['url' => Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1'];
        $Json->setData($data);
        Json::returnJson($Json);
    }
}

// Obtenemos la clave maestra del usuario
if ($UserPass->getClearUserMPass()) {
    // Actualizar el último login del usuario
    UserUtil::setUserLastLogin($User->getItemData()->getUserId());

    // Cargar las variables de sesión del usuario
    SessionUtil::loadUserSession($User->getItemData());

    $Log->addDetails(_('Usuario'), $User->getItemData()->getUserLogin());
    $Log->addDetails(_('Perfil'), Profile::getItem()->getById($User->getItemData()->getUserProfileId())->getUserprofileName());
    $Log->addDetails(_('Grupo'), Group::getItem()->getById($User->getItemData()->getUserGroupId())->getUsergroupName());
    $Log->writeLog();
} else {
    $Log->setLogLevel(Log::ERROR);
    $Log->addDescription(_('Error al obtener la clave maestra del usuario'));
    $Log->writeLog();

    $Json->setDescription(_('Error interno'));
    Json::returnJson($Json);
}

$UserPreferencesData = UserPreferences::getItem()->getById($User->getItemData()->getUserId());
Language::setLanguage(true);
DiFactory::getTheme()->initTheme(true);
Session::setUserPreferences($UserPreferencesData);
Session::setSessionType(Session::SESSION_INTERACTIVE);

if ($UserPreferencesData->isUse2Fa()) {
    Session::set2FApassed(false);
    $data = ['url' => Init::$WEBURI . '/index.php?a=2fa&i=' . $User->getItemData()->getUserId() . '&t=' . time() . '&f=1'];
    $Json->setData($data);
    Json::returnJson($Json);
} else {
    Session::set2FApassed(true);
}

$data = ['url' => 'index.php' . Request::importUrlParamsToGet()];
$Json->setStatus(0);
$Json->setData($data);
Json::returnJson($Json);