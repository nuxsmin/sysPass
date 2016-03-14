<?php

use SP\Mgmt\CustomFields\CustomFieldTypes;

/** @var $icons \Theme\Icons */
/** @var \SP\DataModel\CustomFieldData $field */

foreach ($customFields as $index => $field):
?>
    <?php if ($index !== 'hash'): ?>
    <tr>
        <td class="descField">
            <?php echo $field->getDefinition()->getName(); ?>
            <?php if ($field->getDefinition()->getHelp()): ?>
                <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="" class="inputImgMini help-tooltip"/>
                <div class="tooltip" for="help-<?php echo $field->getDefinition()->getFormId(); ?>" style="display: none;">
                    <p><?php echo $field->getDefinition()->getHelp(); ?></p>
                </div>
            <?php endif; ?>
        </td>
        <td class="valField">
            <?php if (!$isView): ?>
                <input id="<?php echo $field->getDefinition()->getName(); ?>"
                       name="customfield[<?php echo $field->getDefinitionId(); ?>]"
                       type="<?php echo $field->getTypeName(); ?>"
                       class="<?php echo ($field->getDefinition()->getType() === CustomFieldTypes::TYPE_PASSWORD) ? 'passwordfield__input-show passwordfield__no-pki' : ''; ?>"
                       maxlength="500"
                       value="<?php echo $field->getCleanValue(); ?>"
                    <?php echo ($field->getDefinition()->isRequired()) ? 'required' : ''; ?>>
            <?php else: ?>
                <?php if ($field->getDefinition()->getType() === CustomFieldTypes::TYPE_PASSWORD && !$showViewPass): ?>
                    ****
                <?php else: ?>
                    <?php echo $field->getValue(); ?>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endif; ?>
<?php endforeach; ?>