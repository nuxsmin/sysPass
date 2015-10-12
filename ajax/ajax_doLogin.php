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

use SP\Core\CryptMasterPass;
use SP\Http\Request;
use SP\Core\SessionUtil;
use SP\Mgmt\User\UserLdap;
use SP\Mgmt\User\UserPass;
use SP\Mgmt\User\UserPassRecover;
use SP\Mgmt\User\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!\SP\Http\Request::analyze('login', false)) {
    return;
}

$userLogin = \SP\Http\Request::analyze('user');
$userPass = \SP\Http\Request::analyzeEncrypted('pass');
$masterPass = \SP\Http\Request::analyzeEncrypted('mpass');

if (!$userLogin || !$userPass) {
    \SP\Http\Response::printJSON(_('Usuario/Clave no introducidos'));
}

$User = new \SP\Mgmt\User\User();
$User->setUserLogin($userLogin);
$User->setUserPass($userPass);

if ($resLdap = \SP\Auth\Auth::authUserLDAP($userLogin, $userPass)) {
    $User->setUserName(\SP\Auth\Auth::$userName);
    $User->setUserEmail(\SP\Auth\Auth::$userEmail);
}

$Log = new \SP\Log\Log(_('Inicio sesión'));

// Autentificamos por LDAP
if ($resLdap === true) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(sprintf('%s : %s', _('Servidor Login'), \SP\Auth\Ldap::getLdapServer()));

    // Verificamos si el usuario existe en la BBDD
    if (!UserLdap::checkLDAPUserInDB($userLogin)) {
        // Creamos el usuario de LDAP en MySQL
        if (!\SP\Mgmt\User\UserLdap::newUserLDAP($User)) {
            $Log->addDescription(_('Error al guardar los datos de LDAP'));
            $Log->writeLog();

            \SP\Http\Response::printJSON(_('Error interno'));
        }
    } else {
        // Actualizamos la clave del usuario en MySQL
        if (!UserLdap::updateLDAPUserInDB($User)) {
            $Log->addDescription(_('Error al actualizar la clave del usuario en la BBDD'));
            $Log->writeLog();

            \SP\Http\Response::printJSON(_('Error interno'));
        }
    }
} else if ($resLdap == 49) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Login incorrecto'));
    $Log->addDescription(_('Usuario') . ": " . $userLogin);
    $Log->writeLog();

    \SP\Http\Response::printJSON(_('Usuario/Clave incorrectos'));
} else if ($resLdap === 701) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('Cuenta expirada'));
    $Log->addDescription(_('Usuario') . ": " . $userLogin);
    $Log->writeLog();

    \SP\Http\Response::printJSON(_('Cuenta expirada'));
} else if ($resLdap === 702) {
    $Log->addDescription('(LDAP)');
    $Log->addDescription(_('El usuario no tiene grupos asociados'));
    $Log->addDescription(_('Usuario') . ": " . $userLogin);
    $Log->writeLog();

    \SP\Http\Response::printJSON(_('Usuario/Clave incorrectos'));
} else { // Autentificamos por MySQL (ha fallado LDAP)
    $Log->resetDescription();
    $Log->addDescription('(MySQL)');

    // Autentificamos con la BBDD
    if (!\SP\Auth\Auth::authUserMySQL($userLogin, $userPass)) {
        $Log->addDescription(_('Login incorrecto'));
        $Log->addDescription(_('Usuario') . ": " . $userLogin);
        $Log->writeLog();

        \SP\Http\Response::printJSON(_('Usuario/Clave incorrectos'));
    }
}

// Comprobar si el usuario está deshabilitado
if (UserUtil::checkUserIsDisabled($userLogin)) {
    $Log->addDescription(_('Usuario deshabilitado'));
    $Log->addDescription(_('Usuario') . ": " . $userLogin);
    $Log->writeLog();

    \SP\Http\Response::printJSON(_('Usuario deshabilitado'));
}

// Obtenemos los datos del usuario
if (!$User->getUserInfo()) {
    $Log->addDescription(_('Error al obtener los datos del usuario de la BBDD'));
    $Log->writeLog();

    \SP\Http\Response::printJSON(_('Error interno'));
}

// Comprobamos que la clave maestra del usuario es correcta y está actualizada
if (!$masterPass
    && (!UserPass::checkUserMPass($User) || !UserPass::checkUserUpdateMPass($userLogin))
) {
    \SP\Http\Response::printJSON(_('La clave maestra no ha sido guardada o es incorrecta'), 3);
} elseif ($masterPass) {
    if (CryptMasterPass::checkTempMasterPass($masterPass)) {
        $masterPass = CryptMasterPass::getTempMasterPass($masterPass);
    }

    if (!$User->updateUserMPass($masterPass)) {
        $Log->addDescription(_('Clave maestra incorrecta'));
        $Log->writeLog();

        \SP\Http\Response::printJSON(_('Clave maestra incorrecta'), 4);
    }
}

// Comprobar si se ha forzado un cambio de clave
if ($User->isUserChangePass()) {
    $hash = \SP\Util\Util::generate_random_bytes();

    if (UserPassRecover::addPassRecover($userLogin, $hash)) {
        $url = \SP\Core\Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1';
        \SP\Http\Response::printJSON($url, 0);
    }
}

// Obtenemos la clave maestra del usuario
if ($User->getUserMPass()) {
    // Actualizar el último login del usuario
    UserUtil::setUserLastLogin($User->getUserId());

    // Cargar las variables de sesión del usuario
    SessionUtil::loadUserSession($User);

    $Log->addDescription(sprintf('%s : %s', _('Usuario'), $userLogin));
    $Log->addDescription(sprintf('%s : %s', _('Perfil'), \SP\Mgmt\User\Profile::getProfileNameById($User->getUserProfileId())));
    $Log->addDescription(sprintf('%s : %s', _('Grupo'), \SP\Mgmt\User\Groups::getGroupNameById($User->getUserGroupId())));
    $Log->writeLog();
} else {
    \SP\Http\Response::printJSON(_('Error interno'));
}

$UserPrefs = \SP\Mgmt\User\UserPreferences::getPreferences($User->getUserId());

if ($UserPrefs->isUse2Fa()) {
    \SP\Core\Session::set2FApassed(false);
    $url = \SP\Core\Init::$WEBURI . '/index.php?a=2fa&i=' . $User->getUserId() . '&t=' . time() . '&f=1';
    \SP\Http\Response::printJSON($url, 0);
} else {
    \SP\Core\Session::set2FApassed(true);
}

\SP\Core\Language::setLanguage(true);
\SP\Core\Themes::setTheme(true);
\SP\Core\Session::setUserPreferences($UserPrefs);

$params = array();

// Comprobar si existen parámetros adicionales en URL via POST para pasarlos por GET
foreach ($_POST as $param => $value) {
    \SP\Html\Html::sanitize($param);
    \SP\Html\Html::sanitize($value);

    if (!strncmp($param, 'g_', 2)) {
        $params[] = substr($param, 2) . '=' . $value;
    }
}

$urlParams = (count($params) > 0) ? '?' . implode('&', $params) : '';

\SP\Http\Response::printJSON('index.php' . $urlParams, 0);