<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo _('Cambio de Clave'); ?></h2>

    <form method="post" name="updUsrPass" id="frmUpdUsrPass" onsubmit="appMgmtSave('frmUpdUsrPass'); return false;">
        <table class="fancydata">
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <input type="password" id="fancypass" name="pass" title="<?php echo _('Clave'); ?>"
                           class="txtpass passwordfield__input"
                           OnFocus="$('#passLevel').show(); $('#resFancyAccion').hide();"
                           OnKeyUp="checkPassLevel(this.value, 'fancyContainer')"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave (repetir)'); ?></td>
                <td class="valField">
                    <input type="password" id="fancypassR" name="passR" title="<?php echo _('Clave (repetir)'); ?>"
                           class="txtpassv"/>
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
        <button type="submit" form="frmUpdUsrPass" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div>