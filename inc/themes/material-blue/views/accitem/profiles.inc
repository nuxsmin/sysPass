<?php
/** @var $icons \Theme\Icons */
/** @var $profile \SP\DataModel\ProfileData */
?>

<div id="fancyContainer" align="center">
    <h2><?php echo $header; ?></H2>

    <form method="post" name="frmProfiles" id="frmProfiles" class="form-action"
          data-onsubmit="appMgmt/save"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PROFILES; ?>">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="profile_name" name="profile_name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $profile->getUserprofileName(); ?>"
                               maxlength="50" <?php echo $isDisabled; ?>>
                        <label class="mdl-textfield__label"
                               for="profile_name"><?php echo _('Nombre del perfil'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Cuentas'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesAcc" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accadd">
                            <input type="checkbox" id="profile_accadd"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accadd" <?php echo $profile->isAccAdd() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Crear nueva cuenta'); ?>"><?php echo _('Crear'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accview">
                            <input type="checkbox" id="profile_accview"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accview" <?php echo $profile->isAccView() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Ver detalles de cuenta'); ?>"><?php echo _('Ver'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accviewpass">
                            <input type="checkbox" id="profile_accviewpass"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accviewpass" <?php echo $profile->isAccViewPass() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Ver clave de cuenta'); ?>"><?php echo _('Ver Clave'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accviewhistory">
                            <input type="checkbox" id="profile_accviewhistory"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accviewhistory" <?php echo $profile->isAccViewHistory() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Ver historial de cuenta'); ?>"><?php echo _('Ver Historial'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accedit">
                            <input type="checkbox" id="profile_accedit"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accedit" <?php echo $profile->isAccEdit() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Editar cuenta'); ?>"><?php echo _('Editar'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_acceditpass">
                            <input type="checkbox" id="profile_acceditpass"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_acceditpass" <?php echo $profile->isAccEditPass() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Editar clave de cuenta'); ?>"><?php echo _('Editar Clave'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accdel">
                            <input type="checkbox" id="profile_accdel"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accdel" <?php echo $profile->isAccDelete() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Eliminar cuenta'); ?>"><?php echo _('Eliminar'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accfiles">
                            <input type="checkbox" id="profile_accfiles"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accfiles" <?php echo $profile->isAccFiles() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Ver archivos de cuenta'); ?>"><?php echo _('Archivos'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accpublinks">
                            <input type="checkbox" id="profile_accpublinks"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accpublinks" <?php echo $profile->isAccPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Publicar enlace a cuenta'); ?>"><?php echo _('Publicar Enlace'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accprivate">
                            <input type="checkbox" id="profile_accprivate"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accprivate" <?php echo $profile->isAccPrivate() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Crear cuenta privada'); ?>"><?php echo _('Privada'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Configuración'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesConfig" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_config">
                            <input type="checkbox" id="profile_config"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_config" <?php echo $profile->isConfigGeneral() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Configuración general'); ?>"><?php echo _('General'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configmpw">
                            <input type="checkbox" id="profile_configmpw"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_configmpw" <?php echo $profile->isConfigEncryption() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Opciones de encriptación'); ?>"><?php echo _('Encriptación'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configback">
                            <input type="checkbox" id="profile_configback"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_configback" <?php echo $profile->isConfigBackup() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Realizar copia de seguridad y exportar'); ?>"><?php echo _('Backup'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configimport">
                            <input type="checkbox" id="profile_configimport"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_configimport" <?php echo $profile->isConfigImport() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Realizar importación de cuentas'); ?>"><?php echo _('Importar'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Gestión'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesUsers" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_users">
                            <input type="checkbox" id="profile_users"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_users" <?php echo $profile->isMgmUsers() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de usuarios'); ?>"><?php echo _('Usuarios'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_groups">
                            <input type="checkbox" id="profile_groups"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_groups" <?php echo $profile->isMgmGroups() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de grupos'); ?>"><?php echo _('Grupos'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_profiles">
                            <input type="checkbox" id="profile_profiles"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_profiles" <?php echo $profile->isMgmProfiles() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de perfiles'); ?>"><?php echo _('Perfiles'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_categories">
                            <input type="checkbox" id="profile_categories"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_categories" <?php echo $profile->isMgmCategories() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de categorías'); ?>"><?php echo _('Categorías'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_customers">
                            <input type="checkbox" id="profile_customers"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_customers" <?php echo $profile->isMgmCustomers() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de clientes'); ?>"><?php echo _('Clientes'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_customfields">
                            <input type="checkbox" id="profile_customfields"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_customfields" <?php echo $profile->isMgmCustomFields() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de campos personalizados'); ?>"><?php echo _('Campos Personalizados'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_apitokens">
                            <input type="checkbox" id="profile_apitokens"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_apitokens" <?php echo $profile->isMgmApiTokens() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de autorizaciones API'); ?>"><?php echo _('Autorizaciones API'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_publinks">
                            <input type="checkbox" id="profile_publinks"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_publinks" <?php echo $profile->isMgmPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Gestión de enlaces'); ?>"><?php echo _('Enlaces Públicos'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Otros'); ?></td>
                <td class="valField checkbox">
                    <div id="btnProfilesOthers" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_eventlog">
                            <input type="checkbox" id="profile_eventlog"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_eventlog" <?php echo $profile->isEvl() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo _('Ver log de eventos'); ?>"><?php echo _('Log de Eventos'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <?php if ($isView): ?>
                <tr>
                    <td class="descField"><?php echo _('Usado por'); ?></td>
                    <td class="valField">
                        <?php echo $usedBy; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if (!$isView): ?>
            <input type="hidden" name="itemId" value="<?php echo $itemId; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
            <input type="hidden" name="sk" value="">
            <input type="hidden" name="isAjax" value="1">
        <?php endif; ?>
    </form>

    <?php if (!$isView): ?>
        <div id="resCheck"><span id="resFancyAccion"></span></div>
        <div class="action-in-box">
            <button form="frmProfiles"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>