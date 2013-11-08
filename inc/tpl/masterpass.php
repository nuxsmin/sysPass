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

$action = $data['action'];
$activeTab = $data['active'];

SP_Users::checkUserAccess($action) || SP_Html::showCommonError('unavailable');

$lastUpdateMPass = SP_Config::getConfigValue("lastupdatempass");
?>

<form method="post" name="frmCrypt" id="frmCrypt">
    <table class="data tblConfig round">
    <? if ( $lastUpdateMPass > 0 ): ?>
        <tr>
            <td class="descField">
                <? echo _('Último cambio'); ?>
            </td>
            <td class="valField">
                <? echo date("r",$lastUpdateMPass); ?>
            </td>
        </tr>
    <? endif; ?>
        <tr>
            <td class="descField">
                <? echo _('Clave Maestra actual'); ?>
            </td>
            <td class="valField">
                <input type="password" name="curMasterPwd" maxlength="255">
            </td>
        </tr>
        <tr>
            <td class="descField">
                <? echo _('Nueva Clave Maestra'); ?>
            </td>
            <td class="valField">
                <input type="password" name="newMasterPwd" maxlength="255">
            </td>
        </tr>
        <tr>
            <td class="descField">
                <? echo _('Nueva Clave Maestra (repetir)'); ?>
            </td>
            <td class="valField">
                <input type="password" name="newMasterPwdR" maxlength="255">
            </td>
        </tr>
        <tr>
            <td class="descField">
                <? echo _('No modificar cuentas'); ?>
                <? SP_Common::printHelpButton("config", 16); ?>
            </td>
            <td class="valField">
                <label for="chkNoAccountChange"><? echo _('NO'); ?></label>
                <input type="checkbox" class="checkbox" name="chkNoAccountChange" id="chkNoAccountChange" />
            </td>
        </tr>
        <tr>
            <td class="descField">
                <? echo _('Confirmar cambio'); ?>
            </td>
            <td class="valField">
                <img src="imgs/warning.png" ALT="<? echo _('Atención'); ?>" class="iconMini" />
                <? echo _('Guarde la nueva clave en un lugar seguro.'); ?>
                <br>
                <img src="imgs/warning.png" ALT="<? echo _('Atención'); ?>" class="iconMini" />
                <? echo _('Se volverán a encriptar las claves de todas las cuentas.'); ?>
                <br>
                <img src="imgs/warning.png" ALT="<? echo _('Atención'); ?>" class="iconMini" />
                <? echo _('Los usuarios deberán de introducir la nueva clave maestra.'); ?>
                <br>
                <br>
                <label for="confirmPassChange"><? echo _('NO'); ?></label>
                <input type="checkbox" class="checkbox" name="confirmPassChange"  id="confirmPassChange" />
            </td>
        </tr>
    </table>
	<input type="hidden" name="active" value="<? echo $activeTab ?>" />
    <input type="hidden" name="action" value="crypt" />
    <input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey(TRUE); ?>">
</form>
<div class="action">
    <ul>
        <li>
            <img src="imgs/check.png" title="<? echo _('Guardar'); ?>" class="inputImg" OnClick="configMgmt('savempwd');" />
        </li>
    </ul>
</div>

<script>
    $('#frmCrypt .checkbox').button();
    $('#frmCrypt .ui-button').click(function(){
        // El cambio de clase se produce durante el evento de click
        // Si tiene la clase significa que el estado anterior era ON y ahora es OFF
        if ( $(this).hasClass('ui-state-active') ){
            $(this).children().html('<? echo _('NO'); ?>');
        } else{
            $(this).children().html('<? echo _('SI'); ?>');
        }
    });
</script>