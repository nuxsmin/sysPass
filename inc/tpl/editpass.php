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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

$account = new SP_Account;
$account->accountId = $data['id'];
$account->lastAction = $data['lastaction'];
$account->getAccount();

($account->checkAccountAccess("acceditpass") && SP_Users::checkUserAccess("acceditpass")) || SP_Html::showCommonError('nopermission');

?>

<div id="title" class="midroundup titleOrange"><? echo _('Modificar Clave de Cuenta'); ?></div>

 <form method="post" name="editpass" id="frmEditPass" >
    <table class="data round">
        <tr>
            <td class="descField"><? echo _('Nombre'); ?></td><td class="valField"><? echo $account->accountName; ?></td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Cliente'); ?></td><td class="valField"><? echo $account->accountCustomerName; ?></td>
        </tr>
        <tr>
            <td class="descField"><? echo _('URL / IP'); ?></td>
            <td class="valField"><A href="<? echo $account->accountUrl; ?>" target="_blank"><? echo $account->accountUrl; ?></td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Usuario'); ?></td>
            <td class="valField"><? echo $account->accountLogin; ?></td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Clave'); ?></td>
            <td class="valField">
                <input type="password" maxlength="255" name="password" onKeyUp="checkPassLevel(this.value)">
                <img src="imgs/user-pass.png" title="<? echo _('La clave generada se mostrará aquí'); ?>" class="inputImg" id="viewPass" />
                &nbsp;&nbsp;
                <img src="imgs/genpass.png" title="<? echo _('Generar clave aleatoria'); ?>" class="inputImg" OnClick="password(11,true,true);" />
            </td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Clave (repetir)'); ?></td>
            <td class="valField"><INPUT type="password" MAXLENGTH="255" name="password2">
                <span id="passLevel" title="<? echo _('Nivel de fortaleza de la clave'); ?>" ></span>
            </td>
        </tr>
    </table>
    <input type="hidden" name="savetyp" value="4" />
    <input type="hidden" name="accountid" value="<? echo $account->accountId; ?>" />
    <input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey(TRUE); ?>">
    <input type="hidden" name="is_ajax" value="1">
 </form>

 <div class="action">
     <ul>
        <li>
            <img SRC="imgs/back.png" title="<? echo _('Atrás'); ?>" class="inputImg" id="btnBack" OnClick="doAction('<? echo $account->lastAction; ?>','accsearch',<? echo $account->accountId; ?>)" />
        </li>
        <li>
            <img SRC="imgs/check.png" title="<? echo _('Guardar'); ?>" class="inputImg" id="btnSave" OnClick="saveAccount('frmEditPass');" />
        </li>
    </ul>
 </div>