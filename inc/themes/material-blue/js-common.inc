<script type="text/javascript">
    $(function () {
        chosenDetect();

        passwordDetect();

        $(".download").button({
            icons: {primary: "	ui-icon-arrowthickstop-1-s"}
        });

        $(".help-box").dialog({autoOpen: false, title: '<?php echo _('Ayuda'); ?>', width: screen.width / 2.5});

        $(document).ready(function () {
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

            $(window).scroll(function () {
                var scrollTop = $(this).scrollTop();
                var shouldBeFixed = scrollTop > nav.height();
                if (shouldBeFixed && !isFixed) {
                    nav.css(navCssProps);
                    logo.show().css({opacity: 0.75});
                    isFixed = true;
                }
                else if (!shouldBeFixed && isFixed) {
                    nav.css({
                        backgroundColor: 'transparent',
                        border: '0'
                    });
                    logo.hide();
                    isFixed = false;
                }
            });

            // Detectar si al cargar la barra de iconos no está en la posición 0
            if (nav.offset().top > 0) {
                nav.css(navCssProps);
                logo.show().css({opacity: 0.75});
                isFixed = true;
            }
        });
    });
</script>
