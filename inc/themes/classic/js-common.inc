<script>
    $(function () {
        chosenDetect();

        passwordDetect();

        $('.download').button({
            icons: {primary: "	ui-icon-arrowthickstop-1-s"}
        });

        $('.checkbox').button({
            icons: {primary: "ui-icon-transferthick-e-w"}
        }).click(
            function () {
                if ($(this).prop('checked') == true) {
                    $(this).button('option', 'label', '<?php echo _('SI'); ?>');
                } else {
                    $(this).button('option', 'label', '<?php echo _('NO'); ?>');
                }
            }
        );

        $(".help-box").dialog({autoOpen: false, title: '<?php echo _('Ayuda'); ?>', width: screen.width / 2.5});
    });
</script>
