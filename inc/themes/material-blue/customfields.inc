<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmCustomFields" id="frmCustomFields" onsubmit="sysPassUtil.Common.appMgmtSave('frmCustomFields'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required class="mdl-textfield__input fg-blue100"
                               maxlength="50" value="<?php echo ($gotData) ? $field->getName() : ''; ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo _('Nombre del campo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Tipo'); ?></td>
                <td class="valField">
                    <select name="type" id="sel-type" size="1" class="sel-chosen-ns">
                        <?php foreach ($types as $id => $type): ?>
                            <option
                                value='<?php echo $id; ?>' <?php echo ($gotData && $id === $field->getType()) ? 'selected' : ''; ?>><?php echo $type[1]; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Módulo'); ?></td>
                <td class="valField">
                    <select name="module" id="sel-module" size="1" class="sel-chosen-ns">
                        <?php foreach ($modules as $id => $module): ?>
                            <option
                                value='<?php echo $id; ?>' <?php echo ($gotData && $id === $field->getModule()) ? 'selected' : ''; ?>><?php echo $module; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Texto Ayuda'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="help" name="help" type="text" class="mdl-textfield__input fg-blue100"
                               value="<?php echo ($gotData) ? $field->getHelp() : ''; ?>">
                        <label class="mdl-textfield__label"
                               for="help"><?php echo _('Ayuda del campo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Obligatorio'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="required">
                        <input type="checkbox" id="required" class="mdl-switch__input fg-blue100" name="required"
                            <?php echo ($gotData && $field->isRequired()) ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr
            </tbody>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="itemId" value="<?php echo $itemId; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                form="frmCustomFields" title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div>
<script>
    $(function () {
        sysPassUtil.Common.chosenDetect();
    });
</script>