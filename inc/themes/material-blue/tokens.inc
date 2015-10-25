<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmTokens" id="frmTokens" onsubmit="sysPassUtil.Common.appMgmtSave('frmTokens'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Usuario'); ?></td>
                <td class="valField">
                    <select id="selUsers" name="users" class="select-box sel-chosen-user" required>
                        <option value=""></option>
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
                            class="select-box sel-chosen-action" required>
                        <option value=""></option>
                        <?php foreach ($actions as $id => $name): ?>
                            <?php $selected = ($gotData &&$id == $token->authtoken_actionId) ? 'selected' : ''; ?>
                            <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php if (!$isView): ?>
                <tr>
                    <td class="descField"><?php echo _('Opciones'); ?></td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="refreshtoken"
                               title="<?php echo _('Regenerar Autorización'); ?>">
                            <input type="checkbox" id="refreshtoken" class="mdl-switch__input fg-blue100"
                                   name="refreshtoken"/>
                            <span class="mdl-switch__label"><?php echo _('Regenerar Autorización'); ?></span>
                        </label>
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
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                form="frmTokens" title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div>
<script>
    $(function () {
        sysPassUtil.Common.chosenDetect();
    });
</script>