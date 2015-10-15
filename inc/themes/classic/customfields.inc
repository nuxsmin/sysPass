<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmCustomFields" id="frmCustomFields"
          onsubmit="sysPassUtil.Common.appMgmtSave('frmCustomFields'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <input id="name" name="name" type="text" required maxlength="50"
                           value="<?php echo ($gotData) ? $field->getName() : ''; ?>">
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Tipo'); ?></td>
                <td class="valField">
                    <select name="type" id="sel-type" size="1" class="sel-chosen-ns">
                        <?php foreach ($types as $id => $type): ?>
                            <option
                                value='<?php echo $id; ?>' <?php echo ($gotData && $id === $field->getType()) ? 'selected' : ''; ?>><?php echo strtoupper($type); ?></option>
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
                                value='<?php echo $id; ?>' <?php echo ($gotData && $id === $field->getModule()) ? 'selected' : ''; ?>><?php echo strtoupper($module); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Texto Ayuda'); ?></td>
                <td class="valField">
                    <input id="help" name="help" type="text" value="<?php echo ($gotData) ? $field->getHelp() : ''; ?>">
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Obligatorio'); ?></td>
                <td class="valField">
                    <label for="required"><?php echo ($gotData && $field->isRequired()) ? _('SI') : _('NO'); ?></label>
                    <input type="checkbox" id="required" class="checkbox"
                           name="required" <?php echo ($gotData && $field->isRequired()) ? 'checked' : ''; ?>/>
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
        <button type="submit" form="frmCustomFields" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Core\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div>
<script>
    $(function () {
        sysPassUtil.Common.chosenDetect();
        sysPassUtil.Common.checkboxDetect('#fancyContainer');
    });
</script>