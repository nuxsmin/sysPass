<?php /** @var $icons \Theme\Icons */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo $title['name']; ?>
    <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
</div>

<form method="post" name="editpass" id="frmEditPass" onsubmit="sysPassUtil.Common.saveAccount('frmEditPass'); return false;">
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
    <input type="hidden" name="next" value="<?php echo $nextaction; ?>">
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    <input type="hidden" name="isAjax" value="1">

    <div class="action">
        <button id="btnBack" type="button" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                OnClick="sysPassUtil.Common.doAction(<?php echo $lastAction; ?>, <?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountId; ?>)"
                title="<?php echo $icons->getIconBack()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
        </button>

        <button id="btnSave" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</form>


<script>
    $(function () {
        $('input:password:visible:first').focus();
    });
</script>
