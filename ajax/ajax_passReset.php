<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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
require_once APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'Init.php';

SP_Util::checkReferer('POST');

$sk = SP_Common::parseParams('p', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$userLogin = SP_Common::parseParams('p', 'login');
$userEmail = SP_Common::parseParams('p', 'email');
$userPass = SP_Common::parseParams('p', 'pass');
$userPassV = SP_Common::parseParams('p', 'passv');
$hash = SP_Common::parseParams('p', 'hash');
$time = SP_Common::parseParams('p', 'time');

$message['action'] = _('Recuperación de Clave');

if ($userLogin && $userEmail) {
    if (SP_Auth::mailPassRecover($userLogin, $userEmail)) {
        $message['text'][] = SP_Html::strongText(_('Solicitado para') . ': ') . ' ' . $userLogin . ' (' . $userEmail . ')';

        SP_Common::sendEmail($message);
        SP_Log::wrLogInfo($message);
        SP_Common::printJSON(_('Solicitud enviada') . ';;' . _('En breve recibirá un correo para completar la solicitud.'), 0, 'goLogin();');
    } else {
        $message['text'][] = 'ERROR';
        $message['text'][] = SP_Html::strongText(_('Solicitado para') . ': ') . ' ' . $userLogin . ' (' . $userEmail . ')';

        SP_Common::sendEmail($message);
        SP_Log::wrLogInfo($message);
        SP_Common::printJSON(_('No se ha podido realizar la solicitud. Consulte con el administrador.'));
    }
}

if ($userPass && $userPassV && $userPass === $userPassV) {
    $userId = SP_Users::checkHashPassRecover($hash);

    if ($userId) {
        $user = new SP_Users();

        $user->userId = $userId;
        $user->userPass = $userPass;

        if ($user->updateUserPass() && SP_Users::updateHashPassRecover($hash)) {
            $message['action'] = _('Modificar Clave Usuario');
            $message['text'][] = SP_Html::strongText(_('Login') . ': ') . $user->getUserLoginById($userId);

            SP_Log::wrLogInfo($message);
            SP_Common::sendEmail($message);

            SP_Common::printJSON(_('Clave actualizada'), 0, 'goLogin();');
        }
    }

    SP_Common::printJSON(_('Error al modificar la clave'));
} else {
    SP_Common::printJSON(_('La clave es incorrecta o no coincide'));
}