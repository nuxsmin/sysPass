<?php
/**
 * @var ThemeIcons        $icons
 * @var CustomFieldItem[] $customFields
 * @var callable          $_getvar
 * @var Template          $this
 */

use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;
use SP\Services\CustomField\CustomFieldItem;

$customFields = $_getvar('customFields');

foreach ($customFields as $index => $field):?>
    <tr>
        <td class="descField">
            <?php echo $field->definitionName; ?>
            <?php if ($field->isEncrypted && $field->isValueEncrypted === true): ?>
                <i class="icon material-icons mdl-color-text--teal-500"
                   title="<?php echo __('Encrypted'); ?>">
                    lock_outline
                </i>
            <?php elseif ($field->isEncrypted && $field->isValueEncrypted === false): ?>
                <i class="icon material-icons mdl-color-text--amber-500"
                   title="<?php echo __('Data will be encrypted after saving'); ?>">
                    lock_outline
                </i>
            <?php elseif ($field->isEncrypted === 0 && $field->isValueEncrypted === true): ?>
                <i class="icon material-icons mdl-color-text--red-500"
                   title="<?php echo __('Data will be unencrypted after saving'); ?>">
                    lock_open
                </i>
            <?php endif; ?>

            <?php if ($field->help): ?>
                <div id="help-<?php echo $field->formId; ?>"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-<?php echo $field->formId; ?>">
                    <p><?php echo $field->help; ?></p>
                </div>
            <?php endif; ?>
        </td>
        <td class="valField">
            <?php if ($field->typeName === 'color' && $_getvar('isView')): ?>
                <span class="round custom-input-color"
                      style="background-color: <?php echo $field->value; ?>;"></span>
            <?php elseif ($field->typeName === 'password'): ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php echo $field->formId; ?>"
                           name="customfield[<?php echo $field->definitionId; ?>]"
                           type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__no-pki <?php echo $_getvar('isView') ? 'passwordfield__input-show' : 'passwordfield__input'; ?>"
                           data-clipboard="1"
                           maxlength="500"
                           value="<?php echo !$_getvar('showViewCustomPass') && !empty($field->value) ? '***' : htmlspecialchars($field->value, ENT_QUOTES); ?>" <?php echo $field->required ? 'required' : ''; ?> <?php echo $_getvar('readonly'); ?>>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->formId; ?>"><?php echo $field->definitionName; ?></label>
                </div>
            <?php elseif ($field->typeName === 'textarea'): ?>
                <div class="mdl-textfield mdl-js-textfield">
                    <textarea
                            class="mdl-textfield__input mdl-color-text--indigo-400"
                            type="text" rows="3"
                            name="customfield[<?php echo $field->definitionId; ?>]"
                            id="<?php echo $field->formId; ?>" <?php echo $_getvar('readonly'); ?>><?php echo htmlspecialchars($field->value, ENT_QUOTES); ?></textarea>
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
                           value="<?php echo htmlspecialchars($field->value, ENT_QUOTES); ?>" <?php echo $field->required ? 'required' : ''; ?> <?php echo $_getvar('readonly'); ?>>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->formId; ?>"><?php echo $field->definitionName; ?></label>
                </div>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>