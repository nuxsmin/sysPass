<?php foreach ($customFields as $index => $field): ?>
    <?php if ($index !== 'hash'): ?>
        <tr>
            <td class="descField">
                <?php echo $field->text; ?>
                <?php if ($field->help): ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-<?php echo $field->name; ?>" style="display: none;">
                        <p><?php echo $field->help; ?></p>
                    </div>
                <?php endif; ?>
            </td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <input id="<?php echo $field->name; ?>"
                           name="customfield[<?php echo $field->id; ?>]"
                           type="<?php echo $field->typeName; ?>"
                           class="<?php echo ($field->type === \SP\Mgmt\CustomFieldsBase::TYPE_PASSWORD) ? 'passwordfield__input-show passwordfield__no-pki' : ''; ?>"
                           maxlength="500"
                           value="<?php echo \SP\Html\Html::sanitize($field->value); ?>"
                        <?php echo ($field->required) ? 'required' : ''; ?>>
                <?php else: ?>
                    <?php if ($field->type === \SP\Mgmt\CustomFields::TYPE_PASSWORD && !$showViewPass): ?>
                        ****
                    <?php else: ?>
                        <?php echo $field->value; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
<?php endforeach; ?>