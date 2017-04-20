<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - LDAP -->
<div id="tabs-<?php echo $ldap_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo __('LDAP'); ?>
        </div>

        <form method="post" name="frmLdap" id="frmLdap" class="form-action"
              data-onsubmit="config/save"
              data-type="ldap"
              data-hash="">
            <table id="tblLdap" class="data tblConfig round">
                <?php if ($ldapIsAvailable || $isDemoMode): ?>
                    <tr>
                        <td class="descField">
                            <div id="help-ldap_enabled"
                                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                            <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_enabled">
                                <p>
                                    <?php echo __('Habilita de autentificación mediante servidor LDAP.'); ?>
                                </p>

                                <p>
                                    <?php echo __('Este método utilizará MySQL en caso de fallo.'); ?>
                                </p>
                            </div>
                        </td>
                        <td class="valField">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_enabled">
                                <input type="checkbox" id="ldap_enabled"
                                       class="mdl-switch__input mdl-color-text--indigo-400"
                                       name="ldap_enabled"
                                    <?php echo $chkLdap; ?>/>
                                <span class="mdl-switch__label"><?php echo __('Habilitar LDAP'); ?></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField">
                            <div id="help-ldap_ads"
                                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                            <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_ads">
                                <p>
                                    <?php echo __('Habilita el modo de conexión con LDAP de Active Directory.'); ?>
                                </p>
                            </div>
                        </td>
                        <td class="valField">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ldap_ads">
                                <input type="checkbox" id="ldap_ads"
                                       class="mdl-switch__input mdl-color-text--indigo-400"
                                       name="ldap_ads"
                                    <?php echo $chkLdapADS; ?>/>
                                <span class="mdl-switch__label"><?php echo __('Active Directory'); ?></span>
                            </label>
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
                                       maxlength="128" value="<?php echo $ldapServer; ?>"/>
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
                                       maxlength="128" value="<?php echo $ldapBindUser; ?>"/>
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
                                       maxlength="128" value="<?php echo $ldapBindPass; ?>"/>
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
                                       maxlength="128" value="<?php echo $ldapBase; ?>"/>
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
                                       maxlength="128" value="<?php echo $ldapGroup; ?>"/>
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
                                <?php foreach ($groups as $group): ?>
                                    <option
                                            value="<?php echo $group->id; ?>" <?php echo $group->id === $ldapDefaultGroup ? 'selected' : ''; ?>><?php echo $group->name; ?></option>
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
                                <?php foreach ($profiles as $profile): ?>
                                    <option
                                            value="<?php echo $profile->id; ?>" <?php echo ($profile->id === $ldapDefaultProfile) ? 'selected' : ''; ?>><?php echo $profile->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField">
                            <?php echo __('Atributo Login'); ?>
                            <div id="help-ldap_loginattribute"
                                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                            <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_loginattribute">
                                <p>
                                    <?php echo __('Define el atributo a utilizar para el login del usuario en la importación.'); ?>
                                </p>
                            </div>
                        </td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Atributo Login'); ?></div>

                            <select id="ldap_loginattribute" name="ldap_loginattribute"
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
                            <div id="help-ldap_nameattribute"
                                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                            <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap_nameattribute">
                                <p>
                                    <?php echo __('Define el atributo a utilizar para el nombre del usuario en la importación.'); ?>
                                </p>
                            </div>
                        </td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Atributo Nombre'); ?></div>

                            <select id="ldap_nameattribute" name="ldap_nameattribute"
                                    class="select-box" required>
                                <option value="displayname" selected>displayName</option>
                                <option value="fullname">fullName</option>
                                <option value="cn">cn</option>
                                <option value="name">name</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="ldap-results" style="display: none;">
                        <td class="descField">
                            <?php echo __('Resultados'); ?>
                        </td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Resultados'); ?></div>
                            <div class="list-wrap"></div>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td class="option-disabled">
                            <?php echo __('Módulo no disponible'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <input type="hidden" name="type" value="ldap"/>
            <input type="hidden" name="activeTab" value="<?php echo $ldap_tabIndex; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $ldap_actionId; ?>"/>
            <input type="hidden" name="sk" value="">
            <input type="hidden" name="isAjax" value="1"/>
        </form>

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
                            class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                            data-onclick="appMgmt/ldapSync"
                            data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_SYNC_LDAP; ?>"
                            title="<?php echo __('Importar usuarios de LDAP'); ?>">
                        <i class="material-icons">get_app</i>
                    </button>
                </li>
                <li>
                    <button type="button"
                            class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                            data-onclick="checks/ldap"
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
    </div>
</div> <!-- End Tab - LDAP -->