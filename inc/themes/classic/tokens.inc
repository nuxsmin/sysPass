<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmTokens" id="frmTokens" onsubmit="sysPassUtil.Common.appMgmtSave('frmTokens'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Usuario'); ?></td>
                <td class="valField">
                    <select id="selUsers" name="users" class="select-box sel-chosen-user">
                        <option value="0"></option>
                        <?php foreach ($users as $id => $name): ?>
                            <?php $selected = ($gotData && $id == $token->authtoken_userId) ? 'selected' : ''; ?>
                            <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Acción'); ?></td>
                <td class="valField">
                    <select id="selActions" name="actions"
                            class="select-box sel-chosen-action">
                        <option value="0"></option>
                        <?php foreach ($actions as $id => $name): ?>
                            <?php $selected = ($gotData && $id == $token->authtoken_actionId) ? 'selected' : ''; ?>
                            <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php if (!$isView): ?>
                <tr>
                    <td class="descField"><?php echo _('Opciones'); ?></td>
                    <td class="valField">
                        <label for="refreshtoken"><?php echo _('NO'); ?></label>
                        <input type="checkbox" class="checkbox" name="refreshtoken" id="refreshtoken"/>
                        <span class="alt-label"><?php echo _('Regenerar Autorización'); ?></span>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td class="descField"><?php echo _('Token'); ?></td>
                    <td class="valField"><?php echo ($gotData) ? $token->authtoken_token : ''; ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="itemId" value="<?php echo ($gotData) ? $token->authtoken_id : ''; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button type="submit" form="frmTokens" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div>
<script>
    $(function () {
        sysPassUtil.Common.chosenDetect();
        sysPassUtil.Common.checkboxDetect('#fancyContainer');
    });
</script>