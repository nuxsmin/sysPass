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

use SP\Account\AccountUtil;
use SP\Core\SessionUtil;
use SP\Mgmt\User\UserUtil;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

\SP\Http\Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    \SP\Http\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = \SP\Http\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    \SP\Http\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmAccountId = \SP\Http\Request::analyze('accountid', 0);
$frmDescription = \SP\Http\Request::analyze('description');

if (!$frmDescription) {
    \SP\Http\Response::printJSON(_('Es necesaria una descripción'));
}

$accountRequestData = AccountUtil::getAccountRequestData($frmAccountId);

$recipients = array(
    UserUtil::getUserEmail($accountRequestData->account_userId),
    UserUtil::getUserEmail($accountRequestData->account_userEditId)
);

$requestUsername = \SP\Core\Session::getUserName();
$requestLogin = \SP\Core\Session::getUserLogin();

$log = new \SP\Log\Log(_('Solicitud de Modificación de Cuenta'));
$log->addDescription(\SP\Html\Html::strongText(_('Solicitante') . ': ') . $requestUsername . ' (' . $requestLogin . ')');
$log->addDescription(\SP\Html\Html::strongText(_('Cuenta') . ': ') . $accountRequestData->account_name);
$log->addDescription(\SP\Html\Html::strongText(_('Cliente') . ': ') . $accountRequestData->customer_name);
$log->addDescription(\SP\Html\Html::strongText(_('Descripción') . ': ') . $frmDescription);

$mailto = implode(',', $recipients);

if (strlen($mailto) > 1
    && Checks::mailrequestIsEnabled()
    && \SP\Log\Email::sendEmail($log, $mailto)
) {
    $log->writeLog();

    \SP\Http\Response::printJSON(_('Solicitud enviada'), 0, "doAction('" . \SP\Core\ActionsInterface::ACTION_ACC_SEARCH . "');");
}

\SP\Http\Response::printJSON(_('Error al enviar la solicitud'));