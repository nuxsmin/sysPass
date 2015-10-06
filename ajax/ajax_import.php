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

use SP\Request;
use SP\SessionUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

if (SP\Util::demoIsEnabled()) {
    SP\Response::printJSON(_('Ey, esto es una DEMO!!'));
}

$sk = SP\Request::analyze('sk', false);
$defaultUser= SP\Request::analyze('defUser', 0);
$defaultGroup = SP\Request::analyze('defGroup', 0);
$importPwd = SP\Request::analyzeEncrypted('importPwd');
$csvDelimiter = SP\Request::analyze('csvDelimiter');

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    SP\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

SP\Import::setDefUser($defaultUser);
SP\Import::setDefGroup($defaultGroup);
SP\Import::setImportPwd($importPwd);
SP\Import::setCsvDelimiter($csvDelimiter);

$res = SP\Import::doImport($_FILES["inFile"]);

if (isset($res['error']) && is_array($res['error'])) {
    error_log($res['error']['hint']);

    $out = implode('\n\n', $res['error']);

    SP\Response::printJSON($out);
} else if (is_array($res['ok'])) {
    $out = implode('\n\n', $res['ok']);

    SP\Response::printJSON($out, 0);
}