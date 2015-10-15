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

use SP\Auth\Ldap;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Http\Request;
use SP\Http\Response;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmLdapServer = Request::analyze('ldap_server');
$frmLdapBase = Request::analyze('ldap_base');
$frmLdapGroup = Request::analyze('ldap_group');
$frmLdapBindUser = Request::analyze('ldap_binduser');
$frmLdapBindPass = Request::analyzeEncrypted('ldap_bindpass');

if (!$frmLdapServer || !$frmLdapBase || !$frmLdapBindUser || !$frmLdapBindPass) {
    Response::printJSON(_('Los parámetros de LDAP no están configurados'));
}

$resCheckLdap = Ldap::checkLDAPConn($frmLdapServer, $frmLdapBindUser, $frmLdapBindPass, $frmLdapBase, $frmLdapGroup);

if ($resCheckLdap === false) {
    Response::printJSON(_('Error de conexión a LDAP') . ';;' . _('Revise el registro de eventos para más detalles'));
} else {
    Response::printJSON(_('Conexión a LDAP correcta') . ';;' . _('Objetos encontrados') . ': ' . $resCheckLdap, 0);
}