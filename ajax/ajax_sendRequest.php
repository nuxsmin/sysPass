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

if (!SP\Init::isLoggedIn()) {
    SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Common::parseParams('p', 'sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmAccountId = SP\Common::parseParams('p', 'accountid', 0);
$frmDescription = SP\Common::parseParams('p', 'description');

if (!$frmDescription) {
    SP\Common::printJSON(_('Es necesaria una descripción'));
}

$accountRequestData = SP\Account::getAccountRequestData($frmAccountId);

$recipients = array(
    SP\Users::getUserEmail($accountRequestData->account_userId),
    SP\Users::getUserEmail($accountRequestData->account_userEditId)
);

$requestUsername = SP\Common::parseParams('s', 'uname');
$requestLogin = SP\Common::parseParams('s', 'ulogin');

$message['action'] = _('Solicitud de Modificación de Cuenta');
$message['text'][] = SP\Html::strongText(_('Solicitante') . ': ') . $requestUsername . ' (' . $requestLogin . ')';
$message['text'][] = SP\Html::strongText(_('Cuenta') . ': ') . $accountRequestData->account_name;
$message['text'][] = SP\Html::strongText(_('Cliente') . ': ') . $accountRequestData->customer_name;
$message['text'][] = SP\Html::strongText(_('Descripción') . ': ') . $frmDescription;

$mailto = implode(',', $recipients);

if ($mailto
    && SP\Util::mailrequestIsEnabled()
    && SP\Common::sendEmail($message, $mailto)
) {
    SP\Log::wrLogInfo($message);
    SP\Common::printJSON(_('Solicitud enviada'), 0, "doAction('" . \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH . "');");
}

SP\Common::printJSON(_('Error al enviar la solicitud'));