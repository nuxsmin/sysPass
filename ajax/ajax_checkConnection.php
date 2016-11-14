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

use SP\Auth\Ldap\Ldap;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Http\Request;
use SP\Http\Response;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJson(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJson(_('CONSULTA INVÁLIDA'));
}
$frmType = Request::analyze('type');

if ($frmType === 'ldap') {
    $frmLdapServer = Request::analyze('ldap_server');
    $frmLdapBase = Request::analyze('ldap_base');
    $frmLdapGroup = Request::analyze('ldap_group');
    $frmLdapBindUser = Request::analyze('ldap_binduser');
    $frmLdapBindPass = Request::analyzeEncrypted('ldap_bindpass');

    if (!$frmLdapServer || !$frmLdapBase || !$frmLdapBindUser || !$frmLdapBindPass) {
        Response::printJson(_('Los parámetros de LDAP no están configurados'));
    }

    $resCheckLdap = Ldap::checkLDAPConn($frmLdapServer, $frmLdapBindUser, $frmLdapBindPass, $frmLdapBase, $frmLdapGroup);

    if ($resCheckLdap === false) {
        Response::printJson(_('Error de conexión a LDAP') . ';;' . _('Revise el registro de eventos para más detalles'));
    } else {
        Response::printJson(_('Conexión a LDAP correcta') . ';;' . _('Objetos encontrados') . ': ' . $resCheckLdap, 0);
    }
} elseif ($frmType === 'dokuwiki') {
    $frmDokuWikiUrl = Request::analyze('dokuwiki_url');
    $frmDokuWikiUser = Request::analyze('dokuwiki_user');
    $frmDokuWikiPass = Request::analyzeEncrypted('dokuwiki_pass');

    if (!$frmDokuWikiUrl) {
        Response::printJson(_('Los parámetros de DokuWiki no están configurados'));
    }

    try {
        $DokuWikiApi = \SP\Util\Wiki\DokuWikiApi::checkConnection($frmDokuWikiUrl, $frmDokuWikiUser, $frmDokuWikiPass);

        $dokuWikiVersion = $DokuWikiApi->getVersion();
        $version = (is_array($dokuWikiVersion)) ? $dokuWikiVersion[0] : _('Error');

        $data = array(
            'description' => _('Conexión correcta'),
            'data' => sprintf('%s: %s', _('Versión'), $version),
        );

        Response::printJson($data, 0);
    } catch (\SP\Core\Exceptions\SPException $e) {
        Response::printJson(_('Error de conexión a DokuWiki') . ';;' . _('Revise el registro de eventos para más detalles'));
    }
}