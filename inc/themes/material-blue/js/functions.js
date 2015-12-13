sysPass.Util.Theme = function () {
    "use strict";

    var Common = new sysPass.Util.Common(),
        passwordData = Common.passwordData,
        APP_ROOT = Common.APP_ROOT,
        LANG = Common.LANG,
        PK = Common.PK;

    // Mostrar el spinner de carga
    var showLoading = function () {
        $('#wrap-loading').show();
        $('#loading').addClass('is-active');
    };

    // Ocultar el spinner de carga
    var hideLoading = function () {
        $('#wrap-loading').hide();
        $('#loading').removeClass('is-active');
    };

    var activeTooltip = function () {
        // Activar tooltips
        $('.active-tooltip').tooltip({
            content: function () {
                return $(this).attr('title');
            },
            tooltipClass: "tooltip"
        });
    };

    // Función para generar claves aleatorias.
    // By Uzbekjon from  http://jquery-howto.blogspot.com.es
    var password = function (length, special, fancy, targetId) {
        var iteration = 0,
            genPassword = '',
            randomNumber;

        while (iteration < passwordData.complexity.numlength) {
            randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
            if (!passwordData.complexity.symbols) {
                if ((randomNumber >= 33) && (randomNumber <= 47)) {
                    continue;
                }
                if ((randomNumber >= 58) && (randomNumber <= 64)) {
                    continue;
                }
                if ((randomNumber >= 91) && (randomNumber <= 96)) {
                    continue;
                }
                if ((randomNumber >= 123) && (randomNumber <= 126)) {
                    continue;
                }
            }

            if (!passwordData.complexity.numbers && randomNumber >= 48 && randomNumber <= 57) {
                continue;
            }

            if (!passwordData.complexity.uppercase && randomNumber >= 65 && randomNumber <= 90) {
                continue;
            }

            iteration++;
            genPassword += String.fromCharCode(randomNumber);
        }

        if (fancy === true) {
            $("#viewPass").attr("title", genPassword);
            //alertify.alert('<div id="alert"><p id="alert-text">' + LANG[6] + '</p><p id="alert-pass"> ' + password + '</p>');
        } else {
            alertify.alert('<div id="alert"><p id="alert-text">' + LANG[6] + '</p><p id="alert-pass"> ' + genPassword + '</p>');
        }

        var level = zxcvbn(genPassword);
        passwordData.passLength = genPassword.length;

        if (targetId) {
            var dstParent = $('#' + targetId).parent();

            Common.outputResult(level.score, targetId);

            // Actualizar los componentes de MDL
            var mdl = new MaterialTextfield();

            // Poner la clave en los input y actualizar MDL
            dstParent.find('input:password').val(genPassword);
            dstParent.addClass(mdl.CssClasses_.IS_DIRTY).removeClass(mdl.CssClasses_.IS_INVALID);
            // Poner la clave en el input de repetición y encriptarla
            $('#' + targetId + 'R').val(genPassword).parent().addClass(mdl.CssClasses_.IS_DIRTY).removeClass(mdl.CssClasses_.IS_INVALID);
            sysPassUtil.Common.encryptFormValue('#' + targetId + 'R');

            // Mostar el indicador de complejidad
            dstParent.find('#passLevel').show(500);
        } else {
            Common.outputResult(level.score);
            $('input:password, input.password').val(genPassword);
            $('#passLevel').show(500);
        }
    };


    // Diálogo de configuración de complejidad de clave
    var complexityDialog = function () {
        $('<div></div>').dialog({
            modal: true,
            title: LANG[29],
            width: '400px',
            open: function () {
                var thisDialog = $(this);

                var content =
                    '<label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-numbers">' +
                    '<input type="checkbox" id="checkbox-numbers" class="mdl-checkbox__input" name="checkbox-numbers"/>' +
                    '<span class="mdl-checkbox__label">' + LANG[35] + '</span>' +
                    '</label>' +
                    '<label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-uppercase">' +
                    '<input type="checkbox" id="checkbox-uppercase" class="mdl-checkbox__input" name="checkbox-uppercase"/>' +
                    '<span class="mdl-checkbox__label">' + LANG[36] + '</span>' +
                    '</label>' +
                    '<label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-symbols">' +
                    '<input type="checkbox" id="checkbox-symbols" class="mdl-checkbox__input" name="checkbox-symbols"/>' +
                    '<span class="mdl-checkbox__label">' + LANG[37] + '</span>' +
                    '</label>' +
                    '<div class="mdl-textfield mdl-js-textfield textfield-passlength">' +
                    '<input class="mdl-textfield__input" type="number" pattern="[0-9]*" id="passlength" />' +
                    '<label class="mdl-textfield__label" for="passlength">' + LANG[38] + '</label>' +
                    '</div>' +
                    '<button id="btn-complexity" class="mdl-button mdl-js-button mdl-button--raised">Ok</button>';

                thisDialog.html(content);

                // Recentrar después de insertar el contenido
                thisDialog.dialog('option', 'position', 'center');


                // Actualizar componentes de MDL
                thisDialog.ready(function () {
                    $('#checkbox-numbers').prop('checked', passwordData.complexity.numbers);
                    $('#checkbox-uppercase').prop('checked', passwordData.complexity.uppercase);
                    $('#checkbox-symbols').prop('checked', passwordData.complexity.symbols);
                    $('#passlength').val(passwordData.complexity.numlength);

                    $('#btn-complexity').click(function () {
                        passwordData.complexity.numbers = $(' #checkbox-numbers').is(':checked');
                        passwordData.complexity.uppercase = $('#checkbox-uppercase').is(':checked');
                        passwordData.complexity.symbols = $('#checkbox-symbols').is(':checked');
                        passwordData.complexity.numlength = parseInt($('#passlength').val());

                        thisDialog.dialog('close');
                    });

                    // Actualizar objetos de MDL
                    componentHandler.upgradeDom();
                });
            },
            // Forzar la eliminación del objeto para que ZeroClipboard siga funcionando al abrirlo de nuevo
            close: function () {
                $(this).dialog("destroy");
            }
        });
    };

    /**
     * Detectar los campos de clave y añadir funciones
     */
    var passwordDetect = function () {
        // Crear los iconos de acciones sobre claves
        $('.passwordfield__input').each(function () {
            var thisParent = $(this).parent();
            var targetId = $(this).attr('id');

            if (thisParent.next().hasClass('password-actions')) {
                return;
            }

            var btnMenu = '<button id="menu-speed-' + targetId + '" class="mdl-button mdl-js-button mdl-button--icon" type="button" title="' + LANG[27] + '"><i class="material-icons">more_vert</i></button>';

            btnMenu += '<ul class="mdl-menu mdl-js-menu" for="menu-speed-' + targetId + '">';
            btnMenu += '<li class="mdl-menu__item passGen" data-targetid="' + targetId + '"><i class="material-icons">settings</i>' + LANG[28] + '</li>';
            btnMenu += '<li class="mdl-menu__item passComplexity" data-targetid="' + targetId + '"><i class="material-icons">vpn_key</i>' + LANG[29] + '</li>';
            btnMenu += '<li class="mdl-menu__item reset" data-targetid="' + targetId + '"><i class="material-icons">refresh</i>' + LANG[30] + '</li>';

            thisParent.after('<div class="password-actions" />');

            thisParent.next('.password-actions')
                .prepend('<span class="passLevel passLevel-' + targetId + ' fullround" title="' + LANG[31] + '"></span>')
                .prepend('<i class="showpass material-icons" title="' + LANG[32] + '" data-targetid="' + targetId + '">remove_red_eye</i>')
                .prepend(btnMenu);

            $(this).on('keyup', function () {
                Common.checkPassLevel($(this).val(), targetId);
            });
        });

        // Crear los iconos de acciones sobre claves (sólo mostrar clave)
        $('.passwordfield__input-show').each(function () {
            var thisParent = $(this).parent();
            var targetId = $(this).attr('id');

            thisParent
                .after('<i class="showpass material-icons" title="' + LANG[32] + '" data-targetid="' + targetId + '">remove_red_eye</i>');
        });

        // Crear evento para generar clave aleatoria
        $('.passGen').each(function () {
            $(this).on('click', function () {
                var targetId = $(this).data('targetid');
                password(11, true, true, targetId);
                $('#' + targetId).focus();
            });
        });

        $('.passComplexity').each(function () {
            $(this).on('click', function () {
                complexityDialog();
            });
        });

        // Crear evento para mostrar clave generada/introducida
        $('.showpass').each(function () {
            $(this).on('mouseover', function () {
                var targetId = $(this).data('targetid');
                $(this).attr('title', $('#' + targetId).val());
            });
        });

        // Reset de los campos de clave
        $('.reset').each(function () {
            $(this).on('click', function () {
                var targetId = $(this).data('targetid');
                $('#' + targetId).val('');
                $('#' + targetId + 'R').val('');

                // Actualizar objetos de MDL
                componentHandler.upgradeDom();
            });
        });
    };

    return {
        showLoading: showLoading,
        hideLoading: hideLoading,
        activeTooltip: activeTooltip,
        passwordDetect: passwordDetect,
        password : password,
        init: function () {
            jQuery.extend(jQuery.fancybox.defaults, {
                type: 'ajax',
                autoWidth: true,
                autoHeight: true,
                autoResize: true,
                autoCenter: true,
                fitToView: false,
                minHeight: 50,
                padding: 0,
                helpers: {overlay: {css: {'background': 'rgba(0, 0, 0, 0.1)'}}},
                keys: {close: [27]},
                afterShow: function () {
                    $('#fancyContainer').find('input:visible:first').focus();
                }
            });

            jQuery.ajaxSetup({
                beforeSend: function () {
                    showLoading();
                },
                complete: function () {
                    hideLoading();

                    // Actualizar componentes de MDL cargados con AJAX
                    componentHandler.upgradeDom();

                    // Activar tooltips
                    activeTooltip();
                }
            });

            $(document).ready(function () {
                //setContentSize();
                //setWindowAdjustSize();

                // Activar tooltips
                activeTooltip();
            });
        },
        Common : Common
    };
};

// Inicializar funciones del Tema
var sysPassUtil = new sysPass.Util.Theme();
sysPassUtil.init();