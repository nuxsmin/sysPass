sysPass.Util.Theme = function () {
    "use strict";

    var Common = new sysPass.Util.Common(),
        passwordData = Common.passwordData,
        APP_ROOT = Common.APP_ROOT,
        LANG = Common.LANG,
        PK = Common.PK;

    // Mostrar el spinner de carga
    var showLoading = function () {
        if (document.getElementById("wrap-loading") !== null) {
            $('#wrap-loading').show();
            $('#loading').addClass('is-active');
        } else {
            $.fancybox.showLoading();
        }
    };

    // Ocultar el spinner de carga
    var hideLoading = function () {
        if (document.getElementById("wrap-loading") !== null) {
            $('#wrap-loading').hide();
            $('#loading').removeClass('is-active');
        } else {
            $.fancybox.hideLoading();
        }
    };

    var activeTooltip = function () {
        // Activar tooltips
        $('.active-tooltip').tooltip({
            content: function () {
                return $(this).attr('title');
            },
            tooltipClass: "tooltip"
        });

        $('.help-tooltip').tooltip({
            content: function () {
                return $(this).next('div').html();
            },
            tooltipClass: "tooltip"
        })
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
            Common.outputResult(level.score, targetId);

            $('#' + targetId).val(genPassword);
            $('#' + targetId + 'R').val(genPassword);
        } else {
            Common.outputResult(level.score, targetId);

            $('input:password, input.password').val(genPassword);
            $('#passLevel').show(500);
        }
    };


    // Diálogo de configuración de complejidad de clave
    var complexityDialog = function () {
        $('<div id="dialog-complexity"></div>').dialog({
            modal: true,
            title: 'Opciones de Complejidad',
            width: '450px',
            open: function () {
                var thisDialog = $(this);

                var content =
                    '<div class="dialog-btns-complexity">' +
                    '<label for="checkbox-numbers">' + LANG[35] + '</label>' +
                    '<input type="checkbox" id="checkbox-numbers" name="checkbox-numbers"/>' +
                    '<label for="checkbox-uppercase">' + LANG[36] + '</label>' +
                    '<input type="checkbox" id="checkbox-uppercase" name="checkbox-uppercase"/>' +
                    '<label for="checkbox-symbols">' + LANG[37] + '</label>' +
                    '<input type="checkbox" id="checkbox-symbols" name="checkbox-symbols"/>' +
                    '</div>' +
                    '<div class="dialog-length-complexity">' +
                    '<label for="passlength">' + LANG[38] + '</label>' +
                    '<input class="inputNumber" pattern="[0-9]*" id="passlength" />' +
                    '</div>' +
                    '<div class="dialog-buttons">' +
                    '<button class="btnDialogOk">Ok</button>' +
                    '</div>';

                thisDialog.html(content);

                // Recentrar después de insertar el contenido
                thisDialog.dialog('option', 'position', 'center');

                // Actualizar componentes de MDL
                thisDialog.ready(function () {
                    $('#checkbox-numbers').prop('checked', passwordData.complexity.numbers);
                    $('#checkbox-uppercase').prop('checked', passwordData.complexity.uppercase);
                    $('#checkbox-symbols').prop('checked', passwordData.complexity.symbols);
                    $('#passlength').val(passwordData.complexity.numlength);

                    $(".dialog-btns-complexity").buttonset({
                        icons: {primary: "ui-icon-transferthick-e-w"}
                    });

                    $(".inputNumber").spinner();

                    $(".btnDialogOk")
                        .button()
                        .click(function () {
                            passwordData.complexity.numbers = $(' #checkbox-numbers').is(':checked');
                            passwordData.complexity.uppercase = $('#checkbox-uppercase').is(':checked');
                            passwordData.complexity.symbols = $('#checkbox-symbols').is(':checked');
                            passwordData.complexity.numlength = parseInt($('#passlength').val());

                            thisDialog.dialog('close');
                        }
                    );
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
            var thisInput = $(this);
            var targetId = $(this).attr('id');

            if (thisInput.next().hasClass('password-actions')) {
                return;
            }

            var btnMenu = '<div><button type="button" class="quickGenPass" data-targetid="' + targetId + '">' + LANG[28] + '</button>';
            btnMenu += '<button type="button" class="genPassActions">' + LANG[27] + '</button></div>';
            btnMenu += '<ul>';
            btnMenu += '<li data-targetid="' + targetId + '" class="passGen">' + LANG[28] + '</li>';
            btnMenu += '<li data-targetid="' + targetId + '" class="passComplexity">' + LANG[29] + '</li>';
            btnMenu += '<li data-targetid="' + targetId + '" class="reset">' + LANG[30] + '</li>';
            btnMenu += '</ul>';

            thisInput.after('<div class="password-actions" />');

            thisInput.next('.password-actions')
                .prepend(btnMenu)
                .prepend('<img class="showpass inputImg" src="imgs/show.png" title="' + LANG[32] + '" data-targetid="' + targetId + '" />')
                .prepend('<span class="passLevel passLevel-' + targetId + ' fullround" title="' + LANG[31] + '"></span>');

            $(".quickGenPass")
                .button({
                    text: false,
                    icons: {
                        primary: "ui-icon-gear"
                    }
                })
                .click(function () {
                    password(11, true, true, targetId);
                })
                .next()
                .button({
                    text: false,
                    icons: {
                        primary: "ui-icon-key"
                    }
                })
                .click(function () {
                    var menu = $(this).parent().next().show().position({
                        my: "left top",
                        at: "left bottom",
                        of: this
                    });
                    $(document).one("click", function () {
                        menu.hide();
                    });
                    return false;
                })
                .parent()
                .buttonset()
                .next()
                .hide()
                .menu();


            $(this).on('keyup', function () {
                Common.checkPassLevel($(this).val(), targetId);
            });
        });

        // Crear los iconos de acciones sobre claves (sólo mostrar clave)
        $('.passwordfield__input-show').each(function () {
            var thisParent = $(this);
            var targetId = $(this).attr('id');

            thisParent
                .after('<img class="showpass inputImg" src="imgs/show.png" title="' + LANG[32] + '" data-targetid="' + targetId + '" />');
        });

        // Crear evento para generar clave aleatoria
        $('.passGen').each(function () {
            $(this).on('click', function () {
                var targetId = $(this).data('targetid');
                password(11, true, true, targetId);
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

                    Common.setContentSize();

                    // Activar tooltips
                    activeTooltip();
                }
            });

            $(document).ready(function () {
                Common.setContentSize();
                //setWindowAdjustSize();

                // Activar tooltips
                activeTooltip();
            });
        },
        Common : Common
    };
};

// Inicializar funciones del tema
var sysPassUtil = new sysPass.Util.Theme();
sysPassUtil.init();