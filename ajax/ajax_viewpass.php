<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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
require_once APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'init.php';

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    return;
}

$accountId = SP_Common::parseParams('p', 'accountid', false);
$fullTxt = SP_Common::parseParams('p', 'full', 0);
$isHistory = SP_Common::parseParams('p', 'isHistory', 0);

if (!$accountId) {
    return;
}

$account = new SP_Account;
$account->accountParentId = ( isset($_SESSION["accParentId"]) ) ? $_SESSION["accParentId"] : "";
$account->accountId = $accountId;
$account->accountIsHistory = $isHistory;

if (!$isHistory) {
    $accountData = $account->getAccount();

    if (!SP_ACL::checkAccountAccess("accviewpass", $account->getAccountDataForACL()) || !SP_ACL::checkUserAccess("accviewpass")) {
        die('<span class="altTxtRed">' . _('No tiene permisos para acceder a esta cuenta') . '</span>');
    }
} else {
    if ($account->checkAccountMPass()) {
        $accountData = $account->getAccountHistory();
        if (!SP_ACL::checkAccountAccess("accviewpass", $account->getAccountDataForACL()) || !SP_ACL::checkUserAccess("accviewpass")) {
            die('<span class="altTxtRed">' . _('No tiene permisos para acceder a esta cuenta') . '</span>');
        }
    } else {
        echo '<div id="fancyMsg" class="msgError">' . _('La clave maestra no coincide') . '</div>';
        return;
    }
}

if (!SP_Users::checkUserUpdateMPass()) {
    if ($fullTxt) {
        die('<div id="fancyMsg" class="msgError">' . _('Clave maestra actualizada') . '<br>' . _('Reinicie la sesión para cambiarla') . '</div>');
    } else {
        die(_('Clave maestra actualizada') . '<br>' . _('Reinicie la sesión para cambiarla'));
    }
}

$crypt = new SP_Crypt;
$masterPass = $crypt->getSessionMasterPass();
$accountClearPass = $crypt->decrypt($accountData->account_pass, $masterPass, $accountData->account_IV);


if (!$isHistory) {
    $account->incrementDecryptCounter();
}

$message['action'] = _('Ver clave');
$message['text'][] = _('ID') . ': ' . $accountId;
$message['text'][] = _('Cuenta') . ': ' . $accountData->customer_name . " / " . $accountData->account_name;
$message['text'][] = _('IP') . ': ' . $_SERVER['REMOTE_ADDR'];

SP_Log::wrLogInfo($message);

if ($fullTxt) {
    ?>
    <div id="fancyMsg" class="msgInfo">
        <table>
            <tr>
                <td><span class="altTxtBlue"><?php echo _('Usuario'); ?></span></td>
                <td><?php echo $accountData->account_login; ?></td>
            </tr>
            <tr>
                <td><span class="altTxtBlue"><?php echo _('Clave'); ?></span></td>
                <td><?php echo trim($accountClearPass); ?></td>
            </tr>
        </table>
    </div>
    <?php
} else {
    echo trim($accountClearPass);
}
?>