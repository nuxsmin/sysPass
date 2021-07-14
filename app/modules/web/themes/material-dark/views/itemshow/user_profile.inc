<?php
/**
 * @var ProfileData     $profileData
 * @var UserProfileData $profile
 * @var ThemeIcons      $icons
 * @var ConfigData      $configData
 * @var callable        $_getvar
 * @var Template        $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Mvc\View\Template;

$profile = $_getvar('profile');
$profileData = $_getvar('profileData');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmProfile" id="frmProfile" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
          data-hash="">

        <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
            <div class="mdl-tabs__tab-bar">
                <a href="#account-panel"
                   class="mdl-tabs__tab is-active"><?php echo __('Accounts'); ?></a>
                <a href="#management-panel"
                   class="mdl-tabs__tab"><?php echo __('Management'); ?></a>
                <a href="#config-panel"
                   class="mdl-tabs__tab"><?php echo __('Configuration'); ?></a>
                <a href="#other-panel"
                   class="mdl-tabs__tab"><?php echo __('Others'); ?></a>
            </div>

            <div class="mdl-tabs__panel is-active" id="account-panel">
                <ul class="profile-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Add'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Create new account'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accadd">
                                <input type="checkbox" id="profile_accadd"
                                       class="mdl-switch__input"
                                       name="profile_accadd" <?php echo $profileData->isAccAdd() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('View'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('View account details'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accview">
                                <input type="checkbox" id="profile_accview"
                                       class="mdl-switch__input"
                                       name="profile_accview" <?php echo $profileData->isAccView() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('View password'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('View account password'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accviewpass">
                            <input type="checkbox" id="profile_accviewpass"
                                   class="mdl-switch__input"
                                   name="profile_accviewpass" <?php echo $profileData->isAccViewPass() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('View History'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('View account history'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accviewhistory">
                                <input type="checkbox"
                                       id="profile_accviewhistory"
                                       class="mdl-switch__input"
                                       name="profile_accviewhistory" <?php echo $profileData->isAccViewHistory() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Edit'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Edit account'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accedit">
                                <input type="checkbox" id="profile_accedit"
                                       class="mdl-switch__input"
                                       name="profile_accedit" <?php echo $profileData->isAccEdit() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Edit Password'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Edit account password'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_acceditpass">
                                <input type="checkbox" id="profile_acceditpass"
                                       class="mdl-switch__input"
                                       name="profile_acceditpass" <?php echo $profileData->isAccEditPass() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Delete'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Remove account'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accdel">
                                <input type="checkbox" id="profile_accdel"
                                       class="mdl-switch__input"
                                       name="profile_accdel" <?php echo $profileData->isAccDelete() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Files'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('View account files'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accfiles">
                                <input type="checkbox" id="profile_accfiles"
                                       class="mdl-switch__input"
                                       name="profile_accfiles" <?php echo $profileData->isAccFiles() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Share Link'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Share link to account'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accpublinks">
                                <input type="checkbox" id="profile_accpublinks"
                                       class="mdl-switch__input"
                                       name="profile_accpublinks" <?php echo $profileData->isAccPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Private'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Create private account'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accprivate">
                                <input type="checkbox" id="profile_accprivate"
                                       class="mdl-switch__input"
                                       name="profile_accprivate" <?php echo $profileData->isAccPrivate() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Private for Group'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Create private account for group'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accprivategroup">
                                <input type="checkbox"
                                       id="profile_accprivategroup"
                                       class="mdl-switch__input"
                                       name="profile_accprivategroup" <?php echo $profileData->isAccPrivateGroup() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Permissions'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Assign permissions'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accpermissions">
                                <input type="checkbox"
                                       id="profile_accpermissions"
                                       class="mdl-switch__input"
                                       name="profile_accpermissions" <?php echo $profileData->isAccPermission() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Global search'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Global search'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accglobalsearch">
                                <input type="checkbox"
                                       id="profile_accglobalsearch"
                                       class="mdl-switch__input"
                                       name="profile_accglobalsearch" <?php echo $profileData->isAccGlobalSearch() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
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
                            <span><?php echo __('Users'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Users management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_users">
                                <input type="checkbox" id="profile_users"
                                       class="mdl-switch__input"
                                       name="profile_users" <?php echo $profileData->isMgmUsers() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Groups'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Groups management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_groups">
                                <input type="checkbox" id="profile_groups"
                                       class="mdl-switch__input"
                                       name="profile_groups" <?php echo $profileData->isMgmGroups() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Profiles'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Profiles management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_profiles">
                                <input type="checkbox" id="profile_profiles"
                                       class="mdl-switch__input"
                                       name="profile_profiles" <?php echo $profileData->isMgmProfiles() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Categories'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Categories management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_categories">
                                <input type="checkbox" id="profile_categories"
                                       class="mdl-switch__input"
                                       name="profile_categories" <?php echo $profileData->isMgmCategories() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Tags'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Tags management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_tags">
                                <input type="checkbox" id="profile_tags"
                                       class="mdl-switch__input"
                                       name="profile_tags" <?php echo $profileData->isMgmTags() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Clients'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Customers management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_customers">
                                <input type="checkbox" id="profile_customers"
                                       class="mdl-switch__input"
                                       name="profile_customers" <?php echo $profileData->isMgmCustomers() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Custom Fields'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Custom fields management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_customfields">
                                <input type="checkbox" id="profile_customfields"
                                       class="mdl-switch__input"
                                       name="profile_customfields" <?php echo $profileData->isMgmCustomFields() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('API Authorizations'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('API authorizations management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_apitokens">
                                <input type="checkbox" id="profile_apitokens"
                                       class="mdl-switch__input"
                                       name="profile_apitokens" <?php echo $profileData->isMgmApiTokens() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Public Links'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Links management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_publinks">
                                <input type="checkbox" id="profile_publinks"
                                       class="mdl-switch__input"
                                       name="profile_publinks" <?php echo $profileData->isMgmPublicLinks() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Accounts'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Accounts management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_accounts">
                                <input type="checkbox" id="profile_accounts"
                                       class="mdl-switch__input"
                                       name="profile_accounts" <?php echo $profileData->isMgmAccounts() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Files'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Files management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_files">
                                <input type="checkbox" id="profile_files"
                                       class="mdl-switch__input"
                                       name="profile_files" <?php echo $profileData->isMgmFiles() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Default Values'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Default Values Management'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_items_preset">
                                <input type="checkbox" id="profile_items_preset"
                                       class="mdl-switch__input"
                                       name="profile_items_preset" <?php echo $profileData->isMgmItemsPreset() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
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
                            <span class="mdl-list__item-sub-title"><?php echo __('General Configuration'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_config">
                                <input type="checkbox" id="profile_config"
                                       class="mdl-switch__input"
                                       name="profile_config" <?php echo $profileData->isConfigGeneral() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Encryption'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Encryption options'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_configmpw">
                                <input type="checkbox" id="profile_configmpw"
                                       class="mdl-switch__input"
                                       name="profile_configmpw" <?php echo $profileData->isConfigEncryption() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Backup'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Make a backup and export'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_configback">
                                <input type="checkbox" id="profile_configback"
                                       class="mdl-switch__input"
                                       name="profile_configback" <?php echo $profileData->isConfigBackup() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <span class="mdl-list__item-primary-content">
                            <i class="material-icons  mdl-list__item-icon">vpn_key</i>
                            <span><?php echo __('Import'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Make accounts import'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_configimport">
                                <input type="checkbox" id="profile_configimport"
                                       class="mdl-switch__input"
                                       name="profile_configimport" <?php echo $profileData->isConfigImport() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
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
                            <span><?php echo __('Event Log'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('View event log'); ?></span>
                        </span>

                        <span class="mdl-list__item-secondary-action">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="profile_eventlog">
                                <input type="checkbox" id="profile_eventlog"
                                       class="mdl-switch__input"
                                       name="profile_eventlog" <?php echo $profileData->isEvl() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                            </label>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="profile_name" name="profile_name" type="text"
                               required
                               class="mdl-textfield__input"
                               value="<?php echo $profile->getName(); ?>"
                               maxlength="50" <?php echo $_getvar('readonly'); ?>>
                        <label class="mdl-textfield__label"
                               for="profile_name"><?php echo __('Profile name'); ?></label>
                    </div>
                </td>
            </tr>

            <?php if ($_getvar('isView')): ?>
                <tr>
                    <td class="descField"><?php echo __('Used by'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Used by'); ?></div>
                        <div class="list-wrap">
                            <ul class="mdl-list">
                                <?php foreach ($_getvar('usedBy') as $user): ?>
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

        <input type="hidden" name="isAjax" value="1">
    </form>

    <?php if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button form="frmProfile"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
