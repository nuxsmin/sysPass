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
        $('#allowed_exts').selectize({
            create: function(input) {
                return {
                    value: input.toUpperCase(),
                    text: input.toUpperCase()
                }
            },
            createFilter: new RegExp('^[a-z0-9]{1,4}$', 'i'),
            plugins: ['remove_button']
        });
    })
</script>