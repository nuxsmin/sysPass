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

$profile = SP_Profiles::getProfileData($data['itemid']);
$activeTab = $data['active'];

?>
<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $data['header']; ?></H2>
    <form method="post" name="frmProfiles" id="frmProfiles">
        <table class="fancydata">
            <tbody>
                <tr>
                    <td class="descField"><?php echo _('Nombre'); ?></td>
                    <td class="valField">
                        <input type="text" id="profile_name" name="profile_name" title="<?php echo _('Nombre del perfil'); ?>" value="<?php echo $profile['userprofile_name']; ?>" />
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Cuentas'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesAcc" class="btn-checks round5">
                            <label for="profile_accview" title="<?php echo _('Ver detalles de cuenta'); ?>"><?php echo _('Ver'); ?></label>
                            <input type="checkbox" id="profile_accview" name="profile_accview" <?php echo $profile['userProfile_pView']; ?> />
                            <label for="profile_accviewpass" title="<?php echo _('Ver clave de cuenta'); ?>"><?php echo _('Ver Clave'); ?></label>
                            <input type="checkbox" id="profile_accviewpass" name="profile_accviewpass" <?php echo $profile['userProfile_pViewPass']; ?> />
                            <br>
                            <label for="profile_accviewhistory" title="<?php echo _('Ver historial de cuenta'); ?>"><?php echo _('Ver Historial'); ?></label>
                            <input type="checkbox" id="profile_accviewhistory" name="profile_accviewhistory" <?php echo $profile['userProfile_pViewHistory']; ?> />
                            <label for="profile_accedit" title="<?php echo _('Editar cuenta'); ?>"><?php echo _('Editar'); ?></label>
                            <input type="checkbox" id="profile_accedit" name="profile_accedit" <?php echo $profile['userProfile_pEdit']; ?>/>
                            <br>
                            <label for="profile_acceditpass" title="<?php echo _('Editar clave de cuenta'); ?>"><?php echo _('Editar Clave'); ?></label>
                            <input type="checkbox" id="profile_acceditpass" name="profile_acceditpass" <?php echo $profile['userProfile_pEditPass']; ?> />
                            <label for="profile_accadd" title="<?php echo _('Añadir nueva cuenta'); ?>"><?php echo _('Añadir'); ?></label>
                            <input type="checkbox" id="profile_accadd" name="profile_accadd" <?php echo $profile['userProfile_pAdd']; ?> />
                            <br>
                            <label for="profile_accdel" title="<?php echo _('Borrar cuenta'); ?>"><?php echo _('Borrar'); ?></label>
                            <input type="checkbox" id="profile_accdel" name="profile_accdel" <?php echo $profile['userProfile_pDelete']; ?> />
                            <label for="profile_accfiles" title="<?php echo _('Ver archivos de cuenta'); ?>"><?php echo _('Archivos'); ?></label>
                            <input type="checkbox" id="profile_accfiles" name="profile_accfiles" <?php echo $profile['userProfile_pFiles']; ?> />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Configuración'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesConfig" class="btn-checks round5">
                            <label for="profile_config" title="<?php echo _('Configuración general'); ?>"><?php echo _('General'); ?></label>
                            <input type="checkbox" id="profile_config" name="profile_config" <?php echo $profile['userProfile_pConfig']; ?> />
                            <label for="profile_configmpw" title="<?php echo _('Cambiar clave maestra'); ?>"><?php echo _('Clave Maestra'); ?></label>
                            <input type="checkbox" id="profile_configmpw" name="profile_configmpw" <?php echo $profile['userProfile_pConfigMasterPass']; ?> />
                            <br>
                            <label for="profile_configback" title="<?php echo _('Realizar copia de seguridad'); ?>"><?php echo _('Backup'); ?></label>
                            <input type="checkbox" id="profile_configback" name="profile_configback" <?php echo $profile['userProfile_pConfigBackup']; ?> />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Gestión'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesUsers" class="btn-checks round5">
                            <label for="profile_users" title="<?php echo _('Gestión de usuarios'); ?>"><?php echo _('Usuarios'); ?></label>
                            <input type="checkbox" id="profile_users" name="profile_users" <?php echo $profile['userProfile_pUsers']; ?> />
                            <label for="profile_groups" title="<?php echo _('Gestión de grupos'); ?>"><?php echo _('Grupos'); ?></label>
                            <input type="checkbox" id="profile_groups" name="profile_groups" <?php echo $profile['userProfile_pGroups']; ?> />
                            <br>
                            <label for="profile_profiles" title="<?php echo _('Gestión de perfiles'); ?>"><?php echo _('Perfiles'); ?></label>
                            <input type="checkbox" id="profile_profiles" name="profile_profiles" <?php echo $profile['userProfile_pProfiles']; ?> />
                            <label for="profile_categories" title="<?php echo _('Gestión de categorías'); ?>"><?php echo _('Categorías'); ?></label>
                            <input type="checkbox" id="profile_categories" name="profile_categories" <?php echo $profile['userProfile_pAppMgmtCategories']; ?> />
                            <br>
                            <label for="profile_customers" title="<?php echo _('Gestión de clientes'); ?>"><?php echo _('Clientes'); ?></label>
                            <input type="checkbox" id="profile_customers" name="profile_customers" <?php echo $profile['userProfile_pAppMgmtCustomers']; ?> />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Otros'); ?></td>
                    <td class="valField checkbox">
                        <div id="btnProfilesOthers" class="btn-checks round5">
                            <label for="profile_eventlog" title="<?php echo _('Ver log de eventos'); ?>"><?php echo _('Log de Eventos'); ?></label>
                            <input type="checkbox" id="profile_eventlog" name="profile_eventlog" <?php echo $profile['userProfile_pEventlog']; ?> />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
		
        <input type="hidden" name="active" value="<?php echo $activeTab ?>" />
        <input type="hidden" name="id" value="<?php echo $profile['userprofile_id']; ?>" />
        <input type="hidden" name="action" value="<?php echo $profile["action"] ?>" />
        <input type="hidden" name="type" value="<?php echo $data['itemtype']; ?>" />
        <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(TRUE) ?>">
        <input type="hidden" name="is_ajax" value="1">
        <script>
            $(function() { 
                $('#btnProfilesAcc').buttonset();
                $('#btnProfilesConfig').buttonset();
                $('#btnProfilesUsers').buttonset();
                $('#btnProfilesOthers').buttonset();
            });
        </script>
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <ul>
            <li><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" OnClick="appMgmtSave('frmProfiles');" /></li>
        </ul>
    </div>
</div>