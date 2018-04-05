<?php
/**
 * @var \SP\Core\UI\ThemeIcons                     $icons
 * @var \SP\Services\CustomField\CustomFieldItem[] $customFields
 */

foreach ($customFields as $index => $field):?>
    <tr>
        <td class="descField">
            <?php echo $field->definitionName; ?>
            <?php if ($field->isEncrypted && $field->isValueEncrypted === true): ?>
                <i class="icon material-icons mdl-color-text--teal-500"
                   title="<?php echo __('Encriptado'); ?>">
                    lock_outline
                </i>
            <?php elseif ($field->isEncrypted && $field->isValueEncrypted === false): ?>
                <i class="icon material-icons mdl-color-text--amber-500"
                   title="<?php echo __('Los datos serán encriptados al guardar'); ?>">
                    lock_outline
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
            <?php if ($field->typeName === 'color' && $isView): ?>
                <span class="round custom-input-color"
                      style="background-color: <?php echo $field->value; ?>;"></span>
            <?php elseif ($field->typeName === 'password'): ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php echo $field->formId; ?>"
                           name="customfield[<?php echo $field->definitionId; ?>]"
                           type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__no-pki <?php echo $isView ? 'passwordfield__input-show' : 'passwordfield__input'; ?>"
                           data-clipboard="1"
                           maxlength="500"
                           value="<?php echo (!$showViewCustomPass && $field->value !== '') ? '***' : htmlspecialchars($field->value, ENT_QUOTES); ?>" <?php echo $field->required ? 'required' : ''; ?> <?php echo $readonly; ?>>
                    <label class="mdl-textfield__label"
                           for="<?php echo $field->formId; ?>"><?php echo $field->definitionName; ?></label>
                </div>
            <?php elseif ($field->typeName === 'textarea'): ?>
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