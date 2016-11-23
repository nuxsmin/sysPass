<div id="actions" class="public-link" align="center">
    <div id="content" class="public-link">
        <div id="title" class="midroundup titleNormal">
            <?php echo $title['name']; ?>
            <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
        </div>
        <table class="data round">
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <?php echo $accountData->getAccountName(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Cliente'); ?></td>
                <td class="valField">
                    <?php echo $accountData->getCustomerName(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Categoría'); ?></td>
                <td class="valField">
                    <?php echo $accountData->getCategoryName(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('URL / IP'); ?></td>
                <td class="valField">
                    <?php echo $accountData->getAccountUrl(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Usuario'); ?></td>
                <td class="valField">
                    <?php echo $accountData->getAccountLogin(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Notas'); ?></td>
                <td class="valField">
                    <?php echo $accountData->getAccountNotes(); ?>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Clave'); ?></td>
                <td class="valField">
                    <?php if ($useImage): ?>
                        <img class="account-pass-image" src="data:image/png;base64,<?php echo $accountPass; ?>"/>
                    <?php else: ?>
                        <span id="account-pass"><?php echo $accountPass; ?></span>
                    <?php endif; ?>
            </tr>
        </table>

        <div class="action">
            <?php if (!$useImage): ?>
                <button id="btnClipPass"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-color--indigo-A200"
                        data-clipboard-target="#account-pass"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW_PASS; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-history="0"
                        data-onclick="account/copypass">
                    <i class="material-icons">content_paste</i>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>