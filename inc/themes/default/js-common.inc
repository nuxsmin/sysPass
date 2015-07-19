<script>
    $(function () {
        $(".sel-chosen-ns").chosen({disable_search: true});
        $(".sel-chosen-usergroup").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Grupo'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
        });
        $(".sel-chosen-user").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Usuario'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
        });
        $(".sel-chosen-profile").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Perfil'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
        });

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
