<div id="title" class="midroundup <?php use SP\Acl;

echo $title['class']; ?>"><?php echo $title['name']; ?></div>

<form method="post" name="editpass" id="frmEditPass">
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td>
            <td class="valField"><?php echo $accountData->account_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td>
            <td class="valField"><?php echo $accountData->customer_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('URL / IP'); ?></td>
            <td class="valField"><A href="<?php echo $accountData->account_url; ?>"
                                    target="_blank"><?php echo $accountData->account_url; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField"><?php echo $accountData->account_login; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Clave'); ?></td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="password" name="password" type="password" required class="mdl-textfield__input blue100"
                           maxlength="255" OnKeyUp="checkPassLevel(this.value)" autocomplete="off">
                    <label class="mdl-textfield__label" for="password"><?php echo _('Clave'); ?></label>
                </div>
                <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>
                <i id="passGen" class="material-icons" title="<?php echo _('Generar clave aleatoria'); ?>">settings</i>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="password2" name="password2" type="password" required class="mdl-textfield__input blue100"
                           maxlength="255" OnKeyUp="checkPassLevel(this.value)" autocomplete="off">
                    <label class="mdl-textfield__label" for="password2"><?php echo _('Clave'); ?></label>
                </div>
                <span class="passLevel fullround" title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
            </td>
        </tr>
    </table>
    <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
    <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
    <input type="hidden" name="next" value="<?php echo $nextaction; ?>">
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    <input type="hidden" name="isAjax" value="1">
</form>

<div class="action">
    <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
            OnClick="saveAccount('frmEditPass');" title="<?php echo _('Guardar'); ?>">
        <i class="material-icons">save</i>
    </button>

    <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
            OnClick="doAction(<?php echo $lastAction; ?>, <?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountId; ?>)"
            title="<?php echo _('Atrás'); ?>">
        <i class="material-icons">arrow_back</i>
    </button>

</div>
<script>
    $(function () {
        $('input:password:visible:first').focus();
    });
</script>