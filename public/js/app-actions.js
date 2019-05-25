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

sysPass.Actions = function (log) {
    "use strict";

    // Variable para almacenar la llamada a setTimeout()
    let timeout = 0;

    // Atributos de la ordenación de búsquedas
    const order = {key: 0, dir: 0};

    // Objeto con las URLs de las acciones
    const ajaxUrl = {
        entrypoint: "/index.php"
    };

    Object.freeze(ajaxUrl);

    // Función para cargar el contenido de la acción del menú seleccionada
    const getContent = function (data, view) {
        log.info("getContent");

        data.isAjax = 1;
        data.sk = sysPassApp.sk.get();

        const opts = sysPassApp.requests.getRequestOpts();
        opts.method = "get";
        opts.type = "html";
        opts.addHistory = true;
        opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, data);

        return sysPassApp.requests.getActionCall(opts, function (response) {
            const $content = $("#content");

            $content.empty().html(response);

            const views = sysPassApp.triggers.views;
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

                    sysPassApp.triggers.views.common($boxPopup);

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
                        const image = sysPassApp.util.resizeImage($image);

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

            getContent({r: [$obj.data("action-route"), $obj.data("item-id")]}, "account");
        },
        viewHistory: function ($obj) {
            log.info("account:showHistory");

            getContent({r: [$obj.data("action-route"), $obj.val()]}, "account");
        },
        edit: function ($obj) {
            log.info("account:edit");

            getContent({r: [$obj.data("action-route"), $obj.data("item-id")]}, "account");
        },
        delete: function ($obj) {
            log.info("account:delete");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[3] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        const opts = sysPassApp.requests.getRequestOpts();
                        opts.url = ajaxUrl.entrypoint;
                        opts.data = {
                            r: "account/saveDelete/" + $obj.data("item-id"),
                            sk: sysPassApp.sk.get()
                        };

                        sysPassApp.requests.getActionCall(opts, function (json) {
                            sysPassApp.msg.out(json);

                            sysPassApp.sk.set(json.csrf);

                            account.search($obj);
                        });
                    }
                }
            });
        },
        // Ver la clave de una cuenta
        viewPass: function ($obj) {
            log.info("account:viewPass");

            const parentId = $obj.data("parent-id") || 0;
            const id = parentId === 0 ? $obj.data("item-id") : parentId;

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), id, parentId],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    sysPassApp.msg.out(json);
                } else {
                    const $container = $(json.data.html);

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
        // Ver la clave de una cuenta
        viewPassHistory: function ($obj) {
            log.info("account:viewPassHistory");

            account.viewPass($obj);
        },
        copyPass: function ($obj) {
            log.info("account:copyPass");

            const parentId = $obj.data("parent-id");
            const id = parentId === 0 ? $obj.data("item-id") : parentId;

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.async = false;
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), id],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            return sysPassApp.requests.getActionCall(opts);
        },
        copyPassHistory: function ($obj) {
            log.info("account:copyPassHistory");

            account.copyPassHistory($obj);
        },
        copy: function ($obj) {
            log.info("account:copy");

            getContent({r: [$obj.data("action-route"), $obj.data("item-id")]}, "account");
        },
        saveFavorite: function ($obj, callback) {
            log.info("account:saveFavorite");

            const isOn = $obj.data("status") === "on";
            const actionRoute = isOn ? $obj.data("action-route-off") : $obj.data("action-route-on");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.data = {
                r: actionRoute + "/" + $obj.data("item-id"),
                sk: sysPassApp.sk.get(),
                isAjax: 1
            };

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

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

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route") + "/" + $obj.data("item-id");
            opts.data = $obj.serialize() + "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0
                    && json.data['nextAction'] !== undefined
                ) {
                    sysPassApp.sk.set(json.csrf);

                    getContent({r: json.data.nextAction}, "account");
                }
            });
        },
        // Mostrar los botones de acción en los resultados de búsqueda
        menu: function ($obj) {
            $obj.hide();

            const actions = $obj.parent().children(".actions-optional");
            actions.show(250);
        },
        sort: function ($obj) {
            log.info("account:sort");

            const $frmSearch = $("#frmSearch");

            $frmSearch.find("input[name=\"skey\"]").val($obj.data("key"));
            $frmSearch.find("input[name=\"sorder\"]").val($obj.data("dir"));
            $frmSearch.find("input[name=\"start\"]").val($obj.data("start"));

            account.search();
        },
        editPass: function ($obj) {
            log.info("account:editpass");

            const parentId = $obj.data("parent-id");
            const itemId = parentId === undefined ? $obj.data("item-id") : parentId;

            getContent({r: [$obj.data("action-route"), itemId]}, "account");
        },
        saveEditRestore: function ($obj) {
            log.info("account:restore");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.data = {
                r: $obj.data("action-route") + "/" + $obj.data("history-id") + "/" + $obj.data("item-id"),
                sk: sysPassApp.sk.get()
            };

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.data.itemId !== undefined
                    && json.data.nextAction !== undefined
                ) {
                    getContent({r: [json.data.nextAction, json.data.itemId]}, "account");
                }
            });
        },
        listFiles: function ($obj) {
            log.info("account:getfiles");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.type = "html";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    del: $obj.data("delete"),
                    sk: sysPassApp.sk.get()
                });

            sysPassApp.requests.getActionCall(opts, function (response) {
                $obj.html(response);
            });
        },
        search: function ($obj) {
            log.info("account:search");

            const $frmSearch = $("#frmSearch");
            $frmSearch.find("input[name='sk']").val(sysPassApp.sk.get());

            order.key = $frmSearch.find("input[name='skey']").val();
            order.dir = $frmSearch.find("input[name='sorder']").val();

            if ($obj !== undefined) {
                $frmSearch.find("input[name='start']").val(0);
            }

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, {r: $frmSearch.data("action-route")});
            opts.data = $frmSearch.serialize();

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status === 10) {
                    sysPassApp.msg.out(json);
                }

                $("#res-content").empty().html(json.data.html);
            });
        },
        save: function ($obj) {
            log.info("account:save");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route") + "/" + $obj.data("item-id");
            opts.data = $obj.serialize();

            // Sets which "select" elements should be updated
            $("select.select-box-tags[data-hash][data-updated=true]").each(function (index, value) {
                opts.data += "&" + value.getAttribute("id") + "_update=1";
            });

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.data.itemId !== undefined
                    && json.data.nextAction !== undefined
                ) {
                    getContent({r: [json.data.nextAction, json.data.itemId]}, "account");
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
                const opts = sysPassApp.requests.getRequestOpts();
                opts.method = "get";
                opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                    {
                        r: [$obj.data("action-route"), $obj.data("item-id")],
                        sk: sysPassApp.sk.get()
                    });

                sysPassApp.requests.getActionCall(opts, function (json) {
                    callback(json.data);

                    $dst.setValue($obj.data("selected-id"), true);

                    sysPassApp.triggers.updateFormHash();
                });
            });
        },
        update: function ($obj) {
            log.info("items:update");

            const $dst = $("#" + $obj.data("item-dst"))[0].selectize;

            const selected = $dst.getValue();

            $dst.clearOptions();
            $dst.load(function (callback) {
                const opts = sysPassApp.requests.getRequestOpts();
                opts.method = "get";
                opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                    {
                        r: $obj.data("item-route"),
                        sk: sysPassApp.sk.get()
                    });

                sysPassApp.requests.getActionCall(opts, function (json) {
                    callback(json);
                    $dst.setValue(selected, true);
                });
            });
        }
    };

    /**
     * Objeto con las acciones de usuario
     */
    const user = {
        showSettings: function ($obj) {
            log.info("user:showSettings");

            getContent({r: $obj.data("action-route")}, "userSettings");
        },
        saveSettings: function ($obj) {
            log.info("user:saveSettings");

            tabs.save($obj);
        },
        password: function ($obj) {
            log.info("user:password");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.type = "html";
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (response) {
                if (response.length === 0) {
                    main.logout();
                } else {
                    showFloatingBox(response);
                }
            });
        },
        passreset: function ($obj) {
            log.info("user:passreset");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "/?r=" + $obj.data("action-route");
            opts.data = $obj.serialize();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    setTimeout(function () {
                        sysPassApp.util.redirect("index.php");
                    }, 2000);
                }
            });
        },
        savePassword: function ($obj) {
            log.info("user:savepassword");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.method = "post";
            opts.data = $obj.serialize();
            opts.data += "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    closeFloatingBox();

                    if (typeof onSuccess === "function") {
                        onSuccess();
                    }
                }
            });
        }
    };

    /**
     * Objeto con las acciones principales
     *
     * @type {{logout: main.logout, login: main.login, install: main.install, twofa: main.twofa}}
     */
    const main = {
        logout: function () {
            sysPassApp.util.redirect("index.php?r=login/logout");
        },
        login: function ($obj) {
            log.info("main:login");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, {r: $obj.data("route")});
            opts.data = $obj.serialize();

            sysPassApp.requests.getActionCall(opts, function (json) {
                const $extra = $(".extra-hidden");

                switch (json.status) {
                    case 0:
                        sysPassApp.util.redirect(json.data.url);
                        break;
                    case 2:
                        sysPassApp.msg.out(json);

                        $obj.find("input[type='text'],input[type='password']").val("");

                        if ($extra.length > 0) {
                            $extra.hide();
                        }

                        $("#mpass").prop("disabled", false).val("");
                        $("#smpass").show();
                        break;
                    case 5:
                        sysPassApp.msg.out(json);

                        $obj.find("input[type='text'],input[type='password']").val("");

                        if ($extra.length > 0) {
                            $extra.hide();
                        }

                        $("#oldpass").prop("disabled", false).val("");
                        $("#soldpass").show();
                        break;
                    default:
                        sysPassApp.msg.out(json);

                        $obj.find("input[type='text'],input[type='password']").val("");
                }
            });
        },
        install: function ($obj) {
            log.info("main:install");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("route");
            opts.data = $obj.serialize();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    setTimeout(function () {
                        sysPassApp.util.redirect("index.php?r=login/index");
                    }, 1000);
                }
            });
        },
        upgrade: function ($obj) {
            log.info("main:upgrade");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[59] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        const taskId = $obj.find("input[name='taskId']").val();
                        const opts = sysPassApp.requests.getRequestOpts();

                        if (taskId) {
                            opts.useFullLoading = true;
                            task.run(taskId);
                        }

                        opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, {r: $obj.data('action-route')});
                        opts.method = "get";
                        opts.data = $obj.serialize();

                        sysPassApp.requests.getActionCall(opts, function (json) {
                            sysPassApp.msg.out(json);

                            if (json.status !== 0) {
                                $obj.find(":input[name=key]").val("");
                            } else {
                                task.end();

                                setTimeout(function () {
                                    sysPassApp.util.redirect("index.php");
                                }, 5000);
                            }
                        });
                    }
                }
            });
        },
        getUpdates: function () {
            log.info("main:getUpdates");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, {
                r: "status/checkRelease",
                isAjax: 1
            });
            opts.timeout = 10000;
            opts.useLoading = false;

            const $updates = $("#updates");

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status === 0) {
                    if (json.data.length > 0) {
                        $updates.html(
                            `<a id="link-updates" href="${json.data.url}" target="_blank">${json.data.title}
                            <div id="help-hasupdates" class="icon material-icons mdl-color-text--indigo-200">cloud_download</div>
                            </a>
                            <span for="link-updates" class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large">${json.data.description}</span>`);
                    } else {
                        $updates.html(
                            `<div id="updates-info" class="icon material-icons mdl-color-text--teal-200">check_circle</div>
                            <span for="updates-info" class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large">${sysPassApp.config.LANG[68]}</span>`);
                    }
                } else {
                    $updates.html(
                        `<div id="updates-info" class="icon material-icons mdl-color-text--amber-200">warning</div>
                        <span for="updates-info" class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large">${sysPassApp.config.LANG[69]}</span>`);
                }

                sysPassApp.theme.update();
            }, function () {
                $updates.html(
                    `<div id="updates-info" class="icon material-icons mdl-color-text--amber-200">warning</div>
                    <span for="updates-info" class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large">${sysPassApp.config.LANG[69]}</span>`);
            });
        },
        getNotices: function () {
            log.info("main:getNotices");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: "status/checkNotices",
                    isAjax: 1
                });
            opts.timeout = 10000;
            opts.useLoading = false;
            opts.data = {isAjax: 1};

            const $notices = $("#notices");

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status === 0) {
                    if (json.data.length > 0) {
                        $notices.html(
                            `<a href="https://github.com/nuxsmin/sysPass/labels/Notices" target="_blank">
                            <div id="notices-info" 
                            class="material-icons mdl-badge mdl-badge--overlap mdl-color-text--amber-200" 
                            data-badge="${json.data.length}">feedback</div>
                            </a>
                            <span for="notices-info" class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large">
                            <div class="notices-title">${sysPassApp.config.LANG[70]}</div>${json.data.map(x => x.title).join('<br>')}
                            </span>`);
                    }
                }

                sysPassApp.theme.update();
            });
        }
    };

    /**
     * Objeto con las acciones de comprobación
     *
     * @type {{ldap: checks.ldap, wiki: checks.wiki}}
     */
    const checks = {
        wiki: function ($obj) {
            log.info("checks:wiki");

            const $form = $($obj.data("src"));
            $form.find("[name='sk']").val(sysPassApp.sk.get());

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint;
            opts.data = $form.serialize();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

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
    const configManager = {
        save: function ($obj) {
            log.info("config:save");

            tabs.save($obj);
        },
        masterpass: function ($obj) {
            log.info("config:masterpass");

            const atext = `<div id="alert"><p id="alert-text">${sysPassApp.config.LANG[59]}</p></div>`;

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);

                        $obj.find(":input[type=password]").val("");
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        const taskId = $obj.find("input[name='taskId']").val();
                        const opts = sysPassApp.requests.getRequestOpts();

                        if (taskId) {
                            opts.useFullLoading = true;
                            task.run(taskId);
                        }

                        opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, {r: $obj.data('action-route')});
                        opts.data = $obj.serialize() + "&sk=" + sysPassApp.sk.get();

                        sysPassApp.requests.getActionCall(opts, function (json) {
                            sysPassApp.msg.out(json);

                            $obj.find(":input[type=password]").val("");

                            task.end();
                        });
                    }
                }
            });
        },
        backup: function ($obj) {
            log.info("config:backup");

            tabs.state.update($obj);

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.useFullLoading = true;
            opts.data = $obj.serialize() + "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

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

            tabs.save($obj);
        },
        import: function ($obj) {
            log.info("config:import");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.data = $obj.serialize() + "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);
            });
        },
        refreshMpass: function ($obj) {
            log.info("config:import");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: $obj.data("action-route"),
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);
            });
        },
        mailCheck: function ($obj) {
            log.info("config:mailCheck");

            const $form = $($obj.data("src"));
            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + '?r=' + $obj.data("action-route");
            opts.data = $form.serialize() + "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);
            });
        }
    };

    /**
     * Objeto con las acciones de los archivos
     */
    const file = {
        view: function ($obj) {
            log.info("file:view");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get()
                });

            sysPassApp.requests.getActionCall(opts, function (response) {
                if (response.status !== 0) {
                    return sysPassApp.msg.out(response);
                }

                showImageBox($obj, response.data.html);
            });
        },
        download: function ($obj) {
            log.info("file:download");

            const fileType = $obj.data("item-type");
            const url = sysPassApp.requests.getUrl(sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get()
                }));

            if (fileType === 'application/pdf') {
                window.open(url, '_blank');
                return;
            }

            $.fileDownload(url,
                {
                    httpMethod: "GET",
                    successCallback: function (url) {
                        sysPassApp.msg.ok(sysPassApp.config.LANG[72]);
                    }
                }
            );
        },
        delete: function ($obj) {
            log.info("file:delete");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[15] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        const opts = sysPassApp.requests.getRequestOpts();
                        opts.method = "get";
                        opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                            {
                                r: [$obj.data("action-route"), $obj.data("item-id")],
                                sk: sysPassApp.sk.get(),
                            });

                        sysPassApp.requests.getActionCall(opts, function (json) {
                            sysPassApp.msg.out(json);

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

            const request = function (notify) {
                const accountId = $obj.data("account-id");

                const opts = sysPassApp.requests.getRequestOpts();
                opts.method = "get";

                if (accountId) {
                    opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                        {
                            r: [$obj.data("action-route"), accountId, notify],
                            sk: sysPassApp.sk.get(),
                            isAjax: 1
                        });
                } else {
                    opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                        {
                            r: $obj.data("action-route"),
                            sk: sysPassApp.sk.get(),
                            isAjax: 1
                        });

                    opts.data = $obj.serialize();
                }

                sysPassApp.requests.getActionCall(opts, function (json) {
                    sysPassApp.msg.out(json);

                    if (json.status === 0) {
                        getContent({r: [$obj.data("action-next"), accountId]});
                    }
                });
            };

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[48] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        request(0);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        request(1);
                    }
                }
            });
        },
        delete: function ($obj) {
            log.info("link:delete");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[12] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        const opts = sysPassApp.requests.getRequestOpts();
                        opts.method = "get";
                        opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                            {
                                r: [$obj.data("action-route"), $obj.data("item-id")],
                                sk: sysPassApp.sk.get(),
                                isAjax: 1
                            });

                        sysPassApp.requests.getActionCall(opts, function (json) {
                            sysPassApp.msg.out(json);

                            if (json.status === 0) {
                                getContent({r: [$obj.data("action-next"), $obj.data("account-id")]});
                            }
                        });
                    }
                }
            });
        },
        refresh: function ($obj) {
            log.info("link:refresh");

            tabs.state.update($obj);

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    const actionNext = $obj.data("action-next");

                    if (actionNext) {
                        getContent({r: [actionNext, $obj.data("account-id")]});
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

    /**
     * sysPassApp tabs actions
     * @type {{state: {tab: {index: number, refresh: boolean, route: string}, itemId: number, update: update}, save: save, refresh: refresh}}
     */
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
        },
        save: function ($obj, onSuccess) {
            log.info("tabs:save");

            tabs.state.update($obj);

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
            opts.method = $obj.data("action-method") || "post";
            opts.data = $obj.serialize();
            opts.data += "&sk=" + sysPassApp.sk.get();

            // Sets which "select" elements should be updated
            $("select.select-box-tags[data-hash][data-updated=true]").each(function (index, value) {
                opts.data += "&" + value.getAttribute("id") + "_update=1";
            });

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    if (typeof onSuccess === "function") {
                        onSuccess();
                    }

                    if ($obj.data("reload") !== undefined) {
                        log.info('reload');

                        setTimeout(function () {
                            sysPassApp.util.redirect("index.php");
                        }, 2000);
                    } else if (tabs.state.tab.refresh === true) {
                        log.info("refresh");

                        getContent({
                            r: tabs.state.tab.route,
                            tabIndex: tabs.state.tab.index
                        });
                    }
                }
            });
        },
        refresh: function ($obj) {
            this.state.update($obj);

            getContent({
                r: this.state.tab.route,
                tabIndex: this.state.tab.index
            });
        }
    };

    /**
     * Objeto con acciones sobre elementos de la aplicación
     */
    const appMgmt = {
        show: function ($obj) {
            log.info("appMgmt:show");

            tabs.state.update($obj);

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";

            const items = grid.getSelection($obj);

            if (items === false) {
                return;
            }

            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), (items.length === 0 ? $obj.data("item-id") : null)],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });
            opts.data = {items: items};

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    sysPassApp.msg.out(json);
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
                                sysPassApp.actions.items.update($obj);
                            }
                        }
                    });
                }
            });
        },
        delete: function ($obj) {
            log.info("appMgmt:delete");

            tabs.state.update($obj);

            grid.delete($obj, function (items) {
                const opts = sysPassApp.requests.getRequestOpts();
                opts.method = "get";
                opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                    {
                        r: [$obj.data("action-route"), (items.length === 0 ? $obj.data("item-id") : null)],
                        sk: sysPassApp.sk.get(),
                        isAjax: 1
                    });
                opts.data = {items: items};

                sysPassApp.requests.getActionCall(opts, function (json) {
                    sysPassApp.msg.out(json);

                    getContent({
                        r: tabs.state.tab.route,
                        tabIndex: tabs.state.tab.index
                    });
                });
            });
        },
        save: function ($obj) {
            log.info("appMgmt:save");

            tabs.save($obj, function () {
                closeFloatingBox();
            });
        },
        search: function ($obj) {
            log.info("appMgmt:search");

            grid.search($obj);
        },
        nav: function ($obj) {
            log.info("appMgmt:nav");

            grid.nav($obj);
        }
    };

    /**
     * Objeto con acciones sobre el registro de eventos
     *
     * @type {{nav: eventlog.nav, clear: eventlog.clear}}
     */
    const eventlog = {
        clear: function ($obj) {
            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[20] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        tabs.save($obj);
                    }
                }
            });
        },
        refresh: function ($obj) {
            $('#' + $obj.data("action-form")).submit();
        }
    };

    /**
     * Objeto con acciones sobre la wiki
     *
     * @type {{view: wiki.view}}
     */
    const wiki = {
        show: function ($obj) {
            log.info("wiki:show");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: $obj.data("action-route"),
                    pageName: $obj.data("pagename"),
                    actionId: $obj.data("action-id"),
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    sysPassApp.msg.out(json);
                } else {
                    showFloatingBox(json.data.html);
                }
            });
        }
    };

    /**
     * Objeto para las acciones de los plugins
     */
    const plugin = {
        toggle: function ($obj) {
            log.info("plugin:enable");

            tabs.state.update($obj);

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    // Recargar para cargar/descargar el plugin
                    setTimeout(function () {
                        sysPassApp.util.redirect("index.php");
                    }, 2000);
                }
            });
        },
        reset: function ($obj) {
            log.info("plugin:reset");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[58] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        const opts = sysPassApp.requests.getRequestOpts();
                        opts.method = "get";
                        opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                            {
                                r: [$obj.data("action-route"), $obj.data("item-id")],
                                sk: sysPassApp.sk.get(),
                                isAjax: 1
                            });

                        sysPassApp.requests.getActionCall(opts, function (json) {
                            sysPassApp.msg.out(json);

                            if (json.status === 0) {
                                getContent({r: $obj.data("action-next")});
                            }
                        });
                    }
                }
            });
        },
        search: function ($obj) {
            log.info("plugin:search");

            grid.search($obj);
        },
        show: function ($obj) {
            log.info("plugin:show");

            appMgmt.show($obj);
        },
        save: function ($obj) {
            log.info("plugin:save");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("route");
            opts.data = $obj.serialize() + "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    getContent({r: $obj.data("action-next")});

                    $.magnificPopup.close();
                }
            });
        },
        nav: function ($obj) {
            log.info("plugin:nav");

            grid.nav($obj);
        },
        delete: function ($obj) {
            log.info("plugin:delete");

            grid.delete($obj, function (items) {
                const opts = sysPassApp.requests.getRequestOpts();
                opts.method = "get";
                opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                    {
                        r: [$obj.data("action-route"), (items.length === 0 ? $obj.data("item-id") : null)],
                        sk: sysPassApp.sk.get(),
                        isAjax: 1
                    });
                opts.data = {items: items};

                sysPassApp.requests.getActionCall(opts, function (json) {
                    sysPassApp.msg.out(json);

                    if (json.status === 0) {
                        getContent({r: $obj.data("action-next")});
                    }
                });
            });
        },
    };

    /**
     * Objeto para las acciones de las notificaciones
     */
    const notification = {
        check: function ($obj) {
            log.info("notification:check");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status === 0) {
                    getContent({r: $obj.data("action-next")});
                }

                notification.getActive();
            });
        },
        search: function ($obj) {
            log.info("notification:search");

            grid.search($obj);
        },
        show: function ($obj) {
            log.info("notification:show");

            appMgmt.show($obj);
        },
        save: function ($obj) {
            log.info("notification:save");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("route");
            opts.data = $obj.serialize() + "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    $.magnificPopup.close();

                    getContent({r: $obj.data("action-next")}).then(function () {
                        notification.getActive();
                    });
                }
            });
        },
        delete: function ($obj) {
            log.info("notification:delete");

            grid.delete($obj, function (items) {
                const opts = sysPassApp.requests.getRequestOpts();
                opts.method = "get";
                opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                    {
                        r: [$obj.data("action-route"), (items.length === 0 ? $obj.data("item-id") : null)],
                        sk: sysPassApp.sk.get(),
                        isAjax: 1
                    });
                opts.data = {items: items};

                sysPassApp.requests.getActionCall(opts, function (json) {
                    sysPassApp.msg.out(json);

                    getContent({r: $obj.data("action-next")}).then(function () {
                        notification.getActive();
                    });
                });
            });
        },
        getActive: function () {
            log.info("notification:getActive");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.useLoading = false;
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: "items/notifications",
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                const $badge = $(".notifications-badge");
                const $tooltip = $(".notifications-tooltip");

                $badge.each(function () {
                    const $this = $(this);
                    $this.attr("data-badge", json.data.count);

                    if (json.data.count === 0) {
                        $this.removeClass($this.data("color-class"));
                        $tooltip.empty().html(json.data.message);
                    } else {
                        $this.addClass($this.data("color-class"));
                        $tooltip.empty().html(json.data.message_has);
                    }
                });

                if (json.data.count > 0) {
                    sysPassApp.util.sendNotification(
                        json.data.message_has,
                        json.data.notifications.join('\n'),
                        json.data.hash
                    );
                }
            });
        },
        nav: function ($obj) {
            log.info("eventlog:nav");

            grid.nav($obj);
        }
    };

    /**
     * sysPassApp grids actions
     *
     * @type {{search: search, nav: nav, delete: delete, getSelection: getSelection}}
     */
    const grid = {
        search: function ($obj) {
            log.info("grid:search");

            const $target = $($obj.data("target"));
            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, {r: $obj.data("action-route")});
            opts.data = $obj.serialize();

            sysPassApp.requests.getActionCall(opts, function (json) {
                if (json.status === 0) {
                    $target.html(json.data.html);
                } else {
                    $target.html(sysPassApp.msg.html.error(json.description));
                }

                sysPassApp.sk.set(json.csrf);
            });
        },
        nav: function ($obj, callback) {
            log.info("grid:nav");

            const $form = $("#" + $obj.data("action-form"));

            $form.find("[name='start']").val($obj.data("start"));
            $form.find("[name='count']").val($obj.data("count"));
            $form.find("[name='sk']").val(sysPassApp.sk.get());

            if (typeof callback === "function") {
                callback($form);
            } else {
                grid.search($form);
            }
        },
        delete: function ($obj, onAccept) {
            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[12] + "</p></div>";

            const items = grid.getSelection($obj);

            if (items === false) {
                return;
            }

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        if (typeof onAccept === "function") {
                            onAccept(items);
                        }
                    }
                }
            });
        },
        getSelection: function ($obj) {
            const selection = $obj.data("selection");
            const items = [];

            if (selection) {
                $(selection).find(".is-selected").each(function () {
                    items.push($(this).data("item-id"));
                });

                if (items.length === 0) {
                    return false;
                }
            }

            return items;
        }
    };

    const ldap = {
        check: function ($obj) {
            log.info("ldap:check");

            const $form = $($obj.data("src"));
            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = ajaxUrl.entrypoint + '?r=' + $obj.data("action-route");
            opts.data = $form.serialize() + "&sk=" + sysPassApp.sk.get();

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0
                    && json.data['template'] !== undefined
                    && json.data['items'] !== undefined
                ) {
                    showFloatingBox(json.data.template, {
                        open: function () {
                            const $list = $("#ldap-results").find(".list-wrap").empty();

                            json.data.items.forEach(function (value) {
                                $list.append(sysPassApp.theme.html.getList(value.items, value.icon));
                            });
                        }
                    });
                }
            });
        },
        import: function ($obj) {
            log.info("ldap:import");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[57] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        const $form = $($obj.data("src"));
                        const opts = sysPassApp.requests.getRequestOpts();
                        opts.url = ajaxUrl.entrypoint + "?r=" + $obj.data("action-route");
                        opts.data = $form.serialize() + "&sk=" + sysPassApp.sk.get();

                        sysPassApp.requests.getActionCall(opts, function (json) {
                            sysPassApp.msg.out(json);
                        });
                    }
                }
            });
        }
    };

    const accountManager = {
        restore: function ($obj) {
            log.info("accountManager:restore");

            tabs.state.update($obj);

            const itemId = $obj.data("item-id");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                if (json.status === 0) {
                    const actionNext = $obj.data("action-next");

                    if (actionNext) {
                        getContent({r: [actionNext, itemId]});
                    } else {
                        getContent({
                            r: tabs.state.tab.route,
                            tabIndex: tabs.state.tab.index
                        });
                    }
                }
            });
        },
    };

    const task = {
        runner: null,
        run: function (taskId) {
            log.info("task:run");
            log.info("taskId: " + taskId);

            const $taskStatus = $("#taskStatus");
            $taskStatus.css("display", "block");
            $taskStatus.empty().html(sysPassApp.config.LANG[62]);

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint, {r: ["task/trackStatus", taskId]});

            this.runner = sysPassApp.requests.getActionEvent(opts,
                function (result) {
                    const text = `${result.task} - ${result.message} - ${result.time} - ${result.progress}%<br>${sysPassApp.config.LANG[62]}`;

                    log.info(text);

                    $taskStatus.empty().html(text);
                });

            return this.runner;
        },
        end: function () {
            log.info("task:end");

            if (this.runner !== null) {
                log.info('Task ended');

                this.runner.close();

                $("#taskStatus").css("display", "none");
            }
        }
    };

    const track = {
        unlock: function ($obj) {
            log.info("track:unlock");

            const opts = sysPassApp.requests.getRequestOpts();
            opts.method = "get";
            opts.url = sysPassApp.util.getUrl(ajaxUrl.entrypoint,
                {
                    r: [$obj.data("action-route"), $obj.data("item-id")],
                    sk: sysPassApp.sk.get(),
                    isAjax: 1
                });

            sysPassApp.requests.getActionCall(opts, function (json) {
                sysPassApp.msg.out(json);

                tabs.refresh($obj);
            });
        },
        clear: function ($obj) {
            log.info("track:clear");

            const atext = "<div id=\"alert\"><p id=\"alert-text\">" + sysPassApp.config.LANG[71] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: sysPassApp.config.LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        sysPassApp.msg.error(sysPassApp.config.LANG[44]);
                    }
                },
                positive: {
                    title: sysPassApp.config.LANG[43],
                    onClick: function (e) {
                        e.preventDefault();

                        tabs.save($obj);
                    }
                }
            });
        },
        refresh: function ($obj) {
            $('#' + $obj.data("action-form")).submit();
        }
    };

    return {
        getContent: getContent,
        showFloatingBox: showFloatingBox,
        closeFloatingBox: closeFloatingBox,
        appMgmt: appMgmt,
        account: account,
        accountManager: accountManager,
        file: file,
        checks: checks,
        config: configManager,
        main: main,
        user: user,
        link: link,
        eventlog: eventlog,
        ajaxUrl: ajaxUrl,
        plugin: plugin,
        notification: notification,
        wiki: wiki,
        items: items,
        ldap: ldap,
        track: track
    };
};
