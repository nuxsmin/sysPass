<script type="text/javascript">
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

        $( ".download" ).button({
            icons: { primary: "	ui-icon-arrowthickstop-1-s"}
        });

        $(".help-box").dialog({autoOpen: false, title: '<?php echo _('Ayuda'); ?>', width: screen.width / 2.5  });

        // Crear los iconos de acciones sobre claves
        $('.passwordfield__input').each(function(){
            var thisParent =  $(this).parent();
            var targetId = $(this).attr('id');

            thisParent
                .after('<span class="passLevel passLevel-' + targetId + ' fullround" title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>')
                .after('<i class="passGen material-icons" title="<?php echo _('Generar clave aleatoria'); ?>" data-targetid="' + targetId + '">settings</i>')
                .after('<i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>" data-targetid="' + targetId + '">remove_red_eye</i>');

            $(this).on('keyup', function(){
                checkPassLevel($(this).val(), targetId);
            });
        });

        // Crear los iconos de acciones sobre claves (sólo mostrar clave)
        $('.passwordfield__input-show').each(function(){
            var thisParent =  $(this).parent();
            var targetId = $(this).attr('id');

            thisParent
                .after('<i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>" data-targetid="' + targetId + '">remove_red_eye</i>');
        });

        // Crear evento para generar clave aleatoria
        $('.passGen').each(function(){
            $(this).on('click', function () {
                var targetId = $(this).data('targetid');
                password(11, true, true, targetId);

                var mdl = new MaterialTextfield();

                $('#' + targetId).parent().addClass(mdl.CssClasses_.IS_DIRTY).removeClass(mdl.CssClasses_.IS_INVALID);
                $('#' + targetId + 'R').parent().addClass(mdl.CssClasses_.IS_DIRTY).removeClass(mdl.CssClasses_.IS_INVALID);

            });
        });

        // Crear evento para mostrar clave generada/introducida
        $('.showpass').each(function(){
            $(this).on('mouseover', function () {
                var targetId = $(this).data('targetid');
                $(this).attr('title', $('#' + targetId).val());
            });
        });

        // Stick the #nav to the top of the window
        var nav = $('#actionsBar');
        var logo = $('#actionsBar #actionsBar-logo img');
        var isFixed = false;
        var navCssProps = {
            position: 'fixed',
            top: 0,
            left: nav.offset().left,
            width: nav.width(),
            padding: "1em 0",
            backgroundColor: 'rgba(255, 255, 255, .75)',
            borderBottom: '1px solid #ccc'
        }

        $(window).scroll(function() {
            var scrollTop = $(this).scrollTop();
            var shouldBeFixed = scrollTop > nav.height();
            if (shouldBeFixed && !isFixed) {
                nav.css(navCssProps);
                logo.show().css({ opacity: 0.75 });
                isFixed = true;
            }
            else if (!shouldBeFixed && isFixed)
            {
                nav.css({
                    backgroundColor: 'transparent',
                    border: '0'
                });
                logo.hide();
                isFixed = false;
            }
        });

        $(document).ready(function(){
            // Detectar si al cargar la barra de iconos no está en la posición 0
            if(nav.offset().top > 0){
                nav.css(navCssProps);
                logo.show().css({ opacity: 0.75 });
                isFixed = true;
            }
        });
    });
</script>
