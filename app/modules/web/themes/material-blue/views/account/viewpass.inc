<?php
/** @var  \SP\DataModel\CategoryData $category */
/** @var  \SP\Core\UI\ThemeIcons $icons */
?>
<div id="box-popup" class="box-password-view">
    <h2 class="center"><?php echo $header; ?>&nbsp;
        <?php if ($isLinked): ?>
            <i title="<?php echo __('Cuenta Vinculada'); ?>"
               class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
        <?php endif; ?>
    </h2>
    <table>
        <tbody>
        <tr>
            <?php if (!$isImage): ?>
                <td class="dialog-text dialog-user-text"><?php echo wordwrap($login, 50, PHP_EOL, true); ?></td>
                <td class="dialog-button right">
                    <button class="dialog-clip-button mdl-button mdl-js-button mdl-js-ripple-effect"
                            title="<?php echo __('Copiar Usuario'); ?>" data-clipboard-target=".dialog-user-text">
                        <?php echo __('Usuario'); ?>
                        <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                    </button>
                </td>
            <?php else: ?>
                <td class="dialog-user-text">
                    <img src="data:image/png;base64,<?php echo $login; ?>"/>
                </td>
                <td>&nbsp;</td>
            <?php endif; ?>
        </tr>
        <tr>
            <?php if (!$isImage): ?>
                <td class="dialog-text dialog-pass-text"><?php echo wordwrap($pass, 50, PHP_EOL, true); ?></td>
                <td class="dialog-button right">
                    <button class="dialog-clip-button mdl-button mdl-js-button mdl-js-ripple-effect"
                            title="<?php echo __('Copiar Clave'); ?>" data-clipboard-target=".dialog-pass-text">
                        <?php echo __('Clave'); ?>
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
