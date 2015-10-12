<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmProfiles" id="frmProfiles" onsubmit="sysPassUtil.Common.appMgmtSave('frmProfiles'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <input type="text" id="profile_name" name="profile_name"
                           title="<?php echo _('Nombre del perfil'); ?>" value="<?php echo $profile->getName(); ?>" <?php echo $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Cuentas'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesAcc" class="btn-checks round5">
                        <label for="profile_accview"
                               title="<?php echo _('Ver detalles de cuenta'); ?>"><?php echo _('Ver'); ?></label>
                        <input type="checkbox" id="profile_accview"
                               name="profile_accview" <?php echo ($profile->isAccView()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_accviewpass"
                               title="<?php echo _('Ver clave de cuenta'); ?>"><?php echo _('Ver Clave'); ?></label>
                        <input type="checkbox" id="profile_accviewpass"
                               name="profile_accviewpass" <?php echo ($profile->isAccViewPass()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <br>
                        <label for="profile_accviewhistory"
                               title="<?php echo _('Ver historial de cuenta'); ?>"><?php echo _('Ver Historial'); ?></label>
                        <input type="checkbox" id="profile_accviewhistory"
                               name="profile_accviewhistory" <?php echo ($profile->isAccViewHistory()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_accedit"
                               title="<?php echo _('Editar cuenta'); ?>"><?php echo _('Editar'); ?></label>
                        <input type="checkbox" id="profile_accedit"
                               name="profile_accedit" <?php echo ($profile->isAccEdit()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <br>
                        <label for="profile_acceditpass"
                               title="<?php echo _('Editar clave de cuenta'); ?>"><?php echo _('Editar Clave'); ?></label>
                        <input type="checkbox" id="profile_acceditpass"
                               name="profile_acceditpass" <?php echo ($profile->isAccEditPass()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_accadd"
                               title="<?php echo _('Añadir nueva cuenta'); ?>"><?php echo _('Añadir'); ?></label>
                        <input type="checkbox" id="profile_accadd"
                               name="profile_accadd" <?php echo ($profile->isAccAdd()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <br>
                        <label for="profile_accdel"
                               title="<?php echo _('Borrar cuenta'); ?>"><?php echo _('Borrar'); ?></label>
                        <input type="checkbox" id="profile_accdel"
                               name="profile_accdel" <?php echo ($profile->isAccDelete()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_accfiles"
                               title="<?php echo _('Ver archivos de cuenta'); ?>"><?php echo _('Archivos'); ?></label>
                        <input type="checkbox" id="profile_accfiles"
                               name="profile_accfiles" <?php echo ($profile->isAccFiles()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Configuración'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesConfig" class="btn-checks round5">
                        <label for="profile_config"
                               title="<?php echo _('Configuración general'); ?>"><?php echo _('General'); ?></label>
                        <input type="checkbox" id="profile_config"
                               name="profile_config" <?php echo ($profile->isConfigGeneral()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_configmpw"
                               title="<?php echo _('Cambiar clave maestra'); ?>"><?php echo _('Encriptación'); ?></label>
                        <input type="checkbox" id="profile_configmpw"
                               name="profile_configmpw" <?php echo ($profile->isConfigEncryption()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <br>
                        <label for="profile_configback"
                               title="<?php echo _('Realizar copia de seguridad'); ?>"><?php echo _('Backup'); ?></label>
                        <input type="checkbox" id="profile_configback"
                               name="profile_configback" <?php echo ($profile->isConfigBackup()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_configimport"
                               title="<?php echo _('Realizar importación de cuentas'); ?>"><?php echo _('Importar'); ?></label>
                        <input type="checkbox" id="profile_configimport"
                               name="profile_configimport" <?php echo ($profile->isConfigImport()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Gestión'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesUsers" class="btn-checks round5">
                        <label for="profile_users"
                               title="<?php echo _('Gestión de usuarios'); ?>"><?php echo _('Usuarios'); ?></label>
                        <input type="checkbox" id="profile_users"
                               name="profile_users" <?php echo ($profile->isMgmUsers()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_groups"
                               title="<?php echo _('Gestión de grupos'); ?>"><?php echo _('Grupos'); ?></label>
                        <input type="checkbox" id="profile_groups"
                               name="profile_groups" <?php echo ($profile->isMgmGroups()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <br>
                        <label for="profile_profiles"
                               title="<?php echo _('Gestión de perfiles'); ?>"><?php echo _('Perfiles'); ?></label>
                        <input type="checkbox" id="profile_profiles"
                               name="profile_profiles" <?php echo ($profile->isMgmProfiles()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_categories"
                               title="<?php echo _('Gestión de categorías'); ?>"><?php echo _('Categorías'); ?></label>
                        <input type="checkbox" id="profile_categories"
                               name="profile_categories" <?php echo ($profile->isMgmCategories()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <br>
                        <label for="profile_customers"
                               title="<?php echo _('Gestión de clientes'); ?>"><?php echo _('Clientes'); ?></label>
                        <input type="checkbox" id="profile_customers"
                               name="profile_customers" <?php echo ($profile->isMgmCustomers()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <label for="profile_customfields"
                               title="<?php echo _('Gestión de campos personalizados'); ?>"><?php echo _('Campos Personalizados'); ?></label>
                        <input type="checkbox" id="profile_customfields"
                               name="profile_customfields" <?php echo ($profile->isMgmCustomFields()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                        <br>
                        <label for="profile_apitokens"
                               title="<?php echo _('Gestión de autorizaciones API'); ?>"><?php echo _('Autorizaciones API'); ?></label>
                        <input type="checkbox" id="profile_apitokens"
                               name="profile_apitokens" <?php echo ($profile->isMgmApiTokens()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />

                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Otros'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesOthers" class="btn-checks round5">
                        <label for="profile_eventlog"
                               title="<?php echo _('Ver log de eventos'); ?>"><?php echo _('Log de Eventos'); ?></label>
                        <input type="checkbox" id="profile_eventlog"
                               name="profile_eventlog" <?php echo ($profile->isEvl()) ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?> />
                    </div>
                </td>
            </tr>
            <?php if ($isView): ?>
            <tr>
                <td class="descField"><?php echo _('Usado por'); ?></td>
                <td class="valField">
                    <?php if(is_array($usedBy)): ?>
                        <?php foreach($usedBy as $user): ?>
                            <?php echo $user->user_login, ' | '; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php echo _('No usado'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if (!$isView): ?>
            <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
            <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
            <input type="hidden" name="itemId" value="<?php echo $itemId; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
            <input type="hidden" name="isAjax" value="1">
        <?php endif; ?>
        <script>
            $(function () {
                $('#btnProfilesAcc').buttonset();
                $('#btnProfilesConfig').buttonset();
                $('#btnProfilesUsers').buttonset();
                $('#btnProfilesOthers').buttonset();
            });
        </script>
    </form>

    <?php if (!$isView): ?>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button type="submit" form="frmProfiles" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Core\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
    <?php endif; ?>
</div>