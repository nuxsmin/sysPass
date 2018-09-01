<?php /** @var $sessionTimeout \SP\DataModel\ItemPreset\SessionTimeout */ ?>

<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"><?php echo __('Dirección IP'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="ip_address" name="ip_address" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="33"
                       value="<?php printf('%s/%s', $sessionTimeout->getAddress(), $sessionTimeout->getMask()); ?>">
                <label class="mdl-textfield__label"
                       for="ip_address"><?php echo __('Dirección IP'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField"><?php echo __('Timeout'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="timeout" name="timeout" type="number" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       min="0" max="604800"
                       value="<?php echo $sessionTimeout->getTimeout(); ?>">
                <label class="mdl-textfield__label"
                       for="ip_address"><?php echo __('Timeout'); ?></label>
            </div>
        </td>
    </tr>
    </tbody>
</table>