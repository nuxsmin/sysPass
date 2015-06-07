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
    SP_Util::logout();
}

$accountId = SP_Common::parseParams('p', 'accountid', false);
$isHistory = SP_Common::parseParams('p', 'isHistory', false);

if (!$accountId) {
    return;
}

$account = new SP_Accounts;
$account->accountParentId = ( isset($_SESSION["accParentId"]) ) ? $_SESSION["accParentId"] : "";
$account->accountId = $accountId;
//$account->accountIsHistory = $isHistory;

$accountData = $account->getAccountPass($isHistory);

if ($isHistory && !$account->checkAccountMPass()){
    SP_Common::printJSON(_('La clave maestra no coincide'));
}

$accountData = $account->getAccountPass($isHistory);

if (!SP_Acl::checkAccountAccess(SP_Acl::ACTION_ACC_VIEW_PASS, $account->getAccountDataForACL()) || !SP_Acl::checkUserAccess(SP_Acl::ACTION_ACC_VIEW_PASS)) {
    SP_Common::printJSON(_('No tiene permisos para acceder a esta cuenta'));
}

if (!SP_Users::checkUserUpdateMPass()) {
    SP_Common::printJSON(_('Clave maestra actualizada') . '<br>' . _('Reinicie la sesión para cambiarla'));
}

$masterPass = SP_Crypt::getSessionMasterPass();
$accountClearPass = SP_Crypt::getDecrypt($accountData->pass, $masterPass, $accountData->iv);

if (!$isHistory) {
    $account->incrementDecryptCounter();

    $message['action'] = _('Ver Clave');
    $message['text'][] = _('ID') . ': ' . $accountId;
    $message['text'][] = _('Cuenta') . ': ' . $accountData->customer_name . " / " . $accountData->name;

    SP_Log::wrLogInfo($message);
}

$accountPass = htmlentities(trim($accountClearPass),ENT_COMPAT,'UTF-8');

$data = array(
    'title' => _('Clave de Cuenta'),
//    'acclogin' => _('Usuario') . ': ' . $accountData->login,
    'accpass' => $accountPass
);

SP_Common::printJSON($data, 0);