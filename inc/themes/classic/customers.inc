<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmCustomers" id="frmCustomers" onsubmit="sysPassUtil.Common.appMgmtSave('frmCustomers'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <input type="text" id="grpname" name="name" title="<?php echo _('Nombre del cliente'); ?>"
                           value="<?php echo $customer["customer_name"] ?>"/>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Descripción'); ?></td>
                <td class="valField"><input type="text" id="grpdesc" name="description"
                                            title="<?php echo _('Descripción del cliente'); ?>"
                                            value="<?php echo $customer["customer_description"]; ?>"/>
                </td>
            </tr>

            <?php if ($customFields): ?>
                <?php foreach ($customFields as $field): ?>
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
                                <input id="<?php echo $field->name; ?>" name="customfield[<?php echo $field->id; ?>]"
                                       type="<?php echo $field->typeName; ?>"
                                       class="<?php echo ($field->type === \SP\CustomFieldsBase::TYPE_PASSWORD) ? 'passwordfield__input-show' : ''; ?>"
                                       maxlength="50"
                                       value="<?php echo (!$isView) ? \SP\Html::sanitize($field->value) : ''; ?>"
                                    <?php echo ($field->required) ? 'required' : ''; ?>>
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
        <button type="submit" form="frmCustomers" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div>