<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 * @var SelectItem $client
 * @var SelectItem $category
 * @var SelectItem $user
 * @var SelectItem $userGroup
 * @var SelectItem $tag
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
        <div class="mdl-tabs__tab-bar">
            <a href="#general-panel"
               class="mdl-tabs__tab is-active"><?php echo __('General'); ?></a>
            <a href="#permission-panel"
               class="mdl-tabs__tab"><?php echo __('Permissions'); ?></a>
            <a href="#other-panel"
               class="mdl-tabs__tab"><?php echo __('Others'); ?></a>
        </div>

        <form method="post" name="frmAccountBulkEdit" id="frmAccountBulkEdit"
              class="form-action"
              data-onsubmit="appMgmt/save"
              data-action-route="<?php echo $_getvar('route', ''); ?>"
              data-hash="">

            <div class="mdl-tabs__panel is-active" id="general-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td class="descField"><?php echo __('Client'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Client'); ?></div>

                            <select id="client_id" name="client_id"
                                    class="select-box">
                                <option value=""><?php echo __('Select Client'); ?></option>
                                <?php foreach ($_getvar('clients') as $client): ?>
                                    <option
                                            value="<?php echo $client->getId(); ?>"><?php echo $client->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Category'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Category'); ?></div>

                            <select id="category_id" name="category_id"
                                    class="select-box">
                                <option value=""><?php echo __('Select Category'); ?></option>
                                <?php foreach ($_getvar('categories') as $category): ?>
                                    <option
                                            value="<?php echo $category->getId(); ?>"><?php echo $category->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Creator'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Creator'); ?></div>

                            <select id="owner_id" name="owner_id"
                                    class="select-box">
                                <option value=""><?php echo __('Select User'); ?></option>
                                <?php foreach ($_getvar('users') as $user): ?>
                                    <option
                                            value="<?php echo $user->getId(); ?>"><?php echo $user->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Main Group'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Main Group'); ?></div>

                            <select id="main_usergroup_id"
                                    name="main_usergroup_id" class="select-box">
                                <option value=""><?php echo __('Select Group'); ?></option>
                                <?php foreach ($_getvar('userGroups') as $group): ?>
                                    <option
                                            value="<?php echo $group->getId(); ?>"><?php echo $group->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Tags'); ?></td>
                        <td class="valField">
                            <select id="tags" name="tags[]" multiple="multiple"
                                    class="select-box-tags">
                                <option value=""><?php echo __('Select Tags'); ?></option>
                                <?php foreach ($_getvar('tags') as $tag): ?>
                                    <option value="<?php echo $tag->getId(); ?>"><?php echo $tag->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="mdl-tabs__panel" id="permission-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td class="descField"><?php echo __('Users'); ?></td>
                        <td class="valField">
                            <div class="account-permissions">
                                <div class="tag-list-box">
                                    <select id="other_users_view"
                                            name="other_users_view[]"
                                            multiple="multiple"
                                            class="select-box-tags">
                                        <option value=""><?php echo __('Select Users'); ?></option>
                                        <?php foreach ($_getvar('users') as $user): ?>
                                            <option
                                                    value="<?php echo $user->getId(); ?>"><?php echo $user->getName(); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <i class="material-icons select-icon"
                                       title="<?php echo __('View'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                                </div>

                                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                                       for="clear_permission_users_view">
                                    <input type="checkbox"
                                           id="clear_permission_users_view"
                                           class="mdl-checkbox__input"
                                           name="clear_permission_users_view">
                                    <span class="mdl-checkbox__label"><?php echo __('Delete'); ?></span>
                                </label>

                                <div class="tag-list-box">
                                    <select id="other_users_edit"
                                            name="other_users_edit[]"
                                            multiple="multiple"
                                            class="select-box-tags">
                                        <option value=""><?php echo __('Select Users'); ?></option>
                                        <?php foreach ($_getvar('users') as $user): ?>
                                            <option
                                                    value="<?php echo $user->getId(); ?>"><?php echo $user->getName(); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Edit'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                                </div>

                                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                                       for="clear_permission_users_edit">
                                    <input type="checkbox"
                                           id="clear_permission_users_edit"
                                           class="mdl-checkbox__input"
                                           name="clear_permission_users_edit">
                                    <span class="mdl-checkbox__label"><?php echo __('Delete'); ?></span>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Groups'); ?></td>
                        <td class="valField">
                            <div class="account-permissions">
                                <div class="tag-list-box">
                                    <select id="other_usergroups_view"
                                            name="other_usergroups_view[]"
                                            multiple="multiple"
                                            class="select-box-tags">
                                        <option value=""><?php echo __('Select Groups'); ?></option>
                                        <?php foreach ($_getvar('userGroups') as $userGroup): ?>
                                            <option
                                                    value="<?php echo $userGroup->getId(); ?>"><?php echo $userGroup->getName(); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <i class="material-icons select-icon"
                                       title="<?php echo __('View'); ?>"><?php echo $icons->getIconView()->getIcon(); ?></i>
                                </div>

                                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                                       for="clear_permission_usergroups_view">
                                    <input type="checkbox"
                                           id="clear_permission_usergroups_view"
                                           class="mdl-checkbox__input"
                                           name="clear_permission_usergroups_view">
                                    <span class="mdl-checkbox__label"><?php echo __('Delete'); ?></span>
                                </label>

                                <div class="tag-list-box">
                                    <select id="other_usergroups_edit"
                                            name="other_usergroups_edit[]"
                                            multiple="multiple"
                                            class="select-box-tags">
                                        <option value=""><?php echo __('Select Groups'); ?></option>
                                        <?php foreach ($_getvar('userGroups') as $userGroup): ?>
                                            <option
                                                    value="<?php echo $userGroup->getId(); ?>"><?php echo $userGroup->getName(); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <i class="material-icons select-icon"
                                       title="<?php echo __('Edit'); ?>"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                                </div>

                                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                                       for="clear_permission_usergroups_edit">
                                    <input type="checkbox"
                                           id="clear_permission_usergroups_edit"
                                           class="mdl-checkbox__input"
                                           name="clear_permission_usergroups_edit">
                                    <span class="mdl-checkbox__label"><?php echo __('Delete'); ?></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="mdl-tabs__panel" id="other-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td></td>
                        <td>
                            <ul class="other-list-action mdl-list">
                                <li class="mdl-list__item mdl-list__item--two-line">
                                    <div class="mdl-switch__box">
                                        <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                                               for="delete_history">
                                            <input type="checkbox"
                                                   id="delete_history"
                                                   class="mdl-checkbox__input"
                                                   name="delete_history">
                                        </label>
                                    </div>

                                    <span class="mdl-list__item-primary-content">
                                        <span><?php echo __('Delete History'); ?></span>
                                        <span class="mdl-list__item-sub-title">
                                            <?php echo __('Delete History'); ?>
                                        </span>
                                    </span>
                                </li>
                            </ul>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Password Expiry Date'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="password_date_expire"
                                       name="password_date_expire" type="date"
                                       class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                                       data-dst-unix="password_date_expire_unix">
                                <input type='hidden'
                                       name='password_date_expire_unix'
                                       value=""/>
                                <label class="mdl-textfield__label"
                                       for="password_date_expire"><?php echo __('Date'); ?></label>
                            </div>
                            <i class="material-icons btn-action"
                               title="<?php echo __('Select date'); ?>">date_range</i>
                        </td>
                    </tr>

                    </tbody>
                </table>
            </div>

            <input type="hidden" name="itemsId"
                   value="<?php echo implode(',', $_getvar('itemsId')); ?>">
            <input type="hidden" name="isAjax" value="1">
        </form>
    </div>

    <div class="action-in-box">
        <button form="frmAccountBulkEdit"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
