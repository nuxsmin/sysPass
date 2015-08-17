<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmCustomers" id="frmCustomers" onsubmit="appMgmtSave('frmCustomers'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required class="mdl-textfield__input fg-blue100"
                               maxlength="50" value="<?php echo $customer["customer_name"]; ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo _('Nombre del cliente'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Descripción'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text" class="mdl-textfield__input fg-blue100"
                               maxlength="50" value="<?php echo $customer["customer_description"]; ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php echo _('Descripción del cliente'); ?></label>
                    </div>
                </td>
            </tr>

            <?php if($customFields): ?>
                <?php foreach($customFields as $field):?>
                    <tr>
                        <td class="descField">
                            <?php echo $field->text; ?>
                            <?php if($field->help): ?>
                                <div id="help-<?php echo $field->name; ?>" class="icon material-icons fg-blue80">help_outline</div>
                                <div class="mdl-tooltip mdl-tooltip--large" for="help-<?php echo $field->name; ?>">
                                    <p><?php echo $field->help; ?></p>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="valField">
                            <?php if (!$isView): ?>
                                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                    <input id="<?php echo $field->name; ?>" name="customfield[<?php echo $field->id; ?>]"
                                           type="<?php echo $field->typeName; ?>"
                                           class="mdl-textfield__input fg-blue100 <?php echo ($field->type === \SP\CustomFieldsBase::TYPE_PASSWORD) ? 'passwordfield__input-show' : ''; ?>"
                                           maxlength="50" value="<?php echo (!$isView) ? \SP\Html::sanitize($field->value) : ''; ?>"
                                        <?php echo ($field->required) ? 'required' : ''; ?>>
                                    <label class="mdl-textfield__label" for="<?php echo $field->name; ?>"><?php echo $field->text; ?></label>
                                </div>
                            <?php else: ?>
                                <?php echo $field->value; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="itemId" value="<?php echo $customer["customer_id"]; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                form="frmCustomers" title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div>