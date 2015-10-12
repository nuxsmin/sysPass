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

use SP\Http\Request;
use SP\Core\SessionUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    \SP\Http\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = \SP\Http\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    \SP\Http\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmLdapServer = \SP\Http\Request::analyze('ldap_server');
$frmLdapBase = \SP\Http\Request::analyze('ldap_base');
$frmLdapGroup = \SP\Http\Request::analyze('ldap_group');
$frmLdapBindUser = \SP\Http\Request::analyze('ldap_binduser');
$frmLdapBindPass = \SP\Http\Request::analyzeEncrypted('ldap_bindpass');

if (!$frmLdapServer || !$frmLdapBase || !$frmLdapBindUser || !$frmLdapBindPass) {
    \SP\Http\Response::printJSON(_('Los parámetros de LDAP no están configurados'));
}

$resCheckLdap = \SP\Auth\Ldap::checkLDAPConn($frmLdapServer, $frmLdapBindUser, $frmLdapBindPass, $frmLdapBase, $frmLdapGroup);

if ($resCheckLdap === false) {
    \SP\Http\Response::printJSON(_('Error de conexión a LDAP') . ';;' . _('Revise el registro de eventos para más detalles'));
} else {
    \SP\Http\Response::printJSON(_('Conexión a LDAP correcta') . ';;' . _('Objetos encontrados') . ': ' . $resCheckLdap, 0);
}