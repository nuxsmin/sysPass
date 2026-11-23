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
            delete $target[0].dataset.encryptedPass;
            $target.removeAttr("data-length").val(password);
            $dstParent
                .addClass(mdl.CssClasses_.IS_DIRTY)
                .removeClass(mdl.CssClasses_.IS_INVALID);

            // Poner la clave en el input de repetición y encriptarla
            if ($targetR.length > 0) {
                if ($targetR[0]) {
                    $targetR[0].dataset.pass = password;
                    if (typeof $targetR[0].setCustomValidity === "function") {
                        $targetR[0].setCustomValidity("");
                    }
                }
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
    const getPasswordLabels = function () {
        const lang = (typeof sysPassApp !== "undefined" && sysPassApp.config && sysPassApp.config.LANG) || {};
        return {
            show: lang[32] || "Show Password",
            hide: lang[76] || "Hide Password",
            copy: lang[34] || "Copy Password"
        };
    };

    const bindPasswordToggle = function ($input, $toggleBtn) {
        const labels = getPasswordLabels();
        const inputEl = $input[0];

        const hidePassword = function () {
            if ($input.attr("type") === "password") {
                return;
            }

            const plainVal = $input.val();
            const prevPass = inputEl.dataset.pass;
            const encryptedPass = inputEl.dataset.encryptedPass;

            inputEl.dataset.pass = plainVal;
            $input.attr("type", "password");

            if (encryptedPass !== undefined && plainVal === prevPass) {
                $input.val(encryptedPass);
                $input.attr("data-length", encryptedPass.length);
            } else {
                delete inputEl.dataset.encryptedPass;
                $input.removeAttr("data-length");

                if (plainVal !== ""
                    && typeof sysPassApp !== "undefined"
                    && sysPassApp.config
                    && sysPassApp.config.PKI
                    && sysPassApp.config.PKI.AVAILABLE
                    && !$input.hasClass("passwordfield__no-pki")
                    && typeof sysPassApp.encryptFormValue === "function"
                ) {
                    try {
                        sysPassApp.encryptFormValue($input);
                    } catch (err) {
                        log.error(err);
                    }
                }
            }

            $toggleBtn
                .attr("aria-pressed", "false")
                .attr("aria-label", labels.show)
                .attr("title", $toggleBtn.attr("data-level-msg") || labels.show);
            $toggleBtn.find("i.material-icons").text("visibility");
        };

        $toggleBtn.on("click", function () {
            if ($input.attr("type") === "password") {
                const val = $input.val();
                const encryptedLen = parseInt($input.attr("data-length"), 10);
                const isEncrypted = !Number.isNaN(encryptedLen) && encryptedLen === val.length;

                if (isEncrypted && inputEl.dataset.pass !== undefined) {
                    inputEl.dataset.encryptedPass = val;
                    $input.val(inputEl.dataset.pass);
                } else if (inputEl.dataset.pass === undefined) {
                    inputEl.dataset.pass = val;
                }

                $input.attr("type", "text");
                $toggleBtn
                    .attr("aria-pressed", "true")
                    .attr("aria-label", labels.hide)
                    .attr("title", labels.hide);
                $toggleBtn.find("i.material-icons").text("visibility_off");
            } else {
                hidePassword();
            }
        });

        const $form = $input.closest("form");
        if ($form.length > 0) {
            $form.on("submit.spPasswordToggle", function () {
                hidePassword();
            });
        }
    };

    const passwordDetect = function ($container) {
        const labels = getPasswordLabels();

        // Crear los iconos de acciones sobre claves
        $container.find(".passwordfield__input").each(function () {
            const $this = $(this);

            if ($this.attr("data-pass-upgraded") === "true") {
                return;
            }

            const uniqueId = sysPassApp.util.uniqueId();
            const $thisParent = $this.parent();
            const $form = $this.closest("form");
            const originalId = $this.attr("id");
            const targetId = originalId + "-" + uniqueId;
            const originalRepeatId = originalId + "_repeat";
            const targetRepeatId = targetId + "_repeat";

            const $passwordRepeat = $form.find("#" + originalRepeatId);
            $passwordRepeat.attr("id", targetRepeatId);
            $form.find("label[for='" + originalRepeatId + "']").attr("for", targetRepeatId);

            $this.attr("id", targetId);
            $form.find("label[for='" + originalId + "']").attr("for", targetId);
            $this.attr("data-pass", $this.val());

            const optionsLabel = sysPassApp.config.LANG[27] || "Password options";
            let btnMenu =
                `<button id="menu-password-${targetId}" class="mdl-button mdl-js-button mdl-button--icon" type="button" aria-label="${optionsLabel}" title="${optionsLabel}"><i class="material-icons" aria-hidden="true">more_vert</i></button>
                <ul class="mdl-menu mdl-js-menu" for="menu-password-${targetId}">
                <li class="mdl-menu__item passGen"><i class="material-icons">settings</i>${sysPassApp.config.LANG[28]}</li>
                <li class="mdl-menu__item passComplexity"><i class="material-icons">vpn_key</i>${sysPassApp.config.LANG[29]}</li>
                <li class="mdl-menu__item reset"><i class="material-icons">refresh</i>${sysPassApp.config.LANG[30]}</li></ul>`;

            $thisParent.after(`<div class="password-actions" />`);

            $thisParent.next(".password-actions")
                .prepend(`<button type="button" id="password-level-${targetId}" class="sp-password-toggle showpass clip-pass-field password-level" data-clipboard-target="${targetId}" data-level-msg="" aria-label="${labels.show}" aria-pressed="false" title="${labels.show}"><i class="material-icons" aria-hidden="true">visibility</i></button>`)
                .prepend(btnMenu);

            const getPlainPassword = function ($field) {
                if (!$field || $field.length === 0) {
                    return "";
                }
                const el = $field[0];
                const rawVal = $field.val() || "";
                if (el && el.dataset && el.dataset.pass !== undefined && el.dataset.pass !== "") {
                    return el.dataset.pass;
                }
                const encryptedLen = parseInt($field.attr("data-length"), 10);
                const isEncrypted = !Number.isNaN(encryptedLen) && encryptedLen === rawVal.length;
                if (isEncrypted && el && el.dataset && el.dataset.pass !== undefined) {
                    return el.dataset.pass;
                }
                return rawVal;
            };

            const syncPasswordRepeatState = function (forceCheck) {
                if ($passwordRepeat.length === 0) {
                    return;
                }
                const $repeatWrap = $passwordRepeat.parent();
                const $errorNode = $form.find(".sp-password-match-error");
                const primaryPlain = getPlainPassword($this);
                const repeatPlain = getPlainPassword($passwordRepeat);
                const mismatchMsg = $passwordRepeat.attr("data-mismatch-msg") || "Passwords do not match";

                if (repeatPlain === "" && !forceCheck) {
                    $repeatWrap.removeClass("is-invalid");
                    $errorNode.text("").attr("hidden", "hidden");
                    return;
                }

                if (repeatPlain !== "" && primaryPlain !== "" && repeatPlain !== primaryPlain) {
                    $repeatWrap.addClass("is-invalid");
                    $errorNode.text(mismatchMsg).removeAttr("hidden");
                } else {
                    if (repeatPlain !== "") {
                        $repeatWrap.removeClass("is-invalid");
                    }
                    $errorNode.text("").attr("hidden", "hidden");
                }
            };

            $this.on("keyup input", function () {
                if (sysPassApp.util.password && typeof sysPassApp.util.password.checkLevel === "function") {
                    sysPassApp.util.password.checkLevel($this);
                }

                const rawVal = $this.val() || "";
                const encryptedLen = parseInt($this.attr("data-length"), 10);
                if (Number.isNaN(encryptedLen) || encryptedLen !== rawVal.length) {
                    this.dataset.pass = rawVal;
                    delete this.dataset.encryptedPass;
                }
                syncPasswordRepeatState(false);
            });

            if ($passwordRepeat.length > 0) {
                $passwordRepeat.on("keyup input", function () {
                    const rawVal = $passwordRepeat.val() || "";
                    const encryptedLen = parseInt($passwordRepeat.attr("data-length"), 10);
                    if (Number.isNaN(encryptedLen) || encryptedLen !== rawVal.length) {
                        this.dataset.pass = rawVal;
                        delete this.dataset.encryptedPass;
                    }
                    syncPasswordRepeatState(false);
                });
                $passwordRepeat.on("blur", function () {
                    const rawVal = $passwordRepeat.val() || "";
                    const encryptedLen = parseInt($passwordRepeat.attr("data-length"), 10);
                    if (this.dataset.pass === undefined && (Number.isNaN(encryptedLen) || encryptedLen !== rawVal.length)) {
                        this.dataset.pass = rawVal;
                    }
                    syncPasswordRepeatState(false);
                });
            }

            const $passwordActions = $this.parent().next();
            const $toggleBtn = $passwordActions.find(".showpass");

            bindPasswordToggle($this, $toggleBtn);

            // Crear evento para generar clave aleatoria
            $passwordActions
                .find(".passGen")
                .on("click", function () {
                    randomPassword($this);
                    $form.find(".sp-password-match-error").text("").attr("hidden", "hidden");

                    $this.blur();
                });

            $passwordActions
                .find(".passComplexity")
                .on("click", function () {
                    complexityDialog();
                });

            // Mostrar nivel de fortaleza en tooltip sin sobrescribir aria-label
            $toggleBtn.on("mouseover", function () {
                const isPressed = $(this).attr("aria-pressed") === "true";
                const stateLabel = isPressed ? labels.hide : labels.show;

                if (this.dataset.levelMsg) {
                    $(this).attr("title", this.dataset.levelMsg + " (" + stateLabel + ")");
                } else {
                    $(this).attr("title", stateLabel);
                }
            });

            // Reset de los campos de clave
            $passwordActions
                .find(".reset")
                .on("click", function () {
                    $this.val("").removeAttr("data-length");
                    $this[0].dataset.pass = "";
                    delete $this[0].dataset.encryptedPass;
                    $this.parent().removeClass("is-dirty is-invalid is-touched");

                    if ($passwordRepeat.length > 0) {
                        $passwordRepeat.val("").removeAttr("data-length");
                        if ($passwordRepeat[0]) {
                            $passwordRepeat[0].dataset.pass = "";
                            if (typeof $passwordRepeat[0].setCustomValidity === "function") {
                                $passwordRepeat[0].setCustomValidity("");
                            }
                        }
                        $passwordRepeat.parent().removeClass("is-dirty is-invalid is-touched");
                    }
                    $form.find(".sp-password-match-error").text("").attr("hidden", "hidden");

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

            if ($this.attr("data-pass-upgraded") === "true") {
                return;
            }

            $this.attr("data-pass", $this.val());

            const $toggleBtn = $(`<button type="button" class="sp-password-toggle showpass" aria-label="${labels.show}" aria-pressed="false" title="${labels.show}"><i class="material-icons" aria-hidden="true">visibility</i></button>`);

            if ($this.data("clipboard") === 1) {
                const $clip = $(`<button type="button" class="sp-password-toggle clip-pass-icon" aria-label="${labels.copy}" title="${labels.copy}" data-clipboard-target="#${$this.attr("id")}"><i class="material-icons" aria-hidden="true">content_paste</i></button>`);
                $this.parent().after($clip).after($toggleBtn);
            } else {
                $this.parent().after($toggleBtn);
            }

            $this.on("keyup", function () {
                this.dataset.pass = $this.val();
                delete this.dataset.encryptedPass;
            });

            bindPasswordToggle($this, $toggleBtn);
            $this.attr("data-pass-upgraded", "true");
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
                $(".icon-searchfav").find("i").removeClass("sp-color-text--warning");
            });

            $frmSearch.find(".icon-searchfav").on("click", function () {
                const $icon = $(this).find("i");
                const $searchfav = $frmSearch.find("input[name='searchfav']");

                if ($searchfav.val() == 0) {
                    $icon.addClass("sp-color-text--warning");
                    $icon.attr("title", sysPassApp.config.LANG[53]);

                    $searchfav.val(1);
                } else {
                    $icon.removeClass("sp-color-text--warning");
                    $icon.attr("title", sysPassApp.config.LANG[52]);

                    $searchfav.val(0);
                }

                $frmSearch.submit();
            });

            const checkFavorite = function ($obj) {
                if ($obj.data("status") === "on") {
                    $obj.addClass("sp-color-text--warning");
                    $obj.attr("title", sysPassApp.config.LANG[50]);
                    $obj.html("star");
                } else {
                    $obj.removeClass("sp-color-text--warning");
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
                    $actionBar.addClass("is-fixed");
                    $actionBarLogo.show();
                    isFixed = true;
                },
                off: function () {
                    $actionBar.removeClass("is-fixed");
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
        html: html,
        setFixedMenu: setFixedMenu
    };
};