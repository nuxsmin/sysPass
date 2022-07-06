<?php
/**
 * @var UserPreferencesData $userPreferences
 * @var ThemeIcons          $icons
 * @var ConfigData          $configData
 * @var callable            $_getvar
 * @var Template            $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\UserPreferencesData;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

$userPreferences = $_getvar('userPreferences');
?>

<div id="title" class="titleNormal">
    <i class="material-icons">home</i>
    <?php echo __('Site'); ?>
</div>

<form method="post" name="frmPreferences" id="frmPreferences"
      class="form-action"
      data-onsubmit="user/saveSettings"
      data-action-route="<?php echo $_getvar('route'); ?>"
      data-reload="1"
      data-hash="">

    <table id="tblSite" class="data tblConfig round">
        <tr>
            <td class="descField"><?php echo __('Language'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Language'); ?></div>

                <select name="userlang" id="sel-userlang" size="1"
                        class="select-box sel-chosen-ns">
                    <?php /** @var SelectItem $lang */
                    foreach ($_getvar('langs') as $langName => $lang): ?>
                        <option
                                value='<?php echo $lang->getId(); ?>' <?php echo $lang->isSelected() ? 'selected' : ""; ?>><?php echo $lang->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Visual Theme'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Visual Theme'); ?></div>

                <select name="usertheme" id="sel-usertheme" size="1"
                        class="select-box sel-chosen-ns">
                    <?php /** @var SelectItem $theme */
                    foreach ($_getvar('themes') as $theme): ?>
                        <option
                                value='<?php echo $theme->getId(); ?>' <?php echo $theme->isSelected() ? 'selected' : ""; ?>><?php echo $theme->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
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
                    <input id="resultsperpage" name="resultsperpage"
                           type="number" step="6"
                           pattern="[0-9]{1,5}"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $userPreferences->getResultsPerPage(); ?>"
                           required/>
                    <label class="mdl-textfield__label"
                           for="resultsperpage"><?php echo __('Results per page'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="usersettings-general-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="account_link">
                                <input type="checkbox" id="account_link"
                                       class="mdl-switch__input"
                                       name="account_link" <?php echo $userPreferences->isAccountLink() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Account name as link'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Enables to use the account name as a link to account details.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="sort_views">
                                <input type="checkbox" id="sort_views"
                                       class="mdl-switch__input"
                                       name="sort_views" <?php echo $userPreferences->isSortViews() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Sort search results by views'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Sorts accounts search results by the number of account\'s views'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="top_navbar">
                                <input type="checkbox" id="top_navbar"
                                       class="mdl-switch__input"
                                       name="top_navbar" <?php echo $userPreferences->isTopNavbar() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Navigation bar on top'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Displays a navigation bar on top of the search results.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="optional_actions">
                                <input type="checkbox" id="optional_actions"
                                       class="mdl-switch__input"
                                       name="optional_actions" <?php echo $userPreferences->isOptionalActions() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Show Hidden Actions'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Always display hidden actions on the accounts search page.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="resultsascards">
                                <input type="checkbox" id="resultsascards"
                                       class="mdl-switch__input"
                                       name="resultsascards" <?php echo $userPreferences->isResultsAsCards() ? 'checked' : ''; ?>/>
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
                                   for="check_notifications">
                                <input type="checkbox" id="check_notifications"
                                       class="mdl-switch__input"
                                       name="check_notifications" <?php echo $userPreferences->isCheckNotifications() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('In-App Notifications'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Enables the active In-App notifications polling'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="show_account_search_filters">
                                <input type="checkbox"
                                       id="show_account_search_filters"
                                       class="mdl-switch__input"
                                       name="show_account_search_filters" <?php echo $userPreferences->isShowAccountSearchFilters() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Show Filters'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Displays the additional filters selection on the accounts search.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
    </table>

    <input type="hidden" name="isAjax" value="1"/>
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
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
            <button form="frmPreferences"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
