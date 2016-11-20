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

sysPass.Actions = function (Common) {
    "use strict";

    var log = Common.log;

    // Variable para almacenar la llamada a setTimeout()
    var timeout;

    // Atributos de la ordenación de búsquedas
    var order = {key: 0, dir: 0};

    // Objeto con las URLs de las acciones
    var ajaxUrl = {
        doAction: "/ajax/ajax_getContent.php",
        updateItems: "/ajax/ajax_getItems.php",
        user: {
            savePreferences: "/ajax/ajax_userPrefsSave.php",
            password: "/ajax/ajax_usrpass.php",
            passreset: "/ajax/ajax_passReset.php"
        },
        main: {
            login: "/ajax/ajax_doLogin.php",
            install: "/ajax/ajax_install.php",
            twofa: "/ajax/ajax_2fa.php",
            getUpdates: "/ajax/ajax_checkUpds.php"
        },
        checks: "/ajax/ajax_checkConnection.php",
        config: {
            save: "/ajax/ajax_configSave.php",
            export: "/ajax/ajax_export.php",
            import: "/ajax/ajax_import.php"
        },
        file: "/ajax/ajax_filesMgmt.php",
        link: "/ajax/ajax_itemSave.php",
        account: {
            save: "/ajax/ajax_itemSave.php",
            showPass: "/ajax/ajax_accViewPass.php",
            saveFavorite: "/ajax/ajax_appMgmtSave.php",
            request: "/ajax/ajax_sendRequest.php",
            getFiles: "/ajax/ajax_accGetFiles.php",
            search: "/ajax/ajax_accSearch.php"
        },
        appMgmt: {
            show: "/ajax/ajax_itemShow.php",
            save: "/ajax/ajax_itemSave.php",
            search: "/ajax/ajax_itemSearch.php"
        },
        eventlog: "/ajax/ajax_eventlog.php",
        wiki: {
            show: "/ajax/ajax_wiki.php"
        }
    };

    // Función para cargar el contenido de la acción del menú seleccionada
    var doAction = function (obj) {
        var data = {
            actionId: obj.actionId,
            itemId: typeof obj.itemId !== "undefined" ? obj.itemId : 0,
            isAjax: 1
        };

        var opts = Common.appRequests().getRequestOpts();
        opts.url = ajaxUrl.doAction;
        opts.type = "html";
        opts.addHistory = true;
        opts.data = data;

        Common.appRequests().getActionCall(opts, function (response) {
            var $content = $("#content");

            $content.empty().html(response);
            // $content.find(":input:text:visible:first").focus();
        });
    };

    /**
     * Actualizar los elemento de un select
     *
     * @param $obj
     */
    var updateItems = function ($obj) {
        log.info("updateItems");

        var $dst = $("#" + $obj.data("item-dst"))[0].selectize;

        $dst.clearOptions();
        $dst.load(function (callback) {
            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.updateItems;
            opts.method = "get";
            opts.data = {sk: Common.sk.get(), itemType: $obj.data("item-type")};

            Common.appRequests().getActionCall(opts, function (json) {
                callback(json.items);
            });
        });
    };

    /**
     * Mostrar el contenido en una caja flotante
     *
     * @param $obj
     * @param response
     */
    var showFloatingBox = function ($obj, response) {
        $.fancybox(response, {
            padding: [0, 0, 0, 0],
            afterClose: function () {
                if ($obj.data("item-dst")) {
                    updateItems($obj);
                }
            },
            beforeShow: function () {
                Common.appTriggers().views.common("#fancyContainer");
            }
        });
    };

    /**
     * Objeto con las acciones de usuario
     *
     * @type {{savePreferences: user.savePreferences, saveSecurity: user.saveSecurity, password: user.password, passreset: user.passreset}}
     */
    var user = {
        savePreferences: function ($obj) {
            log.info("user:savePreferences");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.user.savePreferences;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                setTimeout(function () {
                    window.location.replace("index.php");
                }, 2000);
            });
        },
        saveSecurity: function ($obj) {
            log.info("user:saveSecurity");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.user.savePreferences;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
            });
        },
        password: function ($obj) {
            log.info("user:password");

            var opts = Common.appRequests().getRequestOpts();
            opts.type = "html";
            opts.method = "get";
            opts.url = ajaxUrl.user.password;
            opts.data = {
                actionId: $obj.data("action-id"),
                itemId: $obj.data("item-id"),
                sk: $obj.data("sk"),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (response) {
                if (response.length === 0) {
                    main.logout();
                } else {
                    showFloatingBox($obj, response);
                }
            });
        },
        passreset: function ($obj) {
            log.info("user:passreset");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.user.passreset;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);
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
            var search = window.location.search;
            var url = "";

            if (search.length > 0) {
                url = "index.php" + search + "&logout=1";
            } else {
                url = "index.php?logout=1";
            }

            Common.redirect(url);
        },
        login: function ($obj) {
            log.info("main:login");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.main.login;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                switch (json.status) {
                    case 0:
                        Common.redirect(json.data.url);
                        break;
                    case 2:
                        Common.msg.out(json);

                        $obj.find("input[type='text'],input[type='password']").val("");
                        $obj.find("input:first").focus();

                        $("#mpass").prop("disabled", false);
                        $("#smpass").val("").show();
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

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.main.install;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status == 0) {
                    setTimeout(function () {
                        Common.redirect("index.php");
                    }, 1000);
                }
            });
        },
        twofa: function ($obj) {
            log.info("main:twofa");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.main.twofa;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status == 0) {
                    setTimeout(function () {
                        Common.redirect("index.php");
                    }, 1000);
                }
            });
        },
        getUpdates: function ($obj) {
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

                if (typeof  componentHandler !== "undefined") {
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
    var checks = {
        ldap: function ($obj) {
            log.info("checks:ldap");

            var $form = $($obj.data("src"));
            $form.find("[name='sk']").val(Common.sk.get());

            var opts = Common.appRequests().getRequestOpts();
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

            var $form = $($obj.data("src"));
            $form.find("[name='sk']").val(Common.sk.get());

            var opts = Common.appRequests().getRequestOpts();
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
    var config = {
        save: function ($obj) {
            log.info("config:save");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.config.save;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0 && typeof $obj.data("nextaction-id") !== "undefined") {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        backup: function ($obj) {
            log.info("config:backup");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.config.export;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0 && typeof $obj.data("nextaction-id") !== "undefined") {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        export: function ($obj) {
            log.info("config:export");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.config.export;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0 && typeof $obj.data("nextaction-id") !== "undefined") {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        import: function ($obj) {
            log.info("config:import");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.config.import;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0 && typeof $obj.data("nextaction-id") !== "undefined") {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        }
    };

    /**
     * Objeto con las acciones de los archivos
     *
     * @type {{view: file.view, download: file.download, delete: file.delete}}
     */
    var file = {
        view: function ($obj) {
            log.info("file:view");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.file;
            opts.type = "html";
            opts.data = {fileId: $obj.data("item-id"), sk: Common.sk.get(), actionId: $obj.data("action-id")};

            Common.appRequests().getActionCall(opts, function (response) {
                if (typeof response.status !== "undefined" && response.status === 1) {
                    Common.msg.out(response);
                    return;
                }

                if (response) {
                    showFloatingBox($obj, response);

                    // Actualizar fancybox para adaptarlo al tamaño de la imagen
                    // setTimeout(function () {
                    //     $.fancybox.update();
                    // }, 1000);
                } else {
                    Common.msg.error(Common.config().LANG[14]);
                }
            });
        },
        download: function ($obj) {
            log.info("file:download");

            var data = {fileId: $obj.data("item-id"), sk: Common.sk.get(), actionId: $obj.data("action-id")};

            $.fileDownload(Common.config().APP_ROOT + ajaxUrl.file, {"httpMethod": "POST", "data": data});
        },
        delete: function ($obj) {
            log.info("file:delete");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[15] + "</p></div>";

            alertify
                .okBtn(Common.config().LANG[43])
                .cancelBtn(Common.config().LANG[44])
                .confirm(atext, function (e) {
                    var opts = Common.appRequests().getRequestOpts();
                    opts.url = ajaxUrl.file;
                    opts.data = {
                        fileId: $obj.data("item-id"),
                        actionId: $obj.data("action-id"),
                        sk: Common.sk.get()
                    };

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.msg.out(json);

                        if (json.status === 0) {
                            var $downFiles = $("#list-account-files");

                            account.getfiles($downFiles);
                        }
                    });
                }, function (e) {
                    e.preventDefault();

                    alertify.error(Common.config().LANG[44]);
                });
        }
    };

    /**
     * Objeto para las acciones de los enlaces
     */
    var link = {
        save: function ($obj) {
            log.info("link:save");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.link;
            opts.data = {
                itemId: $obj.data("item-id"),
                actionId: $obj.data("action-id"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            alertify
                .okBtn(Common.config().LANG[40])
                .cancelBtn(Common.config().LANG[41])
                .confirm(Common.config().LANG[48], function (e) {
                    e.preventDefault();

                    opts.data.notify = 1;

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.msg.out(json);
                    });
                }, function (e) {
                    e.preventDefault();

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.msg.out(json);
                    });
                });
        },
        refresh: function ($obj) {
            log.info("link:refresh");

            var data = {
                "itemId": $obj.data("item-id"),
                "actionId": $obj.data("action-id"),
                "sk": Common.sk.get(),
                "activeTab": $obj.data("activetab"),
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.link;
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);
            });
        }
    };

    /**
     * Objeto con acciones para las cuentas
     *
     * @type {{show: account.show, showHistory: account.showHistory, edit: account.edit, delete: account.delete, showpass: account.showpass, copypass: account.copypass, copy: account.copy, favorite: account.savefavorite, request: account.request, menu: account.menu, sort: account.sort, editpass: account.editpass, restore: account.restore, getfiles: account.getfiles, search: account.search, save: account.save}}
     */
    var account = {
        show: function ($obj) {
            log.info("account:show");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")});
        },
        showHistory: function ($obj) {
            log.info("account:showHistory");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.val()});
        },
        edit: function ($obj) {
            log.info("account:edit");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")});
        },
        delete: function ($obj) {
            log.info("account:delete");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[3] + "</p></div>";

            alertify
                .okBtn(Common.config().LANG[43])
                .cancelBtn(Common.config().LANG[44])
                .confirm(atext, function (e) {
                    var opts = Common.appRequests().getRequestOpts();
                    opts.url = ajaxUrl.account.save;
                    opts.data = {
                        itemId: $obj.data("item-id"),
                        actionId: $obj.data("action-id"),
                        sk: Common.sk.get()
                    };

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.msg.out(json);

                        account.search();
                    });
                }, function (e) {
                    e.preventDefault();

                    alertify.error(Common.config().LANG[44]);
                });
        },
        // Ver la clave de una cuenta
        showpass: function ($obj) {
            log.info("account:showpass");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.showPass;
            opts.data = {
                itemId: $obj.data("item-id"),
                isHistory: $obj.data("history"),
                isFull: $obj.data("full"),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 10) {
                    main.logout();
                    return;
                }

                var $dialog;

                $("<div></div>").dialog({
                    modal: true,
                    title: Common.config().LANG[47],
                    width: "auto",
                    open: function () {
                        $dialog = $(this);

                        var content;
                        var pass = "";
                        var clipboardUserButton =
                            "<button class=\"dialog-clip-user-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary\" data-clipboard-target=\".dialog-user-text\">" +
                            "<span class=\"ui-button-icon-primary ui-icon ui-icon-clipboard\"></span>" +
                            "<span class=\"ui-button-text\">" + Common.config().LANG[33] + "</span>" +
                            "</button>";
                        var clipboardPassButton =
                            "<button class=\"dialog-clip-pass-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary\" data-clipboard-target=\".dialog-pass-text\">" +
                            "<span class=\"ui-button-icon-primary ui-icon ui-icon-clipboard\"></span>" +
                            "<span class=\"ui-button-text\">" + Common.config().LANG[34] + "</span>" +
                            "</button>";
                        var useImage = json.useimage;
                        var user = "<p class=\"dialog-user-text\">" + json.acclogin + "</p>";

                        if (json.status === 0) {
                            if (useImage === 0) {
                                pass = "<p class=\"dialog-pass-text\">" + json.accpass + "</p>";
                            } else {
                                pass = "<img class=\"dialog-pass-text\" src=\"data:image/png;base64," + json.accpass + "\" />";
                                clipboardPassButton = "";
                            }

                            content = user + pass + "<div class=\"dialog-buttons\">" + clipboardUserButton + clipboardPassButton + "</div>";
                        } else {
                            content = "<span class=\"altTxtRed\">" + json.description + "</span>";

                            $dialog.dialog("option", "buttons",
                                [{
                                    text: "Ok",
                                    icons: {primary: "ui-icon-close"},
                                    click: function () {
                                        $dialog.dialog("close");
                                    }
                                }]
                            );
                        }

                        $dialog.html(content);

                        // Recentrar después de insertar el contenido
                        $dialog.dialog("option", "position", "center");

                        // Cerrar Dialog a los 30s
                        $dialog.parent().on("mouseleave", function () {
                            clearTimeout(timeout);
                            timeout = setTimeout(function () {
                                $dialog.dialog("close");
                            }, 30000);
                        });
                    },
                    // Forzar la eliminación del objeto para que siga copiando al protapapeles al abrirlo de nuevo
                    close: function () {
                        clearTimeout(timeout);
                        $dialog.dialog("destroy");
                    }
                });
            });
        },
        copypass: function ($obj) {
            log.info("account:copypass");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.showPass;
            opts.async = false;
            opts.data = {
                itemId: $obj.data("item-id"),
                isHistory: $obj.data("history"),
                isAjax: 1
            };

            return Common.appRequests().getActionCall(opts);
        },
        copy: function ($obj) {
            log.info("account:copy");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")});
        },
        savefavorite: function ($obj, callback) {
            log.info("account:saveFavorite");

            var isOn = $obj.data("status") === "on";

            var data = {
                actionId: isOn ? $obj.data("action-id-off") : $obj.data("action-id-on"),
                itemId: $obj.data("item-id"),
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
        editpass: function ($obj) {
            log.info("account:editpass");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")});
        },
        restore: function ($obj) {
            log.info("account:restore");

            account.save($obj);
        },
        getfiles: function ($obj) {
            log.info("account:getfiles");

            var opts = Common.appRequests().getRequestOpts();
            opts.method = "get";
            opts.type = "html";
            opts.url = ajaxUrl.account.getFiles;
            opts.data = {id: $obj.data("item-id"), del: $obj.data("delete"), sk: Common.sk.get()};

            Common.appRequests().getActionCall(opts, function (response) {
                $obj.html(response);
            });
        },
        search: function () {
            log.info("account:search");

            var $frmSearch = $("#frmSearch");
            $frmSearch.find("input[name='sk']").val(Common.sk.get());

            order.key = $frmSearch.find("input[name='skey']").val();
            order.dir = $frmSearch.find("input[name='sorder']").val();

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.search;
            opts.data = $frmSearch.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 10) {
                    Common.msg.out(json);
                }

                Common.sk.set(json.sk);

                $("#res-content").empty().html(json.html);
                $frmSearch.find("input:first").focus();
            });
        },
        save: function ($obj) {
            log.info("account:save");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.save;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);
            });
        }
    };

    /**
     * Objeto con acciones sobre elementos de la aplicación
     *
     * @type {{show: appMgmt.show, delete: appMgmt.delete, save: appMgmt.save, search: appMgmt.search, nav: appMgmt.nav}}
     */
    var appMgmt = {
        refreshTab: true,
        show: function ($obj) {
            log.info("appMgmt:show");

            if ($obj.data("item-dst")) {
                appMgmt.refreshTab = false;
            }

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.show;
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
                    showFloatingBox($obj, json.data.html);
                }
            });
        },
        delete: function ($obj) {
            log.info("appMgmt:delete");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[12] + "</p></div>";

            alertify
                .okBtn(Common.config().LANG[43])
                .cancelBtn(Common.config().LANG[44])
                .confirm(atext, function (e) {
                    e.preventDefault();

                    var opts = Common.appRequests().getRequestOpts();
                    opts.url = ajaxUrl.appMgmt.save;
                    opts.data = {
                        itemId: $obj.data("item-id"),
                        actionId: $obj.data("action-id"),
                        sk: Common.sk.get(),
                        isAjax: 1
                    };

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.msg.out(json);

                        if ($obj.data("nextaction-id")) {
                            doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                        }
                    });
                }, function (e) {
                    e.preventDefault();

                    Common.msg.error(Common.config().LANG[44]);
                });
        },
        save: function ($obj) {
            log.info("appMgmt:save");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.save;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    if (appMgmt.refreshTab === true) {
                        doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                    }

                    appMgmt.refreshTab = true;

                    $.fancybox.close();
                }
            });
        },
        search: function ($obj) {
            log.info("appMgmt:search");

            var $target = $($obj.data("target"));
            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.search;
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

            var $form = $("#" + $obj.data("action-form"));

            $form.find("[name='start']").val($obj.data("start"));
            $form.find("[name='count']").val($obj.data("count"));
            $form.find("[name='sk']").val(Common.sk.get());

            appMgmt.search($form);
        },
        ldapSync: function ($obj) {
            log.info("appMgmt:ldapSync");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[57] + "</p></div>";

            alertify
                .okBtn(Common.config().LANG[43])
                .cancelBtn(Common.config().LANG[44])
                .confirm(atext, function (e) {
                    var opts = Common.appRequests().getRequestOpts();
                    opts.url = ajaxUrl.appMgmt.save;
                    opts.data = {
                        actionId: $obj.data("action-id"),
                        sk: Common.sk.get(),
                        isAjax: 1
                    };

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.msg.out(json);
                    });
                }, function (e) {
                    e.preventDefault();

                    alertify.error(Common.config().LANG[44]);
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
            if (typeof $obj.data("start") === "undefined") {
                return false;
            }

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.eventlog;
            opts.type = "html";
            opts.data = {start: $obj.data("start"), current: $obj.data("current")};

            Common.appRequests().getActionCall(opts, function (response) {
                $("#content").html(response);
                Common.scrollUp();
            });
        },
        clear: function ($obj) {
            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[20] + "</p></div>";

            alertify
                .okBtn(Common.config().LANG[43])
                .cancelBtn(Common.config().LANG[44])
                .confirm(atext, function (e) {
                    e.preventDefault();

                    var opts = Common.appRequests().getRequestOpts();
                    opts.url = ajaxUrl.eventlog;
                    opts.data = {clear: 1, sk: Common.sk.get(), isAjax: 1};

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.msg.out(json);

                        if (json.status == 0) {
                            doAction({actionId: $obj.data("nextaction-id")});
                        }
                    });
                }, function (e) {
                    e.preventDefault();

                    Common.msg.error(Common.config().LANG[44]);
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
            var opts = Common.appRequests().getRequestOpts();
            opts.type = "html";
            opts.url = ajaxUrl.wiki;
            opts.data = {
                pageName: $obj.data("pageName"),
                actionId: $obj.data("actionId"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (response) {
                showFloatingBox($obj, response);
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
        ajaxUrl: ajaxUrl
    };
}
;
