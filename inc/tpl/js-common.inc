<script>
    $(function () {
        $(".showpass").each(function (index, input) {
            // Cross-browser compatibility
            $(this).on("mouseover", function () {
                $(this).data('powertip', function(){
                 return $(this).prev().val()
                });
            });
        });

        $( ".download" ).button({
            icons: { primary: "	ui-icon-arrowthickstop-1-s"}
        });

        $( ".checkbox" ).button({
            icons: { primary: "ui-icon-transferthick-e-w"}
        });

        $('.checkbox').button().click(
            function () {
//                var curValue = $(this).button('option', 'label');
//
//                if(curValue != ''){
//                    curValue += ' ';
//                }

                if ($(this).attr('checked') == undefined) {
                    $(this).button('option', 'label', '<?php echo _('NO'); ?>');
                } else {
                    $(this).button('option', 'label', '<?php echo _('SI'); ?>');
                }
            }
        );

        $(".help-box").dialog({autoOpen: false, title: '<?php echo _('Ayuda'); ?>', width: screen.width / 2.5  });

        $('#passGen').click(function () {
            password(11, true, true);
        });
    });
</script>
