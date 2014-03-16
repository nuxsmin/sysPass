<?php
/** 
* sysPass
* 
* @author nuxsmin
* @link http://syspass.org
* @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
include_once (APP_ROOT."/inc/init.php");

SP_Util::checkReferer('POST');

if ( ! SP_Common::parseParams('p', 'login', FALSE) ){
    return;
}
        
$userLogin = SP_Common::parseParams('p', 'user');
$userPass = SP_Common::parseParams('p', 'pass', '', false, false, false);
$masterPass = SP_Common::parseParams('p', 'mpass');

if ( ! $userLogin OR ! $userPass ){
    SP_Common::printXML(_('Usuario/Clave no introducidos'));
} 

$objUser = new SP_Users;
$objUser->userLogin = $userLogin;
$objUser->userPass = $userPass;

$resLdap = $objUser->authUserLDAP();

// Autentificamos por LDAP
if( $resLdap == 1){ 
    $message['action'] = _('Inicio sesión (LDAP)');
    
    // Verificamos si el usuario existe en la BBDD
    if ( ! $objUser->checkUserLDAP() ){
        // Creamos el usuario de LDAP en MySQL
        if ( ! $objUser->newUserLDAP() ){
            $message['text'][] = _('Error al guardar los datos de LDAP');
            SP_Common::wrLogInfo($message);
            
            SP_Common::printXML(_('Error interno'));
        }
    } else {
        // Actualizamos la clave del usuario en MySQL
        if ( ! $objUser->updateUserLDAP() ){
            $message['text'][] = _('Error al actualizar la clave del usuario en la BBDD');
            SP_Common::wrLogInfo($message);
            
            SP_Common::printXML(_('Error interno'));
        }        
    }
} else if ( $resLdap == 49 ){
    $message['text'][] = _('Login incorrecto');
    $message['text'][] = _('Usuario').": ".$userLogin;
    $message['text'][] = _('IP').": ".$_SERVER['REMOTE_ADDR'];
    SP_Common::wrLogInfo($message);

    SP_Common::printXML(_('Usuario/Clave incorrectos'));
} else { // Autentificamos por MySQL
    $message['action'] = _('Inicio sesión (MySQL)');
    
    // Autentificamos con la BBDD
    if ( ! $objUser->checkUserPass() ){
        $message['text'][] = _('Login incorrecto');
        $message['text'][] = _('Usuario').": ".$userLogin;
        $message['text'][] = _('IP').": ".$_SERVER['REMOTE_ADDR'];
        SP_Common::wrLogInfo($message);
            
        SP_Common::printXML(_('Usuario/Clave incorrectos'));
    }
}

// Comprobar si el usuario está deshabilitado
if ( $objUser->checkUserIsDisabled() ){
    $message['text'][] = _('Usuario deshabilitado');
    $message['text'][] = _('Usuario') . ": " . $userLogin;
    $message['text'][] = _('IP') . ": " . $_SERVER['REMOTE_ADDR'];
    SP_Common::wrLogInfo($message);

    SP_Common::printXML(_('Usuario deshabilitado'));
}
    
// Obtenemos los datos del usuario
if ( ! $objUser->getUserInfo() ){
    $message['text'][] = _('Error al obtener los datos del usuario de la BBDD');
    SP_Common::wrLogInfo($message);

    SP_Common::printXML(_('Error interno'));
}

// Comprobamos que la clave maestra del usuario es correcta y está actualizada
if ( ! $masterPass 
        && (! $objUser->checkUserMPass() || ! SP_Users::checkUserUpdateMPass($userLogin) ) ){
    SP_Common::printXML(_('La clave maestra no ha sido guardada o es incorrecta'),3);
} elseif ( $masterPass ) {
    if ( ! $objUser->updateUserMPass($masterPass) ){
        $message['text'][] = _('Clave maestra incorrecta');
        SP_Common::wrLogInfo($message);

        SP_Common::printXML(_('Clave maestra incorrecta'),4);
    }
} 

// Obtenemos la clave maestra del usuario
if ( $objUser->getUserMPass() ){ 
    // Establecemos las variables de sesión
    $objUser->setUserSession(); 

    $message['text'][] = _('Usuario').": ".$_SESSION['uname'];
    $message['text'][] = _('Perfil').": ".$_SESSION['uprofile'];
    $message['text'][] = _('Grupo').": ".$_SESSION['ugroup'];
    $message['text'][] = _('IP').": ".$_SERVER['REMOTE_ADDR'];
    SP_Common::wrLogInfo($message);

    SP_Common::printXML('index.php',0);
}