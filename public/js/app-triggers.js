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

    const log = Common.log;

    // Detectar los campos select y añadir funciones
    const selectDetect = function ($container) {
        const options = {
            valueField: "id",
            labelField: "name",
            searchField: ["name"]
        };

        $container.find(".select-box").each(function (e) {
            const $this = $(this);

            options.plugins = $this.hasClass("select-box-deselect") ? {"clear_selection": {title: Common.config().LANG[51]}} : {};

            if ($this.data("onchange")) {
                const onchange = $this.data("onchange").split("/");

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
            createFilter: new RegExp("^[a-z0-9:._-]+$", "i"),
            plugins: ["remove_button"]
        });
    };

    /**
     * Ejecutar acción para botones
     * @param $obj
     */
    const handleActionButton = function ($obj) {
        log.info("handleActionButton: " + $obj.attr("id"));

        const onclick = $obj.data("onclick").split("/");
        let actions;

        const plugin = $obj.data("plugin");

        if (plugin !== undefined && Common.appPlugins()[plugin] !== undefined) {
            actions = Common.appPlugins()[plugin];
        } else {
            actions = Common.appActions();
        }

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
    const handleFormAction = function ($obj) {
        log.info("formAction");

        const lastHash = $obj.attr("data-hash");
        const currentHash = SparkMD5.hash($obj.serialize(), false);

        if (lastHash === currentHash) {
            Common.msg.ok(Common.config().LANG[55]);
            return false;
        }

        const plugin = $obj.data("plugin");
        let actions;

        if (plugin !== undefined && Common.appPlugins()[plugin] !== undefined) {
            actions = Common.appPlugins()[plugin];
        } else {
            actions = Common.appActions();
        }

        const onsubmit = $obj.data("onsubmit").split("/");

        $obj.find("input[name='sk']").val(Common.sk.get());

        if (onsubmit.length === 2) {
            actions[onsubmit[0]][onsubmit[1]]($obj);
        } else {
            actions[onsubmit[0]]($obj);
        }
    };

    const bodyHooks = function () {
        log.info("bodyHooks");

        $("body").on("click", "button.btn-action[data-onclick][type='button'],li.btn-action[data-onclick],span.btn-action[data-onclick],i.btn-action[data-onclick],.btn-action-pager[data-onclick]", function () {
            handleActionButton($(this));
        }).on("click", ".btn-back", function () {
            const appRequests = Common.appRequests();

            if (appRequests.history.length() > 0) {
                log.info("back");

                const lastHistory = appRequests.history.del();

                appRequests.getActionCall(lastHistory, lastHistory.callback);
            }
        }).on("submit", ".form-action", function (e) {
            e.preventDefault();

            handleFormAction($(this));
        }).on("click", ".btn-help", function () {
            const $this = $(this);
            const helpText = $("#" + $this.data("help")).html();

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

            const $this = $(this);

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
    const views = {
        main: function () {
            log.info("views:main");

            if (!clipboard.isSupported()) {
                Common.msg.info(Common.config().LANG[65]);
            }

            $(".btn-menu").click(function () {
                const $this = $(this);

                if ($this.attr("data-history-reset") === "1") {
                    Common.appRequests().history.reset();
                }

                Common.appActions().doAction({r: $this.data("route")}, $this.data("view"));
            });

            $("#btnLogout").click(function (e) {
                Common.appActions().main.logout();
            });

            $("#btnPrefs").click(function (e) {
                Common.appActions().doAction({actionId: $(this).data("route")});
            });

            Common.appActions().doAction({r: "account/index"}, "search");

            if (typeof Common.appTheme().viewsTriggers.main === "function") {
                Common.appTheme().viewsTriggers.main();
            }
        },
        search: function () {
            log.info("views:search");

            const $frmSearch = $("#frmSearch");

            if ($frmSearch.length === 0) {
                return;
            }

            $frmSearch.find("input[name='search']").on("keyup", function (e) {
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
                    const val = $(this).prop("checked") == true ? 1 : 0;

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

            const $frmLogin = $("#frmLogin");

            if (Common.config().AUTHBASIC_AUTOLOGIN && $frmLogin.find("input[name='loggedOut']").val() === "0") {
                log.info("views:login:autologin");

                Common.msg.info(Common.config().LANG[66]);

                Common.appActions().main.login($frmLogin);
            }
        },
        passreset: function () {
            log.info("views:passreset");

            const $form = $("#frmPassReset");

            Common.appTheme().passwordDetect($form);
        },
        footer: function () {
            log.info("views:footer");

        },
        common: function ($container) {
            log.info("views:common");

            selectDetect($container);

            const $sk = $container.find(":input [name='sk']");

            if ($sk.length > 0) {
                Common.sk.set($sk.val());
            }

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
        datatabs: function () {
            log.info("views:datatabs");

            $(".datagrid-action-search>form").each(function () {
                const $this = $(this);

                $this.find("button.btn-clear").on("click", function (e) {
                    e.preventDefault();

                    $this.trigger("reset");
                });
            });
        },
        config: function () {
            log.info("views:config");

            const $dropFiles = $("#drop-import-files");

            if ($dropFiles.length > 0) {
                const upload = Common.fileUpload($dropFiles);

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

            const $listFiles = $("#list-account-files");

            if ($listFiles.length > 0) {
                Common.appActions().account.listFiles($listFiles);
            }

            const $dropFiles = $("#drop-account-files");

            if ($dropFiles.length > 0) {
                const upload = Common.fileUpload($dropFiles);

                upload.url = Common.appActions().ajaxUrl.entrypoint + "?r=" + $dropFiles.data("action-route") + "/" + $dropFiles.data("item-id");

                upload.requestDoneAction = function () {
                    Common.appActions().account.listFiles($listFiles);
                };
            }

            const $extraInfo = $(".show-extra-info");

            if ($extraInfo.length > 0) {
                $extraInfo.on("click", function () {
                    const $this = $(this);
                    const $target = $($this.data("target"));

                    if ($target.is(":hidden")) {
                        $target.slideDown("slow");
                        $this.html($this.data("icon-up"));
                    } else {
                        $target.slideUp("slow");
                        $this.html($this.data("icon-down"));
                    }
                });
            }

            const $selParentAccount = $("#selParentAccount");

            if ($selParentAccount.length > 0) {
                $selParentAccount.on("change", function () {
                    const $this = $(this);
                    const $pass = $("#accountpass,#accountpassR");

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

            const $selTags = $("#selTags");

            if ($selTags.length > 0) {
                $selTags.selectize({
                    persist: false,
                    maxItems: null,
                    valueField: "id",
                    labelField: "name",
                    searchField: ["name"],
                    plugins: ["remove_button"]
                });
            }

            const $otherUsers = $('#otherUsers');

            if ($otherUsers.length > 0) {
                $otherUsers.selectize({
                    persist: false,
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name'],
                    plugins: ['remove_button'],
                    onInitialize: function () {
                        const userId = $otherUsers.data('userId');

                        if (userId > 0) {
                            this.removeOption(userId);
                        }
                    }
                });
            }

            const $otherUserGroups = $('#otherUserGroups');

            if ($otherUserGroups.length > 0) {
                $otherUserGroups.selectize({
                    persist: false,
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name'],
                    plugins: ['remove_button'],
                    onInitialize: function () {
                        const userGroupId = $otherUserGroups.data('userGroupId');

                        if (userGroupId > 0) {
                            this.removeOption(userGroupId);
                        }
                    }
                });
            }

            const $accesses = $("#data-accesses");

            if ($accesses.length > 0 && $accesses[0].childNodes.length === 1) {
                $accesses.parent(".data").hide();
            }

            $('input:text:visible:first').focus();
        },
        install: function () {
            log.info("views:install");

            const $form = $("#frmInstall");

            Common.appTheme().passwordDetect($form);
            selectDetect($form);
        }
    };

    /**
     * Actualizar el token de seguridad en los atributos de los botones y formularios
     *
     */
    const updateSk = function () {
        $("#content").find("[data-sk]").each(function () {
            log.info("updateSk");

            $(this).data("sk", Common.sk.get());
        });
    };

    /**
     * Actualizar el hash de los formularios de acción
     */
    const updateFormHash = function ($container) {
        log.info("updateFormHash");

        let $form;

        if ($container !== undefined) {
            $form = $container.find(".form-action[data-hash]");
        } else {
            $form = $(".form-action[data-hash]");
        }

        if ($form.length > 0) {
            $form.each(function () {
                const $this = $(this);

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