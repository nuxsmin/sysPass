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

use SP\Core\SessionUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

\SP\Http\Request::checkReferer('POST');

$sk = \SP\Http\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    \SP\Http\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$userId = \SP\Http\Request::analyze('itemId', 0);
$pin = \SP\Http\Request::analyze('security_pin', 0);

$twoFa = new \SP\Auth\Auth2FA($userId, $userLogin);

if($userId && $pin && $twoFa->verifyKey($pin)){
    \SP\Core\Session::set2FApassed(true);

    // Comprobar si existen parámetros adicionales en URL via GET
    foreach ($_POST as $param => $value) {
        if (preg_match('/g_.*/', $param)) {
            $params[] = substr($param, 2) . '=' . $value;
        }
    }

    $urlParams = isset($params) ? '?' . implode('&', $params) : '';

    \SP\Http\Response::printJSON(_('Código correcto'), 0, 'redirect(\'index.php\')');
} else {
    \SP\Core\Session::set2FApassed(false);
    \SP\Http\Response::printJSON(_('Código incorrecto'));
}