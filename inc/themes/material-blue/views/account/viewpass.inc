<?php
/** @var  \SP\DataModel\CategoryData $category */
/** @var  \SP\Core\UI\ThemeIconsBase $icons */
?>
<div id="fancyContainer" align="center">
    <h2><?php echo $header; ?></h2>
    <table>
        <tbody>
        <tr>
            <td class="dialog-user-text"><?php echo $login; ?></td>
            <td class="right">
                <button class="dialog-clip-user-button mdl-button mdl-js-button mdl-js-ripple-effect"
                        title="<?php echo _('Copiar Usuario'); ?>" data-clipboard-target=".dialog-user-text">
                    <?php echo _('Usuario'); ?>
                    <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                </button>
            </td>
        </tr>
        <tr>
            <?php if (!$isImage): ?>
                <td class="dialog-pass-text"><?php echo $pass; ?></td>
                <td class="right">
                    <button class="dialog-clip-pass-button mdl-button mdl-js-button mdl-js-ripple-effect"
                            title="<?php echo _('Copiar Clave'); ?>" data-clipboard-target=".dialog-pass-text">
                        <?php echo _('Clave'); ?>
                        <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                    </button>
                </td>
            <?php else: ?>
                <td class="dialog-pass-text">
                    <img src="data:image/png;base64,<?php echo $pass; ?>"/>
                </td>
                <td>&nbsp;</td>
            <?php endif; ?>
        </tr>
        </tbody>
    </table>
</div>
<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>
