/*
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

sysPass.Theme = function (Common) {
    "use strict";

    var log = Common.log;

    /**
     * Funciones a realizar en peticiones AJAX
     *
     * @type {{complete: ajax.complete}}
     */
    var ajax = {
        complete: function () {
            log.info("ajax:complete");

            // Actualizar componentes de MDL cargados con AJAX
            componentHandler.upgradeDom();
        }
    };

    /**
     * Mostrar/Ocultar el spinner de carga
     *
     * @type {{show: loading.show, hide: loading.hide}}
     */
    var loading = {
        elems: {
            $wrap: $("#wrap-loading"),
            $loading: $("#loading")
        },
        show: function (full) {
            if (full !== undefined && full === true) {
                loading.elems.$wrap.addClass("overlay-full");
            }

            loading.elems.$wrap.show();
            loading.elems.$loading.addClass("is-active");
        },
        hide: function () {
            loading.elems.$wrap.removeClass("overlay-full").hide();
            loading.elems.$loading.removeClass("is-active");
        },
        upgradeFull: function () {
            loading.elems.$wrap.addClass("overlay-full");
        }
    };

    // Función para generar claves aleatorias.
    var password = function ($target) {
        var i = 0;
        var chars = "";
        var genPassword = "";

        var getRandomChar = function (min, max) {
            return chars.charAt(Math.floor((Math.random() * max) + min));
        };

        if (Common.passwordData.complexity.symbols) {
            chars += "!\"\\·@|#$~%&/()=?'¿¡^*[]·;,_-{}<>";
        }

        if (Common.passwordData.complexity.numbers) {
            chars += "1234567890";
        }

        if (Common.passwordData.complexity.chars) {
            chars += "abcdefghijklmnopqrstuvwxyz";

            if (Common.passwordData.complexity.uppercase) {
                chars += String("abcdefghijklmnopqrstuvwxyz").toUpperCase();
            }
        }

        for (; i++ <= Common.passwordData.complexity.numlength;) {
            genPassword += getRandomChar(0, chars.length - 1);
        }

        $("#viewPass").attr("title", genPassword);

        var level = zxcvbn(genPassword);
        Common.passwordData.passLength = genPassword.length;

        if ($target) {
            var $dstParent = $target.parent();
            var $targetR = $("#" + $target.attr("id") + "R");

            Common.outputResult(level, $target);

            // Actualizar los componentes de MDL
            var mdl = new MaterialTextfield();

            // Poner la clave en los input y actualizar MDL
            $dstParent.find("input:password").val(genPassword);
            $dstParent.addClass(mdl.CssClasses_.IS_DIRTY).removeClass(mdl.CssClasses_.IS_INVALID);

            // Poner la clave en el input de repetición y encriptarla
            if ($targetR.length > 0) {
                $targetR.val(genPassword).parent().addClass(mdl.CssClasses_.IS_DIRTY).removeClass(mdl.CssClasses_.IS_INVALID);
                Common.encryptFormValue($targetR);
            }

            // Mostar el indicador de complejidad
            $dstParent.find("#passLevel").show(500);
        } else {
            Common.outputResult(level);
            $("input:password, input.password").val(genPassword);
            $("#passLevel").show(500);
        }
    };

    // Diálogo de configuración de complejidad de clave
    var complexityDialog = function () {

        var content =
            "<div id=\"box-complexity\"><div>" +
            "<label class=\"mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect\" for=\"checkbox-chars\">" +
            "<input type=\"checkbox\" id=\"checkbox-chars\" class=\"mdl-checkbox__input\" name=\"checkbox-chars\" checked/>" +
            "<span class=\"mdl-checkbox__label\">" + Common.config().LANG[63] + "</span>" +
            "</label>" +
            "<label class=\"mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect\" for=\"checkbox-numbers\">" +
            "<input type=\"checkbox\" id=\"checkbox-numbers\" class=\"mdl-checkbox__input\" name=\"checkbox-numbers\" checked/>" +
            "<span class=\"mdl-checkbox__label\">" + Common.config().LANG[35] + "</span>" +
            "</label>" +
            "<label class=\"mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect\" for=\"checkbox-uppercase\">" +
            "<input type=\"checkbox\" id=\"checkbox-uppercase\" class=\"mdl-checkbox__input\" name=\"checkbox-uppercase\"/>" +
            "<span class=\"mdl-checkbox__label\">" + Common.config().LANG[36] + "</span>" +
            "</label>" +
            "<label class=\"mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect\" for=\"checkbox-symbols\">" +
            "<input type=\"checkbox\" id=\"checkbox-symbols\" class=\"mdl-checkbox__input\" name=\"checkbox-symbols\"/>" +
            "<span class=\"mdl-checkbox__label\">" + Common.config().LANG[37] + "</span>" +
            "</label>" +
            "<div class=\"mdl-textfield mdl-js-textfield textfield-passlength\">" +
            "<input class=\"mdl-textfield__input\" type=\"number\" pattern=\"[0-9]*\" id=\"passlength\" />" +
            "<label class=\"mdl-textfield__label\" for=\"passlength\">" + Common.config().LANG[38] + "</label>" +
            "</div></div></div>";

        mdlDialog().show({
            title: Common.config().LANG[29],
            text: content,
            negative: {
                title: Common.config().LANG[44]
            },
            positive: {
                title: Common.config().LANG[43],
                onClick: function (e) {
                    e.preventDefault();

                    Common.passwordData.complexity.chars = $("#checkbox-chars").is(":checked");
                    Common.passwordData.complexity.numbers = $("#checkbox-numbers").is(":checked");
                    Common.passwordData.complexity.uppercase = $("#checkbox-uppercase").is(":checked");
                    Common.passwordData.complexity.symbols = $("#checkbox-symbols").is(":checked");
                    Common.passwordData.complexity.numlength = parseInt($("#passlength").val());
                }
            },
            cancelable: true,
            contentStyle: {"max-width": "300px"},
            onLoaded: function () {
                $("#checkbox-chars").prop("checked", Common.passwordData.complexity.chars);
                $("#checkbox-numbers").prop("checked", Common.passwordData.complexity.numbers);
                $("#checkbox-uppercase").prop("checked", Common.passwordData.complexity.uppercase);
                $("#checkbox-symbols").prop("checked", Common.passwordData.complexity.symbols);
                $("#passlength").val(Common.passwordData.complexity.numlength);
            }
        });
    };

    /**
     * Detectar los campos de clave y añadir funciones
     */
    var passwordDetect = function ($container) {
        // Crear los iconos de acciones sobre claves
        $container.find(".passwordfield__input").each(function () {
            var $this = $(this);

            if ($this.attr("data-pass-upgraded") === "true") {
                return;
            }

            var $thisParent = $this.parent();
            var targetId = $this.attr("id");

            var btnMenu = "<button id=\"menu-speed-" + targetId + "\" class=\"mdl-button mdl-js-button mdl-button--icon\" type=\"button\" title=\"" + Common.config().LANG[27] + "\"><i class=\"material-icons\">more_vert</i></button>";

            btnMenu += "<ul class=\"mdl-menu mdl-js-menu\" for=\"menu-speed-" + targetId + "\">";
            btnMenu += "<li class=\"mdl-menu__item passGen\"><i class=\"material-icons\">settings</i>" + Common.config().LANG[28] + "</li>";
            btnMenu += "<li class=\"mdl-menu__item passComplexity\"><i class=\"material-icons\">vpn_key</i>" + Common.config().LANG[29] + "</li>";
            btnMenu += "<li class=\"mdl-menu__item reset\"><i class=\"material-icons\">refresh</i>" + Common.config().LANG[30] + "</li>";

            $thisParent.after("<div class=\"password-actions\" />");

            $thisParent.next(".password-actions")
                .prepend("<span class=\"passLevel passLevel-" + targetId + " fullround\" title=\"" + Common.config().LANG[31] + "\"></span>")
                .prepend("<i class=\"showpass material-icons\" title=\"" + Common.config().LANG[32] + "\">remove_red_eye</i>")
                .prepend(btnMenu);

            $this.on("keyup", function () {
                Common.checkPassLevel($this);
            });

            var $passwordActions = $this.parent().next();

            // Crear evento para generar clave aleatoria
            $passwordActions.find(".passGen").on("click", function () {
                password($this);
                $this.focus();
            });

            $passwordActions.find(".passComplexity").on("click", function () {
                complexityDialog();
            });

            // Crear evento para mostrar clave generada/introducida
            $passwordActions.find(".showpass").on("mouseover", function () {
                $(this).attr("title", $this.val());
            });

            // Reset de los campos de clave
            $passwordActions.find(".reset").on("click", function () {
                $this.val("");

                var $targetIdR = $("#" + targetId + "R");

                if ($targetIdR.length > 0) {
                    $targetIdR.val("");
                }

                // Actualizar objetos de MDL
                componentHandler.upgradeDom();
            });

            $this.attr("data-pass-upgraded", "true");
        });

        // Crear los iconos de acciones sobre claves (sólo mostrar clave)
        $container.find(".passwordfield__input-show").each(function () {
            var $this = $(this);
            var $icon = $("<i class=\"showpass material-icons\" title=\"" + Common.config().LANG[32] + "\">remove_red_eye</i>");

            if ($this.data("clipboard") === 1) {
                var $clip = $("<i class=\"clip-pass-icon material-icons\" title=\"" + Common.config().LANG[34] + "\" data-clipboard-target=\"#" + $this.attr("id") + "\">content_paste</i>");
                $this.parent().after($clip).after($icon);
            } else {
                $this.parent().after($icon);
            }

            // Crear evento para mostrar clave generada/introducida
            $icon.on("mouseover", function () {
                $icon.attr("title", $this.val());
            });
        });
    };

    /**
     * Inicializar el selector de fecha
     * @param $container
     */
    var setupDatePicker = function ($container) {
        log.info("setupDatePicker");

        var datePickerOpts = {
            format: "YYYY-MM-DD",
            lang: Common.config().LOCALE.substr(0, 2),
            time: false,
            cancelText: Common.config().LANG[44],
            okText: Common.config().LANG[43],
            clearText: Common.config().LANG[30],
            nowText: Common.config().LANG[56],
            minDate: new Date(),
            triggerEvent: "dateIconClick"
        };

        var getUnixtime = function (val) {
            return moment.tz(val, Common.config().TIMEZONE).format("X");
        };

        // Actualizar el input oculto con la fecha en formato UNIX
        var updateUnixInput = function ($obj, date) {
            var unixtime;

            if (date !== undefined) {
                unixtime = date;
            } else {
                unixtime = getUnixtime($obj.val());
            }

            $obj.parent().find("input[name='passworddatechange_unix']").val(unixtime);
        };

        $container.find(".password-datefield__input").each(function () {
            var $this = $(this);

            $this.bootstrapMaterialDatePicker(datePickerOpts);

            $this.parent().append("<input type='hidden' name='passworddatechange_unix' value='" + getUnixtime($this.val()) + "' />");

            // Evento de click para el icono de calendario
            $this.parent().next("i").on("click", function () {
                $this.trigger("dateIconClick");
            });

            // Actualizar el campo oculto cuando cambie la fecha
            $this.on("change", function () {
                updateUnixInput($this);
            });
        });
    };

    /**
     * Triggers que se ejecutan en determinadas vistas
     */
    var viewsTriggers = {
        main: function () {
            var layout = document.querySelector(".mdl-layout");
            var $drawer = $(".mdl-layout__drawer");

            $drawer.find("a").click(function () {
                layout.MaterialLayout.toggleDrawer();
            });
        },
        search: function () {
            var $frmSearch = $("#frmSearch");
            var $resContent = $("#res-content");

            $frmSearch.find("button.btn-clear").on("click", function (e) {
                $(".icon-searchfav").find("i").removeClass("mdl-color-text--amber-A200");
            });

            $frmSearch.find(".icon-searchfav").on("click", function () {
                var $icon = $(this).find("i");
                var $searchfav = $frmSearch.find("input[name='searchfav']");

                if ($searchfav.val() == 0) {
                    $icon.addClass("mdl-color-text--amber-A200");
                    $icon.attr("title", Common.config().LANG[53]);

                    $searchfav.val(1);
                } else {
                    $icon.removeClass("mdl-color-text--amber-A200");
                    $icon.attr("title", Common.config().LANG[52]);

                    $searchfav.val(0);
                }

                $frmSearch.submit();
            });

            var checkFavorite = function ($obj) {
                if ($obj.data("status") === "on") {
                    $obj.addClass("mdl-color-text--amber-A100");
                    $obj.attr("title", Common.config().LANG[50]);
                    $obj.html("star");
                } else {
                    $obj.removeClass("mdl-color-text--amber-A100");
                    $obj.attr("title", Common.config().LANG[49]);
                    $obj.html("star_border");
                }
            };

            var $tagsSelect = $frmSearch.find("#tags")[0];
            var $tagsBar = $frmSearch.find(".search-filters-tags");
            var $showFilter = $frmSearch.find("i.show-filter");

            $resContent.on("click", "#data-search-header .sort-down,#data-search-header .sort-up", function () {
                var $this = $(this);
                $this.parent().find("a").addClass("filterOn");

                Common.appActions().account.sort($this);
            }).on("click", "#search-rows i.icon-favorite", function () {
                var $this = $(this);

                Common.appActions().account.savefavorite($this, function () {
                    checkFavorite($this);
                });
            }).on("click", "#search-rows span.tag", function () {
                if ($tagsBar.is(":hidden")) {
                    $showFilter.trigger("click");
                }

                $tagsSelect.selectize.addItem($(this).data("tag-id"));
            });

            $showFilter.on("click", function () {
                var $this = $(this);

                if ($tagsBar.is(":hidden")) {
                    $tagsBar.slideDown("slow");
                    $this.html($this.data("icon-up"));
                } else {
                    $tagsBar.slideUp("slow");
                    $this.html($this.data("icon-down"));
                }
            });

            if ($tagsSelect.selectedIndex > 0) {
                $showFilter.trigger("click");
            }
        },
        common: function ($container) {
            passwordDetect($container);
            setupDatePicker($container);
        }
    };

    /**
     * Función para crear el menu estático al hacer scroll
     */
    var setFixedMenu = function () {
        // Stick the #nav to the top of the window
        var $actionBar = $("#actions-bar");

        if ($actionBar.length > 0) {
            var $actionBarLogo = $actionBar.find("#actions-bar-logo");
            var isFixed = false;

            var scroll = {
                on: function () {
                    $actionBar.css({
                        backgroundColor: "rgba(255, 255, 255, .75)",
                        borderBottom: "1px solid #ccc"
                    });
                    $actionBarLogo.show();
                    isFixed = true;
                },
                off: function () {
                    $actionBar.css({
                        backgroundColor: "transparent",
                        borderBottom: "none"
                    });
                    $actionBarLogo.hide();
                    isFixed = false;
                }
            };


            $(window).on("scroll", function () {
                var scrollTop = $(this).scrollTop();
                var shouldBeFixed = scrollTop > $actionBar.height();

                if (shouldBeFixed && !isFixed) {
                    scroll.on();
                } else if (!shouldBeFixed && isFixed) {
                    scroll.off();
                }
            }).on("resize", function () {
                // Detectar si al cargar la barra de iconos no está en la posición 0
                if ($actionBar.offset().top > 0) {
                    scroll.on();
                }
            });

            // Detectar si al cargar la barra de iconos no está en la posición 0
            if ($actionBar.offset().top > 0) {
                scroll.on();
            }
        }
    };

    /**
     * Elementos HTML del tema
     *
     * @type {{getList: html.getList}}
     */
    var html = {
        getList: function (items) {
            var $ul = $("<ul class=\"ldap-list-item mdl-list\"></ul>");
            var $li = $("<li class=\"mdl-list__item\"></li>");
            var $span = $("<span class=\"mdl-list__item-primary-content\"></span>");
            var icon = "<i class=\"material-icons mdl-list__item-icon\">person</i>";

            items.forEach(function (value) {
                var $spanClone = $span.clone();
                $spanClone.append(icon);
                $spanClone.append(value);

                var $item = $li.clone().append($spanClone);
                $ul.append($item);
            });

            return $ul;
        },
        tabs: {
            add: function (header, index, title, isActive) {
                var $header = $(header);
                var active = "";

                if (isActive === 1) {
                    $header.parent().find("#tabs-" + index).addClass("is-active");
                    active = "is-active";
                }

                var tab = "<a href=\"#tabs-" + index + "\" class=\"mdl-tabs__tab " + active + "\">" + title + "</a>";

                $header.append(tab);
            }
        }
    };

    /**
     * Inicialización
     */
    var init = function () {
    };

    init();

    return {
        passwordDetect: passwordDetect,
        password: password,
        viewsTriggers: viewsTriggers,
        loading: loading,
        ajax: ajax,
        html: html
    };
};