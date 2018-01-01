<?php

use SP\Mgmt\CustomFields\CustomFieldTypes;

/** @var $icons \Theme\Icons */

foreach ($customFields as $index => $field):?>
    <tr>
        <td class="descField">
            <?php echo $field->typeName; ?>
            <?php if ($field->help): ?>
                <div id="help-<?php echo $field->formId; ?>"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-<?php echo $field->formId; ?>">
                    <p><?php echo $field->formId; ?></p>
                </div>
            <?php endif; ?>
        </td>
        <td class="valField">
            <?php if ($field->typeId === CustomFieldTypes::TYPE_COLOR && $isView): ?>
                <span class="round custom-input-color"
                      style="background-color: <?php echo $field->getValue(); ?>;"></span>
            <?php elseif ($field->typeId === CustomFieldTypes::TYPE_PASSWORD): ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php echo $field->formId; ?>"
                           name="customfield[<?php echo $field->definitionId; ?>]"
                           type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__no-pki <?php echo $isView ? 'passwordfield__input-show' : 'passwordfield__input'; ?>"
                           data-clipboard="1"
                           maxlength="500"
                           value="<?php echo (!$showViewCustomPass && $field->value !== '') ? '***' : htmlspecialchars($field->value, ENT_QUOTES); ?>" <?php echo $field->required ? 'required' : ''; ?> <?php echo $readonly; ?>>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->formId; ?>"><?php echo $field->typeName; ?></label>
                </div>
            <?php elseif ($field->typeId === CustomFieldTypes::TYPE_TEXTAREA): ?>
                <div class="mdl-textfield mdl-js-textfield">
                    <textarea class="mdl-textfield__input mdl-color-text--indigo-400" type="text" rows="3"
                              name="customfield[<?php echo $field->definitionId; ?>]"
                              id="<?php echo $field->formId; ?>" <?php echo $readonly; ?>><?php echo SP\Html\Html::sanitize($field->value); ?></textarea>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->formId; ?>"><?php echo $field->definitionName; ?></label>
                </div>
            <?php else: ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php echo $field->formId; ?>"
                           name="customfield[<?php echo $field->definitionId; ?>]"
                           type="<?php echo $field->typeName; ?>"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="500"
                           value="<?php echo SP\Html\Html::sanitize($field->value); ?>" <?php echo $field->required ? 'required' : ''; ?> <?php echo $readonly; ?>>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->formId; ?>"><?php echo $field->definitionName; ?></label>
                </div>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>