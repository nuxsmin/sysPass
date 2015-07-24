<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo _('Cambio de Clave'); ?></h2>

    <form method="post" name="updUsrPass" id="frmUpdUsrPass" onsubmit="appMgmtSave('frmUpdUsrPass'); return false;">
        <table class="fancydata">

            <tr>
                <td class="descField"><?php echo _('Clave'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="userpass" name="pass" type="password" required class="mdl-textfield__input fg-blue100 passwordfield__input"
                               maxlength="50">
                        <label class="mdl-textfield__label" for="userpass"><?php echo _('Clave'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="userpassR" name="passR" type="password" required class="mdl-textfield__input fg-blue100"
                               maxlength="50">
                        <label class="mdl-textfield__label"
                               for="userpassR"><?php echo _('Clave (repetir)'); ?></label>
                    </div>
                </td>
            </tr>
        </table>
        <input type="hidden" name="itemId" value="<?php echo $userId; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div id="resCheck">
        <span id="resFancyAccion"></span>
    </div>
    <div class="action-in-box">
        <button form="frmUpdUsrPass" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                 title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div>

<script>
    $(function () {
        $('#passGen').click(function () {
            $('#resFancyAccion').hide();
            password(11, true, true);
        });

        $('#pass').focus(function() {
            $('#passLevel').show();
            $('#resFancyAccion').hide();
        });
    });
</script>