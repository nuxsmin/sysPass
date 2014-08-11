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
defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

$account = new SP_Account;
$account->accountId = $data['id'];
$account->lastAction = $data['lastaction'];
$accountData = $account->getAccount();

(!SP_ACL::checkAccountAccess("acceditpass", $account->getAccountDataForACL()) || !SP_ACL::checkUserAccess("acceditpass")) && SP_Html::showCommonError('noaccpermission');
?>

<div id="title" class="midroundup titleOrange"><?php echo _('Modificar Clave de Cuenta'); ?></div>

<form method="post" name="editpass" id="frmEditPass">
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td>
            <td class="valField"><?php echo $accountData->account_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td>
            <td class="valField"><?php echo $accountData->customer_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('URL / IP'); ?></td>
            <td class="valField"><A href="<?php echo $accountData->account_url; ?>"
                                    target="_blank"><?php echo $accountData->account_url; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField"><?php echo $accountData->account_login; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Clave'); ?></td>
            <td class="valField">
                <input type="password" maxlength="255" name="password" onKeyUp="checkPassLevel(this.value)" autocomplete="off">
                <img src="imgs/user-pass.png" title="<?php echo _('La clave generada se mostrará aquí'); ?>"
                     class="inputImg" id="viewPass"/>
                &nbsp;&nbsp;
                <img id="passGen" src="imgs/genpass.png" title="<?php echo _('Generar clave aleatoria'); ?>"
                     class="inputImg"/>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
            <td class="valField"><INPUT type="password" MAXLENGTH="255" name="password2" autocomplete="off">
                <span class="passLevel fullround" title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
            </td>
        </tr>
    </table>
    <input type="hidden" name="savetyp" value="4"/>
    <input type="hidden" name="accountid" value="<?php echo $account->accountId; ?>"/>
    <input type="hidden" name="next" value="acceditpass">
    <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(true); ?>">
    <input type="hidden" name="isAjax" value="1">
</form>

<div class="action">
    <ul>
        <li>
            <img SRC="imgs/back.png" title="<?php echo _('Atrás'); ?>" class="inputImg" id="btnBack"
                 OnClick="doAction('<?php echo $account->lastAction; ?>', 'accsearch',<?php echo $account->accountId; ?>)"/>
        </li>
        <li>
            <img SRC="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" id="btnSave"
                 OnClick="saveAccount('frmEditPass');"/>
        </li>
    </ul>
</div>
<script>
    $('input:password:visible:first').focus();
    $('#passGen').click(function () {
        password(11, true, true);
    });
</script>