<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Http\Request;
use SP\Http\Response;
use SP\Import\Migrate;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJson(_('La sesión no se ha iniciado o ha caducado'), 10);
}

if (Checks::demoIsEnabled()) {
    Response::printJson(_('Ey, esto es una DEMO!!'));
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJson(_('CONSULTA INVÁLIDA'));
}

$frmDBUser = Request::analyze('dbuser');
$frmDBPass = Request::analyzeEncrypted('dbpass');
$frmDBName = Request::analyze('dbname');
$frmDBHost = Request::analyze('dbhost');
$frmMigrateEnabled = Request::analyze('chkmigrate', 0, false, 1);

if (!$frmMigrateEnabled) {
    Response::printJson(_('Confirmar la importación de cuentas'));
} elseif (!$frmDBUser) {
    Response::printJson(_('Es necesario un usuario de conexión'));
} elseif (!$frmDBPass) {
    Response::printJson(_('Es necesaria una clave de conexión'));
} elseif (!$frmDBName) {
    Response::printJson(_('Es necesario el nombre de la BBDD'));
} elseif (!$frmDBHost) {
    Response::printJson(_('Es necesario un nombre de host'));
}

$options['dbhost'] = $frmDBHost;
$options['dbname'] = $frmDBName;
$options['dbuser'] = $frmDBUser;
$options['dbpass'] = $frmDBPass;

$res = Migrate::migrate($options);

if (is_array($res['error'])) {
    foreach ($res['error'] as $error) {
        $errors [] = $error['description'];
        $errors [] = $error['hint'];
        error_log($error['hint']);
    }

    $out = implode('<br>', $errors);
    Response::printJson($out);
} elseif (is_array($res['ok'])) {
    $out = implode('<br>', $res['ok']);

    Response::printJson($out, 0);
}