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
    SP\Util::logout();
}

$accountId = SP\Common::parseParams('p', 'accountid', false);
$isHistory = SP\Common::parseParams('p', 'isHistory', false);

if (!$accountId) {
    return;
}

$account = (!$isHistory) ? new SP\Account() : new SP\AccountHistory();

$account->setAccountParentId((isset($_SESSION["accParentId"])) ? $_SESSION["accParentId"] : "");
$account->setAccountId($accountId);

$accountData = $account->getAccountPassData();

if ($isHistory && !$account->checkAccountMPass()) {
    SP\Common::printJSON(_('La clave maestra no coincide'));
}

if (!SP\Acl::checkAccountAccess(SP\Acl::ACTION_ACC_VIEW_PASS, $account->getAccountDataForACL()) || !SP\Acl::checkUserAccess(SP\Acl::ACTION_ACC_VIEW_PASS)) {
    SP\Common::printJSON(_('No tiene permisos para acceder a esta cuenta'));
}

if (!SP\Users::checkUserUpdateMPass()) {
    SP\Common::printJSON(_('Clave maestra actualizada') . '<br>' . _('Reinicie la sesión para cambiarla'));
}

$masterPass = SP\Crypt::getSessionMasterPass();
$accountClearPass = SP\Crypt::getDecrypt($accountData->pass, $masterPass, $accountData->iv);

if (!$isHistory) {
    $account->incrementDecryptCounter();

    $message['action'] = _('Ver Clave');
    $message['text'][] = _('ID') . ': ' . $accountId;
    $message['text'][] = _('Cuenta') . ': ' . $accountData->customer_name . " / " . $accountData->name;

    SP\Log::wrLogInfo($message);
}

$accountPass = htmlentities(trim($accountClearPass), ENT_COMPAT, 'UTF-8');

$data = array(
    'title' => _('Clave de Cuenta'),
//    'acclogin' => _('Usuario') . ': ' . $accountData->login,
    'accpass' => $accountPass
);

SP\Common::printJSON($data, 0);