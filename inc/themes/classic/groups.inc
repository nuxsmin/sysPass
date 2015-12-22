<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmGroups" id="frmGroups" onsubmit="sysPassUtil.Common.appMgmtSave('frmGroups'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <input type="text" id="grpname" name="name" title="<?php echo _('Nombre del grupo'); ?>"
                           value="<?php echo $group["usergroup_name"] ?>"/>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Descripción'); ?></td>
                <td class="valField"><input type="text" id="grpdesc" name="description"
                                            title="<?php echo _('Descripción del grupo'); ?>"
                                            value="<?php echo $group["usergroup_description"]; ?>"/>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Usuarios'); ?></td>
                <td class="valField">
                    <select id="selUsers" name="users[]" multiple="multiple" class="select-box sel-chosen-user">
                        <option value=""></option>
                        <?php foreach ($users as $id => $name): ?>
                            <?php $selected = (in_array($id, $groupUsers)) ? 'selected' : ''; ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
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
                                       class="<?php echo ($field->type === \SP\CustomFieldsBase::TYPE_PASSWORD) ? 'passwordfield__input-show passwordfield__no-pki' : ''; ?>"
                                       maxlength="500"
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
        <input type="hidden" name="itemId" value="<?php echo $group["usergroup_id"]; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button type="submit" form="frmGroups" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div>
<script>
    $(function(){
        sysPassUtil.Common.chosenDetect();
    });
</script>