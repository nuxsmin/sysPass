<?php
/** @var $icons \Theme\Icons */
/** @var $profile \SP\DataModel\ProfileData */
?>

<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmProfiles" id="frmProfiles" class="form-action"
          data-onsubmit="appMgmt/save"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PROFILES; ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="profile_name" name="profile_name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $profile->getUserprofileName(); ?>"
                               maxlength="50" <?php echo $isReadonly; ?>>
                        <label class="mdl-textfield__label"
                               for="profile_name"><?php echo __('Nombre del perfil'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Cuentas'); ?></td>
                <td class="valField checkbox">
                    <div class="lowres-title"><?php echo __('Cuentas'); ?></div>

                    <div id="btnProfilesAcc" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accadd">
                            <input type="checkbox" id="profile_accadd"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accadd" <?php echo $profile->isAccAdd() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Crear nueva cuenta'); ?>"><?php echo __('Crear'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accview">
                            <input type="checkbox" id="profile_accview"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accview" <?php echo $profile->isAccView() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Ver detalles de cuenta'); ?>"><?php echo __('Ver'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accviewpass">
                            <input type="checkbox" id="profile_accviewpass"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accviewpass" <?php echo $profile->isAccViewPass() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Ver clave de cuenta'); ?>"><?php echo __('Ver Clave'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accviewhistory">
                            <input type="checkbox" id="profile_accviewhistory"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accviewhistory" <?php echo $profile->isAccViewHistory() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Ver historial de cuenta'); ?>"><?php echo __('Ver Historial'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accedit">
                            <input type="checkbox" id="profile_accedit"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accedit" <?php echo $profile->isAccEdit() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Editar cuenta'); ?>"><?php echo __('Editar'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_acceditpass">
                            <input type="checkbox" id="profile_acceditpass"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_acceditpass" <?php echo $profile->isAccEditPass() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Editar clave de cuenta'); ?>"><?php echo __('Editar Clave'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accdel">
                            <input type="checkbox" id="profile_accdel"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accdel" <?php echo $profile->isAccDelete() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Eliminar cuenta'); ?>"><?php echo __('Eliminar'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accfiles">
                            <input type="checkbox" id="profile_accfiles"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accfiles" <?php echo $profile->isAccFiles() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Ver archivos de cuenta'); ?>"><?php echo __('Archivos'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accpublinks">
                            <input type="checkbox" id="profile_accpublinks"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accpublinks" <?php echo $profile->isAccPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Publicar enlace a cuenta'); ?>"><?php echo __('Publicar Enlace'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accprivate">
                            <input type="checkbox" id="profile_accprivate"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accprivate" <?php echo $profile->isAccPrivate() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Crear cuenta privada'); ?>"><?php echo __('Privada'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accprivategroup">
                            <input type="checkbox" id="profile_accprivategroup"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accprivategroup" <?php echo $profile->isAccPrivateGroup() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Crear cuenta privada para grupo'); ?>"><?php echo __('Privada Grupo'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accpermissions">
                            <input type="checkbox" id="profile_accpermissions"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accpermissions" <?php echo $profile->isAccPermission() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Asignar permisos'); ?>"><?php echo __('Permisos'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accglobalsearch">
                            <input type="checkbox" id="profile_accglobalsearch"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accglobalsearch" <?php echo $profile->isAccGlobalSearch() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Búsqueda global'); ?>"><?php echo __('Búsqueda global'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Gestión'); ?></td>
                <td class="valField checkbox">
                    <div class="lowres-title"><?php echo __('Gestión'); ?></div>

                    <div id="btnProfilesUsers" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_users">
                            <input type="checkbox" id="profile_users"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_users" <?php echo $profile->isMgmUsers() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de usuarios'); ?>"><?php echo __('Usuarios'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_groups">
                            <input type="checkbox" id="profile_groups"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_groups" <?php echo $profile->isMgmGroups() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de grupos'); ?>"><?php echo __('Grupos'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_profiles">
                            <input type="checkbox" id="profile_profiles"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_profiles" <?php echo $profile->isMgmProfiles() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de perfiles'); ?>"><?php echo __('Perfiles'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_categories">
                            <input type="checkbox" id="profile_categories"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_categories" <?php echo $profile->isMgmCategories() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de categorías'); ?>"><?php echo __('Categorías'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_customers">
                            <input type="checkbox" id="profile_customers"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_customers" <?php echo $profile->isMgmCustomers() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de clientes'); ?>"><?php echo __('Clientes'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_customfields">
                            <input type="checkbox" id="profile_customfields"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_customfields" <?php echo $profile->isMgmCustomFields() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de campos personalizados'); ?>"><?php echo __('Campos Personalizados'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_apitokens">
                            <input type="checkbox" id="profile_apitokens"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_apitokens" <?php echo $profile->isMgmApiTokens() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de autorizaciones API'); ?>"><?php echo __('Autorizaciones API'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_publinks">
                            <input type="checkbox" id="profile_publinks"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_publinks" <?php echo $profile->isMgmPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de enlaces'); ?>"><?php echo __('Enlaces Públicos'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accounts">
                            <input type="checkbox" id="profile_accounts"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_accounts" <?php echo $profile->isMgmAccounts() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de cuentas'); ?>"><?php echo __('Cuentas'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_files">
                            <input type="checkbox" id="profile_files"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_files" <?php echo $profile->isMgmFiles() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de archivos'); ?>"><?php echo __('Archivos'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_tags">
                            <input type="checkbox" id="profile_tags"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_tags" <?php echo $profile->isMgmTags() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Gestión de etiquetas'); ?>"><?php echo __('Etiquetas'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Configuración'); ?></td>
                <td class="valField checkbox">
                    <div class="lowres-title"><?php echo __('Configuración'); ?></div>

                    <div id="btnProfilesConfig" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_config">
                            <input type="checkbox" id="profile_config"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_config" <?php echo $profile->isConfigGeneral() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Configuración General'); ?>"><?php echo __('General'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configmpw">
                            <input type="checkbox" id="profile_configmpw"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_configmpw" <?php echo $profile->isConfigEncryption() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Opciones de encriptación'); ?>"><?php echo __('Encriptación'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configback">
                            <input type="checkbox" id="profile_configback"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_configback" <?php echo $profile->isConfigBackup() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Realizar copia de seguridad y exportar'); ?>"><?php echo __('Backup'); ?></span>
                        </label>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configimport">
                            <input type="checkbox" id="profile_configimport"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_configimport" <?php echo $profile->isConfigImport() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Realizar importación de cuentas'); ?>"><?php echo __('Importar'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Otros'); ?></td>
                <td class="valField checkbox">
                    <div class="lowres-title"><?php echo __('Otros'); ?></div>

                    <div id="btnProfilesOthers" class="btn-checks">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_eventlog">
                            <input type="checkbox" id="profile_eventlog"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="profile_eventlog" <?php echo $profile->isEvl() ? 'CHECKED' : ''; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"
                                  title="<?php echo __('Ver log de eventos'); ?>"><?php echo __('Log de Eventos'); ?></span>
                        </label>
                    </div>
                </td>
            </tr>
            <?php if ($isView): ?>
                <tr>
                    <td class="descField"><?php echo __('Usado por'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Usado por'); ?></div>

                        <div class="list-wrap">
                            <ul class="mdl-list">
                                <?php foreach ($usedBy as $user): ?>
                                    <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">person</i>
                                    <?php echo $user->user_login; ?>
                                </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
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