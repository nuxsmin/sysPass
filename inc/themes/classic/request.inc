<div id="title" class="midroundup titleNormal"><?php echo _('Solicitar Modificación de Cuenta'); ?></div>

<form method="post" name="requestmodify" id="frmRequestModify" onsubmit="sendRequest(); return false;">
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td><td class="valField"><?php echo $accountData->account_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td><td class="valField"><?php echo $accountData->customer_name; ?></td>
        </tr>
        <?php if ( $lastAction == \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH ): ?>
        <tr>
            <td class="descField"><?php echo _('URL / IP'); ?></td>
            <td class="valField"><A href="<?php echo $accountData->account_url; ?>" target="_blank"><?php echo $accountData->account_url; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField"><?php echo $accountData->account_login; ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="descField"><?php echo _('Petición'); ?></td>
            <td class="valField">
                <textarea name="description" cols="30" rows="5" placeholder="<?php echo _('Descripción de la petición'); ?>" maxlength="1000"></textarea>
            </td>
        </tr>
    </table>
    <input type="hidden" name="accountid" value="<?php echo $accountId; ?>" />
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    <input type="hidden" name="isAjax" value="1">
</form>
<div class="action">
    <button type="button" title="<?php echo _('Atrás'); ?>" class="button-action"
            OnClick="doAction('<?php echo $lastAction; ?>', <?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>)">
        <img src="<?php \SP\Init::$WEBURI; ?>imgs/back.png" alt="save"/>
    </button>

    <button type="submit" form="frmUpdUsrPass" title="<?php echo _('Enviar'); ?>" class="button-action">
        <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
    </button>
</div>