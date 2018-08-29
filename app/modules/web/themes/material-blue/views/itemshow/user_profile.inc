<?php
/**
 * @var $icons       \SP\Core\UI\ThemeIcons
 * @var $profile     \SP\DataModel\UserProfileData
 * @var $profileData \SP\DataModel\ProfileData
 */
?>

<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmProfile" id="frmProfile" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo isset($route) ? $route : ''; ?>"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-hash="">

        <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
            <div class="mdl-tabs__tab-bar">
                <a href="#account-panel" class="mdl-tabs__tab is-active"><?php echo __('Cuentas'); ?></a>
                <a href="#management-panel" class="mdl-tabs__tab"><?php echo __('Gestión'); ?></a>
                <a href="#config-panel" class="mdl-tabs__tab"><?php echo __('Configuración'); ?></a>
                <a href="#other-panel" class="mdl-tabs__tab"><?php echo __('Otros'); ?></a>
            </div>

            <div class="mdl-tabs__panel is-active" id="account-panel">
                <ul class="profile-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Crear'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Crear nueva cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accadd">
                                <input type="checkbox" id="profile_accadd"
                                       class="mdl-switch__input"
                                       name="profile_accadd" <?php echo $profileData->isAccAdd() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Ver'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Ver detalles de cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accview">
                                <input type="checkbox" id="profile_accview"
                                       class="mdl-switch__input"
                                       name="profile_accview" <?php echo $profileData->isAccView() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Ver Clave'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Ver clave de cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accviewpass">
                            <input type="checkbox" id="profile_accviewpass"
                                   class="mdl-switch__input"
                                   name="profile_accviewpass" <?php echo $profileData->isAccViewPass() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Ver Historial'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Ver historial de cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accviewhistory">
                                <input type="checkbox" id="profile_accviewhistory"
                                       class="mdl-switch__input"
                                       name="profile_accviewhistory" <?php echo $profileData->isAccViewHistory() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Editar'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Editar cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accedit">
                                <input type="checkbox" id="profile_accedit"
                                       class="mdl-switch__input"
                                       name="profile_accedit" <?php echo $profileData->isAccEdit() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Editar Clave'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Editar clave de cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_acceditpass">
                                <input type="checkbox" id="profile_acceditpass"
                                       class="mdl-switch__input"
                                       name="profile_acceditpass" <?php echo $profileData->isAccEditPass() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Eliminar'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Eliminar cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accdel">
                                <input type="checkbox" id="profile_accdel"
                                       class="mdl-switch__input"
                                       name="profile_accdel" <?php echo $profileData->isAccDelete() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Archivos'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Ver archivos de cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accfiles">
                                <input type="checkbox" id="profile_accfiles"
                                       class="mdl-switch__input"
                                       name="profile_accfiles" <?php echo $profileData->isAccFiles() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Publicar Enlace'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Publicar enlace a cuenta'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accpublinks">
                                <input type="checkbox" id="profile_accpublinks"
                                       class="mdl-switch__input"
                                       name="profile_accpublinks" <?php echo $profileData->isAccPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Privada'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Crear cuenta privada'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accprivate">
                                <input type="checkbox" id="profile_accprivate"
                                       class="mdl-switch__input"
                                       name="profile_accprivate" <?php echo $profileData->isAccPrivate() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Privada Grupo'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Crear cuenta privada para grupo'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accprivategroup">
                                <input type="checkbox" id="profile_accprivategroup"
                                       class="mdl-switch__input"
                                       name="profile_accprivategroup" <?php echo $profileData->isAccPrivateGroup() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Permisos'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Asignar permisos'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accpermissions">
                                <input type="checkbox" id="profile_accpermissions"
                                       class="mdl-switch__input"
                                       name="profile_accpermissions" <?php echo $profileData->isAccPermission() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Búsqueda global'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Búsqueda global'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accglobalsearch">
                                <input type="checkbox" id="profile_accglobalsearch"
                                       class="mdl-switch__input"
                                       name="profile_accglobalsearch" <?php echo $profileData->isAccGlobalSearch() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="mdl-tabs__panel" id="management-panel">
                <ul class="profile-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Usuarios'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de usuarios'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_users">
                                <input type="checkbox" id="profile_users"
                                       class="mdl-switch__input"
                                       name="profile_users" <?php echo $profileData->isMgmUsers() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Grupos'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de grupos'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_groups">
                                <input type="checkbox" id="profile_groups"
                                       class="mdl-switch__input"
                                       name="profile_groups" <?php echo $profileData->isMgmGroups() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Perfiles'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de perfiles'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_profiles">
                                <input type="checkbox" id="profile_profiles"
                                       class="mdl-switch__input"
                                       name="profile_profiles" <?php echo $profileData->isMgmProfiles() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Categorías'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de categorías'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_categories">
                                <input type="checkbox" id="profile_categories"
                                       class="mdl-switch__input"
                                       name="profile_categories" <?php echo $profileData->isMgmCategories() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Etiquetas'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de etiquetas'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_tags">
                                <input type="checkbox" id="profile_tags"
                                       class="mdl-switch__input"
                                       name="profile_tags" <?php echo $profileData->isMgmTags() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Clientes'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de clientes'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_customers">
                                <input type="checkbox" id="profile_customers"
                                       class="mdl-switch__input"
                                       name="profile_customers" <?php echo $profileData->isMgmCustomers() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Campos Personalizados'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de campos personalizados'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_customfields">
                                <input type="checkbox" id="profile_customfields"
                                       class="mdl-switch__input"
                                       name="profile_customfields" <?php echo $profileData->isMgmCustomFields() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Autorizaciones API'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de autorizaciones API'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_apitokens">
                                <input type="checkbox" id="profile_apitokens"
                                       class="mdl-switch__input"
                                       name="profile_apitokens" <?php echo $profileData->isMgmApiTokens() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Enlaces Públicos'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de enlaces'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_publinks">
                                <input type="checkbox" id="profile_publinks"
                                       class="mdl-switch__input"
                                       name="profile_publinks" <?php echo $profileData->isMgmPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Cuentas'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de cuentas'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_accounts">
                                <input type="checkbox" id="profile_accounts"
                                       class="mdl-switch__input"
                                       name="profile_accounts" <?php echo $profileData->isMgmAccounts() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Archivos'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Gestión de archivos'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_files">
                                <input type="checkbox" id="profile_files"
                                       class="mdl-switch__input"
                                       name="profile_files" <?php echo $profileData->isMgmFiles() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="mdl-tabs__panel" id="config-panel">
                <ul class="profile-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('General'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Configuración General'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_config">
                                <input type="checkbox" id="profile_config"
                                       class="mdl-switch__input"
                                       name="profile_config" <?php echo $profileData->isConfigGeneral() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Encriptación'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Opciones de encriptación'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configmpw">
                                <input type="checkbox" id="profile_configmpw"
                                       class="mdl-switch__input"
                                       name="profile_configmpw" <?php echo $profileData->isConfigEncryption() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Backup'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Realizar copia de seguridad y exportar'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configback">
                                <input type="checkbox" id="profile_configback"
                                       class="mdl-switch__input"
                                       name="profile_configback" <?php echo $profileData->isConfigBackup() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Importar'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Realizar importación de cuentas'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_configimport">
                                <input type="checkbox" id="profile_configimport"
                                       class="mdl-switch__input"
                                       name="profile_configimport" <?php echo $profileData->isConfigImport() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="mdl-tabs__panel" id="other-panel">
                <ul class="profile-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Log de Eventos'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Ver log de eventos'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="profile_eventlog">
                                <input type="checkbox" id="profile_eventlog"
                                       class="mdl-switch__input"
                                       name="profile_eventlog" <?php echo $profileData->isEvl() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                            </label>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="profile_name" name="profile_name" type="text" required
                               class="mdl-textfield__input"
                               value="<?php echo $profile->getName(); ?>"
                               maxlength="50" <?php echo $readonly; ?>>
                        <label class="mdl-textfield__label"
                               for="profile_name"><?php echo __('Nombre del perfil'); ?></label>
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
                                    <li class="mdl-list__item mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">person</i>
                                    <?php echo $user->login; ?>
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
            <input type="hidden" name="sk" value="">
            <input type="hidden" name="isAjax" value="1">
        <?php endif; ?>
    </form>

    <?php if (!$isView): ?>
        <div class="action-in-box">
            <button form="frmProfile"
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