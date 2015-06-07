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

if (!SP_Init::isLoggedIn()) {
    SP_Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmAccountId = SP_Common::parseParams('p', 'accountid', 0);
$frmDescription = SP_Common::parseParams('p', 'description');

if (!$frmDescription) {
    SP_Common::printJSON(_('Es necesaria una descripción'));
}

$accountRequestData = SP_Accounts::getAccountRequestData($frmAccountId);

$recipients = array(
    SP_Users::getUserEmail($accountRequestData->account_userId),
    SP_Users::getUserEmail($accountRequestData->account_userEditId)
);

$requestUsername = SP_Common::parseParams('s', 'uname');
$requestLogin = SP_Common::parseParams('s', 'ulogin');

$message['action'] = _('Solicitud de Modificación de Cuenta');
$message['text'][] = SP_Html::strongText(_('Solicitante') . ': ') . $requestUsername . ' (' . $requestLogin . ')';
$message['text'][] = SP_Html::strongText(_('Cuenta') . ': ') . $accountRequestData->account_name;
$message['text'][] = SP_Html::strongText(_('Cliente') . ': ') . $accountRequestData->customer_name;
$message['text'][] = SP_Html::strongText(_('Descripción') . ': ') . $frmDescription;

$mailto = implode(',', $recipients);

if ($mailto
    && SP_Util::mailrequestIsEnabled()
    && SP_Common::sendEmail($message, $mailto)
) {
    SP_Log::wrLogInfo($message);
    SP_Common::printJSON(_('Solicitud enviada'), 0, "doAction('accsearch');");
}

SP_Common::printJSON(_('Error al enviar la solicitud'));