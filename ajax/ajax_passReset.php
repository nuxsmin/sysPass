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

use SP\Auth\Auth;
use SP\Core\SessionUtil;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Html\Html;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserPassRecover;
use SP\Mgmt\Users\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$userLogin = Request::analyze('login');
$userEmail = Request::analyze('email');
$userPass = Request::analyzeEncrypted('pass');
$userPassR = Request::analyzeEncrypted('passR');


if ($userLogin && $userEmail) {
    $Log = new Log(_('Recuperación de Clave'));
    $Log->addDetailsHtml(_('Solicitado para'), sprintf('%s (%s)', $userLogin, $userEmail));

    $UserData = User::getItem()->getByLogin($userLogin)->getItemData();

    if ($UserData->getUserEmail() === $userEmail
        && Auth::mailPassRecover($UserData)
    ) {
        $Log->addDescription(_('Solicitud enviada'));
        $Log->writeLog();

        Response::printJSON($Log->getDescription() . ';;' . _('En breve recibirá un correo para completar la solicitud.'), 0, 'goLogin();');
    }

    $Log->addDescription(_('Solicitud no enviada'));
    $Log->addDescription(_('Compruebe datos de usuario o consulte con el administrador'));
    $Log->writeLog();

    Email::sendEmail($Log);

    Response::printJSON($Log->getDescription());
} elseif ($userPass && $userPassR && $userPass === $userPassR) {
    $Log = new Log(_('Modificar Clave Usuario'));

    try {
        UserPassRecover::getItem()->getHashUserId(Request::analyze('hash'));
        UserPass::getItem()->updateUserPass(UserPassRecover::getItem()->getItemData()->getUserpassrUserId(), $userPass);
    } catch (SPException $e) {
        $Log->addDescription($e->getMessage());
        $Log->writeLog();

        Response::printJSON($e->getMessage());
    }

    $Log->addDescription(_('Clave actualizada'));
    $Log->addDetailsHtml(_('Login'), UserPass::getItem()->getItemData()->getUserLogin());
    $Log->writeLog();

    Response::printJSON($Log->getDescription(), 0, 'goLogin();');
} else {
    Response::printJSON(_('La clave es incorrecta o no coincide'));
}