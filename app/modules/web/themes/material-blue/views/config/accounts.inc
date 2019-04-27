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

<?php if ($_getvar('gdIsAvailable') !== true): ?>
    <div class="msg-option-unvailable">
        <?php printf(__('The \'%s\' extension is unavailable'), 'gd'); ?>
        <i id="help-gd"
           class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
        <div class="mdl-tooltip mdl-tooltip--large" for="help-gd">
            <p>
                <?php echo __('This extension is needed to display passwords as images'); ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<form method="post" name="frmAccounts" id="frmAccounts" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configAccount/save"
      data-hash="">

    <div id="title" class="titleNormal">
        <i class="material-icons">search</i>
        <?php echo __('Searching'); ?>
    </div>

    <table id="tblAccounts" class="data tblConfig">
        <tr>
            <td class="descField">
                <?php echo __('Results per page'); ?>
                <div id="help-account_count"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-account_count">
                    <p>
                        <?php echo __('Number of results per page to display when performing a search.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="account_count" name="account_count" type="number"
                           step="6"
                           pattern="[0-9]{1,5}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $configData->getAccountCount(); ?>"
                           required/>
                    <label class="mdl-textfield__label"
                           for="account_count"><?php echo __('Results per page'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-accounts-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_expire_enabled">
                                <input type="checkbox"
                                       id="account_expire_enabled"
                                       class="mdl-switch__input"
                                       name="account_expire_enabled" <?php echo $configData->isAccountExpireEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Accounts password expiry'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Enables the accounts password expiry date.'); ?></span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Password expiry time'); ?>
                <div id="help-account_expire_time"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-account_expire_time">
                    <p>
                        <?php echo __('Number of days for account\'s password expiry date.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="account_expire_time" name="account_expire_time"
                           type="number"
                           pattern="[0-9]{1,3}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           value="<?php echo $configData->getAccountExpireTime() / 24 / 3600; ?>"/>
                    <label class="mdl-textfield__label"
                           for="account_expire_time"><?php echo __('Expire time (days)'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-accounts-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_link_enabled">
                                <input type="checkbox" id="account_link_enabled"
                                       class="mdl-switch__input"
                                       name="account_link_enabled" <?php echo $configData->isAccountLink() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Account name as link'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Enables to use the account name as a link to account details.'); ?></span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_globalsearch_enabled">
                                <input type="checkbox"
                                       id="account_globalsearch_enabled"
                                       class="mdl-switch__input"
                                       name="account_globalsearch_enabled" <?php echo $configData->isGlobalSearch() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Global searches'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Allows the users to do searches that includes all accounts, they won\'t be able to display the account details if they don\'t have permission.'); ?></span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_passtoimage_enabled">
                                <input type="checkbox"
                                       id="account_passtoimage_enabled"
                                       class="mdl-switch__input"
                                       name="account_passtoimage_enabled" <?php echo $configData->isAccountPassToImage() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Image to show password'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Generate an image with a text of the account password.'); ?>
                                <br>
                                <?php echo __('Useful for environments where copying a password to clipboard is a security risk.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_resultsascards_enabled">
                                <input type="checkbox"
                                       id="account_resultsascards_enabled"
                                       class="mdl-switch__input"
                                       name="account_resultsascards_enabled" <?php echo $configData->isResultsAsCards() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Results like Cards'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Displays account\'s search results on a card like format.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_fullgroup_access_enabled">
                                <input type="checkbox"
                                       id="account_fullgroup_access_enabled"
                                       class="mdl-switch__input"
                                       name="account_fullgroup_access_enabled" <?php echo $configData->isAccountFullGroupAccess() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Secondary Groups Access'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Grants access to users that are included in secondary groups.'); ?>
                                <br>
                                <?php echo __('By default, user in a secondary group is granted if the secondary group is set as user\'s main group.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
    </table>

    <div id="title" class="titleNormal">
        <i class="material-icons">link</i>
        <?php echo __('Public Links'); ?>
    </div>

    <table id="tblFiles" class="data tblConfig round">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-publiclinks-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="publiclinks_enabled">
                                <input type="checkbox" id="publiclinks_enabled"
                                       class="mdl-switch__input"
                                       name="publiclinks_enabled" <?php echo $configData->isPublinksEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Enable Public Links'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Enables the ability to create public links to view an account\'s details'); ?>
                                <br>
                                <?php echo __('Linked accounts will be visible by anyone that have the link.'); ?>
                                <br>
                                <?php echo __('In order to create links, users must have activated the option on their profiles.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="publiclinks_image_enabled">
                                <input type="checkbox"
                                       id="publiclinks_image_enabled"
                                       class="mdl-switch__input"
                                       name="publiclinks_image_enabled" <?php echo $configData->isPublinksImageEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Use an image for password'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('The account password is shown as image.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Expire time'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="publiclinks_maxtime" name="publiclinks_maxtime"
                           type="number" step="5"
                           pattern="[0-9]{1,5}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $configData->getPublinksMaxTime() / 60; ?>"/>
                    <label class="mdl-textfield__label"
                           for="publiclinks_maxtime"><?php echo __('Expire time (minutes)'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Maximum visits'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="publiclinks_maxviews" name="publiclinks_maxviews"
                           type="number" step="1"
                           pattern="[0-9]{1,5}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $configData->getPublinksMaxViews(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="publiclinks_maxviews"><?php echo __('Maximum visits'); ?></label>
                </div>
            </td>
        </tr>
    </table>

    <div id="title" class="titleNormal">
        <i class="material-icons">attachment</i>
        <?php echo __('Files'); ?>
    </div>

    <table id="tblFiles" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-accounts-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="files_enabled">
                                <input type="checkbox" id="files_enabled"
                                       class="mdl-switch__input"
                                       name="files_enabled" <?php echo $configData->isFilesEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Files management'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Enables the uploading and downloading of accounts files.'); ?></span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Allowed MIME types'); ?>
                <div id="help-files_allowed_mimetypes"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-files_allowed_mimetypes">
                    <p>
                        <?php echo __('Allowed file MIME types for uploading.'); ?>
                    </p>
                    <p>
                        <?php echo __('In order to add more MIME types, you need to add them into mime.xml file within the config directory.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Allowed MIME types'); ?></div>

                <select id="files_allowed_mimetypes"
                        name="files_allowed_mimetypes[]"
                        multiple="multiple" class="select-box"
                        data-create="true">
                    <?php /** @var SelectItem $mimeType */
                    foreach ($_getvar('mimeTypes') as $mimeType): ?>
                        <option
                                value="<?php echo $mimeType->getName(); ?>"
                            <?php echo $mimeType->isSelected() ? 'selected' : '' ?>><?php echo $mimeType->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Maximum file size'); ?>
                <div id="help-files_allowed_size"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-files_allowed_size">
                    <p>
                        <?php echo __('Sets the maximum file size for uploading.'); ?>
                    </p>

                    <p>
                        <?php echo __('Absolute maximum is 16MB.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="files_allowed_size" name="files_allowed_size"
                           type="number" step="512"
                           pattern="[0-9]{1,5}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $configData->getFilesAllowedSize(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="files_allowed_size"><?php echo __('Maximum file size in kilobytes'); ?></label>
                </div>
            </td>
        </tr>
    </table>
</form>

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
            <button form="frmAccounts"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
