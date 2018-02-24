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

sysPass.Actions = function (Common) {
    "use strict";

    const log = Common.log;

    // Variable para almacenar la llamada a setTimeout()
    let timeout = 0;

    // Atributos de la ordenación de búsquedas
    const order = {key: 0, dir: 0};

    // Objeto con las URLs de las acciones
    const ajaxUrl = {
        entrypoint: "/index.php",
        doAction: "/index.php",
        updateItems: "/index.php",
        user: {
            savePreferences: "/ajax/ajax_userPrefsSave.php",
            password: "/ajax/ajax_usrpass.php",
            passreset: "/ajax/ajax_passReset.php"
        },
        main: {
            login: "/index.php?r=login/login",
            install: "/ajax/ajax_install.php",
            upgrade: "/ajax/ajax_upgrade.php",
            getUpdates: "/index.php?r=index/checkUpdates",
            task: "/ajax/ajax_task.php"
        },
        checks: "/ajax/ajax_checkConnection.php",
        config: {
            save: "/ajax/ajax_configSave.php",
            export: "/ajax/ajax_configSave.php",
            import: "/ajax/ajax_configSave.php"
        },
        file: "/ajax/ajax_filesMgmt.php",
        link: "/index.php",
        plugin: "/ajax/ajax_itemSave.php",
        account: {
            save: "/index.php",
            saveFavorite: "/ajax/ajax_itemSave.php",
            request: "/ajax/ajax_itemSave.php",
            getFiles: "/index.php",
            search: "/index.php?r=account/search"
        },
        appMgmt: {
            show: "/index.php",
            save: "/index.php",
            search: "/index.php"
        },
        eventlog: "/ajax/ajax_eventlog.php",
        wiki: {
            show: "/ajax/ajax_wiki.php"
        },
        notice: {
            show: "/ajax/ajax_noticeShow.php",
            search: "/ajax/ajax_noticeSearch.php"
        }
    };

    Object.freeze(ajaxUrl);

    // Función para cargar el contenido de la acción del menú seleccionada
    const doAction = function (obj, view) {
        const itemId = obj.itemId !== undefined ? "/" + obj.itemId : "";

        const data = {
            r: obj.r + itemId,
            isAjax: 1
        };

        const opts = Common.appRequests().getRequestOpts();
        opts.url = ajaxUrl.doAction;
        opts.method = "get";
        opts.type = "html";
        opts.addHistory = true;
        opts.data = data;

        Common.appRequests().getActionCall(opts, function (response) {
            const $content = $("#content");

            $content.empty().html(response);

            const views = Common.triggers().views;
            views.common($content);

            if (view !== undefined && typeof views[view] === "function") {
                views[view]();
            }

            const $mdlContent = $(".mdl-layout__content");

            if ($mdlContent.scrollTop() > 0) {
                $mdlContent.animate({scrollTop: 0}, 1000);
            }
        });
    };

    // Función para cargar el contenido de la acción del menú seleccionada
    const getContent = function (data, view) {
        log.info("getContent");

        data.isAjax = 1;

        const opts = Common.appRequests().getRequestOpts();
        opts.url = ajaxUrl.doAction;
        opts.method = "get";
        opts.type = "html";
        opts.addHistory = true;
        opts.data = data;

        Common.appRequests().getActionCall(opts, function (response) {
            const $content = $("#content");

            $content.empty().html(response);

            const views = Common.triggers().views;
            views.common($content);

            if (view !== undefined && typeof views[view] === "function") {
                views[view]();
            }

            const $mdlContent = $(".mdl-layout__content");

            if ($mdlContent.scrollTop() > 0) {
                $mdlContent.animate({scrollTop: 0}, 1000);
            }
        });
    };

    /**
     * Mostrar el contenido en una caja flotante
     *
     * @param response
     * @param {Object} callback
     * @param {function} callback.open
     * @param {function} callback.close
     */
    const showFloatingBox = function (response, callback) {
        response = response || "";

        $.magnificPopup.open({
            items: {
                src: response,
                type: "inline"
            },
            callbacks: {
                open: function () {
                    const $boxPopup = $("#box-popup");

                    Common.appTriggers().views.common($boxPopup);

                    $boxPopup.find(":input:text:visible:first").focus();

                    if (callback !== undefined && typeof callback.open === "function") {
                        callback.open();
                    }
                },
                close: function () {
                    if (callback !== undefined && typeof callback.close === "function") {
                        callback.close();
                    }
                }
            },
            showCloseBtn: false
        });
    };

    /**
     * Mostrar una imagen
     *
     * @param $obj
     * @param response
     */
    const showImageBox = function ($obj, response) {
        const $content = $("<div id=\"box-popup\" class=\"image\">" + response + "</div>");
        const $image = $content.find("img");

        if ($image.length === 0) {
            return showFloatingBox(response);
        }

        $image.hide();

        $.magnificPopup.open({
            items: {
                src: $content,
                type: "inline"
            },
            callbacks: {
                open: function () {
                    const $popup = this;

                    $image.on("click", function () {
                        $popup.close();
                    });

                    setTimeout(function () {
                        const image = Common.resizeImage($image);

                        $content.css({
                            backgroundColor: "#fff",
                            width: image.width,
                            height: "auto"
                        });

                        $image.show("slow");
                    }, 500);
                }
            }
        });
    };

    /**
     * Cerrar los diálogos
     */
    const closeFloatingBox = function () {
        $.magnificPopup.close();
    };

    /**
     * Objeto con acciones para las cuentas
     */
    const account = {
        view: function ($obj) {
            log.info("account:show");

            getContent(Common.appRequests().getRouteForQuery($obj.data("action-route"), $obj.data("item-id")), "account");
        },
        viewHistory: function ($obj) {
            log.info("account:showHistory");

            getContent(Common.appRequests().getRouteForQuery($obj.data("action-route"), $obj.val()), "account");
        },
        edit: function ($obj) {
            log.info("account:edit");

            getContent(Common.appRequests().getRouteForQuery($obj.data("action-route"), $obj.data("item-id")), "account");
        },
        delete: function ($obj) {
            log.info("account:delete");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[3] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        const opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.account.save;
                        opts.data = {
                            r: "account/saveDelete/" + $obj.data("item-id"),
                            sk: Common.sk.get()
                        };

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            account.search();
                        });
                    }
                }
            });
        },
        // Ver la clave de una cuenta
        viewPass: function ($obj) {
            log.info("account:showpass");

            const parentId = $obj.data("parent-id") || 0;
            const id = parentId === 0 ? $obj.data("item-id") : parentId;
            const history = $obj.data("history") || 0;

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.method = "get";
            opts.data = {
                r: $obj.data("action-route") + "/" + id + "/" + history,
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    Common.msg.out(json);
                } else {
                    var $container = $(json.data.html);

                    showFloatingBox($container);

                    timeout = setTimeout(function () {
                        closeFloatingBox();
                    }, 30000);

                    $container.on("mouseleave", function () {
                        clearTimeout(timeout);
                        timeout = setTimeout(function () {
                            closeFloatingBox();
                        }, 30000);
                    }).on("mouseenter", function () {
                        if (timeout !== 0) {
                            clearTimeout(timeout);
                        }
                    });
                }
            });
        },
        copyPass: function ($obj) {
            log.info("account:copypass");

            var parentId = $obj.data("parent-id");
            var id = parentId === 0 ? $obj.data("item-id") : parentId;

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.method = "get";
            opts.async = false;
            opts.data = {
                r: $obj.data("action-route") + "/" + id + "/" + $obj.data("history"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            return Common.appRequests().getActionCall(opts);
        },
        copy: function ($obj) {
            log.info("account:copy");

            getContent(Common.appRequests().getRouteForQuery($obj.data("action-route"), $obj.data("item-id")), "account");
        },
        saveFavorite: function ($obj, callback) {
            log.info("account:saveFavorite");

            var isOn = $obj.data("status") === "on";
            var actionId = isOn ? $obj.data("action-id-off") : $obj.data("action-id-on");

            var data = {
                r: actionId + "/" + $obj.data("item-id"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.saveFavorite;
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    $obj.data("status", isOn ? "off" : "on");

                    if (typeof callback === "function") {
                        callback();
                    }
                }
            });
        },
        request: function ($obj) {
            log.info("account:request");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.request;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);
            });
        },
        // Mostrar los botones de acción en los resultados de búsqueda
        menu: function ($obj) {
            $obj.hide();

            var actions = $obj.parent().children(".actions-optional");
            actions.show(250);
        },
        sort: function ($obj) {
            log.info("account:sort");

            var $frmSearch = $("#frmSearch");

            $frmSearch.find("input[name=\"skey\"]").val($obj.data("key"));
            $frmSearch.find("input[name=\"sorder\"]").val($obj.data("dir"));
            $frmSearch.find("input[name=\"start\"]").val($obj.data("start"));

            account.search();
        },
        editPass: function ($obj) {
            log.info("account:editpass");

            var parentId = $obj.data("parent-id");
            var itemId = parentId === undefined ? $obj.data("item-id") : parentId;

            getContent(Common.appRequests().getRouteForQuery($obj.data("action-route"), itemId), "account");
        },
        saveEditRestore: function ($obj) {
            log.info("account:restore");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route") + "/" + $obj.data("history-id") + "/" + $obj.data("item-id");
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.data.itemId !== undefined && json.data.nextAction !== undefined) {
                    getContent(Common.appRequests().getRouteForQuery(json.data.nextAction, json.data.itemId), "account");
                }
            });
        },
        listFiles: function ($obj) {
            log.info("account:getfiles");

            const opts = Common.appRequests().getRequestOpts();
            opts.method = "get";
            opts.type = "html";
            opts.url = ajaxUrl.entrypoint;
            opts.data = {
                r: $obj.data("action-route") + "/" + $obj.data("item-id"),
                del: $obj.data("delete"),
                sk: Common.sk.get()
            };

            Common.appRequests().getActionCall(opts, function (response) {
                $obj.html(response);
            });
        },
        search: function ($obj) {
            log.info("account:search");

            var $frmSearch = $("#frmSearch");
            $frmSearch.find("input[name='sk']").val(Common.sk.get());

            order.key = $frmSearch.find("input[name='skey']").val();
            order.dir = $frmSearch.find("input[name='sorder']").val();

            if ($obj !== undefined) {
                $frmSearch.find("input[name='start']").val(0);
            }

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.search;
            opts.method = "get";
            opts.data = $frmSearch.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 10) {
                    Common.msg.out(json);
                }

                Common.sk.set(json.data.sk);

                $("#res-content").empty().html(json.data.html);
            });
        },
        save: function ($obj) {
            log.info("account:save");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.save + "?r=" + $obj.data("action-route") + "/" + $obj.data("item-id");
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.data.itemId !== undefined && json.data.nextAction !== undefined) {
                    getContent(Common.appRequests().getRouteForQuery(json.data.nextAction, json.data.itemId), "account");
                }
            });
        }
    };

    /**
     * Actualizar los elemento de un select
     *
     * @param $obj
     */
    const items = {
        get: function ($obj) {
            log.info("items:get");

            const $dst = $obj[0].selectize;
            $dst.clearOptions();
            $dst.load(function (callback) {
                const opts = Common.appRequests().getRequestOpts();
                opts.url = ajaxUrl.updateItems;
                opts.method = "get";
                opts.data = {
                    r: $obj.data("action-route") + "/" + $obj.data("item-id"),
                    sk: $obj.data("sk")
                };

                Common.appRequests().getActionCall(opts, function (json) {
                    callback(json.data);

                    $dst.setValue($obj.data("selected-id"), true);

                    Common.appTriggers().updateFormHash();
                });
            });
        },
        update: function ($obj) {
            log.info("items:update");

            const $dst = $("#" + $obj.data("item-dst"))[0].selectize;

            $dst.clearOptions();
            $dst.load(function (callback) {
                const opts = Common.appRequests().getRequestOpts();
                opts.url = ajaxUrl.updateItems;
                opts.method = "get";
                opts.data = {
                    r: $obj.data("item-route"),
                    sk: Common.sk.get()
                };

                Common.appRequests().getActionCall(opts, function (json) {
                    callback(json);
                });
            });
        }
    };

    /**
     * Objeto con las acciones de usuario
     *
     * @type {{savePreferences: user.savePreferences, saveSecurity: user.saveSecurity, password: user.password, passreset: user.passreset}}
     */
    const user = {
        savePreferences: function ($obj) {
            log.info("user:savePreferences");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.user.savePreferences;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                setTimeout(function () {
                    Common.redirect("index.php");
                }, 2000);
            });
        },
        password: function ($obj) {
            log.info("user:password");

            const opts = Common.appRequests().getRequestOpts();
            opts.type = "html";
            opts.method = "get";
            opts.url = ajaxUrl.user.password;
            opts.data = {
                actionId: $obj.data("action-id"),
                itemId: $obj.data("item-id"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (response) {
                if (response.length === 0) {
                    main.logout();
                } else {
                    showFloatingBox(response);
                }
            });
        },
        passreset: function ($obj) {
            log.info("user:passreset");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.user.passreset;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status == 0) {
                    setTimeout(function () {
                        Common.redirect("index.php");
                    }, 2000);
                }
            });
        }
    };

    /**
     * Objeto con las acciones principales
     *
     * @type {{logout: main.logout, login: main.login, install: main.install, twofa: main.twofa}}
     */
    var main = {
        logout: function () {
            Common.redirect("index.php?r=login/logout");
        },
        login: function ($obj) {
            log.info("main:login");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("route");
            opts.method = "get";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                const $extra = $(".extra-hidden");

                switch (json.status) {
                    case 0:
                        Common.redirect(json.data.url);
                        break;
                    case 2:
                        Common.msg.out(json);

                        $obj.find("input[type='text'],input[type='password']").val("");
                        $obj.find("input:first").focus();

                        if ($extra.length > 0) {
                            $extra.hide();
                        }

                        $("#mpass").prop("disabled", false).val("");
                        $("#smpass").show();
                        break;
                    case 5:
                        Common.msg.out(json);

                        $obj.find("input[type='text'],input[type='password']").val("");
                        $obj.find("input:first").focus();

                        if ($extra.length > 0) {
                            $extra.hide();
                        }

                        $("#oldpass").prop("disabled", false).val("");
                        $("#soldpass").show();
                        break;
                    default:
                        Common.msg.out(json);

                        $obj.find("input[type='text'],input[type='password']").val("");
                        $obj.find("input:first").focus();
                }
            });
        },
        install: function ($obj) {
            log.info("main:install");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("route");
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    setTimeout(function () {
                        Common.redirect("index.php?r=login/index");
                    }, 1000);
                }
            });
        },
        upgrade: function ($obj) {
            log.info("main:upgrade");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[59] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        var $useTask = $obj.find("input[name='useTask']");
                        var $taskStatus = $("#taskStatus");

                        $taskStatus.empty().html(Common.config().LANG[62]);

                        if ($useTask.length > 0 && $useTask.val() == 1) {
                            var optsTask = Common.appRequests().getRequestOpts();
                            optsTask.url = ajaxUrl.main.task;
                            optsTask.data = {
                                source: $obj.find("input[name='lock']").val(),
                                taskId: $obj.find("input[name='taskId']").val()
                            };

                            var task = Common.appRequests().getActionEvent(optsTask, function (result) {
                                var text = result.task + " - " + result.message + " - " + result.time + " - " + result.progress + "%";
                                text += "<br>" + Common.config().LANG[62];

                                $taskStatus.empty().html(text);
                            });
                        }

                        var opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.main.upgrade;
                        opts.method = "get";
                        opts.useFullLoading = true;
                        opts.data = $obj.serialize();

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            if (json.status !== 0) {
                                $obj.find(":input[name=h]").val("");
                            } else {
                                if (task !== undefined) {
                                    task.close();
                                }

                                setTimeout(function () {
                                    Common.redirect("index.php");
                                }, 5000);
                            }
                        });
                    }
                }
            });
        },
        getUpdates: function () {
            log.info("main:getUpdates");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.main.getUpdates;
            opts.type = "html";
            opts.method = "get";
            opts.timeout = 10000;
            opts.useLoading = false;
            opts.data = {isAjax: 1};

            Common.appRequests().getActionCall(opts, function (response) {
                $("#updates").html(response);

                if (componentHandler !== undefined) {
                    componentHandler.upgradeDom();
                }
            }, function () {
                $("#updates").html("!");
            });
        }
    };

    /**
     * Objeto con las acciones de comprobación
     *
     * @type {{ldap: checks.ldap, wiki: checks.wiki}}
     */
    const checks = {
        ldap: function ($obj) {
            log.info("checks:ldap");

            const $form = $($obj.data("src"));
            $form.find("[name='sk']").val(Common.sk.get());

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.checks;
            opts.data = $form.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                var $results = $("#ldap-results");
                $results.find(".list-wrap").html(Common.appTheme().html.getList(json.data));
                $results.show("slow");
            });
        },
        wiki: function ($obj) {
            log.info("checks:wiki");

            const $form = $($obj.data("src"));
            $form.find("[name='sk']").val(Common.sk.get());

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.checks;
            opts.data = $form.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    $("#dokuWikiResCheck").html(json.data);
                }
            });
        }
    };

    /**
     * Objeto con las acciones de configuración
     *
     * @type {{save: config.save, backup: config.backup, export: config.export, import: config.import}}
     */
    const config = {
        save: function ($obj) {
            log.info("config:save");

            tabs.state.update($obj);

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    if (tabs.state.tab.refresh === true) {
                        getContent({
                            r: tabs.state.tab.route,
                            tabIndex: tabs.state.tab.index
                        });
                    } else if ($obj.data("reload") !== undefined) {
                        setTimeout(function () {
                            Common.redirect("index.php");
                        }, 2000);
                    }
                }
            });
        },
        masterpass: function ($obj) {
            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[59] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);

                        $obj.find(":input[type=password]").val("");
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        const $useTask = $obj.find("input[name='useTask']");
                        const $taskStatus = $("#taskStatus");

                        $taskStatus.empty().html(Common.config().LANG[62]);

                        if ($useTask.length > 0 && $useTask.val() == 1) {
                            const optsTask = Common.appRequests().getRequestOpts();
                            optsTask.url = ajaxUrl.main.task;
                            optsTask.data = {
                                source: $obj.find("input[name='lock']").val(),
                                taskId: $obj.find("input[name='taskId']").val()
                            };

                            const task = Common.appRequests().getActionEvent(optsTask, function (result) {
                                let text = result.task + " - " + result.message + " - " + result.time + " - " + result.progress + "%";
                                text += "<br>" + Common.config().LANG[62];

                                $taskStatus.empty().html(text);
                            });
                        }

                        const opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.config.save;
                        opts.useFullLoading = true;
                        opts.data = $obj.serialize();

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            $obj.find(":input[type=password]").val("");

                            if (task !== undefined) {
                                task.close();
                            }
                        });
                    }
                }
            });
        },
        backup: function ($obj) {
            log.info("config:backup");

            tabs.state.update($obj);

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.useFullLoading = true;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    getContent({
                        r: tabs.state.tab.route,
                        tabIndex: tabs.state.tab.index
                    });
                }
            });
        },
        export: function ($obj) {
            log.info("config:export");

            tabs.state.update($obj);

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    getContent({
                        r: tabs.state.tab.route,
                        tabIndex: tabs.state.tab.index
                    });
                }
            });
        },
        import: function ($obj) {
            log.info("config:import");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);
            });
        },
        refreshMpass: function ($obj) {
            log.info("config:import");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.data = {
                sk: $obj.data("sk"),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);
            });
        }
    };

    /**
     * Objeto con las acciones de los archivos
     */
    const file = {
        view: function ($obj) {
            log.info("file:view");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.method = "get";
            opts.data = {
                r: $obj.data("action-route") + "/" + $obj.data("item-id"),
                sk: Common.sk.get()
            };

            Common.appRequests().getActionCall(opts, function (response) {
                if (response.status === 1) {
                    return Common.msg.out(response);
                }

                showImageBox($obj, response.data.html);
            });
        },
        download: function ($obj) {
            log.info("file:download");

            const data = {
                r: $obj.data("action-route") + "/" + $obj.data("item-id"),
                sk: Common.sk.get()
            };

            $.fileDownload(ajaxUrl.entrypoint, {"httpMethod": "GET", "data": data});
        },
        delete: function ($obj) {
            log.info("file:delete");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[15] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        const opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.entrypoint;
                        opts.method = "get";
                        opts.data = {
                            r: $obj.data("action-route") + "/" + $obj.data("item-id"),
                            sk: Common.sk.get()
                        };

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            if (json.status === 0) {
                                account.listFiles($("#list-account-files"));
                            }
                        });
                    }
                }
            });
        }
    };

    /**
     * Objeto para las acciones de los enlaces
     */
    const link = {
        save: function ($obj) {
            log.info("link:save");

            const itemId = $obj.data("item-id");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.data = {
                r: $obj.data("action-route"),
                accountId: itemId,
                notify: 0,
                sk: Common.sk.get(),
                isAjax: 1
            };

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[48] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            if (json.status === 0) {
                                getContent({r: $obj.data("action-next") + "/" + itemId});
                            }
                        });
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        opts.data.notify = 1;

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            if (json.status === 0) {
                                getContent({
                                    r: $obj.data("action-next") + "/" + itemId
                                });
                            }
                        });
                    }
                }
            });
        },
        refresh: function ($obj) {
            log.info("link:refresh");

            tabs.state.update($obj);

            const itemId = $obj.data("item-id");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.data = {
                r: $obj.data("action-route") + "/" + itemId,
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    const actionNext = $obj.data("action-next");

                    if (actionNext) {
                        getContent({
                            r: actionNext + "/" + itemId
                        });
                    } else {
                        getContent({
                            r: tabs.state.tab.route,
                            tabIndex: tabs.state.tab.index
                        });
                    }
                }
            });
        }
    };


    const tabs = {
        state: {
            tab: {
                index: 0,
                refresh: true,
                route: ""
            },
            itemId: 0,
            update: function ($obj) {
                const $currentTab = $("#content").find("[id^='tabs-'].is-active");

                if ($currentTab.length > 0) {
                    tabs.state.tab.refresh = !$obj.data("item-dst");
                    tabs.state.tab.index = $currentTab.data("tab-index");
                    tabs.state.tab.route = $currentTab.data("tab-route");
                    tabs.state.itemId = $obj.data("item-id");
                }
            }
        }
    };

    /**
     * Objeto con acciones sobre elementos de la aplicación
     */
    const appMgmt = {
        show: function ($obj) {
            log.info("appMgmt:show");

            tabs.state.update($obj);

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.show;
            opts.method = "get";
            opts.data = {
                r: $obj.data("action-route") + "/" + $obj.data("item-id"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    Common.msg.out(json);
                } else {
                    const $itemDst = $obj.data("item-dst");

                    showFloatingBox(json.data.html, {
                        open: function () {
                            if ($itemDst) {
                                tabs.state.tab.refresh = false;
                            }
                        },
                        close: function () {
                            if ($itemDst) {
                                items.update($obj);
                            }
                        }
                    });
                }
            });
        },
        delete: function ($obj) {
            log.info("appMgmt:delete");

            tabs.state.update($obj);

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[12] + "</p></div>";
            const selection = $obj.data("selection");
            const items = [];

            // FIXME
            if (selection) {
                $(selection).find(".is-selected").each(function () {
                    const $this = $(this);

                    items.push($this.data("item-id"));
                });

                if (items.length === 0) {
                    return;
                }
            }

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        const opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.entrypoint;
                        opts.method = "get";
                        opts.data = {
                            r: $obj.data("action-route") + "/" + $obj.data("item-id"),
                            sk: Common.sk.get(),
                            isAjax: 1
                        };

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            getContent({
                                r: tabs.state.tab.route,
                                tabIndex: tabs.state.tab.index
                            });
                        });
                    }
                }
            });
        },
        save: function ($obj) {
            log.info("appMgmt:save");

            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("route");
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    if (tabs.state.tab.refresh === true) {
                        getContent({
                            r: tabs.state.tab.route,
                            tabIndex: tabs.state.tab.index
                        });
                    }

                    $.magnificPopup.close();
                }
            });
        },
        search: function ($obj) {
            log.info("appMgmt:search");

            const $target = $($obj.data("target"));
            const opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.method = "get";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 0) {
                    $target.html(json.data.html);
                } else {
                    $target.html(Common.msg.html.error(json.description));
                }

                Common.sk.set(json.csrf);
            });
        },
        nav: function ($obj) {
            log.info("appMgmt:nav");

            const $form = $("#" + $obj.data("action-form"));

            $form.find("[name='start']").val($obj.data("start"));
            $form.find("[name='count']").val($obj.data("count"));
            $form.find("[name='sk']").val(Common.sk.get());

            appMgmt.search($form);
        },
        ldapSync: function ($obj) {
            log.info("appMgmt:ldapSync");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[57] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        var opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.appMgmt.save;
                        opts.data = {
                            actionId: $obj.data("action-id"),
                            sk: Common.sk.get(),
                            isAjax: 1,
                            ldap_loginattribute: $("#ldap_loginattribute").val(),
                            ldap_nameattribute: $("#ldap_nameattribute").val(),
                            ldap_ads: $("#ldap_ads").prop("checked")
                        };

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);
                        });
                    }
                }
            });
        }
    };

    /**
     * Objeto con acciones sobre el registro de eventos
     *
     * @type {{nav: eventlog.nav, clear: eventlog.clear}}
     */
    var eventlog = {
        nav: function ($obj) {
            if ($obj.data("start") === undefined) {
                return false;
            }

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.eventlog;
            opts.method = "get";
            opts.type = "html";
            opts.data = {
                actionId: $obj.data("action-id"),
                sk: Common.sk.get(),
                isAjax: 1,
                start: $obj.data("start"),
                count: $obj.data("count"),
                current: $obj.data("current")
            };

            Common.appRequests().getActionCall(opts, function (response) {
                $("#content").html(response);
                Common.scrollUp();
            });
        },
        clear: function ($obj) {
            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[20] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        var opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.eventlog;
                        opts.method = "get";
                        opts.data = {clear: 1, sk: Common.sk.get(), isAjax: 1};

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);

                            if (json.status == 0) {
                                doAction({actionId: $obj.data("nextaction-id")});
                            }
                        });
                    }
                }
            });
        }
    };

    /**
     * Objeto con acciones sobre la wiki
     *
     * @type {{view: wiki.view}}
     */
    var wiki = {
        show: function ($obj) {
            log.info("wiki:show");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.wiki.show;
            opts.method = "get";
            opts.data = {
                pageName: $obj.data("pagename"),
                actionId: $obj.data("action-id"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    Common.msg.out(json);
                } else {
                    showFloatingBox(json.data.html);
                }
            });
        }
    };

    /**
     * Objeto para las acciones de los plugins
     */
    var plugin = {
        toggle: function ($obj) {
            log.info("plugin:enable");

            var data = {
                itemId: $obj.data("item-id"),
                actionId: $obj.data("action-id"),
                sk: Common.sk.get(),
                activeTab: $obj.data("activetab")
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.save;
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    // Recargar para cargar/descargar el plugin
                    setTimeout(function () {
                        Common.redirect("index.php");
                    }, 2000);

                    //doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        reset: function ($obj) {
            log.info("plugin:reset");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[58] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.msg.error(Common.config().LANG[44]);
                    }
                },
                positive: {
                    title: Common.config().LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        var data = {
                            "itemId": $obj.data("item-id"),
                            "actionId": $obj.data("action-id"),
                            "sk": Common.sk.get(),
                            "activeTab": $obj.data("activetab")
                        };

                        var opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.appMgmt.save;
                        opts.data = data;

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);
                        });
                    }
                }
            });
        }
    };

    /**
     * Objeto para las acciones de las notificaciones
     */
    var notice = {
        check: function ($obj) {
            log.info("notice:check");

            var data = {
                "itemId": $obj.data("item-id"),
                "actionId": $obj.data("action-id"),
                "sk": Common.sk.get()
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.save;
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        search: function ($obj) {
            log.info("notice:search");

            var $target = $($obj.data("target"));
            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.notice.search;
            opts.method = "get";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 0) {
                    $target.html(json.data.html);
                } else {
                    $target.html(Common.msg.html.error(json.description));
                }

                Common.sk.set(json.csrf);
            });
        },
        show: function ($obj) {
            log.info("notice:show");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.notice.show;
            opts.method = "get";
            opts.data = {
                itemId: $obj.data("item-id"),
                actionId: $obj.data("action-id"),
                activeTab: $obj.data("activetab"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    Common.msg.out(json);
                } else {
                    showFloatingBox(json.data.html);
                }
            });
        }
    };

    return {
        doAction: doAction,
        appMgmt: appMgmt,
        account: account,
        file: file,
        checks: checks,
        config: config,
        main: main,
        user: user,
        link: link,
        eventlog: eventlog,
        ajaxUrl: ajaxUrl,
        plugin: plugin,
        notice: notice,
        wiki: wiki,
        items: items
    };
};
