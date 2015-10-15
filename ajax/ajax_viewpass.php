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

use SP\Account\Account;
use SP\Account\AccountHistory;
use SP\Core\Acl;
use SP\Core\Crypt;
use SP\Core\Init;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Log;
use SP\Mgmt\User\UserPass;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$accountId = Request::analyze('accountid', false);
$isHistory = Request::analyze('isHistory', false);

if (!$accountId) {
    return;
}

$Account = (!$isHistory) ? new Account() : new AccountHistory();

$Account->setAccountParentId(\SP\Core\Session::getAccountParentId());
$Account->setAccountId($accountId);

$accountData = $Account->getAccountPassData();

if ($isHistory && !$Account->checkAccountMPass()) {
    Response::printJSON(_('La clave maestra no coincide'));
}

if (!Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_PASS)
    || !Acl::checkAccountAccess(Acl::ACTION_ACC_VIEW_PASS, $Account->getAccountDataForACL())) {
    Response::printJSON(_('No tiene permisos para acceder a esta cuenta'));
} elseif (!UserPass::checkUserUpdateMPass()) {
    Response::printJSON(_('Clave maestra actualizada') . '<br>' . _('Reinicie la sesión para cambiarla'));
}

$accountClearPass = Crypt::getDecrypt($accountData->pass, $accountData->iv);

if (!$isHistory) {
    $Account->incrementDecryptCounter();

    $log = new Log(_('Ver Clave'));
    $log->addDetails(_('ID'), $accountId);
    $log->addDetails(_('Cuenta'), $accountData->customer_name . ' / ' . $accountData->name);
    $log->writeLog();
}

//$accountPass = htmlspecialchars(trim($accountClearPass));

$useImage = intval(Checks::accountPassToImageIsEnabled());

$data = array(
    'title' => _('Clave de Cuenta'),
    'acclogin' => $accountData->login,
    'accpass' => (!$useImage) ? trim($accountClearPass) : \SP\Util\ImageUtil::convertText($accountClearPass),
    'useimage' => $useImage
);

Response::printJSON($data, 0);