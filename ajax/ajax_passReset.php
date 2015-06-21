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

SP\Util::checkReferer('POST');

$sk = SP\Common::parseParams('p', 'sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$userLogin = SP\Common::parseParams('p', 'login');
$userEmail = SP\Common::parseParams('p', 'email');
$userPass = SP\Common::parseParams('p', 'pass');
$userPassV = SP\Common::parseParams('p', 'passv');
$hash = SP\Common::parseParams('p', 'hash');
$time = SP\Common::parseParams('p', 'time');

$message['action'] = _('Recuperación de Clave');

if ($userLogin && $userEmail) {
    if (SP\Auth::mailPassRecover($userLogin, $userEmail)) {
        $message['text'][] = SP\Html::strongText(_('Solicitado para') . ': ') . ' ' . $userLogin . ' (' . $userEmail . ')';

        SP\Common::sendEmail($message);
        SP\Log::wrLogInfo($message);
        SP\Common::printJSON(_('Solicitud enviada') . ';;' . _('En breve recibirá un correo para completar la solicitud.'), 0, 'goLogin();');
    } else {
        $message['text'][] = 'ERROR';
        $message['text'][] = SP\Html::strongText(_('Solicitado para') . ': ') . ' ' . $userLogin . ' (' . $userEmail . ')';

        SP\Common::sendEmail($message);
        SP\Log::wrLogInfo($message);
        SP\Common::printJSON(_('No se ha podido realizar la solicitud. Consulte con el administrador.'));
    }
}

if ($userPass && $userPassV && $userPass === $userPassV) {
    $userId = SP\Users::checkHashPassRecover($hash);

    if ($userId) {
        $user = new SP\Users();

        $user->userId = $userId;
        $user->userPass = $userPass;

        if ($user->updateUserPass() && SP\Users::updateHashPassRecover($hash)) {
            $message['action'] = _('Modificar Clave Usuario');
            $message['text'][] = SP\Html::strongText(_('Login') . ': ') . $user->getUserLoginById($userId);

            SP\Log::wrLogInfo($message);
            SP\Common::sendEmail($message);

            SP\Common::printJSON(_('Clave actualizada'), 0, 'goLogin();');
        }
    }

    SP\Common::printJSON(_('Error al modificar la clave'));
} else {
    SP\Common::printJSON(_('La clave es incorrecta o no coincide'));
}