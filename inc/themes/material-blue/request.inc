<div id="title" class="midroundup titleNormal"><?php echo _('Solicitar Modificación de Cuenta'); ?></div>

<form method="post" name="requestmodify" id="frmRequestModify">
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
                <textarea name="description" cols="30" rows="5"
                          placeholder="<?php echo _('Descripción de la petición'); ?>" maxlength="1000"></textarea>
            </td>
        </tr>
    </table>
    <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    <input type="hidden" name="isAjax" value="1">
</form>
<div class="action">
    <ul>
        <li>
            <img SRC="imgs/back.png" title="<?php echo _('Atrás'); ?>" class="inputImg" id="btnBack"
                 OnClick="doAction('<?php echo $lastAction; ?>', <?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>)"/>
        </li>
        <li>
            <img SRC="imgs/check.png" title="<?php echo _('Enviar'); ?>" class="inputImg" id="btnSave"
                 OnClick="sendRequest();"/>
        </li>
    </ul>
</div>