<?php /** @var $icons \Theme\Icons */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo $title['name']; ?>
    <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
</div>

<form method="post" name="editpass" id="frmEditPass" class="form-action" data-onsubmit="account/save" data-hash="">
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td>
            <td class="valField"><?php echo $accountData->getAccountName(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td>
            <td class="valField"><?php echo $accountData->customer_name; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('URL / IP'); ?></td>
            <td class="valField"><A href="<?php echo $accountData->getAccountUrl(); ?>"
                                    target="_blank"><?php echo $accountData->getAccountUrl(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField"><?php echo $accountData->getAccountLogin(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Clave'); ?></td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="accountpass" name="pass" type="password" required class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label" for="accountpass"><?php echo _('Clave'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="accountpassR" name="passR" type="password" required class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label" for="accountpassR"><?php echo _('Clave'); ?></label>
                </div>
            </td>
        </tr>
    </table>
    <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
    <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
    <input type="hidden" name="sk" value="">
    <input type="hidden" name="isAjax" value="1">

    <div class="action">
        <button id="btnBack" type="button"
                class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                title="<?php echo _('Atrás'); ?>">
            <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
        </button>

        <button id="btnSave" name="btnSave" type="submit" form="frmEditPass"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo _('Guardar'); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</form>


<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
    $('input:password:visible:first').focus();
</script>
