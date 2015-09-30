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

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

if (SP\Util::demoIsEnabled()) {
    SP\Common::printJSON(_('Ey, esto es una DEMO!!'));
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmDBUser = SP\Request::analyze('dbuser');
$frmDBPass = SP\Request::analyzeEncrypted('dbpass');
$frmDBName = SP\Request::analyze('dbname');
$frmDBHost = SP\Request::analyze('dbhost');
$frmMigrateEnabled = SP\Request::analyze('chkmigrate', 0, false, 1);

if (!$frmMigrateEnabled) {
    SP\Common::printJSON(_('Confirmar la importación de cuentas'));
} elseif (!$frmDBUser) {
    SP\Common::printJSON(_('Es necesario un usuario de conexión'));
} elseif (!$frmDBPass) {
    SP\Common::printJSON(_('Es necesaria una clave de conexión'));
} elseif (!$frmDBName) {
    SP\Common::printJSON(_('Es necesario el nombre de la BBDD'));
} elseif (!$frmDBHost) {
    SP\Common::printJSON(_('Es necesario un nombre de host'));
}

$options['dbhost'] = $frmDBHost;
$options['dbname'] = $frmDBName;
$options['dbuser'] = $frmDBUser;
$options['dbpass'] = $frmDBPass;

$res = SP\Migrate::migrate($options);

if (is_array($res['error'])) {
    foreach ($res['error'] as $error) {
        $errors [] = $error['description'];
        $errors [] = $error['hint'];
        error_log($error['hint']);
    }

    $out = implode('<br>', $errors);
    SP\Common::printJSON($out);
} elseif (is_array($res['ok'])) {
    $out = implode('<br>', $res['ok']);

    SP\Common::printJSON($out, 0);
}