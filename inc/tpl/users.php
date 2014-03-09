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

$activeTab = $data['activeTab'];
$onCloseAction = $data['onCloseAction'];
$isView = $data['view'];

$user = SP_Users::getUserData($data['itemid']);

$isDemo = SP_Util::demoIsEnabled();
$isDisabled = ( $isDemo || $isView ) ? 'disabled' : '';

$profilesSelProp = array('name' => 'profileid',
    'id' => 'selProfile',
    'class' => '',
    'size' => 1,
    'label' => '',
    'selected' => $user['user_profileId'],
    'default' => '',
    'js' => '',
    'attribs' => array('required',$isDisabled));

$groupsSelProp = array('name' => 'groupid',
    'id' => 'selGroup',
    'class' => '',
    'size' => 1,
    'label' => '',
    'selected' => $user['user_groupId'],
    'default' => '',
    'js' => '',
    'attribs' => array('required',$isDisabled));

$ro = ( $user['checks']['user_isLdap'] ) ? "READONLY" : "";
?>

<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $data['header']; ?></h2>
    <form method="post" name="frmUsers" id="frmUsers">
        <table class="fancydata">
            <tbody>
                <tr>
                    <td class="descField"><?php echo _('Nombre') ?></td>
                    <td class="valField">
                        <?php 
                        if ( ! $isView ){
                        ?>
                            <input type="text" id="usrname" name="name" title="<?php echo _('Nombre de usuario completo'); ?>" class="txtuser" maxlength="80" value="<?php echo $user['user_name']; ?>" />
                        <?php 
                        } else{
                            echo $user['user_name'];
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Login'); ?></td><td class="valField">
                        <?php
                        if ( ! $isView ){
                        ?>
                            <input type="text" id="usrlogin" name="login" title="<?php echo _('Login de inicio de sesión'); ?>" class="txtlogin" maxlength="30" value="<?php echo $user['user_login'] ?>" <?php echo $ro; ?> />
                            <?php if ($ro): ?>
                                <img src="imgs/ldap.png" title="<?php echo _('Usuario de LDAP'); ?>" class="iconMini" />
                            <?php endif; ?>
                        <?php
                        } else{
                            echo $user['user_login'];
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Perfil'); ?></td>
                    <td class="valField">
                    <?php  SP_Html::printSelect(DB::getValuesForSelect('usrProfiles', 'userprofile_id', 'userprofile_name'), $profilesSelProp); ?>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Grupo'); ?></td>
                    <td class="valField">
                    <?php SP_Html::printSelect(DB::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'), $groupsSelProp); ?>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Email'); ?></td>
                    <td class="valField">
                        <?php 
                        if ( ! $isView ){
                        ?>
                            <input type="text" id="usremail" name="email" title="<?php echo _('Dirección de correo'); ?>" class="txtemail" maxlength="50" value="<?php echo $user['user_email']; ?>" />
                        <?php
                        } else{
                            echo $user['user_email'];
                        }
                        ?>
                    </td>

                </tr>

                <?php if ( $user['action'] === 1 && ! $isView ): ?>
                    <tr>
                        <td class="descField"><?php echo _('Clave'); ?></td>
                        <td class="valField">
                            <input type="password" id="usrpass" name="pass" class="txtpass" maxlength="50" OnFocus="$('#passLevel').show();
                                    $('#resFancyAccion').hide();" OnKeyUp="checkPassLevel(this.value)" />
                            <img src="imgs/genpass.png" title="<?php echo _('Generar clave aleatoria') ?>" class="inputImg" OnClick="$('#resFancyAccion').hide();
                                    password(11, true);" />
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                        <td class="valField">
                            <input type="password" id="usrpassv" name="passv" class="txtpassv" maxlength="50" />
                            <span id="passLevel" title="<?php echo _('Nivel de fortaleza de la clave'); ?>" ></span>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <td class="descField"><?php echo _('Notas') ?></td>
                    <td class="valField">
                        <textarea name="notes" id="usrnotes" rows="4"><?php echo $user['user_notes']; ?></textarea>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo _('Opciones'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnUserOptions" class="btn-checks round5">
                            <?php if ($_SESSION["uisadminapp"] === 1 || $isDemo): ?>
                                <label for="usradminapp" title="<?php echo _('Administrador de la aplicación'); ?>"><?php echo _('Admin. Aplicación'); ?></label>
                                <input type="checkbox" id="usradminapp" name="adminapp" <?php echo $user['checks']['user_isAdminApp'] . ' ' . $isDisabled; ?>/>
                                <label for="usradminacc" title="<?php echo _('Administrador de cuentas') ?>"><?php echo _('Admin. Cuentas') ?></label>
                                <input type="checkbox" id="usradminacc" name="adminacc" <?php echo $user['checks']['user_isAdminAcc'] . ' ' . $isDisabled; ?> />
                            <?php endif; ?>
                            <br>
                            <label for="usrdisabled" title="<?php echo _('Deshabilitado'); ?>"><?php echo _('Deshabilitado'); ?></label>
                            <input type="checkbox" id="usrdisabled" name="disabled" <?php echo $user['checks']['user_isDisabled'] . ' ' . $isDisabled; ?>/>
                            <label for="usrchangepass" title="<?php echo _('Forzar cambio de clave'); ?>"><?php echo _('Cambio de Clave'); ?></label>
                            <input type="checkbox" id="usrchangepass" name="changepass" <?php echo $user['checks']['user_isChangePass'] . ' ' . $isDisabled; ?>/>
                        </div>
                    </td>
                </tr>
                <?php if ( $isView ): ?>
                <tr>
                    <td class="descField"><?php echo _('Entradas'); ?></td>
                    <td class="valField"> <?php echo $user['user_count']; ?></td>
                </tr>
                
                <tr>
                    <td class="descField"><?php echo _('Último Acceso'); ?></td>
                    <td class="valField"> <?php echo $user['user_lastLogin']; ?></td>
                </tr>
                
                <tr>
                    <td class="descField"><?php echo _('Última Modificación'); ?></td>
                    <td class="valField"> <?php echo $user['user_lastUpdate']; ?></td>
                </tr>
                
                <tr>
                    <td class="descField"><?php echo _('Fecha Clave Maestra'); ?></td>
                    <td class="valField"> <?php echo $user['user_lastUpdateMPass']; ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ( ! $isView ): ?>
            <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>" />
            <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>" />
            <input type="hidden" name="ldap" value="<?php echo $user['user_isLdap']; ?>" />
            <input type="hidden" name="id" value="<?php echo $user['user_id']; ?>" />
            <input type="hidden" name="action" value="<?php echo $user['action']; ?>" />
            <input type="hidden" name="type" value="<?php echo $data['itemtype']; ?>" />
            <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(true) ?>">
            <input type="hidden" name="isAjax" value="1">
        <?php endif; ?>
    </form>
    
    <?php if ( ! $isView ): ?>
        <div id="resCheck"><span id="resFancyAccion"></span></div>
        <div class="action-in-box">
            <ul>
                <li><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" OnClick="appMgmtSave('frmUsers');" /></li>
            </ul>
        </div>
    <?php endif; ?>
</div>        
<script>
    $("#btnUserOptions").buttonset();
    $("#selProfile").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Perfil'); ?>", 
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
    });
    $("#selGroup").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Grupo'); ?>", 
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
    });
</script>