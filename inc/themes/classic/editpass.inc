<div id="title" class="midroundup <?php use SP\Acl;

echo $title['class']; ?>"><?php echo $title['name']; ?></div>

<form method="post" name="editpass" id="frmEditPass" onsubmit="saveAccount('frmEditPass'); return false;">
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
                <input type="password" maxlength="255" name="password" class="password passwordfield__input" onKeyUp="checkPassLevel(this.value)"
                       autocomplete="off">
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
            <td class="valField"><input type="password" class="password" maxlength="255" name="password2" autocomplete="off">
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
    <button type="button" title="<?php echo _('Volver'); ?>" class="button-action"
            OnClick="doAction(<?php echo $lastAction; ?>, <?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountId; ?>)">
        <img src="<?php \SP\Init::$WEBURI; ?>imgs/back.png" alt="save" />
    </button>

    <button type="submit" form="frmEditPass" title="<?php echo _('Guardar'); ?>" class="button-action">
        <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save" />
    </button>
</div>
<script>
    $(function(){
        $('input:password:visible:first').focus();
    });
</script>