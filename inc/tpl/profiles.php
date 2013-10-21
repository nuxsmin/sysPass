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

$profile = SP_Users::getProfileData($data['itemid']);
$activeTab = $data['active'];

?>
<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $data['header']; ?></H2>
    <form method="post" name="frmProfiles" id="frmProfiles">
        <table class="fancydata">
            <tbody>
                <tr>
                    <td class="descField"><? echo _('Nombre'); ?></td>
                    <td class="valField">
                        <input type="text" id="profile_name" name="profile_name" title="<? echo _('Nombre del perfil'); ?>" value="<? echo $profile['userprofile_name']; ?>" />
                    </td>
                </tr>

                <tr>
                    <td class="descField"><? echo _('Cuentas'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesAcc" class="btnChecks">
                            <label for="profile_accview" title="<? echo _('Ver detalles de cuenta'); ?>"><? echo _('Ver'); ?></label>
                            <input type="checkbox" id="profile_accview" name="profile_accview" <? echo $profile['userProfile_pView']; ?> />
                            <label for="profile_accviewpass" title="<? echo _('Ver clave de cuenta'); ?>"><? echo _('Ver Clave'); ?></label>
                            <input type="checkbox" id="profile_accviewpass" name="profile_accviewpass" <? echo $profile['userProfile_pViewPass']; ?> />
                            <br>
                            <label for="profile_accviewhistory" title="<? echo _('Ver historial de cuenta'); ?>"><? echo _('Ver Historial'); ?></label>
                            <input type="checkbox" id="profile_accviewhistory" name="profile_accviewhistory" <? echo $profile['userProfile_pViewHistory']; ?> />
                            <label for="profile_accedit" title="<? echo _('Editar cuenta'); ?>"><? echo _('Editar'); ?></label>
                            <input type="checkbox" id="profile_accedit" name="profile_accedit" <? echo $profile['userProfile_pEdit']; ?>/>
                            <br>
                            <label for="profile_acceditpass" title="<? echo _('Editar clave de cuenta'); ?>"><? echo _('Editar Clave'); ?></label>
                            <input type="checkbox" id="profile_acceditpass" name="profile_acceditpass" <? echo $profile['userProfile_pEditPass']; ?> />
                            <label for="profile_accadd" title="<? echo _('Añadir nueva cuenta'); ?>"><? echo _('Añadir'); ?></label>
                            <input type="checkbox" id="profile_accadd" name="profile_accadd" <? echo $profile['userProfile_pAdd']; ?> />
                            <br>
                            <label for="profile_accdel" title="<? echo _('Borrar cuenta'); ?>"><? echo _('Borrar'); ?></label>
                            <input type="checkbox" id="profile_accdel" name="profile_accdel" <? echo $profile['userProfile_pDelete']; ?> />
                            <label for="profile_accfiles" title="<? echo _('Ver archivos de cuenta'); ?>"><? echo _('Archivos'); ?></label>
                            <input type="checkbox" id="profile_accfiles" name="profile_accfiles" <? echo $profile['userProfile_pFiles']; ?> />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><? echo _('Configuración'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesConfig" class="btnChecks">
                            <label for="profile_config" title="<? echo _('Configuración general'); ?>"><? echo _('General'); ?></label>
                            <input type="checkbox" id="profile_config" name="profile_config" <? echo $profile['userProfile_pConfig']; ?> />
                            <label for="profile_configcat" title="<? echo _('Gestión de categorías'); ?>"><? echo _('Categorías'); ?></label>
                            <input type="checkbox" id="profile_configcat" name="profile_configcat" <? echo $profile['userProfile_pConfigCategories']; ?> />
                            <br>
                            <label for="profile_configmpw" title="<? echo _('Cambiar clave maestra'); ?>"><? echo _('Clave Maestra'); ?></label>
                            <input type="checkbox" id="profile_configmpw" name="profile_configmpw" <? echo $profile['userProfile_pConfigMasterPass']; ?> />
                            <label for="profile_configback" title="<? echo _('Realizar copia de seguridad'); ?>"><? echo _('Backup'); ?></label>
                            <input type="checkbox" id="profile_configback" name="profile_configback" <? echo $profile['userProfile_pConfigBackup']; ?> />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><? echo _('Gestión'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesUsers" class="btnChecks">
                            <label for="profile_users" title="<? echo _('Gestión de usuarios'); ?>"><? echo _('Usuarios'); ?></label>
                            <input type="checkbox" id="profile_users" name="profile_users" <? echo $profile['userProfile_pUsers']; ?> />
                            <label for="profile_groups" title="<? echo _('Gestión de grupos'); ?>"><? echo _('Grupos'); ?></label>
                            <input type="checkbox" id="profile_groups" name="profile_groups" <? echo $profile['userProfile_pGroups']; ?> />
                            <br>
                            <label for="profile_profiles" title="<? echo _('Gestión de perfiles'); ?>"><? echo _('Perfiles'); ?></label>
                            <input type="checkbox" id="profile_profiles" name="profile_profiles" <? echo $profile['userProfile_pProfiles']; ?> />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><? echo _('Otros'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesOthers" class="btnChecks">
                            <label for="profile_eventlog" title="<? echo _('Ver log de eventos'); ?>"><? echo _('Log de Eventos'); ?></label>
                            <input type="checkbox" id="profile_eventlog" name="profile_eventlog" <? echo $profile['userProfile_pEventlog']; ?> />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
		
        <input type="hidden" name="active" value="<? echo $activeTab ?>" />
        <input type="hidden" name="id" value="<? echo $profile['userprofile_id']; ?>" />
        <input type="hidden" name="action" value="<? echo $profile["action"] ?>" />
        <input type="hidden" name="type" value="<?php echo $data['itemtype']; ?>" />
        <input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey() ?>">
        <input type="hidden" name="is_ajax" value="1">
        <script>
            $(function() { 
                $("#btnProfilesAcc").buttonset();
                $("#btnProfilesConfig").buttonset();
                $("#btnProfilesUsers").buttonset();
                $("#btnProfilesOthers").buttonset();
            });
        </script>
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <ul>
            <li><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" OnClick="usersMgmt('frmProfiles');" /></li>
        </ul>
    </div>
</div>

