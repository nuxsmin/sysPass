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

$activeTab = $data['active'];

$user = SP_Users::getUserData($data['itemid']);

$isDemo = SP_Config::getValue('demoenabled', 0);
$isDisabled = ( $isDemo ) ? 'disabled' : '';

$profilesSelProp = array('name' => 'profileid',
    'id' => 'selProfile',
    'class' => '',
    'size' => 1,
    'label' => '',
    'selected' => $user['user_profileId'],
    'default' => '',
    'js' => '',
    'attribs' => array('required'));

$groupsSelProp = array('name' => 'groupid',
    'id' => 'selGroup',
    'class' => '',
    'size' => 1,
    'label' => '',
    'selected' => $user['user_groupId'],
    'default' => '',
    'js' => '',
    'attribs' => array('required'));

$ro = ( $user['checks']['user_isLdap'] ) ? "READONLY" : "";
?>

<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $data['header']; ?></h2>
    <form method="post" name="frmUsers" id="frmUsers">
        <table class="fancydata">
            <tbody>
                <tr>
                    <td class="descField"><? echo _('Nombre') ?></td>
                    <td class="valField">
                        <input type="text" id="usrname" name="name" title="<? echo _('Nombre de usuario completo'); ?>" class="txtuser" maxlength="80" value="<? echo $user['user_name']; ?>" />
                    </td>
                </tr>

                <tr>
                    <td class="descField"><? echo _('Login'); ?>'</td><td class="valField">
                        <input type="text" id="usrlogin" name="login" title="<? echo _('Login de inicio de sesión'); ?>" class="txtlogin" maxlength="10" value="<? echo $user['user_login'] ?>" <? echo $ro; ?> />
                        <? if ($ro): ?>
                            <img src="imgs/ldap.png" title="<? echo _('Usuario de LDAP'); ?>" class="iconMini" />
                        <? endif; ?>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><? echo _('Perfil'); ?></td>
                    <td class="valField">
                    <?  SP_Html::printSelect(SP_Users::getValuesForSelect('usrProfiles', 'userprofile_id', 'userprofile_name'), $profilesSelProp); ?>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><? echo _('Grupo'); ?></td>
                    <td class="valField">
                    <? SP_Html::printSelect(SP_Users::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'), $groupsSelProp); ?>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><? echo _('Email'); ?></td>
                    <td class="valField">
                        <input type="text" id="usremail" name="email" title="<? echo _('Dirección de correo'); ?>" class="txtemail" maxlength="50" value="<? echo $user['user_email']; ?>" />
                    </td>

                </tr>

                <? if ( $user['action'] === 1): ?>
                    <tr>
                        <td class="descField"><? echo _('Clave'); ?></td>
                        <td class="valField">
                            <input type="password" id="usrpass" name="pass" class="txtpass" maxlength="50" OnFocus="$('#passLevel').show();
                                    $('#resFancyAccion').hide();" OnKeyUp="checkPassLevel(this.value)" />
                            <img src="imgs/genpass.png" title="<? echo _('Generar clave aleatoria') ?>" class="inputImg" OnClick="$('#resFancyAccion').hide();
                                    password(11, true);" />
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><? echo _('Clave (repetir)'); ?></td>
                        <td class="valField">
                            <input type="password" id="usrpassv" name="passv" class="txtpassv" maxlength="50" />
                            <span id="passLevel" title="<? echo _('Nivel de fortaleza de la clave'); ?>" ></span>
                        </td>
                    </tr>
                <? endif; ?>

                <tr>
                    <td class="descField"><? echo _('Notas') ?></td>
                    <td class="valField">
                        <textarea name="notes" id="usrnotes" rows="4"><? echo $user['user_notes']; ?></textarea>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><? echo _('Opciones'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnUserOptions" class="btnChecks">
                            <? if ($_SESSION["uisadminapp"] === 1 || $isDemo): ?>
                                <label for="usradminapp" title="<? echo _('Administrador de la aplicación'); ?>"><? echo _('Admin. Aplicación'); ?></label>
                                <input type="checkbox" id="usradminapp" name="adminapp" <? echo $user['checks']['user_isAdminApp'] . ' ' . $isDisabled; ?>/>
                                <label for="usradminacc" title="<? echo _('Administrador de cuentas') ?>"><? echo _('Admin. Cuentas') ?></label>
                                <input type="checkbox" id="usradminacc" name="adminacc" <? echo $user['checks']['user_isAdminAcc'] . ' ' . $isDisabled; ?> />
                            <? endif; ?>
                            <label for="usrdisabled" title="<? echo _('Deshabilitado'); ?>"><? echo _('Deshabilitado'); ?></label>
                            <input type="checkbox" id="usrdisabled" name="disabled" <? echo $user['checks']['user_isDisabled']; ?>/>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="active" value="<? echo $activeTab ?>" />
        <input type="hidden" name="ldap" value="<? echo $user['user_isLdap']; ?>" />
        <input type="hidden" name="id" value="<? echo $user['user_id']; ?>" />
        <input type="hidden" name="action" value="<? echo $user['action']; ?>" />
        <input type="hidden" name="type" value="<?php echo $data['itemtype']; ?>" />
        <input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey() ?>">
        <input type="hidden" name="is_ajax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <ul>
            <li><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" OnClick="usersMgmt('frmUsers');" /></li>
        </ul>
    </div>
</div>        
<script>
    $("#btnUserOptions").buttonset();
    $("#selProfile").chosen({
            placeholder_text_single: "<? echo _('Seleccionar Perfil'); ?>", 
            disable_search_threshold: 10,
            no_results_text: "<? echo _('Sin resultados'); ?>"
    });
    $("#selGroup").chosen({
            placeholder_text_single: "<? echo _('Seleccionar Grupo'); ?>", 
            disable_search_threshold: 10,
            no_results_text: "<? echo _('Sin resultados'); ?>"
    });
</script>