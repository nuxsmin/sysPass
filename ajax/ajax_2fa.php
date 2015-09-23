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

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

SP\Request::checkReferer('POST');

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$userId = SP\Request::analyze('itemId', 0);
$pin = SP\Request::analyze('security_pin', 0);

$twoFa = new \SP\Auth\Auth2FA($userId, $userLogin);

if($userId && $pin && $twoFa->verifyKey($pin)){
    \SP\Session::set2FApassed(true);

    // Comprobar si existen parámetros adicionales en URL via GET
    foreach ($_POST as $param => $value) {
        if (preg_match('/g_.*/', $param)) {
            $params[] = substr($param, 2) . '=' . $value;
        }
    }

    $urlParams = isset($params) ? '?' . implode('&', $params) : '';

    SP\Common::printJSON(_('Código correcto'), 0, 'redirect(\'index.php\')');
} else {
    \SP\Session::set2FApassed(false);
    SP\Common::printJSON(_('Código incorrecto'));
}