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

sysPass.Main = function () {
    "use strict";

    // Configuración de atributos generales
    var config = {
        APP_ROOT: "", // Base para llamadas AJAX
        LANG: [], // Array de lenguaje
        PK: "", // Clave pública
        MAX_FILE_SIZE: 1024, // Máximo tamaño de archivo
        crypt: new JSEncrypt(), // Inicializar la encriptación RSA
        CHECK_UPDATES: false // Comprobar actualizaciones
    };

    // Variable para determinar si una clave de cuenta ha sido copiada al portapapeles
    var passToClip = 0;

    // Atributos del generador de claves
    var passwordData = {
        passLength: 0,
        minPasswordLength: 8,
        complexity: {
            numbers: true,
            symbols: true,
            uppercase: true,
            numlength: 12
        }
    };

    // Objeto con las funciones propias del tema visual
    var appTheme = {};

    // Objeto con los triggers de la aplicación
    var appTriggers = {};

    // Objeto con las acciones de la aplicación
    var appActions = {};

    // Objeto con las funciones para peticiones de la aplicación
    var appRequests = {};

    // Objeto con las propiedades públicas
    var oPublic = {};

    // Objeto con las propiedades protegidas
    var oProtected = {};

    // Logging
    var log = {
        log: function (msg) {
            console.log(msg);
        },
        info: function (msg) {
            console.info(msg);
        },
        error: function (msg) {
            console.error(msg);
        },
        warn: function (msg) {
            console.warn(msg);
        }
    };

    /**
     * Retrollamadas de los elementos
     */
    var setupCallbacks = function () {
        log.info("setupCallbacks");

        if ($("#boxLogin").length > 0) {
            appTriggers.views.login();
        }

        if ($("#searchbox").length > 0) {
            appTriggers.views.search();
        }

        if ($("footer").length > 0) {
            appTriggers.views.footer();
        }
    };

    /**
     * Respuesta en formato json para mostrar mensaje
     *
     * @param json
     * @param callback
     */
    var jsonResponseMessage = function (json, callback) {
        var status = json.status;
        var description = json.description;
        // var action = json.action;

        if (typeof json.messages !== "undefined" && json.messages.length > 0) {
            description = description + "<br>" + json.messages.join("<br>");
        }

        //$.fancybox.close();
        var $alertify = alertify
            .logPosition("bottom right")
            .closeLogOnClick(true)
            .delay(10000);

        switch (status) {
            case 0:
                $alertify.success(description, callback);
                break;
            case 1:
            case 2:
                $alertify.error(description, callback);
                break;
            case 3:
                $alertify.warn(description, callback);
                break;
            case 10:
                appActions.main.logout();
                break;
            default:
                return;
        }
    };

    /**
     * Inicialización
     */
    var init = function () {
        log.info("init");

        oPublic = getPublic();
        oProtected = getProtected();

        appTriggers = sysPass.Triggers(oProtected);
        appActions = sysPass.Actions(oProtected);
        appRequests = sysPass.Requests(oProtected);

        getEnvironment(function () {
            if (config.PK !== "") {
                bindPassEncrypt();
            }

            if (typeof sysPass.Theme === "function") {
                appTheme = sysPass.Theme(oProtected);
            }

            if (config.CHECK_UPDATES === true) {
                checkUpds();
            }

            initializeClipboard();
            setupCallbacks();
        });
    };

    /**
     * Obtener las variables de entorno de sysPass
     */
    var getEnvironment = function (callback) {
        log.info("getEnvironment");

        var path = window.location.pathname.split("/");
        var rootPath = function () {
            var fullPath = "";

            for (var i = 1; i <= path.length - 2; i++) {
                fullPath += "/" + path[i];
            }

            return fullPath;
        };
        var base = window.location.protocol + "//" + window.location.host + rootPath();

        var opts = appRequests.getRequestOpts();
        opts.url = base + "/ajax/ajax_getEnvironment.php";
        opts.method = "get";
        opts.async = false;
        opts.data = {isAjax: 1};

        appRequests.getActionCall(opts, function (json) {
            config.APP_ROOT = json.app_root;
            config.LANG = json.lang;
            config.PK = json.pk;
            config.CHECK_UPDATES = json.check_updates;
            config.crypt.setPublicKey(json.pk);

            if (typeof callback === "function") {
                callback();
            }
        });
    };

    // Objeto para leer/escribir el token de seguridad
    var sk = {
        get: function () {
            log.info("sk:get");
            return $("#content").attr("data-sk");
        },
        set: function (sk) {
            log.info("sk:set");
            $("#content").attr("data-sk", sk);
        }
    };

    // Función para establecer la altura del contenedor ajax
    var setContentSize = function () {
        var $container = $("#container");

        if ($container.hasClass("content-no-auto-resize")) {
            return;
        }

        //console.info($("#content").height());

        // Calculate total height for full body resize
        var totalHeight = $("#content").height() + 200;
        //var totalWidth = $("#wrap").width();

        $container.css("height", totalHeight);
    };

    // Función para retornar el scroll a la posición inicial
    var scrollUp = function () {
        $("html, body").animate({scrollTop: 0}, "slow");
    };

    // Función para navegar por el log de eventos
    var navLog = function (start, current) {
        if (typeof start === "undefined") {
            return false;
        }

        var opts = appRequests.getRequestOpts();
        opts.url = "/ajax/ajax_eventlog.php";
        opts.type = "html";
        opts.data = {"start": start, "current": current};

        appRequests.getActionCall(opts, function (response) {
            $("#content").html(response);
            scrollUp();
        });
    };


    // Función para obtener las variables de la URL y parsearlas a un array.
    var getUrlVars = function () {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf("?") + 1).split("&");
        for (var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split("=");
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    };

    // Función para comprobar si se ha salido de la sesión
    var checkLogout = function () {
        var session = getUrlVars()["session"];

        if (session === 0) {
            resMsg("warn", config.LANG[2], "", "location.search = ''");
        }
    };

    var redirect = function (url) {
        window.location.replace(url);
    };

    // Función para enviar una solicitud de modificación de cuenta
    var sendRequest = function () {
        var opts = appRequests.getRequestOpts();
        opts.url = "/ajax/ajax_sendRequest.php";
        opts.data = $("#frmRequestModify").serialize();

        appRequests.getActionCall(opts, function (json) {
            jsonResponseMessage(json);
        });
    };

    // Función para habilitar la subida de archivos en una zona o formulario
    var fileUpload = function ($obj) {
        var requestData = function () {
            return {
                actionId: $obj.data("action-id"),
                itemId: $obj.data("item-id"),
                sk: sk.get()
            };
        };

        var options = {
            requestDoneAction: "",
            requestData: function (data) {
                requestData = function () {
                    return data;
                };
            },
            beforeSendAction: "",
            url: ""
        };

        // Subir un archivo
        var sendFile = function (file) {
            if (typeof options.url === "undefined" || options.url === "") {
                return false;
            }

            // Objeto FormData para crear datos de un formulario
            var fd = new FormData();
            fd.append("inFile", file);
            fd.append("isAjax", 1);

            var data = requestData();

            Object.keys(data).forEach(function (key) {
                log.info(key);

                fd.append(key, data[key]);
            });

            var opts = appRequests.getRequestOpts();
            opts.url = options.url;
            opts.processData = false;
            opts.contentType = false;
            opts.data = fd;

            appRequests.getActionCall(opts, function (json) {
                var status = json.status;
                var description = json.description;

                if (status === 0) {
                    if (typeof options.requestDoneAction === "function") {
                        options.requestDoneAction();
                    }

                    resMsg("ok", description);
                } else if (status === 10) {
                    appActions.main.logout();
                } else {
                    resMsg("error", description);
                }
            });

        };

        var checkFileSize = function (size) {
            return (size / 1000 > config.MAX_FILE_SIZE);
        };

        var checkFileExtension = function (name) {
            var file_exts_ok = $obj.data("files-ext").toLowerCase().split(",");

            for (var i = 0; i <= file_exts_ok.length; i++) {
                if (name.indexOf(file_exts_ok[i]) !== -1) {
                    return true;
                }
            }

            return false;
        };

        // Comprobar los archivos y subirlos
        var handleFiles = function (filesArray) {
            if (filesArray.length > 5) {
                resMsg("error", config.LANG[17] + " (Max: 5)");
                return;
            }

            for (var i = 0; i < filesArray.length; i++) {
                var file = filesArray[i];
                if (checkFileSize(file.size)) {
                    resMsg("error", config.LANG[18] + "<br>" + file.name + " (Max: " + config.MAX_FILE_SIZE + ")");
                } else if (!checkFileExtension(file.name)) {
                    resMsg("error", config.LANG[19] + "<br>" + file.name);
                } else {
                    sendFile(filesArray[i]);
                }
            }
        };

        // Inicializar la zona de subida de archivos Drag&Drop
        var init = function () {
            log.info("fileUpload:init");

            var fallback = initForm(false);

            $obj.on("dragover dragenter", function (e) {
                log.info("fileUpload:drag");

                e.stopPropagation();
                e.preventDefault();
            });

            $obj.on("drop", function (e) {
                log.info("fileUpload:drop");

                e.stopPropagation();
                e.preventDefault();

                if (typeof options.beforeSendAction === "function") {
                    options.beforeSendAction();
                }

                handleFiles(e.dataTransfer.files);
            });

            $obj.on("click", function () {
                fallback.click();
            });
        };

        // Inicializar el formulario de archivos en modo compatibilidad
        var initForm = function (display) {
            var $form = $("#fileUploadForm");

            if (display === false) {
                $form.hide();
            }

            var $input = $form.find("input[type='file']");

            $input.on("change", function () {
                if (typeof options.beforeSendAction === "function") {
                    options.beforeSendAction();
                }

                handleFiles(this.files);
            });

            return $input;
        };


        if (window.File && window.FileList && window.FileReader) {
            init();
        } else {
            initForm(true);
        }

        return options;
    };


    // Función para realizar una petición ajax
    // FIXME: eliminar
    var sendAjax = function (data, url) {
        var opts = appRequests.getRequestOpts();
        opts.url = url;
        opts.data = data;

        appRequests.getActionCall(opts, function (json) {
            jsonResponseMessage(json);
        });
    };

    // Función para crear un enlace público
    var linksMgmtSave = function (itemId, actionId, sk) {
        var data = {"itemId": itemId, "actionId": actionId, "sk": sk, "isAjax": 1};

        alertify
            .okBtn(config.LANG[40])
            .cancelBtn(config.LANG[41])
            .confirm(config.LANG[48], function (e) {
                $.extend(data, {notify: 1});

                var opts = appRequests.getRequestOpts();
                opts.url = "/ajax/ajax_appMgmtSave.php";
                opts.data = data;

                appRequests.getActionCall(opts, function (json) {
                    jsonResponseMessage(json);
                });
            }, function (e) {
                e.preventDefault();

                var opts = appRequests.getRequestOpts();
                opts.url = "/ajax/ajax_appMgmtSave.php";
                opts.data = data;

                appRequests.getActionCall(opts, function (json) {
                    jsonResponseMessage(json);
                });
            });
    };

    // Función para renovar un enlace
    var linksMgmtRefresh = function (obj, actionId, sk) {
        var itemId = $(obj).attr("data-itemid");
        var activeTab = $(obj).attr("data-activetab");
        var nextActionId = $(obj).attr("data-nextactionid");

        var data = {
            "itemId": itemId,
            "actionId": actionId,
            "sk": sk,
            "activeTab": activeTab,
            "onCloseAction": nextActionId
        };

        var opts = appRequests.getRequestOpts();
        opts.url = "/ajax/ajax_appMgmtSave.php";
        opts.data = data;

        appRequests.getActionCall(opts, function (json) {
            jsonResponseMessage(json);
        });
    };

    // Función para verificar si existen actualizaciones
    var checkUpds = function () {
        var opts = appRequests.getRequestOpts();
        opts.type = "html";
        opts.method = "get";
        opts.timeout = 10000;
        opts.url = "/ajax/ajax_checkUpds.php";

        appRequests.getActionCall(opts, function (response) {
            $("#updates").html(response);

            if (typeof  componentHandler !== "undefined") {
                componentHandler.upgradeDom();
            }
        }, function () {
            $("#updates").html("!");
        });
    };

    // Función para limpiar el log de eventos
    var clearEventlog = function (sk) {
        var atext = "<div id=\"alert\"><p id=\"alert-text\">" + config.LANG[20] + "</p></div>";

        alertify
            .okBtn(config.LANG[43])
            .cancelBtn(config.LANG[44])
            .confirm(atext, function (e) {
                var opts = appRequests.getRequestOpts();
                opts.url = "/ajax/ajax_eventlog.php";
                opts.data = {"clear": 1, "sk": sk, "isAjax": 1};

                appRequests.getActionCall(opts, function (json) {
                    jsonResponseMessage(json);
                });
            }, function (e) {
                e.preventDefault();

                alertify.error(config.LANG[44]);
            });
    };

    // Función para obtener el tiempo actual en milisegundos
    var getTime = function () {
        var t = new Date();
        return t.getTime();
    };

    // Funciones para analizar al fortaleza de una clave
    // From http://net.tutsplus.com/tutorials/javascript-ajax/build-a-simple-password-strength-checker/
    var checkPassLevel = function (password, dst) {
        passwordData.passLength = password.length;

        outputResult(zxcvbn(password), dst);
    };

    var outputResult = function (level, dstId) {
        var complexity, selector = ".passLevel-" + dstId;
        var score = level.score;

        complexity = $(selector);
        complexity.show();
        complexity.removeClass("weak good strong strongest");

        if (passwordData.passLength === 0) {
            complexity.attr("title", "").empty();
        } else if (passwordData.passLength < passwordData.minPasswordLength) {
            complexity.attr("title", config.LANG[11]).addClass("weak");
        } else if (score === 0) {
            complexity.attr("title", config.LANG[9] + " - " + level.feedback.warning).addClass("weak");
        } else if (score === 1 || score === 2) {
            complexity.attr("title", config.LANG[8] + " - " + level.feedback.warning).addClass("good");
        } else if (score === 3) {
            complexity.attr("title", config.LANG[7]).addClass("strong");
        } else if (score === 4) {
            complexity.attr("title", config.LANG[10]).addClass("strongest");
        }
    };

    // Función para mostrar mensaje con alertify
    var resMsg = function (type, txt, url, action) {
        if (typeof url !== "undefined") {
            var opts = appRequests.getRequestOpts();
            opts.type = "get";
            opts.method = "html";
            opts.url = url;
            opts.async = false;

            appRequests.getActionCall(opts, function (response) {
                txt = response;
            });
        }

        var html;
        txt = txt.replace(/(\\n|;;)/g, "<br>");

        switch (type) {
            case "ok":
                alertify
                    .closeLogOnClick(true)
                    .delay(15000)
                    .success(txt);
                break;
            case "error":
                alertify
                    .closeLogOnClick(true)
                    .delay(15000)
                    .error(txt);
                break;
            case "warn":
                alertify
                    .delay(30000)
                    .log(txt);
                break;
            case "nofancyerror":
                html = "<p class=\"error round\">Oops...<br>" + config.LANG[1] + "<br>" + txt + "</p>";
                return html;
            default:
                alertify.error(txt);
                break;
        }

        if (typeof action !== "undefined") {
            eval(action);
        }
    };

    /**
     * Detectar los imputs del tipo checkbox para generar botones
     *
     * @param container El contenedor donde buscar
     */
    var checkboxDetect = function (container) {
        $(container).find(".checkbox").button({
            icons: {primary: "ui-icon-transferthick-e-w"}
        }).click(
            function () {
                var $this = $(this);

                if ($this.prop("checked") === true) {
                    $this.button("option", "label", config.LANG[40]);
                } else {
                    $this.button("option", "label", config.LANG[41]);
                }
            }
        );
    };

    /**
     * Encriptar el valor de un campo del formulario
     *
     * @param inputId El id del campo
     */
    var encryptFormValue = function (inputId) {
        var input = $(inputId);
        var curValue = input.val();
        var nextName = inputId + "-encrypted";
        var nextInput = input.next(":input[name=\"" + nextName + "\"]");

        if ((curValue !== "" && nextInput.attr("name") !== nextName)
            || (curValue !== "" && nextInput.attr("name") === nextName && parseInt(input.next().val()) !== curValue.length)
        ) {
            var passEncrypted = config.crypt.encrypt(curValue);
            input.val(passEncrypted);

            if (nextInput.length > 0) {
                nextInput.val(passEncrypted.length);
            } else {
                input.after("<input type=\"hidden\" name=\"" + nextName + "\" value=\"" + passEncrypted.length + "\" />");
            }
        }
    };

    var initializeClipboard = function () {
        log.info("initializeClipboard");

        var clipboard = new Clipboard(".clip-pass-button", {
            text: function (trigger) {
                var pass = appActions.account.copypass($(trigger));

                return pass.responseJSON.accpass;
            }
        });

        clipboard.on("success", function (e) {
            resMsg("ok", config.LANG[45]);
        });

        clipboard.on("error", function (e) {
            resMsg("error", config.LANG[46]);
        });

        // Portapapeles para claves visualizadas

        // Inicializar el objeto para copiar al portapapeles
        var clipboardPass = new Clipboard(".dialog-clip-pass-button");
        var clipboardUser = new Clipboard(".dialog-clip-user-button");

        clipboardPass.on("success", function (e) {
            $(".dialog-pass-text").addClass("dialog-clip-pass-copy round");
            e.clearSelection();
        });

        clipboardUser.on("success", function (e) {
            e.clearSelection();
        });
    };

    /**
     * Delegar los eventos 'blur' y 'keypress' para que los campos de claves
     * sean encriptados antes de ser enviados por el formulario
     */
    var bindPassEncrypt = function () {
        log.info("bindPassEncrypt");

        var $body = $("body");

        $body.on("blur", ":input[type=password]", function (e) {
            if ($(this).hasClass("passwordfield__no-pki")) {
                return;
            }

            var id = $(this).attr("id");
            encryptFormValue("#" + id);
        });

        $body.on("keypress", ":input[type=password]", function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();

                var form = $(this).closest("form");
                var id = $(this).attr("id");

                encryptFormValue("#" + id);
                form.submit();
            }
        });
    };

    // Función para mostrar los datos de un registro
    var viewWiki = function (pageName, actionId, sk) {
        var opts = appRequests.getRequestOpts();
        opts.type = "html";
        opts.url = "/ajax/ajax_wiki.php";
        opts.data = {"pageName": pageName, "actionId": actionId, "sk": sk, "isAjax": 1};

        appRequests.getActionCall(opts, function (response) {
            $.fancybox(response, {padding: [0, 10, 10, 10]});
        });
    };

    /**
     * Evaluar una acción javascript y ejecutar la función
     *
     * @param evalFn
     * @param $obj
     */
    var evalAction = function (evalFn, $obj) {
        console.info("Eval: " + evalFn);

        if (typeof evalFn === "function") {
            evalFn($obj);
        } else {
            throw Error("Function not found: " + evalFn);
        }
    };

    // Objeto con métodos y propiedades protegidas
    var getProtected = function () {
        return $.extend({
            log: log,
            config: function () {
                return config;
            },
            appTheme: function () {
                return appTheme;
            },
            appActions: function () {
                return appActions;
            },
            appTriggers: function () {
                return appTriggers;
            },
            appRequests: function () {
                return appRequests;
            },
            evalAction: evalAction
        }, oPublic);
    };

    // Objeto con métodos y propiedades públicas
    var getPublic = function () {
        return {
            sk: sk,
            actions: function () {
                return appActions;
            },
            triggers: function () {
                return appTriggers;
            },
            jsonResponseMessage: jsonResponseMessage,
            checkboxDetect: checkboxDetect,
            checkPassLevel: checkPassLevel,
            checkUpds: checkUpds,
            clearEventlog: clearEventlog,
            encryptFormValue: encryptFormValue,
            fileUpload: fileUpload,
            linksMgmtSave: linksMgmtSave,
            linksMgmtRefresh: linksMgmtRefresh,
            navLog: navLog,
            outputResult: outputResult,
            passToClip: passToClip,
            passwordData: passwordData,
            redirect: redirect,
            resMsg: resMsg,
            scrollUp: scrollUp,
            sendAjax: sendAjax,
            sendRequest: sendRequest,
            setContentSize: setContentSize,
            viewWiki: viewWiki
        };
    };

    init();

    return oPublic;
};