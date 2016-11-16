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

use SP\Auth\Ldap\LdapMsAds;
use SP\Auth\Ldap\LdapStd;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Util\Json;
use SP\Util\Wiki\DokuWikiApi;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

$Json = new JsonResponse();

if (!Init::isLoggedIn()) {
    $Json->setDescription(_('La sesión no se ha iniciado o ha caducado'));
    $Json->setStatus(10);
    Json::returnJson($Json);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    $Json->setDescription(_('CONSULTA INVÁLIDA'));
    Json::returnJson($Json);
}

$frmType = Request::analyze('type');

if ($frmType === 'ldap') {
    $ldapServer = Request::analyze('ldap_server');
    $ldapBase = Request::analyze('ldap_base');
    $ldapGroup = Request::analyze('ldap_group');
    $ldapBindUser = Request::analyze('ldap_binduser');
    $ldapBindPass = Request::analyzeEncrypted('ldap_bindpass');

    if (!$ldapServer || !$ldapBase || !$ldapBindUser || !$ldapBindPass) {
        $Json->setDescription(_('Los parámetros de LDAP no están configurados'));
        Json::returnJson($Json);
    }

    if (Request::analyze('ldap_enabled', false, false, true)) {
        $Ldap = new  LdapMsAds();
    } else {
        $Ldap = new  LdapStd();
    }

    $Ldap->setServer($ldapServer);
    $Ldap->setSearchBase($ldapBase);
    $Ldap->setGroup($ldapGroup);
    $Ldap->setBindDn($ldapBindUser);
    $Ldap->setBindPass($ldapBindPass);

    $resCheckLdap = $Ldap->checkConnection();

    if ($resCheckLdap === false) {
        $Json->setDescription(_('Error de conexión a LDAP'));
        $Json->addMessage(_('Revise el registro de eventos para más detalles'));
    } else {
        $Json->setDescription(_('Conexión a LDAP correcta'));
        $Json->addMessage(sprintf(_('Objetos encontrados: %d'), $resCheckLdap));
        $Json->setStatus(0);
    }

    Json::returnJson($Json);
} elseif ($frmType === 'dokuwiki') {
    $frmDokuWikiUrl = Request::analyze('dokuwiki_url');
    $frmDokuWikiUser = Request::analyze('dokuwiki_user');
    $frmDokuWikiPass = Request::analyzeEncrypted('dokuwiki_pass');

    if (!$frmDokuWikiUrl) {
        $Json->setDescription(_('Los parámetros de DokuWiki no están configurados'));
        Json::returnJson($Json);
    }

    try {
        $DokuWikiApi = DokuWikiApi::checkConnection($frmDokuWikiUrl, $frmDokuWikiUser, $frmDokuWikiPass);

        $dokuWikiVersion = $DokuWikiApi->getVersion();
        $version = is_array($dokuWikiVersion) ? $dokuWikiVersion[0] : _('Error');

        $Json->setDescription(_('Conexión correcta'));
        $Json->addMessage(sprintf('%s: %s', _('Versión'), $version));
        $Json->setStatus(0);
    } catch (\SP\Core\Exceptions\SPException $e) {
        $Json->setDescription(_('Error de conexión a DokuWiki'));
        $Json->addMessage(_('Revise el registro de eventos para más detalles'));
    }

    Json::returnJson($Json);
}