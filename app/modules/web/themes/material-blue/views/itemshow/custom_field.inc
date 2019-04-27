<?php
/**
 * @var CustomFieldDefinitionData $field
 * @var ThemeIcons                $icons
 * @var ConfigData                $configData
 * @var callable                  $_getvar
 * @var Template                  $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\CustomFieldDefinitionData;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

$field = $_getvar('field');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmCustomFields" id="frmCustomFields"
          class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo $field->getName(); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Field name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Type'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Type'); ?></div>

                    <select name="type" id="sel-type" size="1"
                            class="select-box">
                        <?php /** @var SelectItem $type */
                        foreach ($_getvar('types') as $type): ?>
                            <option
                                    value='<?php echo $type->getId(); ?>' <?php echo ($type->getId() === $field->getTypeId()) ? 'selected' : ''; ?>><?php echo __($type->getItemProperty('text')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Module'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Module'); ?></div>

                    <select name="module" id="sel-module" size="1"
                            class="select-box">
                        <?php /** @var SelectItem $module */
                        foreach ($_getvar('modules') as $module): ?>
                            <option
                                    value='<?php echo $module->getId(); ?>' <?php echo $module->isSelected() ? 'selected' : ''; ?>><?php echo $module->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Help Text'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="help" name="help" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $field->getHelp(); ?>">
                        <label class="mdl-textfield__label"
                               for="help"><?php echo __('Field\'s help'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="required">
                        <input type="checkbox" id="required"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="required"
                            <?php echo $field->getRequired() ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Required'); ?></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField"></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="encrypted">
                        <input type="checkbox" id="encrypted"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="encrypted" <?php echo $field->getisEncrypted() ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Encrypted'); ?></span>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmCustomFields"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
