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

    var log = Common.log;

    // Variable para almacenar la llamada a setTimeout()
    var timeout = 0;

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
            upgrade: "/ajax/ajax_upgrade.php",
            getUpdates: "/ajax/ajax_checkUpds.php",
            task: "/ajax/ajax_task.php"
        },
        checks: "/ajax/ajax_checkConnection.php",
        config: {
            save: "/ajax/ajax_configSave.php",
            export: "/ajax/ajax_configSave.php",
            import: "/ajax/ajax_configSave.php"
        },
        file: "/ajax/ajax_filesMgmt.php",
        link: "/ajax/ajax_itemSave.php",
        plugin: "/ajax/ajax_itemSave.php",
        account: {
            save: "/ajax/ajax_itemSave.php",
            saveFavorite: "/ajax/ajax_itemSave.php",
            request: "/ajax/ajax_itemSave.php",
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
        },
        notice: {
            show: "/ajax/ajax_noticeShow.php",
            search: "/ajax/ajax_noticeSearch.php"
        }
    };

    Object.freeze(ajaxUrl);

    // Función para cargar el contenido de la acción del menú seleccionada
    var doAction = function (obj, view) {
        var data = {
            actionId: obj.actionId,
            itemId: obj.itemId !== "undefined" ? obj.itemId : 0,
            isAjax: 1
        };

        var opts = Common.appRequests().getRequestOpts();
        opts.url = ajaxUrl.doAction;
        opts.method = "get";
        opts.type = "html";
        opts.addHistory = true;
        opts.data = data;

        Common.appRequests().getActionCall(opts, function (response) {
            var $content = $("#content");

            $content.empty().html(response);

            var views = sysPassApp.triggers().views;
            views.common($content);

            if (view !== undefined && typeof views[view] === "function") {
                views[view]();
            }

            var $mdlContent = $(".mdl-layout__content");

            if ($mdlContent.scrollTop() > 0) {
                $mdlContent.animate({scrollTop: 0}, 1000);
            }
        });
    };

    /**
     * Mostrar el contenido en una caja flotante
     *
     * @param $obj
     * @param response
     */
    var showFloatingBox = function ($obj, response) {
        $.magnificPopup.open({
            items: {
                src: response,
                type: "inline"
            },
            callbacks: {
                open: function () {
                    var $boxPopup = $("#box-popup");

                    Common.appTriggers().views.common($boxPopup);

                    $boxPopup.find(":input:text:visible:first").focus();
                },
                close: function () {
                    if ($obj.data("item-dst")) {
                        items.update($obj);
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
    var showImageBox = function ($obj, response) {
        var $content = $("<div id=\"box-popup\" class=\"image\">" + response + "</div>");
        var $image = $content.find("img");

        if ($image.length === 0) {
            return showFloatingBox($obj, response);
        }

        $image.hide();

        $.magnificPopup.open({
            items: {
                src: $content,
                type: "inline"
            },
            callbacks: {
                open: function () {
                    var $popup = this;

                    $image.on("click", function () {
                        $popup.close();
                    });

                    setTimeout(function () {
                        var image = Common.resizeImage($image);

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
    var closeFloatingBox = function () {
        $.magnificPopup.close();
    };

    /**
     * Actualizar los elemento de un select
     *
     * @param $obj
     */
    var items = {
        get: function ($obj) {
            log.info("items:get");

            var $dst = $obj[0].selectize;
            $dst.clearOptions();
            $dst.load(function (callback) {
                var opts = Common.appRequests().getRequestOpts();
                opts.url = ajaxUrl.updateItems;
                opts.method = "get";
                opts.cache = true;
                opts.data = {sk: $obj.data("sk"), itemType: $obj.data("item-type"), itemId: $obj.data("item-id")};

                Common.appRequests().getActionCall(opts, function (json) {
                    callback(json.data);

                    $dst.setValue($obj.data("selected-id"), true);

                    Common.appTriggers().updateFormHash();
                });
            });
        },
        update: function ($obj) {
            log.info("items:update");

            var $dst = $("#" + $obj.data("item-dst"))[0].selectize;

            $dst.clearOptions();
            $dst.load(function (callback) {
                var opts = Common.appRequests().getRequestOpts();
                opts.url = ajaxUrl.updateItems;
                opts.method = "get";
                opts.data = {sk: Common.sk.get(), itemType: $obj.data("item-type")};

                Common.appRequests().getActionCall(opts, function (json) {
                    callback(json.data);
                });
            });
        }
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
                    Common.redirect("index.php");
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
                sk: Common.sk.get(),
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
            opts.method = "get";
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                var $extra = $(".extra-hidden");

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

            if ($obj.data("type") === "masterpass") {
                opts.useFullLoading = true;
            }

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    if ($obj.data("nextaction-id") !== undefined) {
                        doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                    } else if ($obj.data("reload") !== undefined) {
                        setTimeout(function () {
                            Common.redirect("index.php");
                        }, 2000);
                    }
                }
            });
        },
        masterpass: function ($obj) {
            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[59] + "</p></div>";

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

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.config.export;
            opts.method = "post";
            opts.useFullLoading = true;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0 && $obj.data("nextaction-id") !== undefined) {
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

                if (json.status === 0 && $obj.data("nextaction-id") !== undefined) {
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

                if (json.status === 0 && $obj.data("nextaction-id") !== undefined) {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
            });
        },
        refreshMpass: function ($obj) {
            log.info("config:import");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.config.save;
            opts.data = {
                actionId: $obj.data("action-id"),
                itemId: $obj.data("item-id"),
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
                if (response.status !== undefined && response.status === 1) {
                    Common.msg.out(response);
                    return;
                }

                if (response) {
                    showImageBox($obj, response);
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
                    }
                }
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

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[48] + "</p></div>";

            mdlDialog().show({
                text: atext,
                negative: {
                    title: Common.config().LANG[44],
                    onClick: function (e) {
                        e.preventDefault();

                        Common.appRequests().getActionCall(opts, function (json) {
                            Common.msg.out(json);
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

                            doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("item-id")});
                        });
                    }
                }
            });
        },
        refresh: function ($obj) {
            log.info("link:refresh");

            var data = {
                "itemId": $obj.data("item-id"),
                "actionId": $obj.data("action-id"),
                "sk": Common.sk.get(),
                "activeTab": $obj.data("activetab")
            };

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.link;
            opts.data = data;

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.status === 0) {
                    doAction({actionId: $obj.data("nextaction-id"), itemId: $obj.data("activetab")});
                }
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

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")}, "account");
        },
        showHistory: function ($obj) {
            log.info("account:showHistory");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.val()}, "account");
        },
        edit: function ($obj) {
            log.info("account:edit");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")}, "account");
        },
        delete: function ($obj) {
            log.info("account:delete");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[3] + "</p></div>";

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
                    }
                }
            });
        },
        // Ver la clave de una cuenta
        showpass: function ($obj) {
            log.info("account:showpass");

            var parentId = $obj.data("parent-id");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.show;
            opts.method = "get";
            opts.data = {
                itemId: parentId == 0 ? $obj.data("item-id") : parentId,
                actionId: $obj.data("action-id"),
                isHistory: $obj.data("history"),
                isLinked: parentId > 0 ? 1 : 0,
                isFull: 1,
                sk: Common.sk.get(),
                isAjax: 1
            };

            Common.appRequests().getActionCall(opts, function (json) {
                if (json.status !== 0) {
                    Common.msg.out(json);
                } else {
                    var $container = $(json.data.html);

                    showFloatingBox($obj, $container);

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
        copypass: function ($obj) {
            log.info("account:copypass");

            var parentId = $obj.data("parent-id");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.show;
            opts.method = "get";
            opts.async = false;
            opts.data = {
                itemId: parentId == 0 ? $obj.data("item-id") : parentId,
                actionId: $obj.data("action-id"),
                isHistory: $obj.data("history"),
                isFull: 0,
                sk: Common.sk.get(),
                isAjax: 1
            };

            return Common.appRequests().getActionCall(opts);
        },
        copy: function ($obj) {
            log.info("account:copy");

            doAction({actionId: $obj.data("action-id"), itemId: $obj.data("item-id")}, "account");
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

            var parentId = $obj.data("parent-id");

            doAction({
                actionId: $obj.data("action-id"),
                itemId: parentId == 0 ? $obj.data("item-id") : parentId
            }, "account");
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

                Common.sk.set(json.sk);

                $("#res-content").empty().html(json.html);
            });
        },
        save: function ($obj) {
            log.info("account:save");

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.account.save;
            opts.data = $obj.serialize();

            Common.appRequests().getActionCall(opts, function (json) {
                Common.msg.out(json);

                if (json.data.itemId !== undefined && json.data.nextActionId !== undefined) {
                    doAction({actionId: json.data.nextActionId, itemId: json.data.itemId}, "account");
                }
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

            if ($obj.data("item-dst") || $obj.data("activetab") === undefined) {
                appMgmt.refreshTab = false;
            }

            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.show;
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
                    showFloatingBox($obj, json.data.html);
                }
            });
        },
        delete: function ($obj) {
            log.info("appMgmt:delete");

            var atext = "<div id=\"alert\"><p id=\"alert-text\">" + Common.config().LANG[12] + "</p></div>";
            var selection = $obj.data("selection");
            var items = [];

            if (selection) {
                $(selection).find(".is-selected").each(function (index, value) {
                    var $this = $(this);

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

                        var opts = Common.appRequests().getRequestOpts();
                        opts.url = ajaxUrl.appMgmt.save;
                        opts.data = {
                            itemId: selection ? items : $obj.data("item-id"),
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
                    }
                }
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
                    var activeTab = $obj.data("activetab");

                    if (appMgmt.refreshTab === true && activeTab !== undefined) {
                        doAction({actionId: $obj.data("nextaction-id"), itemId: activeTab});
                    }

                    $.magnificPopup.close();
                }
            });
        },
        search: function ($obj) {
            log.info("appMgmt:search");

            var $target = $($obj.data("target"));
            var opts = Common.appRequests().getRequestOpts();
            opts.url = ajaxUrl.appMgmt.search;
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

            var $form = $("#" + $obj.data("action-form"));

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
                    showFloatingBox($obj, json.data.html);
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
                    showFloatingBox($obj, json.data.html);
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
