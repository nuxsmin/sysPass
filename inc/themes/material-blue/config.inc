<!-- Start Tab - Config -->
<?php /** @var $icons \Theme\Icons */ ?>
<div id="tabs-<?php echo $config_tabIndex; ?>">

    <form method="post" name="frmConfig" id="frmConfig"
          onsubmit="sysPassUtil.Common.configMgmt('config', this); return false;">

        <?php include 'config-site.inc'; ?>
        <?php include 'config-events.inc'; ?>
        <?php include 'config-accounts.inc'; ?>
        <?php include 'config-files.inc'; ?>
        <?php include 'config-links.inc'; ?>
        <?php include 'config-proxy.inc'; ?>

        <?php if ($isDemoMode): ?>
            <input type="hidden" name="log_enabled" value="1"/>
            <input type="hidden" name="files_enabled" value="1"/>
        <?php endif; ?>

        <input type="hidden" name="activeTab" value="<?php echo $config_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $config_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button form="frmConfig"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div> <!-- End Tab - Config -->

<script>
    $(function () {
        // Mostrar un aviso tras añadir una etiqueta a la lista
        var showTagWarning = function (obj) {
            // Fix scrolling to bottom
            var $tagsbox = obj.next();
            $tagsbox.animate({scrollTop: $tagsbox.height()});

            if (!$tagsbox.find('span').hasClass('icon')) {
                $tagsbox.find('div:last').prev().append('<span class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>" title="' + sysPassUtil.Common.LANG[5] + '"><?php echo $icons->getIconWarning()->getIcon(); ?></span>');
            }
        };

        $('#allowed_exts').tagsInput({
            'width': '350px',
            'defaultText': '<?php echo _('Añadir extensión'); ?>',
            'defaultRemoveText': '<?php echo _('Eliminar extensión'); ?>',
            'removeWithBackspace': false,
            'tagsToUpper': true,
            'maxChars': 4,
            'onAddTag': function () {
                showTagWarning($(this))
            },
            'onRemoveTag': function () {
                showTagWarning($(this))
            }
        });
    })
</script>