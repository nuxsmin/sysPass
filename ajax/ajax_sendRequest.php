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
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Html\Html;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$frmAccountId = Request::analyze('accountid', 0);
$frmDescription = Request::analyze('description');

if (!$frmDescription) {
    Response::printJSON(_('Es necesaria una descripción'));
}

$accountRequestData = AccountUtil::getAccountRequestData($frmAccountId);

$recipients = array(
    UserUtil::getUserEmail($accountRequestData->account_userId),
    UserUtil::getUserEmail($accountRequestData->account_userEditId)
);

$requestUsername = Session::getUserName();
$requestLogin = Session::getUserLogin();

$Log = new Log(_('Solicitud de Modificación de Cuenta'));
$Log->addDetails(Html::strongText(_('Solicitante')), sprintf('%s (%s)', $requestUsername, $requestLogin));
$Log->addDetails(Html::strongText(_('Cuenta')), $accountRequestData->account_name);
$Log->addDetails(Html::strongText(_('Cliente')), $accountRequestData->customer_name);
$Log->addDetails(Html::strongText(_('Descripción')), $frmDescription);

$mailto = implode(',', $recipients);

if (strlen($mailto) > 1
    && Checks::mailrequestIsEnabled()
    && Email::sendEmail($Log, $mailto)
) {
    $Log->writeLog();

    Response::printJSON(_('Solicitud enviada'), 0, "doAction('" . ActionsInterface::ACTION_ACC_SEARCH . "');");
}

Response::printJSON(_('Error al enviar la solicitud'));