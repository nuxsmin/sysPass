<div id="title" class="midroundup titleNormal"><?php echo _('Solicitar Modificación de Cuenta'); ?></div>

<form method="post" name="requestmodify" id="frmRequestModify" onsubmit="sysPassUtil.Common.sendRequest(); return false;">
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td>
            <td class="valField"><?php echo $accountData->account_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td>
            <td class="valField"><?php echo $accountData->customer_name; ?></td>
        </tr>
        <?php if ($lastAction == \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH): ?>
            <tr>
                <td class="descField"><?php echo _('URL / IP'); ?></td>
                <td class="valField"><A href="<?php echo $accountData->account_url; ?>"
                                        target="_blank"><?php echo $accountData->account_url; ?></td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Usuario'); ?></td>
                <td class="valField"><?php echo $accountData->account_login; ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="descField"><?php echo _('Petición'); ?></td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <textarea class="mdl-textfield__input fg-blue100" rows= "3" id="description" name="description" maxlength="1000"></textarea>
                    <label class="mdl-textfield__label" for="description"><?php echo _('Descripción de la petición'); ?></label>
                </div>
            </td>
        </tr>
    </table>
    <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    <input type="hidden" name="isAjax" value="1">
</form>
<div class="action">
    <button id="btnBack" type="button"
            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
            OnClick="sysPassUtil.Common.doAction('<?php echo $lastAction; ?>', <?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>)"
            title="<?php echo _('Atrás'); ?>">
        <i class="material-icons">arrow_back</i>
    </button>

    <button id="btnSave" form="frmRequestModify"
            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
            title="<?php echo _('Enviar'); ?>">
        <i class="material-icons">save</i>
    </button>
</div>
