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

use SP\Account\Account;
use SP\Core\Session;
use SP\Account\AccountHistory;
use SP\Core\Acl;
use SP\Core\Crypt;
use SP\Core\Init;
use SP\DataModel\AccountExtData;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Log;
use SP\Mgmt\Users\UserPass;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJson(__('La sesión no se ha iniciado o ha caducado'), 10);
}

$accountId = Request::analyze('itemId', false);
$isHistory = Request::analyze('isHistory', false);
$isFull = Request::analyze('isFull', false);

if (!$accountId) {
    return;
}

$AccountData = new AccountExtData();

if (!$isHistory) {
    $AccountData->setAccountId($accountId);
    $Account = new Account($AccountData);
} else {
    $Account = new AccountHistory($AccountData);
    $Account->setId($accountId);
}

$Account->getAccountPassData();

if ($isHistory && !$Account->checkAccountMPass()) {
    Response::printJson(__('La clave maestra no coincide'));
}

$Acl = new Acl(Acl::ACTION_ACC_VIEW_PASS);
$Acl->setAccountData($Account->getAccountDataForACL());

if (!Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_PASS) || !$Acl->checkAccountAccess()) {
    Response::printJson(__('No tiene permisos para acceder a esta cuenta'));
} elseif (!UserPass::getItem(Session::getUserData())->checkUserUpdateMPass()) {
    Response::printJson(__('Clave maestra actualizada') . '<br>' . __('Reinicie la sesión para cambiarla'));
}

$accountClearPass = Crypt::getDecrypt($AccountData->getAccountPass(), $AccountData->getAccountIV());

if (!$isHistory) {
    $Account->incrementDecryptCounter();

    $Log = new Log();
    $LogMessage = $Log->getLogMessage();
    $LogMessage->setAction(__('Ver Clave', false));
    $LogMessage->addDetails(__('ID', false), $accountId);
    $LogMessage->addDetails(__('Cuenta', false), $AccountData->getCustomerName() . ' / ' . $AccountData->getAccountName());
    $Log->writeLog();
}

$useImage = (int)Checks::accountPassToImageIsEnabled();

if (!$useImage) {
    $pass = $isFull ? htmlentities(trim($accountClearPass)) : trim($accountClearPass);
} else {
    $pass = \SP\Util\ImageUtil::convertText($accountClearPass);
}

$data = [
    'title' => __('Clave de Cuenta'),
    'acclogin' => $AccountData->getAccountLogin(),
    'accpass' => $pass,
    'useimage' => $useImage
];

Response::printJson($data, 0);