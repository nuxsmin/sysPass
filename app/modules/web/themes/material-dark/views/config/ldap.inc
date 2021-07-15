<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

?>

<?php if (!$_getvar('ldapIsAvailable')): ?>
    <div class="msg-option-unvailable">
        <?php printf(__('The \'%s\' extension is unavailable'), 'ldap'); ?>
        <i id="help-ldap"
           class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
        <div class="mdl-tooltip mdl-tooltip--large" for="help-ldap">
            <p>
                <?php echo __('This extension is needed to connect to the LDAP server'); ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<div id="title" class="titleNormal">
    <i class="material-icons">business</i>
    <?php echo __('Configuration'); ?>
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
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="ldap_enabled">
                                <input type="checkbox" id="ldap_enabled"
                                       class="mdl-switch__input"
                                       name="ldap_enabled" <?php echo $configData->isLdapEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Enable LDAP'); ?></span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Enables authentication against an LDAP server.'); ?>
                                    <br>
                                    <?php echo __('This method will use MySQL as fallback.'); ?>
                                </span>
                            </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="ldap_tls_enabled">
                                <input type="checkbox" id="ldap_tls_enabled"
                                       class="mdl-switch__input"
                                       name="ldap_tls_enabled" <?php echo $configData->isLdapTlsEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                                <span>TLS</span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Enables the connection over TLS'); ?>
                                </span>
                            </span>
                    </li>

                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Server Type'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Server Type'); ?></div>

                <select id="ldap_defaultgroup" name="ldap_server_type"
                        class="select-box sel-chosen-usergroup" required>
                    <option value=""><?php echo __('Server Type'); ?></option>
                    <?php /** @var SelectItem $serverType */
                    foreach ($_getvar('serverTypes') as $serverType): ?>
                        <option
                                value="<?php echo $serverType->getId(); ?>" <?php echo $serverType->isSelected() ? 'selected' : ''; ?>><?php echo $serverType->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Server'); ?>
                <div id="help-ldap_server"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_server">
                    <p>
                        <?php echo __('Hostname or IP address of LDAP server.'); ?>
                    </p>

                    <p>
                        <?php echo __('Examples:'); ?>
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
                           maxlength="128"
                           value="<?php echo $configData->getLdapServer(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_server"><?php echo __('Server'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Bind User'); ?>
                <div id="help-ldap_binduser"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_binduser">
                    <p>
                        <?php echo __('User to connect to LDAP service.'); ?>
                    </p>

                    <p>
                        <?php echo __('Examples:'); ?>
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
                           maxlength="128"
                           value="<?php echo $configData->getLdapBindUser(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_binduser"><?php echo __('User'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Bind Password'); ?>
                <div id="help-ldap_bindpass"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_bindpass">
                    <p>
                        <?php echo __('LDAP connection user\'s password'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_bindpass" name="ldap_bindpass"
                           type="password"
                           class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                           maxlength="128"
                           value="<?php echo $configData->getLdapBindPass() ? '***' : ''; ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_bindpass"><?php echo __('Password'); ?></label>

                </div>
                <?php if ($configData->getLdapBindPass()): ?>
                    <div id="help-ldap_password"
                         class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large"
                         for="help-ldap_password">
                        <p>
                            <?php echo __('This isn\'t the real LDAP password. You should set the real one before checking or importing LDAP objects'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Search base'); ?>
                <div id="help-ldap_base"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_base">
                    <p>
                        <?php echo __('LDAP base to perform the LDAP users search.'); ?>
                    </p>

                    <p>
                        <?php echo __('Examples:'); ?>
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
                           maxlength="128"
                           value="<?php echo $configData->getLdapBase(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_base"><?php echo __('Search base'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Group'); ?>
                <div id="help-ldap_group"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_group">
                    <p>
                        <?php echo __('LDAP group which user must belong to for granting to log in.'); ?>
                    </p>

                    <p>
                        <?php echo __('This group needs to be placed in the LDAP search base.'); ?>
                    </p>

                    <p>
                        <?php echo __('Examples:'); ?>
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
                           maxlength="128"
                           value="<?php echo $configData->getLdapGroup(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="ldap_group"><?php echo __('Group'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Default Group'); ?>
                <div id="help-ldap_defaultgroup"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_defaultgroup">
                    <p>
                        <?php echo __('Sets the default users group for newly created LDAP users'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Default Group'); ?></div>

                <select id="ldap_defaultgroup" name="ldap_defaultgroup"
                        class="select-box sel-chosen-usergroup" required>
                    <option value=""><?php echo __('Select Group'); ?></option>
                    <?php /** @var SelectItem $userGroup */
                    foreach ($_getvar('userGroups') as $userGroup): ?>
                        <option
                                value="<?php echo $userGroup->getId(); ?>" <?php echo $userGroup->getId() === $configData->getLdapDefaultGroup() ? 'selected' : ''; ?>><?php echo $userGroup->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Default Profile'); ?>
                <div id="help-ldap_defaultprofile"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_defaultprofile">
                    <p>
                        <?php echo __('Sets the default profile for the newly created LDAP users.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Default Profile'); ?></div>

                <select id="ldap_defaultprofile" name="ldap_defaultprofile"
                        class="select-box sel-chosen-profile" required>
                    <option value=""><?php echo __('Select User'); ?></option>
                    <?php /** @var SelectItem $userProfile */
                    foreach ($_getvar('userProfiles') as $userProfile): ?>
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
                        title="<?php echo __('Back'); ?>">
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
        <?php echo __('Import'); ?>
    </div>

    <table id="tblLdap" class="data tblConfig">
        <tr>
            <td class="descField">
                <?php echo __('Login Attribute'); ?>
                <div id="help-ldap_login_attribute"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_login_attribute">
                    <p>
                        <?php echo __('Defines the attribute for the user\'s login when importing.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Login Attribute'); ?></div>

                <select id="ldap_login_attribute" name="ldap_login_attribute"
                        class="select-box" required>
                    <option value="samaccountname" selected>sAMAccountName
                    </option>
                    <option value="userprincipalname">userPrincipalName</option>
                    <option value="uid">uid</option>
                    <option value="login">login</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Name Attribute'); ?>
                <div id="help-ldap_username_attribute"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_username_attribute">
                    <p>
                        <?php echo __('Defines the attribute for the user\'s name when importing.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Name Attribute'); ?></div>

                <select id="ldap_username_attribute"
                        name="ldap_username_attribute"
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
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="ldap_import_groups">
                                <input type="checkbox" id="ldap_import_groups"
                                       class="mdl-switch__input"
                                       name="ldap_import_groups"/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Import Groups'); ?></span>
                                <span class="mdl-list__item-sub-title"></span>
                            </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Group Name Attribute'); ?>
                <div id="help-ldap_groupname_attribute"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_groupname_attribute">
                    <p>
                        <?php echo __('Defines the attribute for the user group name when importing.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Group Name Attribute'); ?></div>

                <select id="ldap_groupname_attribute"
                        name="ldap_groupname_attribute"
                        class="select-box" required>
                    <option value="cn">cn</option>
                    <option value="name">name</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Filter'); ?>
                <div id="help-ldap_import_filter"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-ldap_import_filter">
                    <p>
                        <?php echo __('Filter for importing LDAP users or groups.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="ldap_import_filter" name="ldap_import_filter"
                           type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"/>
                    <label class="mdl-textfield__label"
                           for="ldap_import_filter"><?php echo __('Filter'); ?></label>
                </div>
            </td>
        </tr>
    </table>

    <div class="tab-actions">
        <ul>
            <li>
                <button id="btnBack" type="button"
                        class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        title="<?php echo __('Back'); ?>">
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
                        title="<?php echo __('Import users from LDAP'); ?>">
                    <i class="material-icons">get_app</i>
                </button>
            </li>
        </ul>
    </div>
</form>