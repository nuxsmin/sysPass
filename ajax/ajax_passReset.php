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

use SP\Auth\AuthUtil;
use SP\Core\SessionUtil;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserPassRecover;
use SP\Util\Json;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

$JsonResponse = new JsonResponse();

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    $JsonResponse->setDescription(__('CONSULTA INVÁLIDA'));
    Json::returnJson($JsonResponse);
}

$userLogin = Request::analyze('login');
$userEmail = Request::analyze('email');
$userPass = Request::analyzeEncrypted('pass');
$userPassR = Request::analyzeEncrypted('passR');

$Log = new Log();
$LogMessage = $Log->getLogMessage();

if ($userLogin && $userEmail) {
    $LogMessage->setAction(__('Recuperación de Clave', false));
    $LogMessage->addDetailsHtml(__('Solicitado para'), sprintf('%s (%s)', $userLogin, $userEmail));

    $UserData = User::getItem()->getByLogin($userLogin);

    if ($UserData->getUserEmail() === $userEmail
        && AuthUtil::mailPassRecover($UserData)
    ) {
        $LogMessage->addDescription(__('Solicitud enviada', false));
        $Log->writeLog();

        $JsonResponse->setDescription($LogMessage->getDescription());
        $JsonResponse->addMessage(__('En breve recibirá un correo para completar la solicitud.'));
        Json::returnJson($JsonResponse);
    }

    $LogMessage->addDescription(__('Solicitud no enviada', false));
    $LogMessage->addDescription(__('Compruebe datos de usuario o consulte con el administrador', false));
    $Log->writeLog();

    Email::sendEmail($LogMessage);

    $JsonResponse->setDescription($LogMessage->getDescription());
    Json::returnJson($JsonResponse);
} elseif ($userPass && $userPassR && $userPass === $userPassR) {
    $LogMessage->setAction(__('Modificar Clave Usuario', false));

    try {
        $UserPassRecover = UserPassRecover::getItem()->getHashUserId(Request::analyze('hash'));
        UserPass::getItem()->updateUserPass($UserPassRecover->getItemData()->getUserpassrUserId(), $userPass);
    } catch (SPException $e) {
        $LogMessage->addDescription($e->getMessage());
        $Log->writeLog();

        $JsonResponse->setDescription($e->getMessage());
        Json::returnJson($JsonResponse);
    }

    $LogMessage->addDescription(__('Clave actualizada', false));
    $LogMessage->addDetailsHtml(__('Login', false), UserPass::getItem()->getItemData()->getUserLogin());
    $Log->writeLog();

    $JsonResponse->setStatus(0);
    $JsonResponse->setDescription($LogMessage->getDescription());
    Json::returnJson($JsonResponse);
} else {
    $JsonResponse->setDescription(_('La clave es incorrecta o no coincide'));
    Json::returnJson($JsonResponse);
}