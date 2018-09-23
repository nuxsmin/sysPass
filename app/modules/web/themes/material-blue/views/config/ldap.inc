<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 */ ?>

<?php if (!$ldapIsAvailable): ?>
    <div class="msg-option-unvailable">
        <?php printf(__('La extensión \'%s\' no está disponible'), 'ldap'); ?>
        <i id="help-ldap" class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap">
            <p>
                <?php echo __('Esta extensión es necesaria conectar con el servidor de LDAP'); ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<div id="title" class="titleNormal">
    <i class="material-icons">business</i>
    <?php echo __('Configuración'); ?>
</div>

<form method="post" name="frmLdap" id="frmLdap" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configLdap/save"
      data-hash="">
    <table id="tblLdap" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-ldap-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_enabled">
                                <input type="checkbox" id="ldap_enabled"
                                       class="mdl-switch__input"
                                       name="ldap_enabled" <?php echo $configData->isLdapEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Habilitar LDAP'); ?></span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Habilita de autentificación mediante servidor LDAP.'); ?>
                                    <br>
                                    <?php echo __('Este método utilizará MySQL en caso de fallo.'); ?>
                                </span>
                            </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_ads_enabled">
                                <input type="checkbox" id="ldap_ads_enabled"
                                       class="mdl-switch__input"
                                       name="ldap_ads_enabled" <?php echo $configData->isLdapAds() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Active Directory'); ?></span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Habilita el modo de conexión con LDAP de Active Directory.'); ?>
                                </span>
                            </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_tls_enabled">
                                <input type="checkbox" id="ldap_tls_enabled"
                                       class="mdl-switch__input"
                                       name="ldap_tls_enabled" <?php echo $configData->isLdapTlsEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                                <span>TLS</span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Habilita la conexión mediante TLS.'); ?>
                                </span>
                            </span>
                    </li>

                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Servidor'); ?>
                <div id="help-ldap_server"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_server">
                    <p>
                        <?php echo __('Nombre o dirección IP del servidor de LDAP.'); ?>
                    </p>

                    <p>
                        <?php echo __('Ejemplos:'); ?>
                    </p>

                    <ul>
                        <li>ldap.example.org</li>
                        <li>ldap://ldap.example.org</li>
                        <li>ldaps://ldap.example.org</li>
                    </ul>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_server" name="ldap_server" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getLdapServer(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_server"><?php echo __('Servidor'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Usuario de conexión'); ?>
                <div id="help-ldap_binduser"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_binduser">
                    <p>
                        <?php echo __('Usuario para conectar con el servicio de LDAP.'); ?>
                    </p>

                    <p>
                        <?php echo __('Ejemplos:'); ?>
                    </p>

                    <ul>
                        <li>cn=syspass,ou=Users,dc=syspass,o=org</li>
                        <li>syspass</li>
                    </ul>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_binduser" name="ldap_binduser" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getLdapBindUser(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_binduser"><?php echo __('Usuario'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Clave de conexión'); ?>
                <div id="help-ldap_bindpass"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_bindpass">
                    <p>
                        <?php echo __('Clave del usuario de conexión a LDAP.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_bindpass" name="ldap_bindpass" type="password"
                           class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getLdapBindPass() ? '***' : ''; ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_bindpass"><?php echo __('Clave'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Base de búsqueda'); ?>
                <div id="help-ldap_base"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_base">
                    <p>
                        <?php echo __('Base en la que realizar la búsqueda de usuarios de LDAP.'); ?>
                    </p>

                    <p>
                        <?php echo __('Ejemplos:'); ?>
                    </p>

                    <ul>
                        <li>cn=Users,dc=example,dc=org</li>
                        <li>ou=AdminUsers,dc=example,o=org</li>
                    </ul>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_base" name="ldap_base" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getLdapBase(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_base"><?php echo __('Base de búsqueda'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Grupo'); ?>
                <div id="help-ldap_group"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_group">
                    <p>
                        <?php echo __('Grupo de LDAP al que debe de pertenecer el usuario para permitir el acceso.'); ?>
                    </p>

                    <p>
                        <?php echo __('Este grupo debe de estar ubicado en la base de búsquedas de LDAP.'); ?>
                    </p>

                    <p>
                        <?php echo __('Ejemplos:'); ?>
                    </p>

                    <ul>
                        <li>cn=GRP_SPUSERS,cn=Users,dc=example,dc=org</li>
                        <li>GRP_SPUSERS</li>
                    </ul>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_group" name="ldap_group" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getLdapGroup(); ?>"/>
                    <label class="mdl-textfield__label" for="ldap_group"><?php echo __('Grupo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Grupo por Defecto'); ?>
                <div id="help-ldap_defaultgroup"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_defaultgroup">
                    <p>
                        <?php echo __('Define el grupo de usuarios por defecto para los nuevos usuarios de LDAP.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Grupo por Defecto'); ?></div>

                <select id="ldap_defaultgroup" name="ldap_defaultgroup"
                        class="select-box sel-chosen-usergroup" required>
                    <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userGroups */
                    foreach ($userGroups as $userGroup): ?>
                        <option
                                value="<?php echo $userGroup->getId(); ?>" <?php echo $userGroup->getId() === $configData->getLdapDefaultGroup() ? 'selected' : ''; ?>><?php echo $userGroup->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Perfil por Defecto'); ?>
                <div id="help-ldap_defaultprofile"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_defaultprofile">
                    <p>
                        <?php echo __('Define el perfil de usuarios por defecto para los nuevos usuarios de LDAP.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Perfil por Defecto'); ?></div>

                <select id="ldap_defaultprofile" name="ldap_defaultprofile"
                        class="select-box sel-chosen-profile" required>
                    <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $userProfiles */
                    foreach ($userProfiles as $userProfile): ?>
                        <option
                                value="<?php echo $userProfile->getId(); ?>" <?php echo ($userProfile->getId() === $configData->getLdapDefaultProfile()) ? 'selected' : ''; ?>><?php echo $userProfile->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>


    <div class="tab-actions">
        <ul>
            <li>
                <button id="btnBack" type="button"
                        class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        title="<?php echo __('Atrás'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                </button>
            </li>
            <li>
                <button type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                        data-onclick="ldap/check"
                        data-action-route="configLdap/check"
                        data-src="#frmLdap"
                        title="<?php echo $icons->getIconCheck()->getTitle(); ?>">
                    <i class="material-icons"><?php echo $icons->getIconCheck()->getIcon(); ?></i>
                </button>
            </li>
            <li>
                <button form="frmLdap"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                        title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                    <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                </button>
            </li>
        </ul>
    </div>

    <div id="title" class="titleNormal">
        <i class="material-icons">import_export</i>
        <?php echo __('Importación'); ?>
    </div>

    <table id="tblLdap" class="data tblConfig">
        <tr>
            <td class="descField">
                <?php echo __('Atributo Login'); ?>
                <div id="help-ldap_login_attribute"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_login_attribute">
                    <p>
                        <?php echo __('Define el atributo a utilizar para el login del usuario en la importación.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Atributo Login'); ?></div>

                <select id="ldap_login_attribute" name="ldap_login_attribute"
                        class="select-box" required>
                    <option value="samaccountname" selected>sAMAccountName</option>
                    <option value="userprincipalname">userPrincipalName</option>
                    <option value="uid">uid</option>
                    <option value="login">login</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Atributo Nombre'); ?>
                <div id="help-ldap_username_attribute"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_username_attribute">
                    <p>
                        <?php echo __('Define el atributo a utilizar para el nombre del usuario en la importación.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Atributo Nombre'); ?></div>

                <select id="ldap_username_attribute" name="ldap_username_attribute"
                        class="select-box" required>
                    <option value="displayname" selected>displayName</option>
                    <option value="fullname">fullName</option>
                    <option value="cn">cn</option>
                    <option value="name">name</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-ldap-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_import_groups">
                                <input type="checkbox" id="ldap_import_groups"
                                       class="mdl-switch__input"
                                       name="ldap_import_groups"/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Importar Grupos'); ?></span>
                                <span class="mdl-list__item-sub-title"></span>
                            </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Atributo Nombre Grupo'); ?>
                <div id="help-ldap_groupname_attribute"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_groupname_attribute">
                    <p>
                        <?php echo __('Define el atributo a utilizar para el nombre del grupo en la importación.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Atributo Nombre Grupo'); ?></div>

                <select id="ldap_groupname_attribute" name="ldap_groupname_attribute"
                        class="select-box" required>
                    <option value="cn">cn</option>
                    <option value="name">name</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Filtro'); ?>
                <div id="help-ldap_import_filter"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_import_filter">
                    <p>
                        <?php echo __('Filtro para importar usuarios o grupos de LDAP.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_import_filter" name="ldap_import_filter" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"/>
                    <label class="mdl-textfield__label"
                           for="ldap_import_filter"><?php echo __('Filtro'); ?></label>
                </div>
            </td>
        </tr>
    </table>

    <div class="tab-actions">
        <ul>
            <li>
                <button id="btnBack" type="button"
                        class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        title="<?php echo __('Atrás'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                </button>
            </li>
            <li>
                <button type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                        data-onclick="ldap/check"
                        data-action-route="configLdap/checkImport"
                        data-src="#frmLdap"
                        title="<?php echo $icons->getIconCheck()->getTitle(); ?>">
                    <i class="material-icons"><?php echo $icons->getIconCheck()->getIcon(); ?></i>
                </button>
            </li>
            <li>
                <button type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                        data-onclick="ldap/import"
                        data-action-route="configLdap/import"
                        data-src="#frmLdap"
                        title="<?php echo __('Importar usuarios de LDAP'); ?>">
                    <i class="material-icons">get_app</i>
                </button>
            </li>
        </ul>
    </div>
</form>