<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

define('APP_ROOT', '..');
require_once APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'init.php';

SP_Util::checkReferer('POST');

if (!SP_Common::parseParams('p', 'login', false)) {
    return;
}

$userLogin = SP_Common::parseParams('p', 'user');
$userPass = SP_Common::parseParams('p', 'pass', '', false, false, false);
$masterPass = SP_Common::parseParams('p', 'mpass');

if (!$userLogin || !$userPass) {
    SP_Common::printJSON(_('Usuario/Clave no introducidos'));
}

$resLdap = SP_Auth::authUserLDAP($userLogin,$userPass);

$objUser = new SP_Users;
$objUser->userLogin = $userLogin;
$objUser->userPass = $userPass;
$objUser->userName = SP_Auth::$userName;
$objUser->userEmail = SP_Auth::$userEmail;

// Autentificamos por LDAP
if ($resLdap === true) {
    $message['action'] = _('Inicio sesión (LDAP)');

    // Verificamos si el usuario existe en la BBDD
    if (!$objUser->checkLDAPUserInDB()) {
        // Creamos el usuario de LDAP en MySQL
        if (!$objUser->newUserLDAP()) {
            $message['text'][] = _('Error al guardar los datos de LDAP');
            SP_Log::wrLogInfo($message);

            SP_Common::printJSON(_('Error interno'));
        }
    } else {
        // Actualizamos la clave del usuario en MySQL
        if (!$objUser->updateLDAPUserInDB()) {
            $message['text'][] = _('Error al actualizar la clave del usuario en la BBDD');
            SP_Log::wrLogInfo($message);

            SP_Common::printJSON(_('Error interno'));
        }
    }
} else if ($resLdap == 49) {
    $message['action'] = _('Inicio sesión (LDAP)');
    $message['text'][] = _('Login incorrecto');
    $message['text'][] = _('Usuario') . ": " . $userLogin;
    SP_Log::wrLogInfo($message);

    SP_Common::printJSON(_('Usuario/Clave incorrectos'));
} else if ($resLdap === 701) {
    $message['action'] = _('Inicio sesión (LDAP)');
    $message['text'][] = _('Cuenta expirada');
    $message['text'][] = _('Usuario') . ": " . $userLogin;
    SP_Log::wrLogInfo($message);

    SP_Common::printJSON(_('Cuenta expirada'));
} else if ($resLdap === 702) {
    $message['action'] = _('Inicio sesión (LDAP)');
    $message['text'][] = _('El usuario no tiene grupos asociados');
    $message['text'][] = _('Usuario') . ": " . $userLogin;
    SP_Log::wrLogInfo($message);

    SP_Common::printJSON(_('Usuario/Clave incorrectos'));
} else { // Autentificamos por MySQL (ha fallado LDAP)
    $message['action'] = _('Inicio sesión (MySQL)');

    // Autentificamos con la BBDD
    if (!SP_Auth::authUserMySQL($userLogin,$userPass)) {
        $message['text'][] = _('Login incorrecto');
        $message['text'][] = _('Usuario') . ": " . $userLogin;
        SP_Log::wrLogInfo($message);

        SP_Common::printJSON(_('Usuario/Clave incorrectos'));
    }
}

// Comprobar si el usuario está deshabilitado
if (SP_Users::checkUserIsDisabled($userLogin)) {
    $message['text'][] = _('Usuario deshabilitado');
    $message['text'][] = _('Usuario') . ": " . $userLogin;
    SP_Log::wrLogInfo($message);

    SP_Common::printJSON(_('Usuario deshabilitado'));
}

// Obtenemos los datos del usuario
if (!$objUser->getUserInfo()) {
    $message['text'][] = _('Error al obtener los datos del usuario de la BBDD');
    SP_Log::wrLogInfo($message);

    SP_Common::printJSON(_('Error interno'));
}

// Comprobamos que la clave maestra del usuario es correcta y está actualizada
if (!$masterPass && (!$objUser->checkUserMPass() || !SP_Users::checkUserUpdateMPass($userLogin) )) {
    SP_Common::printJSON(_('La clave maestra no ha sido guardada o es incorrecta'), 3);
} elseif ($masterPass) {
    if (!$objUser->updateUserMPass($masterPass)) {
        $message['text'][] = _('Clave maestra incorrecta');
        SP_Log::wrLogInfo($message);

        SP_Common::printJSON(_('Clave maestra incorrecta'), 4);
    }
}

// Comprobar si se ha forzado un cambio de clave
if ($objUser->userChangePass){
    $hash = SP_Util::generate_random_bytes();

    if (SP_Users::addPassRecover($userLogin, $hash)){
        $url = SP_Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1';
        SP_Common::printJSON($url, 0);
    }
}

// Obtenemos la clave maestra del usuario
if ($objUser->getUserMPass()) {
    // Establecemos las variables de sesión
    $objUser->setUserSession();

    $message['text'][] = _('Usuario') . ": " . $userLogin;
    $message['text'][] = _('Perfil') . ": " . SP_Profiles::getProfileNameById($objUser->userProfileId);
    $message['text'][] = _('Grupo') . ": " . SP_Groups::getGroupNameById($objUser->userGroupId);

    SP_Log::wrLogInfo($message);
    
    // Comprobar si existen parámetros adicionales en URL via GET
    foreach ($_POST as $param => $value){
        if(preg_match('/g_.*/', $param)){
            $params[] = substr($param,2).'='.$value;
        }
    }
    
    $urlParams = isset($params) ? '?'.implode('&', $params) : '';
    
    SP_Common::printJSON('index.php'.$urlParams, 0);
}