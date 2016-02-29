<?php foreach ($customFields as $index => $field): ?>
    <?php if ($index !== 'hash'): ?>
        <tr>
            <td class="descField">
                <?php echo $field->text; ?>
                <?php if ($field->help): ?>
                    <div id="help-<?php echo $field->name; ?>" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                        <?php echo $icons->getIconHelp()->getIcon(); ?>
                    </div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-<?php echo $field->name; ?>">
                        <p><?php echo $field->help; ?></p>
                    </div>
                <?php endif; ?>
            </td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="<?php echo $field->name; ?>"
                               name="customfield[<?php echo $field->id; ?>]"
                               type="<?php echo $field->typeName; ?>"
                               class="mdl-textfield__input mdl-color-text--indigo-400 <?php echo ($field->type === \SP\Mgmt\CustomFields\CustomFieldsBase::TYPE_PASSWORD) ? 'passwordfield__input-show passwordfield__no-pki' : ''; ?>"
                               maxlength="500"
                               value="<?php echo \SP\Html\Html::sanitize($field->value); ?>"
                            <?php echo ($field->required) ? 'required' : ''; ?>>
                        <label class="mdl-textfield__label"
                               for="<?php echo $field->name; ?>"><?php echo $field->text; ?></label>
                    </div>
                <?php else: ?>
                    <?php if ($field->type === \SP\Mgmt\CustomFields\CustomFields::TYPE_PASSWORD && !$showViewPass): ?>
                        ****
                    <?php elseif ($field->type === \SP\Mgmt\CustomFields\CustomFields::TYPE_COLOR): ?>
                        <span class="round custom-input-color"
                              style="background-color: <?php echo $field->value; ?>;"></span>
                    <?php else: ?>
                        <?php echo $field->value; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
<?php endforeach; ?>