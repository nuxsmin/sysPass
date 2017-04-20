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

                options.onChange = function (value) {
                    if (value > 0) {
                        if (onchange.length === 2) {
                            sysPassApp.actions()[onchange[0]][onchange[1]]($this);
                        } else {
                            sysPassApp.actions()[onchange[0]]($this);
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
            createFilter: new RegExp("^[a-z0-9:\._-]+$", "i"),
            plugins: ["remove_button"]
        });
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
        log.info("formAction");

        var lastHash = $obj.attr("data-hash");
        var currentHash = SparkMD5.hash($obj.serialize(), false);

        if (lastHash === currentHash) {
            Common.msg.ok(Common.config().LANG[55]);
            return false;
        }

        var plugin = $obj.data("plugin");
        var actions;

        if (typeof plugin !== "undefined") {
            actions = sysPass.Plugin[plugin](Common);
        } else {
            actions = Common.appActions();
        }

        var onsubmit = $obj.data("onsubmit").split("/");

        $obj.find("input[name='sk']").val(Common.sk.get());

        if (onsubmit.length === 2) {
            actions[onsubmit[0]][onsubmit[1]]($obj);
        } else {
            actions[onsubmit[0]]($obj);
        }
    };

    var bodyHooks = function () {
        log.info("bodyHooks");

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
            var helpText = $("#" + $this.data("help")).html();

            mdlDialog().show({
                title: Common.config().LANG[54],
                text: helpText,
                positive: {
                    title: Common.config().LANG[43]
                }
            });
        }).on("reset", ".form-action", function (e) {
            e.preventDefault();

            log.info("reset");

            var $this = $(this);

            $this.find("input:text, input:password, input:file, textarea").val("").parent("div").removeClass("is-dirty");
            $this.find("input:radio, input:checkbox").prop("checked", false).prop("selected", false);
            $this.find("input[name='start'], input[name='skey'], input[name='sorder']").val(0);

            $this.find("select").each(function () {
                $(this)[0].selectize.clear(true);
            });

            $this.submit();
        }).on("click", ".btn-popup-close", function (e) {
            $.magnificPopup.close();
        });
    };

    /**
     * Triggers que se ejecutan en determinadas vistas
     */
    var views = {
        main: function () {
            log.info("views:main");

            if (!clipboard.isSupported()) {
                Common.msg.info(Common.config().LANG[65]);
            }

            $(".btn-menu").click(function () {
                var $this = $(this);

                if ($this.attr("data-history-reset") === "1") {
                    Common.appRequests().history.reset();
                }

                Common.appActions().doAction({actionId: $this.data("action-id")}, $this.data("view"));
            });

            $("#btnLogout").click(function (e) {
                Common.appActions().main.logout();
            });

            $("#btnPrefs").click(function (e) {
                Common.appActions().doAction({actionId: $(this).data("action-id")});
            });

            Common.appActions().doAction({actionId: 1}, "search");

            if (typeof Common.appTheme().viewsTriggers.main === "function") {
                Common.appTheme().viewsTriggers.main();
            }
        },
        search: function () {
            log.info("views:search");

            var $frmSearch = $("#frmSearch");

            if ($frmSearch.length === 0) {
                return;
            }

            $frmSearch.find("input[name='search']").on('keyup', function (e) {
                e.preventDefault();

                if (e.which === 13 || e.keyCode === 13) {
                    $frmSearch.submit();
                }
            });

            $frmSearch.find("select, #rpp").on("change", function () {
                $frmSearch.submit();
            });

            $frmSearch.find("button.btn-clear").on("click", function (e) {
                e.preventDefault();

                $frmSearch.find("input[name=\"searchfav\"]").val(0);

                $frmSearch[0].reset();
            });

            $frmSearch.find("input:text:visible:first").focus();

            $("#globalSearch").click(function () {
                    var val = $(this).prop("checked") == true ? 1 : 0;

                    $frmSearch.find("input[name='gsearch']").val(val);
                    $frmSearch.submit();
                }
            );

            if (typeof Common.appTheme().viewsTriggers.search === "function") {
                Common.appTheme().viewsTriggers.search();
            }
        },
        login: function () {
            log.info("views:login");
        },
        passreset: function () {
            log.info("views:passreset");

            var $form = $("#frmPassReset");

            Common.appTheme().passwordDetect($form);
        },
        footer: function () {
            log.info("views:footer");

        },
        common: function ($container) {
            log.info("views:common");

            selectDetect($container);

            // $container.find(".help-box").dialog({
            //     autoOpen: false,
            //     title: Common.config().LANG[54],
            //     width: screen.width / 2.5
            // });

            if (typeof Common.appTheme().viewsTriggers.common === "function") {
                Common.appTheme().viewsTriggers.common($container);
            }

            Common.appTriggers().updateFormHash($container);
        },
        datatabs: function (active) {
            log.info("views:datatabs");

            $(".datagrid-action-search>form").each(function () {
                var $this = $(this);

                $this.find("button.btn-clear").on("click", function (e) {
                    e.preventDefault();

                    $this.trigger("reset");
                });
            });
        },
        config: function () {
            log.info("views:config");

            var $dropFiles = $("#drop-import-files");

            if ($dropFiles.length > 0) {
                var upload = Common.fileUpload($dropFiles);

                upload.url = Common.appActions().ajaxUrl.config.import;
                upload.beforeSendAction = function () {
                    upload.setRequestData({
                        sk: Common.sk.get(),
                        csvDelimiter: $("#csvDelimiter").val(),
                        importPwd: $("#importPwd").val(),
                        importMasterPwd: $("#importMasterPwd").val(),
                        import_defaultuser: $("#import_defaultuser").val(),
                        import_defaultgroup: $("#import_defaultgroup").val()
                    });
                };
            }
        },
        account: function () {
            log.info("views:account");

            var $listFiles = $("#list-account-files");

            if ($listFiles.length > 0) {
                Common.appActions().account.getfiles($listFiles);
            }

            var $dropFiles = $("#drop-account-files");

            if ($dropFiles.length > 0) {
                var upload = Common.fileUpload($dropFiles);

                upload.url = Common.appActions().ajaxUrl.file;
                upload.requestDoneAction = function () {
                    Common.appActions().account.getfiles($listFiles);
                };
            }

            var $extraInfo = $(".show-extra-info");

            if ($extraInfo.length > 0) {
                $extraInfo.on("click", function () {
                    var $this = $(this);
                    var $target = $($this.data("target"));

                    if ($target.is(":hidden")) {
                        $target.slideDown("slow");
                        $this.html($this.data("icon-up"));
                    } else {
                        $target.slideUp("slow");
                        $this.html($this.data("icon-down"));
                    }
                });
            }

            var $selParentAccount = $("#selParentAccount");

            if ($selParentAccount.length > 0) {
                $selParentAccount.on("change", function () {
                    var $this = $(this);
                    var $pass = $("#accountpass,#accountpassR");

                    if ($this[0].value > 0) {
                        $pass.each(function () {
                            $(this).prop("disabled", "true");
                            $(this).prop("required", "false");
                        });
                    } else {
                        $pass.each(function () {
                            $(this).prop("disabled", "");
                            $(this).prop("required", "true");
                        });
                    }
                });

                Common.appActions().items.get($selParentAccount);
            }
        },
        install: function () {
            log.info("views:install");

            var $form = $("#frmInstall");

            Common.appTheme().passwordDetect($form);
            selectDetect($form);
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

    /**
     * Actualizar el hash de los formularios de acción
     */
    var updateFormHash = function ($container) {
        log.info("updateFormHash");

        var $form;

        if ($container !== undefined) {
            $form = $container.find(".form-action[data-hash]");
        } else {
            $form = $(".form-action[data-hash]");
        }

        if ($form.length > 0) {
            $form.each(function () {
                var $this = $(this);

                $this.attr("data-hash", SparkMD5.hash($this.serialize(), false));
            });
        }
    };

    return {
        views: views,
        selectDetect: selectDetect,
        updateSk: updateSk,
        updateFormHash: updateFormHash,
        bodyHooks: bodyHooks
    };
};