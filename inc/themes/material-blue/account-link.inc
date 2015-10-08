<div id="actions" class="public-link" align="center">
    <div id="logo"></div>
    <div id="content" class="public-link">
        <div id="title" class="midroundup titleNormal">
            <?php echo $title['name']; ?>
            <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
        </div>
        <table class="data round">
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <?php echo $accountData->account_name; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Cliente'); ?></td>
                <td class="valField">
                    <?php echo $accountData->customer_name; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Categoría'); ?></td>
                <td class="valField">
                    <?php echo $accountData->category_name; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('URL / IP'); ?></td>
                <td class="valField">
                    <?php echo $accountData->account_url; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Usuario'); ?></td>
                <td class="valField">
                    <?php echo $accountData->account_login; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Notas'); ?></td>
                <td class="valField">
                    <?php echo $accountData->account_notes; ?>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Clave'); ?></td>
                <td class="valField">
                    <?php if(\SP\Util::accountPassToImageIsEnabled()): ?>
                        <img class="account-pass-image" src="data:image/png;base64,<?php echo $accountPass; ?>" />
                    <?php else: ?>
                        <span id="account-pass"><?php echo $accountPass; ?></span>
                    <?php endif; ?>
            </tr>
        </table>

        <div id="clip-pass-text" style="visibility: hidden"></div>

        <div class="action">
            <?php if(!\SP\Util::accountPassToImageIsEnabled()): ?>
                <button id="btnClipPass" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored" data-clipboard-target="#account-pass">
                    <i class="material-icons">content_paste</i>
                </button>

                <script type="application/javascript">
                    window.onload = function () {
                        // Inicializar el objeto para copiar al portapapeles
                        var clipboardPass = new Clipboard("#btnClipPass");

                        clipboardPass.on('success', function (e) {
                            sysPassUtil.Common.resMsg("ok", sysPassUtil.Common.LANG[45]);
                        });
                    };
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>