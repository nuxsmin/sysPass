<?php
/**
 * @var ItemPresetData $preset
 * @var ThemeIcons     $icons
 * @var ConfigData     $configData
 * @var callable       $_getvar
 * @var Template       $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\ItemPresetData;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

$preset = $_getvar('preset');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
        <div class="mdl-tabs__tab-bar">
            <a href="#general-panel"
               class="mdl-tabs__tab is-active"><?php echo __('General'); ?></a>
            <a href="#presets-panel"
               class="mdl-tabs__tab"><?php echo $_getvar('presetName'); ?></a>
        </div>

        <form method="post" name="frmItemPreset" id="frmItemPreset"
              class="form-action"
              data-onsubmit="appMgmt/save"
              data-action-route="<?php echo $_getvar('route', ''); ?>"
              data-hash="">

            <div class="mdl-tabs__panel is-active" id="general-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td class="descField"><?php echo __('User'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('User'); ?></div>

                            <select id="selUser" name="user_id"
                                    class="select-box select-box-deselect"
                                    title="<?php echo __('User'); ?>" <?php echo $_getvar('disabled'); ?>>
                                <option value=""><?php echo __('Select User'); ?></option>
                                <?php /** @var SelectItem $user */
                                foreach ($_getvar('users') as $user): ?>
                                    <option
                                            value="<?php echo $user->getId(); ?>" <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Group'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Group'); ?></div>

                            <select id="selUserGroup" name="user_group_id"
                                    class="select-box select-box-deselect"
                                    title="<?php echo __('Group'); ?>" <?php echo $_getvar('disabled'); ?>>
                                <option value=""><?php echo __('Select Group'); ?></option>
                                <?php /** @var SelectItem $userGroup */
                                foreach ($_getvar('userGroups') as $userGroup): ?>
                                    <option
                                            value="<?php echo $userGroup->getId(); ?>" <?php echo $userGroup->isSelected() ? 'selected' : ''; ?>><?php echo $userGroup->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Profile'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Profile'); ?></div>

                            <select id="selUserProfile" name="user_profile_id"
                                    class="select-box select-box-deselect"
                                    title="<?php echo __('Profile'); ?>" <?php echo $_getvar('disabled'); ?>>
                                <option value=""><?php echo __('Select Profile'); ?></option>
                                <?php /** @var SelectItem $userProfile */
                                foreach ($_getvar('userProfiles') as $userProfile): ?>
                                    <option
                                            value="<?php echo $userProfile->getId(); ?>" <?php echo $userProfile->isSelected() ? 'selected' : ''; ?>><?php echo $userProfile->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField">
                            <?php echo __('Priority'); ?>

                            <div id="help-priority"
                                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                            <div class="mdl-tooltip mdl-tooltip--large"
                                 for="help-priority">
                                <p>
                                    <?php echo __('Assignment priority if values match with others assigned by user, group or profile.'); ?>
                                </p>
                            </div>
                        </td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="priority" name="priority"
                                       type="number" step="1"
                                       pattern="[0-9]{1,3}"
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="3" min="0" max="128"
                                       value="<?php echo $preset->getPriority(); ?>"/>
                                <label class="mdl-textfield__label"
                                       for="priority"><?php echo __('Assignment Priority'); ?></label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"></td>
                        <td class="valField">
                            <ul class="mdl-list">
                                <li class="mdl-list__item mdl-list__item--two-line">
                                    <div class="mdl-switch__box">
                                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                               for="fixed_enabled">
                                            <input type="checkbox"
                                                   id="fixed_enabled"
                                                   class="mdl-switch__input"
                                                   name="fixed_enabled" <?php echo $preset->getFixed() ? 'checked' : ''; ?>/>
                                        </label>
                                    </div>

                                    <span class="mdl-list__item-primary-content">
                                <span><?php echo __('Forced'); ?></span>
                                <span class="mdl-list__item-sub-title">
                                    <?php echo __('Enforces the values to be set either on creating or updating an item.'); ?>
                                    <br>
                                    <?php echo __('Values will be merged with the existing ones.'); ?>
                                </span>
                            </span>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="mdl-tabs__panel" id="presets-panel">
                <?php if ($_getvar('typeTemplate')) : include $this->includeTemplate($_getvar('typeTemplate'), 'itemshow'); endif; ?>
            </div>


            <input type="hidden" name="type"
                   value="<?php echo $preset->getType(); ?>">
            <input type="hidden" name="isAjax" value="1">
        </form>
    </div
    >
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmItemPreset"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
