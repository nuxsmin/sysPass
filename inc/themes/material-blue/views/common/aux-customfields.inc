<?php

use SP\Mgmt\CustomFields\CustomFieldTypes;

/** @var $icons \Theme\Icons */
/** @var \SP\DataModel\CustomFieldData $field */

foreach ($customFields as $index => $field):?>
    <tr>
        <td class="descField">
            <?php echo $field->getDefinition()->getName(); ?>
            <?php if ($field->getDefinition()->getHelp()): ?>
                <div id="help-<?php echo $field->getDefinition()->getFormId(); ?>"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-<?php echo $field->getDefinition()->getFormId(); ?>">
                    <p><?php echo $field->getDefinition()->getHelp(); ?></p>
                </div>
            <?php endif; ?>
        </td>
        <td class="valField">
            <?php if ($field->getDefinition()->getType() === CustomFieldTypes::TYPE_COLOR && $isView): ?>
                <span class="round custom-input-color"
                      style="background-color: <?php echo $field->getValue(); ?>;"></span>
            <?php elseif ($field->getDefinition()->getType() === CustomFieldTypes::TYPE_PASSWORD): ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php echo $field->getDefinition()->getFormId(); ?>"
                           name="customfield[<?php echo $field->getDefinitionId(); ?>]"
                           type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__no-pki <?php echo $isView ? 'passwordfield__input-show' : 'passwordfield__input'; ?>"
                           data-clipboard="1"
                           maxlength="500"
                           value="<?php echo (!$showViewCustomPass && $field->getValue() !== '') ? '***' : $field->getSafeHtmlValue(); ?>" <?php echo $field->getDefinition()->isRequired() ? 'required' : ''; ?> <?php echo $readonly; ?>>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->getDefinition()->getFormId(); ?>"><?php echo $field->getDefinition()->getName(); ?></label>
                </div>
            <?php elseif ($field->getDefinition()->getType() === CustomFieldTypes::TYPE_TEXTAREA): ?>
                <div class="mdl-textfield mdl-js-textfield">
                    <textarea class="mdl-textfield__input mdl-color-text--indigo-400" type="text" rows="3"
                              name="customfield[<?php echo $field->getDefinitionId(); ?>]"
                              id="<?php echo $field->getDefinition()->getFormId(); ?>" <?php echo $readonly; ?>><?php echo $field->getCleanValue(); ?></textarea>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->getDefinition()->getFormId(); ?>"><?php echo $field->getDefinition()->getName(); ?></label>
                </div>
            <?php else: ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php echo $field->getDefinition()->getFormId(); ?>"
                           name="customfield[<?php echo $field->getDefinitionId(); ?>]"
                           type="<?php echo $field->getTypeName(); ?>"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="500"
                           value="<?php echo $field->getCleanValue(); ?>" <?php echo $field->getDefinition()->isRequired() ? 'required' : ''; ?> <?php echo $readonly; ?>>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->getDefinition()->getFormId(); ?>"><?php echo $field->getDefinition()->getName(); ?></label>
                </div>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>