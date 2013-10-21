<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
include_once (APP_ROOT . "/inc/init.php");

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    return;
}

$accountId = ( isset($_POST["accountid"]) ) ? (int) $_POST["accountid"] : 0;
$fullTxt = ( isset($_POST["full"]) ) ? (int) $_POST["full"] : 0;
$isHistory = ( isset($_POST["isHistory"]) ) ? (int) $_POST["isHistory"] : 0;

if ($accountId == 0) {
    return;
}

$objAccount = new SP_Account;
$objAccount->accountParentId = ( isset($_SESSION["accParentId"]) ) ? $_SESSION["accParentId"] : "";
$objAccount->accountId = $accountId;
$objAccount->accountIsHistory = $isHistory;

if (!$isHistory) {
    $objAccount->getAccount();
    if (!$objAccount->checkAccountAccess("accviewpass") || !SP_Users::checkUserAccess("accviewpass"))
        die('<span class="altTxtRed">' . _('No tiene permisos para acceder a esta cuenta') . '</span>');
} else {
    if ($objAccount->checkAccountMPass()) {
        $objAccount->getAccountHistory();
        if (!$objAccount->checkAccountAccess("accviewpass") || !SP_Users::checkUserAccess("accviewpass"))
            die('<span class="altTxtRed">' . _('No tiene permisos para acceder a esta cuenta') . '</span>');
    } else {
        echo '<div id="fancyMsg" class="msgError">' . _('La clave maestra no coincide') . '</div>';
        return;
    }
}

if (!SP_Users::checkUserUpdateMPass()) {
    echo ( $fullTxt ) ? '<div id="fancyMsg" class="msgError">' . _('Clave maestra actualizada') . '<BR />' . _('Reinicie la sesión para cambiarla') . '</div>' : _('Clave maestra actualizada') . '<BR />' . _('Reinicie la sesión para cambiarla');
    return;
}

$objCrypt = new SP_Crypt;

//$strDecrypted = $objCrypt->decrypt($objAccount->accountPass, $_SESSION["mPass"], $objAccount->accountIV);

$mPass = $objCrypt->decrypt($_SESSION["mPass"], $_SESSION['mPassPwd'], $_SESSION['mPassIV']);
$strDecrypted = $objCrypt->decrypt($objAccount->accountPass, $mPass, $objAccount->accountIV);


if (!$isHistory)
    $objAccount->incrementDecryptCounter($accountId);

$message['action'] = _('Ver clave');
$message['text'][] = _('ID') . ': ' . $accountId;
$message['text'][] = _('Cuenta') . ': ' . $objAccount->accountCustomerName . " / " . $objAccount->accountName;
$message['text'][] = _('IP') . ': ' . $_SERVER['REMOTE_ADDR'];

SP_Common::wrLogInfo($message);

if ($fullTxt) {
    echo '<div id="fancyMsg" class="msgInfo">';
    echo '<table>
        <tr>
            <td><span class="altTxtBlue">' . _('Usuario') . '</span></td>
            <td>' . $objAccount->accountLogin . '</td>
        </tr>
        <tr>
            <td><span class="altTxtBlue">' . _('Clave') . '</span></td>
            <td>' . trim($strDecrypted) . '</td>
        </tr>
        </table>';
    echo '</div>';
} else {
    echo trim($strDecrypted);
}