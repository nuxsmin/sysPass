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
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    \SP\Http\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

if (Checks::demoIsEnabled()) {
    \SP\Http\Response::printJSON(_('Ey, esto es una DEMO!!'));
}

$sk = \SP\Http\Request::analyze('sk', false);
$defaultUser= \SP\Http\Request::analyze('defUser', 0);
$defaultGroup = \SP\Http\Request::analyze('defGroup', 0);
$importPwd = \SP\Http\Request::analyzeEncrypted('importPwd');
$csvDelimiter = \SP\Http\Request::analyze('csvDelimiter');

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    \SP\Http\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

\SP\Import\Import::setDefUser($defaultUser);
\SP\Import\Import::setDefGroup($defaultGroup);
\SP\Import\Import::setImportPwd($importPwd);
\SP\Import\Import::setCsvDelimiter($csvDelimiter);

$res = \SP\Import\Import::doImport($_FILES["inFile"]);

if (isset($res['error']) && is_array($res['error'])) {
    error_log($res['error']['hint']);

    $out = implode('\n\n', $res['error']);

    \SP\Http\Response::printJSON($out);
} else if (is_array($res['ok'])) {
    $out = implode('\n\n', $res['ok']);

    \SP\Http\Response::printJSON($out, 0);
}