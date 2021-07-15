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

sysPass.Theme = function (log) {
    "use strict";

    const updateEvent = $.Event("theme:update");

    /**
     * Funciones a realizar en peticiones AJAX
     *
     * @type {{complete: ajax.complete}}
     */
    const ajax = {
        complete: function () {
            log.info("ajax:complete");

            update();
        }
    };

    /**
     * Mostrar/Ocultar el spinner de carga
     *
     * @type {{show: loading.show, hide: loading.hide}}
     */
    const loading = {
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
    const randomPassword = function ($target) {
        sysPassApp.util.password.random(function (password, level) {
            $target.attr("data-pass", password);

            // if ($target) {
            const $dstParent = $target.parent();
            const $targetR = $("#" + $target.attr("id") + "_repeat");

            sysPassApp.util.password.output(level, $target);

            // Actualizar los componentes de MDL
            const mdl = new MaterialTextfield();

            // Poner la clave en los input y actualizar MDL
            $dstParent.find("input:password").val(password);
            $dstParent
                .addClass(mdl.CssClasses_.IS_DIRTY)
                .removeClass(mdl.CssClasses_.IS_INVALID);

            // Poner la clave en el input de repetición y encriptarla
            if ($targetR.length > 0) {
                $targetR.val(password).parent()
                    .addClass(mdl.CssClasses_.IS_DIRTY)
                    .removeClass(mdl.CssClasses_.IS_INVALID);
                sysPassApp.encryptFormValue($targetR);
            }
        });
    };

    // Diálogo de configuración de complejidad de clave
    const complexityDialog = function () {

        const content =
            `<div id="box-complexity">
                <div>
                    <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-chars">
                        <input type="checkbox" id="checkbox-chars" class="mdl-checkbox__input" name="checkbox-chars" checked/>
                        <span class="mdl-checkbox__label">${sysPassApp.config.LANG[63]}</span>
                    </label>
                    <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-numbers">
                        <input type="checkbox" id="checkbox-numbers" class="mdl-checkbox__input" name="checkbox-numbers" checked/>
                        <span class="mdl-checkbox__label">${sysPassApp.config.LANG[35]}</span>
                    </label>
                    <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-uppercase">
                        <input type="checkbox" id="checkbox-uppercase" class="mdl-checkbox__input" name="checkbox-uppercase"/>
                        <span class="mdl-checkbox__label">${sysPassApp.config.LANG[36]}</span>
                    </label>
                    <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-symbols">
                        <input type="checkbox" id="checkbox-symbols" class="mdl-checkbox__input" name="checkbox-symbols"/>
                        <span class="mdl-checkbox__label">${sysPassApp.config.LANG[37]}</span>
                    </label>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input class="mdl-textfield__input" type="number" pattern="[0-9]{1,3}" id="passlength" 
                        name="passlength" value="${sysPassApp.util.password.config.complexity.numlength}"
                        min="1" max="117"/>
                        <label class="mdl-textfield__label" for="passlength">${sysPassApp.config.LANG[38]}</label>
                    </div>
                </div>
            </div>
            <datalist id="defaultLength"><option value="8"><option value="12"><option value="16"><option value="32"><option value="64"></datalist>`;

        mdlDialog().show({
            title: sysPassApp.config.LANG[29],
            text: content,
            negative: {
                title: sysPassApp.config.LANG[44]
            },
            positive: {
                title: sysPassApp.config.LANG[43],
                onClick: function (e) {
                    e.preventDefault();

                    sysPassApp.util.password.config.complexity.chars = $("#checkbox-chars").is(":checked");
                    sysPassApp.util.password.config.complexity.numbers = $("#checkbox-numbers").is(":checked");
                    sysPassApp.util.password.config.complexity.uppercase = $("#checkbox-uppercase").is(":checked");
                    sysPassApp.util.password.config.complexity.symbols = $("#checkbox-symbols").is(":checked");
                    sysPassApp.util.password.config.complexity.numlength = parseInt($("#passlength").val());
                }
            },
            cancelable: true,
            contentStyle: {"max-width": "300px"},
            onLoaded: function () {
                $("#checkbox-chars").prop("checked", sysPassApp.util.password.config.complexity.chars);
                $("#checkbox-numbers").prop("checked", sysPassApp.util.password.config.complexity.numbers);
                $("#checkbox-uppercase").prop("checked", sysPassApp.util.password.config.complexity.uppercase);
                $("#checkbox-symbols").prop("checked", sysPassApp.util.password.config.complexity.symbols);
            }
        });
    };

    /**
     * Detectar los campos de clave y añadir funciones
     */
    const passwordDetect = function ($container) {
        // Crear los iconos de acciones sobre claves
        $container.find(".passwordfield__input").each(function () {
            const $this = $(this);

            if ($this.attr("data-pass-upgraded") === "true") {
                return;
            }

            const uniqueId = sysPassApp.util.uniqueId();
            const $thisParent = $this.parent();
            const $form = $this.closest("form");
            const targetId = $this.attr("id") + "-" + uniqueId;

            const $passwordRepeat = $form.find("#" + $this.attr("id") + "_repeat");
            $passwordRepeat.attr("id", targetId + "_repeat");

            $this.attr("id", targetId);
            $this.attr("data-pass", $this.val());

            let btnMenu =
                `<button id="menu-password-${targetId}" class="mdl-button mdl-js-button mdl-button--icon" type="button" title="${sysPassApp.config.LANG[27]}"><i class="material-icons">more_vert</i></button>
                <ul class="mdl-menu mdl-js-menu" for="menu-password-${targetId}">
                <li class="mdl-menu__item passGen"><i class="material-icons">settings</i>${sysPassApp.config.LANG[28]}</li>
                <li class="mdl-menu__item passComplexity"><i class="material-icons">vpn_key</i>${sysPassApp.config.LANG[29]}</li>
                <li class="mdl-menu__item reset"><i class="material-icons">refresh</i>${sysPassApp.config.LANG[30]}</li></ul>`;

            $thisParent.after(`<div class="password-actions" />`);

            $thisParent.next(".password-actions")
                .prepend(`<i id='password-level-${targetId}' class="showpass material-icons clip-pass-field password-level" data-clipboard-target='${targetId}' data-level-msg= '' title="${sysPassApp.config.LANG[32]}">remove_red_eye</i>`)
                .prepend(btnMenu);

            $this.on("keyup", function () {
                sysPassApp.util.password.checkLevel($this);

                this.dataset.pass = $this.val();
            });

            const $passwordActions = $this.parent().next();

            // Crear evento para generar clave aleatoria
            $passwordActions
                .find(".passGen")
                .on("click", function () {
                    randomPassword($this);

                    $this.blur();
                });

            $passwordActions
                .find(".passComplexity")
                .on("click", function () {
                    complexityDialog();
                });

            // Crear evento para mostrar clave generada/introducida
            $passwordActions
                .find(".showpass")
                .on("mouseover", function () {
                    if (this.dataset.levelMsg !== "") {
                        $(this).attr("title", this.dataset.levelMsg + "\n\n" + $this[0].dataset.pass);
                    } else {
                        $(this).attr("title", $this[0].dataset.pass);
                    }
                });

            // Reset de los campos de clave
            $passwordActions
                .find(".reset")
                .on("click", function () {
                    $this.val("");
                    $this[0].dataset.pass = "";

                    if ($passwordRepeat.length > 0) {
                        $passwordRepeat.val("");
                    }

                    // Actualizar objetos de MDL
                    componentHandler.upgradeDom();
                });

            $this.attr("data-pass-upgraded", "true");

            // Actualizar objetos de MDL
            componentHandler.upgradeDom();
        });

        // Crear los iconos de acciones sobre claves (sólo mostrar clave)
        $container.find(".passwordfield__input-show").each(function () {
            const $this = $(this);
            const $icon = $("<i class=\"showpass material-icons\" title=\"" + sysPassApp.config.LANG[32] + "\">remove_red_eye</i>");

            if ($this.data("clipboard") === 1) {
                const $clip = $("<i class=\"clip-pass-icon material-icons\" title=\"" + sysPassApp.config.LANG[34] + "\" data-clipboard-target=\"#" + $this.attr("id") + "\">content_paste</i>");
                $this.parent().after($clip).after($icon);
            } else {
                $this.parent().after($icon);
            }

            // Crear evento para mostrar clave generada/introducida
            $icon.on("mouseover", function () {
                $icon.attr("title", $this[0].dataset.pass);
            });
        });
    };

    /**
     * Inicializar el selector de fecha
     * @param $container
     */
    const setupDatePicker = function ($container) {
        log.info("setupDatePicker");

        const datePickerOpts = {
            format: "YYYY-MM-DD",
            lang: sysPassApp.config.BROWSER.LOCALE.substr(0, 2),
            time: false,
            cancelText: sysPassApp.config.LANG[44],
            okText: sysPassApp.config.LANG[43],
            clearText: sysPassApp.config.LANG[30],
            nowText: sysPassApp.config.LANG[56],
            minDate: new Date(),
            triggerEvent: "dateIconClick"
        };

        const getUnixtime = function (val) {
            return moment.tz(val, sysPassApp.config.BROWSER.TIMEZONE).format("X");
        };

        $container.find(".password-datefield__input").each(function () {
            const $this = $(this);
            const $parent = $this.parent();

            $this.bootstrapMaterialDatePicker(datePickerOpts);

            // Search for an input to set the unix timestamp from a localized date
            const $dstUnix = $parent.find("input[name=" + $this.data('dst-unix') + "]");

            if ($this.val() > 0) {
                $dstUnix.val(getUnixtime($this.val()));
            }

            // Evento de click para el icono de calendario
            $parent.next("i").on("click", function () {
                $this.trigger("dateIconClick");
            });

            // Actualizar el campo oculto cuando cambie la fecha
            $this.on("change", function () {
                $dstUnix.val(getUnixtime($this.val()));
            });
        });
    };

    /**
     * Triggers que se ejecutan en determinadas vistas
     */
    const viewsTriggers = {
        main: function () {
            const layout = document.querySelector(".mdl-layout");
            const $drawer = $(".mdl-layout__drawer");

            $drawer.find("a").click(function () {
                layout.MaterialLayout.toggleDrawer();
            });
        },
        search: function () {
            const $frmSearch = $("#frmSearch");
            const $resContent = $("#res-content");

            $frmSearch.find("button.btn-clear").on("click", function (e) {
                $(".icon-searchfav").find("i").removeClass("mdl-color-text--amber-A200");
            });

            $frmSearch.find(".icon-searchfav").on("click", function () {
                const $icon = $(this).find("i");
                const $searchfav = $frmSearch.find("input[name='searchfav']");

                if ($searchfav.val() == 0) {
                    $icon.addClass("mdl-color-text--amber-A200");
                    $icon.attr("title", sysPassApp.config.LANG[53]);

                    $searchfav.val(1);
                } else {
                    $icon.removeClass("mdl-color-text--amber-A200");
                    $icon.attr("title", sysPassApp.config.LANG[52]);

                    $searchfav.val(0);
                }

                $frmSearch.submit();
            });

            const checkFavorite = function ($obj) {
                if ($obj.data("status") === "on") {
                    $obj.addClass("mdl-color-text--amber-A100");
                    $obj.attr("title", sysPassApp.config.LANG[50]);
                    $obj.html("star");
                } else {
                    $obj.removeClass("mdl-color-text--amber-A100");
                    $obj.attr("title", sysPassApp.config.LANG[49]);
                    $obj.html("star_border");
                }
            };

            const $tagsSelect = $frmSearch.find("#tags")[0];
            const $tagsBar = $frmSearch.find(".search-filters-tags");
            const $showFilter = $frmSearch.find("i.show-filter");

            $resContent.on("click", "#data-search-header .sort-down,#data-search-header .sort-up", function () {
                const $this = $(this);
                $this.parent().find("a").addClass("filterOn");

                sysPassApp.actions.account.sort($this);
            }).on("click", "#search-rows i.icon-favorite", function () {
                const $this = $(this);

                sysPassApp.actions.account.saveFavorite($this, function () {
                    checkFavorite($this);
                });
            }).on("click", "#search-rows span.tag", function () {
                if ($tagsBar.is(":hidden")) {
                    $showFilter.trigger("click");
                }

                $tagsSelect.selectize.addItem($(this).data("tag-id"), false);
            });

            $showFilter.on("click", function () {
                const $this = $(this);

                if ($tagsBar.is(":hidden")) {
                    $tagsBar.slideDown("slow");
                    $this.html($this.data("icon-up"));
                } else {
                    $tagsBar.slideUp("slow");
                    $this.html($this.data("icon-down"));
                }
            });

            if ($tagsSelect.selectedIndex !== -1
                || $showFilter.data('show') === 1
            ) {
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
     */
    const html = {
        getList: function (items, icon) {
            const $ul = $("<ul class=\"ldap-list-item mdl-list\"></ul>");
            const $li = $("<li class=\"mdl-list__item\"></li>");
            const $span = $("<span class=\"mdl-list__item-primary-content\"></span>");

            const i = "<i class=\"material-icons mdl-list__item-icon\">" + (icon === undefined || icon === "" ? "description" : icon) + "</i>";

            items.forEach(function (value) {
                const $spanClone = $span.clone();
                $spanClone.append(i);
                $spanClone.append(value);

                const $item = $li.clone().append($spanClone);
                $ul.append($item);
            });

            return $ul;
        },
        tabs: {
            add: function (header, index, title, isActive) {
                const $header = $(header);
                let active;

                if (isActive === 1) {
                    $header.parent().find("#tabs-" + index).addClass("is-active");
                    active = "is-active";
                }

                const tab = "<a href=\"#tabs-" + index + "\" class=\"mdl-tabs__tab " + active + "\">" + title + "</a>";

                $header.append(tab);
            }
        }
    };

    /**
     * Triggers an update of the theme components
     */
    const update = function () {
        log.info("theme:update");

        // Actualizar componentes de MDL cargados con AJAX
        componentHandler.upgradeDom();

        $("body").trigger(updateEvent);
    };

    /**
     * Initialization
     */
    const init = function () {
    };

    init();

    return {
        passwordDetect: passwordDetect,
        password: randomPassword,
        update: update,
        viewsTriggers: viewsTriggers,
        loading: loading,
        ajax: ajax,
        html: html
    };
};