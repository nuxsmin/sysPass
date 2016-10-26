/*
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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

sysPass.Triggers = function (Common) {
    "use strict";

    var log = Common.log;

    // Detectar los campos select y añadir funciones
    var selectDetect = function ($container) {
        var options = {
            valueField: "id",
            labelField: "name",
            searchField: ["name"]
        };

        $container.find(".select-box").each(function (e) {
            var $this = $(this);

            options.plugins = $this.hasClass("select-box-deselect") ? {"clear_selection": {title: Common.config().LANG[51]}} : {};

            if ($this.data("onchange")) {
                var onchange = $this.data("onchange").split("/");
                var actionId = $this.data("action-id");

                options.onChange = function (value) {
                    var data = {"action-id": actionId, "item-id": value};

                    if (value > 0) {
                        if (onchange.length === 2) {
                            sysPassApp.actions()[onchange[0]][onchange[1]](data);
                        } else {
                            sysPassApp.actions()[onchange[0]](data);
                        }
                    }
                };
            }

            $this.selectize(options);
        });

        $container.find("#allowed_exts").selectize({
            create: function (input) {
                return {
                    value: input.toUpperCase(),
                    text: input.toUpperCase()
                };
            },
            createFilter: new RegExp("^[a-z0-9]{1,4}$", "i"),
            plugins: ["remove_button"]
        });

        $container.find("#wikifilter").selectize({
            create: true,
            createFilter: new RegExp("^[a-z0-9\._-]+$", "i"),
            plugins: ["remove_button"]
        });
    };

    /**
     * Función para crear el menu estático al hacer scroll
     */
    var setFixedMenu = function () {
        // Stick the #nav to the top of the window
        var nav = $("#actionsBar");
        var logo = $("#actionsBar #actionsBar-logo img");
        var isFixed = false;
        var navCssProps = {
            position: "fixed",
            top: 0,
            left: nav.offset().left,
            width: nav.width(),
            padding: "1em 0",
            backgroundColor: "rgba(255, 255, 255, .75)",
            borderBottom: "1px solid #ccc"
        };

        $(window).scroll(function () {
            var scrollTop = $(this).scrollTop();
            var shouldBeFixed = scrollTop > nav.height();
            if (shouldBeFixed && !isFixed) {
                nav.css(navCssProps);
                logo.show().css({opacity: 0.75});
                isFixed = true;
            } else if (!shouldBeFixed && isFixed) {
                nav.css({
                    backgroundColor: "transparent",
                    border: "0"
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
    };

    /**
     * Ejecutar acción para botones
     * @param $obj
     */
    var btnAction = function ($obj) {
        var onclick = $obj.data("onclick").split("/");
        var actions = Common.appActions();

        if (onclick.length === 2) {
            actions[onclick[0]][onclick[1]]($obj);
        } else {
            actions[onclick[0]]($obj);
        }
    };

    /**
     * Ejecutar acción para formularios
     *
     * @param $obj
     */
    var formAction = function ($obj) {
        var onsubmit = $obj.data("onsubmit").split("/");
        var actions = Common.appActions();

        var lastHash = $obj.attr("data-hash");
        var currentHash = SparkMD5.hash($obj.serialize(), false);

        if (lastHash === currentHash) {
            Common.resMsg("ok", Common.config().LANG[55]);
            return false;
        }

        $obj.find("input[name='sk']").val(Common.sk.get());

        if (onsubmit.length === 2) {
            actions[onsubmit[0]][onsubmit[1]]($obj);
        } else {
            actions[onsubmit[0]]($obj);
        }
    };

    /**
     * Triggers que se ejecutan en determinadas vistas
     */
    var views = {
        main: function () {
            $(".btn-menu").click(function () {
                var $this = $(this);

                if ($this.attr("data-history-reset") === "1") {
                    Common.appRequests().history.reset();
                }

                Common.appActions().doAction({actionId: $(this).data("action-id")});
            });

            $("body").on("click", ".btn-action[data-onclick],.btn-action-pager[data-onclick]", function () {
                btnAction($(this));
            }).on("click", ".btn-back", function () {
                var appRequests = Common.appRequests();

                if (appRequests.history.length() > 0) {
                    log.info("back");

                    var lastHistory = appRequests.history.del();

                    appRequests.getActionCall(lastHistory, lastHistory.callback);
                }
            }).on("submit", ".form-action", function (e) {
                e.preventDefault();

                formAction($(this));
            }).on("click", ".btn-help", function () {
                var $this = $(this);

                $("#" + $this.data("help")).dialog("open");
            });

            setFixedMenu();

            Common.appActions().doAction({actionId: 1});
        },
        search: function () {
            var $frmSearch = $("#frmSearch");

            $frmSearch.on("submit", function (e) {
                e.preventDefault();

                Common.appActions().account.search();
            });

            $frmSearch.find("select, #rpp").on("change", function () {
                Common.appActions().account.search();
            });

            $frmSearch.find("#btnClear").click(function () {
                Common.appActions().account.search(true);
            });

            $frmSearch.find("input:text:visible:first").focus();

            $("#chkgsearch").click(
                function () {
                    var val = $(this).prop("checked") == true ? 1 : 0;

                    $frmSearch.find("input[name='gsearch']").val(val);

                    Common.appActions().account.search();
                }
            );

            if (typeof Common.appTheme().viewsTriggers.search === "function") {
                Common.appTheme().viewsTriggers.search();
            }
        },
        login: function () {
            $("#frmLogin").on("submit", function (e) {
                e.preventDefault();

                formAction($(this));
            });

            $("#boxLogout").fadeOut(1500, function () {
                location.href = Common.config().APP_ROOT + "/index.php";
            });
        },
        footer: function () {
            $("#btnLogout").click(function (e) {
                Common.appActions().main.logout();
            });

            $("#btnPrefs").click(function (e) {
                Common.appActions().doAction({actionId: $(this).data("action-id")});
            });

            $("#btnUserPass").click(function (e) {
                Common.appActions().appMgmt.userpass($(this));
            });
        },
        common: function (container) {
            var $container = $(container);

            selectDetect($container);

            $container.find(".help-box").dialog({
                autoOpen: false,
                title: Common.config().LANG[54],
                width: screen.width / 2.5
            });

            if (typeof Common.appTheme().viewsTriggers.common === "function") {
                Common.appTheme().viewsTriggers.common($container);
            }
        },
        datatabs: function (active) {
            $("#tabs").tabs({
                active: active
            });

            $(".datagrid-action-search>form").each(function () {
                var $this = $(this);

                $this.find("i.btn-clear").on("click", function () {
                    $this.trigger("reset").submit();
                });
            });
        },
        config: function () {
            var $dropFiles = $("#drop-import-files");

            if ($dropFiles.length > 0) {
                var upload = Common.fileUpload($dropFiles);

                upload.url = "/ajax/ajax_import.php";
                upload.beforeSendAction = function () {
                    upload.requestData({
                        sk: Common.sk.get(),
                        csvDelimiter: $("#csvDelimiter").val(),
                        importPwd: $("#importPwd").val(),
                        import_defaultuser: $("#import_defaultuser").val(),
                        import_defaultgroup: $("#import_defaultgroup").val()
                    });
                };
            }

            var $form = $(".form-action");

            if ($form.length > 0) {
                $form.each(function () {
                    var $this = $(this);
                    if (typeof $this.attr("data-hash") !== "undefined") {
                        $this.attr("data-hash", SparkMD5.hash($this.serialize(), false));
                    }
                });
            }
        },
        account: function () {
            var $listFiles = $("#list-account-files");

            if ($listFiles.length > 0) {
                Common.appActions().account.getfiles($listFiles);
            }

            var $dropFiles = $("#drop-account-files");

            if ($dropFiles.length > 0) {
                var upload = Common.fileUpload($dropFiles);

                upload.url = "/ajax/ajax_files.php";
                upload.requestDoneAction = function () {
                    Common.appActions().account.getfiles($listFiles);
                };
            }

            var $form = $(".form-action");

            if ($form.length > 0) {
                $form.attr("data-hash", SparkMD5.hash($form.serialize(), false));
            }
        }
    };

    /**
     * Actualizar el token de seguridad en los atributos de los botones y formularios
     *
     */
    var updateSk = function () {
        $("#content").find("[data-sk]").each(function () {
            log.info("updateSk");

            $(this).data("sk", Common.sk.get());
        });
    };

    return {
        views: views,
        selectDetect: selectDetect,
        updateSk: updateSk
    };
};