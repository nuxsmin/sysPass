/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

sysPass.Triggers = function (log) {
    "use strict";

    const regex = {
        email: "^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$"
    };

    // Detectar los campos select y añadir funciones
    const selectDetect = function ($container) {
        const options = {
            valueField: "id",
            labelField: "name",
            searchField: ["name"],
            onInitialize: function () {
                const $wrapper = $(this.$wrapper[0]);
                const $input = $(this.$input[0]);
                const $selectBoxAddIcon = $input.siblings(".btn-add-select");

                if ($selectBoxAddIcon.length === 1) {
                    $wrapper.append($selectBoxAddIcon);
                }
            }
        };

        $container.find(".select-box").each(function (e) {
            const $this = $(this);
            const self_options = {};

            if ($this.data("create") === true) {
                self_options.create = true;
            }

            options.plugins = $this.hasClass("select-box-deselect") ? {"clear_selection": {title: sysPassApp.config.LANG[51]}} : {};

            if ($this.data("onchange")) {
                const onchange = $this.data("onchange").split("/");

                options.onChange = function (value) {
                    if (value > 0) {
                        if (onchange.length === 2) {
                            sysPassApp.actions[onchange[0]][onchange[1]]($this);
                        } else {
                            sysPassApp.actions[onchange[0]]($this);
                        }
                    }
                };
            }

            $this.selectize($.extend(self_options, options));
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

        $container.find(".select-items-tag").selectize({
            create: function (input) {
                return {
                    value: input.toLowerCase(),
                    text: input.toLowerCase()
                };
            },
            createFilter: new RegExp(regex.email),
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

        if (plugin !== undefined && sysPassApp.plugins[plugin] !== undefined) {
            actions = sysPassApp.plugins[plugin];
        } else {
            actions = sysPassApp.actions;
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
        const currentHash = sysPassApp.util.hash.md5($obj.serialize());

        if (lastHash === currentHash) {
            sysPassApp.msg.ok(sysPassApp.config.LANG[55]);
            return false;
        }

        const plugin = $obj.data("plugin");
        let actions;

        if (plugin !== undefined && sysPassApp.plugins[plugin] !== undefined) {
            actions = sysPassApp.plugins[plugin];
        } else {
            actions = sysPassApp.actions;
        }

        const onsubmit = $obj.data("onsubmit").split("/");

        $obj.find("input[name='sk']").val(sysPassApp.sk.get());

        if (onsubmit.length === 2) {
            actions[onsubmit[0]][onsubmit[1]]($obj);
        } else {
            actions[onsubmit[0]]($obj);
        }
    };

    const bodyHooks = function () {
        log.info("bodyHooks");

        $("body").on("click", "button.btn-action[data-onclick][type='button']" +
            ",li.btn-action[data-onclick]" +
            ",span.btn-action[data-onclick]" +
            ",i.btn-action[data-onclick]" +
            ",a.btn-action[data-onclick]" +
            ",.btn-action-pager[data-onclick]", function () {
            handleActionButton($(this));
        }).on("click", ".btn-back", function () {
            if (sysPassApp.requests.history.length() > 0) {
                log.info("back");

                const lastHistory = sysPassApp.requests.history.del();

                if (!lastHistory.hasOwnProperty('data')) {
                    lastHistory.data = {sk: sysPassApp.sk.get()};
                } else {
                    lastHistory.data.sk = sysPassApp.sk.get();
                }

                sysPassApp.requests.getActionCall(lastHistory, lastHistory.callback);
            }
        }).on("submit", ".form-action", function (e) {
            e.preventDefault();

            handleFormAction($(this));
        }).on("click", ".btn-help[data-help]", function () {
            const $this = $(this);
            const $helpSrc = $.find("div[for='" + $this.data("help") + "']");

            if ($helpSrc.length > 0) {
                const title = sysPassApp.config.LANG[54] + " - " + $helpSrc[0].getAttribute("title") || sysPassApp.config.LANG[54];

                mdlDialog().show({
                    title: title,
                    text: $helpSrc[0].innerHTML,
                    positive: {
                        title: sysPassApp.config.LANG[43]
                    }
                });
            }
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
        }).on("theme:update", function () {
            log.debug("on:theme:update");

            const $box = $("#box-popup");

            if ($box.length > 0) {
                sysPassApp.util.focus($box);
            } else {
                sysPassApp.util.focus($(this));
            }
        });
    };

    /**
     * Triggers que se ejecutan en determinadas vistas
     */
    const views = {
        main: function ($obj) {
            log.info("views:main");

            if (!clipboard.isSupported()) {
                sysPassApp.msg.info(sysPassApp.config.LANG[65]);
            }

            $(".btn-menu").click(function () {
                const $this = $(this);

                if ($this.attr("data-history-reset") === "1") {
                    sysPassApp.requests.history.reset();
                }

                sysPassApp.actions.getContent({r: $this.data("route")}, $this.data("view"));
            });


            sysPassApp.actions.notification.getActive();

            if (sysPassApp.config.STATUS.CHECK_NOTIFICATIONS) {
                setInterval(function () {
                    sysPassApp.actions.notification.getActive();
                }, 120000);
            }

            if ($obj.data("upgraded") === 0) {
                sysPassApp.actions.getContent({r: "account/index"}, "search");
            } else {
                const $content = $("#content");
                const page = $content.data('page');

                views.common($content);

                if (page !== "" && typeof views[page] === "function") {
                    views[page]();
                }
            }

            if (sysPassApp.config.STATUS.CHECK_UPDATES === true) {
                sysPassApp.actions.main.getUpdates();
            }

            if (sysPassApp.config.STATUS.CHECK_NOTICES === true) {
                sysPassApp.actions.main.getNotices();
            }

            if (typeof sysPassApp.theme.viewsTriggers.main === "function") {
                sysPassApp.theme.viewsTriggers.main();
            }
        },
        search: function () {
            log.info("views:search");

            const $frmSearch = $("#frmSearch");

            if ($frmSearch.length === 0) {
                return;
            }

            // $frmSearch.find("input[name='search']")
            //     .on("keyup", function (e) {
            //         e.preventDefault();
            //
            //         if (e.key === "Enter"
            //             || e.which === 13
            //         ) {
            //             $frmSearch.submit();
            //         }
            //     });

            $frmSearch.find("select, #rpp")
                .on("change", function () {
                    $frmSearch.submit();
                });

            $frmSearch.find("button.btn-clear")
                .on("click", function (e) {
                    e.preventDefault();

                    $frmSearch.find("input[name=\"searchfav\"]").val(0);

                    $frmSearch[0].reset();
                });

            $("#globalSearch").click(function () {
                    const val = $(this).prop("checked") == true ? 1 : 0;

                    $frmSearch.find("input[name='gsearch']").val(val);
                    $frmSearch.submit();
                }
            );

            if (typeof sysPassApp.theme.viewsTriggers.search === "function") {
                sysPassApp.theme.viewsTriggers.search();
            }
        },
        login: function () {
            log.info("views:login");

            const $frmLogin = $("#frmLogin");

            if (sysPassApp.config.AUTH.AUTHBASIC_AUTOLOGIN
                && $frmLogin.find("input[name='loggedOut']").val() === "0"
            ) {
                log.info("views:login:autologin");

                sysPassApp.msg.info(sysPassApp.config.LANG[66]);

                sysPassApp.actions.main.login($frmLogin);
            }
        },
        userpassreset: function () {
            log.info("views:userpassreset");

            const $form = $("#frmUserPassReset");

            sysPassApp.theme.passwordDetect($form);
        },
        footer: function () {
            log.info("views:footer");
        },
        common: function ($container) {
            log.info("views:common");

            selectDetect($container);

            const $sk = $container.find(":input[name='sk']");

            if ($sk.length > 0 && $sk[0].value !== "") {
                sysPassApp.sk.set($sk[0].value);
            }

            if (typeof sysPassApp.theme.viewsTriggers.common === "function") {
                sysPassApp.theme.viewsTriggers.common($container);
            }

            initializeTags($container);

            sysPassApp.triggers.updateFormHash($container);
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
                const upload = sysPassApp.util.fileUpload($dropFiles);

                upload.url = sysPassApp.util.getUrl(
                    sysPassApp.actions.ajaxUrl.entrypoint,
                    {r: $dropFiles.data("action-route")}
                );
                upload.allowedMime = sysPassApp.config.FILES.IMPORT_ALLOWED_MIME;
                upload.beforeSendAction = function () {
                    upload.setRequestData({
                        sk: sysPassApp.sk.get(),
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
                sysPassApp.actions.account.listFiles($listFiles);
            }

            const $dropFiles = $("#drop-account-files");

            if ($dropFiles.length > 0) {
                const upload = sysPassApp.util.fileUpload($dropFiles);

                upload.url = sysPassApp.util.getUrl(
                    sysPassApp.actions.ajaxUrl.entrypoint,
                    {r: [$dropFiles.data("action-route"), $dropFiles.data("item-id")]}
                );
                upload.allowedMime = sysPassApp.config.FILES.ACCOUNT_ALLOWED_MIME;
                upload.requestDoneAction = function () {
                    sysPassApp.actions.account.listFiles($listFiles);
                };
            }

            const $selParentAccount = $("#parent_account_id");

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

                sysPassApp.actions.items.get($selParentAccount);
            }
        },
        install: function () {
            log.info("views:install");

            const $form = $("#frmInstall");

            sysPassApp.theme.passwordDetect($form);
            selectDetect($form);
        }
    };

    const initializeTags = function ($container) {
        log.info("initializeTags");

        $container
            .find(".select-box-tags").selectize({
            persist: false,
            valueField: 'id',
            labelField: 'name',
            searchField: ['name'],
            plugins: ['remove_button'],
            onInitialize: function () {
                const $wrapper = $(this.$wrapper[0]);
                const $input = $(this.$input[0]);
                const value = this.getValue();

                if (value !== "") {
                    $input.attr("data-hash", sysPassApp.util.hash.md5(value.join()));
                }

                const currentItemId = $input.data("currentItemId");

                if (currentItemId !== undefined) {
                    this.removeOption(currentItemId, true);
                }

                const $selectBoxTagsNext = $input.siblings(".btn-add-select");

                if ($selectBoxTagsNext.length === 1) {
                    $wrapper.append($selectBoxTagsNext);
                }

                const $selectBoxIcon = $input.siblings(".select-icon");

                if ($selectBoxIcon.length === 1) {
                    $wrapper.prepend($selectBoxIcon);
                }
            },
            onChange: function () {
                const $input = $(this.$input[0]);

                // Calculates the current data hash and compares it against the orginal one.
                // It sets the data-updated attribute to the comparation result
                const updated = sysPassApp.util.hash.md5(this.getValue().join()) !== $input.data("hash");
                $input.attr("data-updated", updated);
            }
        });
    };

    /**
     * Actualizar el token de seguridad en los atributos de los botones y formularios
     *
     */
    const updateSk = function () {
        $("#content").find("[data-sk]").each(function () {
            log.info("updateSk");

            $(this).data("sk", sysPassApp.sk.get());
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

                $this.attr("data-hash", sysPassApp.util.hash.md5($this.serialize()));
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