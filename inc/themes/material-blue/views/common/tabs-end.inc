</div> <!-- End Tabs-->

<script>
    $("#tabs").tabs({
        active: <?php echo $activeTabId; ?>,
        create: function( event, ui ) {
            $("input:visible:first").focus();
        },
        activate: function( event, ui ) {
            sysPassApp.setContentSize();
        }
    });

    sysPassApp.sk.set("<?php echo $sk; ?>");
    // FIXME: eliminar
    sysPassApp.triggers().views.config();
</script>