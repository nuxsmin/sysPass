<div id="fancyContainer" align="center">
    <h2><?php echo _('Cambio de Clave'); ?></h2>

    <form method="post" name="updUsrPass" id="frmUpdUsrPass" class="form-action" data-onsubmit="user/password">
        <table class="fancydata">

            <tr>
                <td class="descField"><?php echo _('Clave'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="fancypass" name="pass" type="password" required class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="50">
                        <label class="mdl-textfield__label" for="userpass"><?php echo _('Clave'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="fancypassR" name="passR" type="password" required class="mdl-textfield__input mdl-color-text--indigo-400"
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
        <button form="frmUpdUsrPass" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                 title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>

<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>