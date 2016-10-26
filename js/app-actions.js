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

    // Función para cargar el contenido de la acción del menú seleccionada
    var doAction = function (obj) {
        var data = {
            actionId: obj.actionId,
            itemId: typeof obj.itemId !== "undefined" ? obj.itemId : 0,
            isAjax: 1
        };

        var opts = Common.appRequests().getRequestOpts();
        opts.url = "/ajax/ajax_getContent.php";
        opts.type = "html";
        opts.addHistory = true;
        opts.data = data;

        Common.appRequests().getActionCall(opts, function (response) {
            $("#content").html(response);
            Common.setContentSize();
        });
    };

    /**
     * Actualizar los elemento de un select
     *
     * @param $obj
     */
    var updateItems = function ($obj) {
        var $dst = $("#" + $obj.data("item-dst"))[0].selectize;

        $dst.clearOptions();
        $dst.load(function (callback) {
            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_getItems.php";
            opts.method = "get";
            opts.data = {sk: Common.sk.get(), itemType: $obj.data("item-type")};

            Common.appRequests().getActionCall(opts, function (json) {
                callback(json.items);
            });
        });
    };

    var user = {
        savePreferences: function ($obj) {
            log.info("user:savePreferences");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_userPrefsSave.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);

                setTimeout(function () {
                    window.location.replace("index.php");
                }, 2000);
            });
        },
        saveSecurity: function ($obj) {
            log.info("user:saveSecurity");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_userPrefsSave.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);
            });
        },
        password: function ($obj) {
            log.info("user:password");

            var opts = Common.appRequests().getRequestOpts();
            opts.type = "html";
            opts.method = "get";
            opts.url = "/ajax/ajax_usrpass.php";
            opts.data = {
                actionId: $obj.data("action-id"),
                userId: $obj.data("item-id"),
                sk: $obj.data("sk"),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (response) {
                if (response.length === 0) {
                    main.logout();
                } else {
                    $.fancybox(response, {padding: 0});
                }
            });
        }
    };

    /**
     * Objeto con las acciones principales
     *
     * @type {{logout: main.logout, login: main.login}}
     */
    var main = {
        logout: function () {
            var search = window.location.search;

            if (search.length > 0) {
                window.location.replace("index.php" + search + "&logout=1");
            } else {
                window.location.replace("index.php?logout=1");
            }
        },
        login: function ($obj) {
            log.info("main:login");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_doLogin.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                switch (json.status) {
                    case 0:
                        window.location.replace(json.data.url);
                        break;
                    case 2:
                        Common.resMsg("error", json.description);

                        $obj.find("input[type='text'],input[type='password']").val("");
                        $obj.find("input:first").focus();

                        $("#mpass").prop("disabled", false);
                        $("#smpass").val("").show();
                        break;
                    default:
                        Common.resMsg("error", json.description);

                        $obj.find("input[type='text'],input[type='password']").val("");
                        $obj.find("input:first").focus();
                }
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

            var ldapBindPass = $form.find("[name='ldap_bindpass']").val();

            var data = {
                type: "ldap",
                ldap_server: $form.find("[name='ldap_server']").val(),
                ldap_base: $form.find("[name='ldap_base']").val(),
                ldap_group: $form.find("[name='ldap_group']").val(),
                ldap_binduser: $form.find("[name='ldap_binduser']").val(),
                ldap_bindpass: Common.config().PK !== "" ? Common.config().crypt.encrypt(ldapBindPass) : ldapBindPass,
                sk: Common.sk.get(),
                isAjax: 1
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_checkConnection.php";
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);
            });

        },
        wiki: function ($obj) {
            log.info("checks:wiki");

            var $form = $($obj.data("src"));

            var data = {
                type: "dokuwiki",
                dokuwiki_url: $form.find("[name='dokuwiki_url']").val(),
                dokuwiki_user: $form.find("[name='dokuwiki_user']").val(),
                dokuwiki_pass: $form.find("[name='dokuwiki_pass']").val(),
                isAjax: 1,
                sk: Common.sk.get()
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_checkConnection.php";
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 1) {
                    Common.resMsg("error", json.description);
                } else if (json.status === 0) {
                    Common.resMsg("ok", json.description);

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
            opts.url = "/ajax/ajax_configSave.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);

                if (json.status === 0 && typeof $obj.data("nextaction-id") !== "undefined") {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        backup: function ($obj) {
            log.info("config:backup");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_export.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);

                if (json.status === 0 && typeof $obj.data("nextaction-id") !== "undefined") {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        export: function ($obj) {
            log.info("config:export");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_export.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);

                if (json.status === 0 && typeof $obj.data("nextaction-id") !== "undefined") {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        import: function ($obj) {
            log.info("config:import");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_import.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);

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
            opts.url = "/ajax/ajax_files.php";
            opts.type = "html";
            opts.data = {fileId: $obj.data("item-id"), sk: Common.sk.get(), actionId: $obj.data("action-id")};

            Common.appRequests().getActionCall(opts, function (json) {
                if (typeof json.status !== "undefined" && json.status === 1) {
                    Common.resMsg("error", json.description);
                    return;
                }

                if (json) {
                    $.fancybox(json, {padding: [10, 10, 10, 10]});
                    // Actualizar fancybox para adaptarlo al tamaño de la imagen
                    setTimeout(function () {
                        $.fancybox.update();
                    }, 1000);
                } else {
                    Common.resMsg("error", Common.config().LANG[14]);
                }
            });
        },
        download: function ($obj) {
            log.info("file:download");

            var data = {fileId: $obj.data("item-id"), sk: Common.sk.get(), actionId: $obj.data("action-id")};

            $.fileDownload(Common.config().APP_ROOT + "/ajax/ajax_files.php", {"httpMethod": "POST", "data": data});
        },
        delete: function ($obj) {
            log.info("file:delete");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[15] + "</p></div>";

            alertify
                .okBtn(Common.config().LANG[43])
                .cancelBtn(Common.config().LANG[44])
                .confirm(atext, function (e) {
                    var opts = Common.appRequests().getRequestOpts();
                    opts.url = "/ajax/ajax_files.php";
                    opts.data = {
                        fileId: $obj.data("item-id"),
                        actionId: $obj.data("action-id"),
                        sk: Common.sk.get()
                    };

                    Common.appRequests().getActionCall(opts, function (json) {
                        if (json.status === 0) {
                            var $downFiles = $("#list-account-files");

                            account.getfiles($downFiles);

                            Common.resMsg("ok", json.description);
                        } else {
                            Common.resMsg("error", json.description);
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
        showHistory: function (obj) {
            log.info("account:showHistory");

            doAction({actionId: obj["action-id"], itemId: obj["item-id"]});
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
                    opts.url = "/ajax/ajax_accountSave.php";
                    opts.data = {
                        accountid: $obj.data("item-id"),
                        actionId: $obj.data("action-id"),
                        sk: Common.sk.get()
                    };

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.jsonResponseMessage(json);
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
            opts.url = "/ajax/ajax_accViewPass.php";
            opts.data = {
                accountid: $obj.data("item-id"),
                isHistory: $obj.data("history"),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 10) {
                    doLogout();
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
            opts.url = "/ajax/ajax_accViewPass.php";
            opts.async = false;
            opts.data = {
                accountid: $obj.data("item-id"),
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
                accountId: $obj.data("item-id"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_accFavorites.php";
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 0) {
                    $obj.data("status", isOn ? "off" : "on");

                    if (typeof callback === "function") {
                        callback();
                    }

                    Common.resMsg("ok", json.description);
                } else if (json.status === 1) {
                    Common.resMsg("error", json.description);
                }
            });
        },
        request: function ($obj) {
            log.info("account:request");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_sendRequest.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);
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

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")});
        },
        getfiles: function ($obj) {
            log.info("account:getfiles");

            var opts = Common.appRequests().getRequestOpts();
            opts.method = "get";
            opts.type = "html";
            opts.url = "/ajax/ajax_accGetFiles.php";
            opts.data = {id: $obj.data("item-id"), del: $obj.data("delete"), sk: Common.sk.get()};

            Common.appRequests().getActionCall(opts, function (response) {
                $obj.html(response);
            });
        },
        search: function (clear) {
            log.info("account:search");

            var $frmSearch = $("#frmSearch");

            if (clear === true) {
                // document.frmSearch.search.value = "";

                $frmSearch.find("select").each(function () {
                    $(this)[0].selectize.clear();
                });

                $frmSearch.find("input[name=\"search\"]").val("");
                $frmSearch.find("input[name=\"start\"], input[name=\"skey\"], input[name=\"sorder\"]").val(0);
                $frmSearch.find("input[name=\"searchfav\"]").val(0).change();
                order.key = 0;
                order.dir = 0;
            }

            // $frmSearch.find("input[name=\"start\"]").val(0);

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_accSearch.php";
            opts.data = $frmSearch.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                if (typeof json.sk !== "undefined") {
                    Common.sk.set(json.sk);

                    $frmSearch.find("input[name=\"sk\"]").val(json.sk);
                }

                $("#res-content").html(json.html);
            });
        },
        save: function ($obj) {
            log.info("account:save");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_accSave.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);
            });
        }
    };

    /**
     * Objeto con acciones sobre elementos de la aplicación
     */
    var appMgmt = {
        show: function ($obj) {
            log.info("appMgmt:show");

            var opts = Common.appRequests().getRequestOpts();
            opts.type = "html";
            opts.url = "/ajax/ajax_appMgmtData.php";
            opts.data = {
                itemId: $obj.data("item-id"),
                actionId: $obj.data("action-id"),
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (response) {
                $.fancybox(response, {
                    padding: [0, 10, 10, 10],
                    afterClose: function () {
                        if ($obj.data("action-dst")) {
                            updateItems($obj);
                        }
                    },
                    beforeShow: function () {
                        Common.appTriggers().views.common("#fancyContainer");
                    }
                });
            });
        },
        delete: function ($obj) {
            log.info("appMgmt:delete");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[12] + "</p></div>";

            alertify
                .okBtn(Common.config().LANG[43])
                .cancelBtn(Common.config().LANG[44])
                .confirm(atext, function (e) {
                    var opts = Common.appRequests().getRequestOpts();
                    opts.url = "/ajax/ajax_appMgmtSave.php";
                    opts.data = {
                        itemId: $obj.data("item-id"),
                        actionId: $obj.data("action-id"),
                        sk: Common.sk.get(),
                        isAjax: 1
                    };

                    Common.appRequests().getActionCall(opts, function (json) {
                        Common.jsonResponseMessage(json);

                        if ($obj.data("nextaction-id")) {
                            doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                        }
                    });
                }, function (e) {
                    e.preventDefault();

                    alertify.error(Common.config().LANG[44]);
                });
        },
        save: function ($obj) {
            log.info("appMgmt:save");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_appMgmtSave.php";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.jsonResponseMessage(json);

                if ($obj.data("nextaction-id")) {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        search: function (form) {
            log.info("appMgmt:search");

            var $form = $(form);
            var targetId = $form.find("[name=target]").val();

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_appMgmtSearch.php";
            opts.data = $form.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status === 0) {
                    $("#" + targetId).html(json.html);
                    $form.find("[name='sk']").val(json.sk);
                } else {
                    $("#" + targetId).html(Common.resMsg("nofancyerror", json.description));
                }
            });

            return false;
        },
        nav: function ($obj) {
            log.info("appMgmt:nav");

            var $form = $("#" + $obj.data("action-form"));

            var opts = Common.appRequests().getRequestOpts();
            opts.url = "/ajax/ajax_appMgmtSearch.php";
            opts.data = $form.serialize() + "&start=" + $obj.data("start") + "&count=" + $obj.data("count");

            Common.getActionCall(opts, function (json) {
                var $target = $("#" + $form.find("[name=target]").val());

                if (json.status === 0) {
                    $target.html(json.html);
                    $form.find("[name='sk']").val(json.sk);
                } else {
                    $target.html(Common.resMsg("nofancyerror", json.description));
                }
            });

            return false;
        },
        userpass: function ($obj) {
            log.info("appMgmt:userpass");

            var opts = Common.appRequests().getRequestOpts();
            opts.type = "html";
            opts.method = "get";
            opts.url = "/ajax/ajax_usrpass.php";
            opts.data = {
                actionId: $obj.data("action-id"),
                userId: $obj.data("item-id"),
                sk: $obj.data("sk"),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (response) {
                if (response.length === 0) {
                    main.logout();
                } else {
                    $.fancybox(response, {padding: 0});
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
        user: user
    };
}
;
