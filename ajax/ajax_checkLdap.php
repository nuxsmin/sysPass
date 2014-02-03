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
    SP_Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', FALSE);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmLdapServer = SP_Common::parseParams('p', 'ldapserver');
$frmLdapBase = SP_Common::parseParams('p', 'ldapbase');
$frmLdapGroup = SP_Common::parseParams('p', 'ldapgroup');
$frmLdapBindUser = SP_Common::parseParams('p', 'ldapbinduser');
$frmLdapBindPass = SP_Common::parseParams('p', 'ldapbindpass');

$resCheckLdap = SP_LDAP::checkLDAPConn($frmLdapServer,$frmLdapBindUser,$frmLdapBindPass,$frmLdapBase,$frmLdapGroup);

if ( $resCheckLdap === FALSE ){
    SP_Common::printJSON(_('Error de conexión a LDAP').';;'._('Revise el registro de eventos para más detalles'));
} else{
    SP_Common::printJSON(_('Conexión a LDAP correcta').';;'._('Objetos encontrados').': '.$resCheckLdap,0);
}