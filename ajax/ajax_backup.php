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

if ( ! SP_Init::isLoggedIn() ) {
    SP_Common::printXML(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', FALSE);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

SP_Users::checkUserAccess("backup") || die ('<DIV CLASS="error">'._('No tiene permisos para acceder a esta página').'</DIV');

$doBackup = SP_Common::parseParams('p', 'backup', 0);

if ( $doBackup ){
    $arrOut = SP_Config::makeBackup();

	$message['action'] = _('Realizar Backup');
	$message['text'] = '';

	SP_Common::sendEmail($message);
    
    if ( array_key_exists('error', $arrOut) ){
        SP_Common::printXML(_('Error al realizar el backup').'<br><br>'.$arrOut['error']);
    }
    
    SP_Common::printXML(_('Proceso de backup finalizado'),0);
}