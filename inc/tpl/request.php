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
$accountData = $account->getAccount();

?>

<div id="title" class="midroundup titleNormal"><?php echo _('Solicitar Modificación de Cuenta'); ?></div>

<form method="post" name="requestmodify" id="frmRequestModify" >
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td><td class="valField"><?php echo $accountData->account_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td><td class="valField"><?php echo $accountData->customer_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('URL / IP'); ?></td>
            <td class="valField"><A href="<?php echo $accountData->account_url; ?>" target="_blank"><?php echo $accountData->account_url; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField"><?php echo $accountData->account_login; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Petición'); ?></td>
            <td class="valField">
                <textarea name="description" cols="30" rows="5" placeholder="<?php echo _('Descripción de la petición'); ?>" maxlength="1000"></textarea>
            </td>
        </tr>
    </table>
    <input type="hidden" name="accountid" value="<?php echo $account->accountId; ?>" />
    <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(TRUE); ?>">
    <input type="hidden" name="is_ajax" value="1">
</form>

<div class="action">
    <ul>
        <li>
            <img SRC="imgs/back.png" title="<?php echo _('Atrás'); ?>" class="inputImg" id="btnBack" OnClick="doAction('<?php echo $data['lastaction']; ?>', 'accsearch',<?php echo $account->accountId; ?>)" />
        </li>
        <li>
            <img SRC="imgs/check.png" title="<?php echo _('Enviar'); ?>" class="inputImg" id="btnSave" OnClick="sendRequest();" />
        </li>
    </ul>
</div>