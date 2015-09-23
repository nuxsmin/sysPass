<script>
    $(function () {
        sysPassUtil.Common.chosenDetect();

        sysPassUtil.passwordDetect();

        sysPassUtil.Common.checkboxDetect('#content');

        $('.download').button({
            icons: {primary: "	ui-icon-arrowthickstop-1-s"}
        });

        $(".help-box").dialog({autoOpen: false, title: '<?php echo _('Ayuda'); ?>', width: screen.width / 2.5});
    });
</script>
