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

sysPass.Main = function () {
    "use strict";

    /**
     * Comprobar si hay actualizaciones de plugins
     */
    const checkPluginUpdates = function () {
        log.info("checkPluginUpdates");

        for (const plugin in oPublic.plugins) {
            if (typeof oPublic.plugins[plugin].checkVersion === "function") {
                oPublic.plugins[plugin].checkVersion().then(function (json) {
                    if (json.status === 0 && json.data.plugin !== undefined) {
                        msg.info(String.format(oPublic.config.LANG[67], json.data.plugin, json.data.remoteVersion));
                    }
                });
            }
        }
    };

    /**
     * Inicializar los plugins
     */
    const initPlugins = function () {
        log.info("initPlugins");

        const plugins = {};

        for (let i = 0; i < oPublic.config.PLUGINS.length; i++) {
            const plugin = oPublic.config.PLUGINS[i];

            if (typeof sysPass.Plugins[plugin] === "function") {
                plugins[plugin] = sysPass.Plugins[plugin](oPublic);
            }
        }

        return plugins;
    };

    /**
     * Evaluar una acción javascript y ejecutar la función
     *
     * @param evalFn
     * @param $obj
     */
    const evalAction = function (evalFn, $obj) {
        log.info("Eval: " + evalFn);

        if (typeof evalFn === "function") {
            evalFn($obj);
        } else {
            throw Error("Function not found: " + evalFn);
        }
    };

    /**
     * Delegar los eventos 'blur' y 'keypress' para que los campos de claves
     * sean encriptados antes de ser enviados por el formulario
     */
    const bindPassEncrypt = function () {
        log.info("bindPassEncrypt");

        $("body").on("blur", ":input[type=password]", function (e) {
            const $this = $(this);

            if ($this.hasClass("passwordfield__no-pki")) {
                return;
            }

            try {
                encryptFormValue($this);
            } catch (e) {
                log.error(e);

                msg.error(e);
            }
        }).on("keypress", ":input[type=password]", function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();

                const $this = $(this);

                try {
                    encryptFormValue($this);
                } catch (e) {
                    log.error(e);

                    msg.error(e);
                }

                $this.closest("form").submit();
            }
        });
    };

    /**
     * Inicializar portapapeles
     */
    const initializeClipboard = function () {
        log.info("initializeClipboard");

        if (!clipboard.isSupported()) {
            log.warn(oPublic.config.LANG[65]);
            return;
        }

        $("body").on("click", ".clip-pass-button", function () {
            const json = oPublic.actions.account.copyPass($(this)).done(function (json) {
                if (json.status !== 0) {
                    msg.out(json);

                    return false;
                }

                sk.set(json.csrf);
            });

            if (json !== false) {
                clipboard
                    .copy(json.responseJSON.data.accpass)
                    .then(
                        function () {
                            msg.ok(oPublic.config.LANG[45]);
                        },
                        function (err) {
                            msg.error(oPublic.config.LANG[46]);
                        }
                    );
            }
        }).on("click", ".dialog-clip-button", function () {
            const $target = $(this.dataset.clipboardTarget);

            clipboard
                .copy($target.text().replace(/[\r\n]+/g, '')                )
                .then(
                    function () {
                        $(".dialog-text").removeClass("dialog-clip-copy");
                        $target.addClass("dialog-clip-copy");
                    },
                    function (err) {
                        msg.error(oPublic.config.LANG[46]);
                    }
                );
        }).on("click", ".clip-pass-icon", function () {
            const $target = $(this.dataset.clipboardTarget);

            clipboard
                .copy(oPublic.util.decodeEntities($target.val()))
                .then(
                    function () {
                        msg.ok(oPublic.config.LANG[45]);
                    },
                    function (err) {
                        msg.error(oPublic.config.LANG[46]);
                    }
                );
        }).on("click", ".clip-pass-field", function () {
            const target = document.getElementById(this.dataset.clipboardTarget);

            clipboard
                .copy(oPublic.util.decodeEntities(target.dataset.pass))
                .then(
                    function () {
                        msg.ok(oPublic.config.LANG[45]);
                    },
                    function (err) {
                        msg.error(oPublic.config.LANG[46]);
                    }
                );
        });
    };

    /**
     * Encriptar el valor de un campo del formulario
     *
     * @param $input El id del campo
     */
    const encryptFormValue = function ($input) {
        log.info("encryptFormValue");

        const curValue = $input.val();

        if (curValue !== ""
            && parseInt($input.attr("data-length")) !== curValue.length
        ) {
            if (curValue.length > oPublic.config.PKI.MAX_SIZE) {
                $input.val("");

                throw "Data length too big for encrypting";
            }

            const passEncrypted = oPublic.config.PKI.CRYPTO.encrypt(curValue);

            $input.val(passEncrypted);
            $input.attr("data-length", passEncrypted.length);
        }
    };

    // Función para comprobar si se ha salido de la sesión
    const checkLogout = function () {
        log.info("checkLogout");

        if (getUrlVars("r") === "login/logout") {
            msg.sticky(oPublic.config.LANG[61], function () {
                oPublic.util.redirect("index.php?r=login");
            });

            return true;
        }

        return false;
    };

    // Objeto para leer/escribir el token de seguridad
    const sk = {
        current: "",
        get: function () {
            log.info("sk:get");
            return $("#container").attr("data-sk");
        },
        set: function (sk) {
            log.info("sk:set");
            log.debug(sk);

            $("#container").attr("data-sk", sk);

            this.current = sk;
        }
    };

    // Logging
    const log = {
        log: function (msg) {
            if (oPublic.config.DEBUG === true) {
                console.log(msg);
            }
        },
        info: function (msg) {
            if (oPublic.config.DEBUG === true) {
                console.info(msg);
            }
        },
        error: function (msg) {
            console.error(msg);
        },
        warn: function (msg) {
            console.warn(msg);
        },
        debug: function (msg) {
            if (oPublic.config.DEBUG === true) {
                console.debug(msg);
            }
        }
    };

    Object.freeze(log);

    // Opciones para Toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-center",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    /**
     * Retrollamadas de los elementos
     */
    const setupCallbacks = function () {
        log.info("setupCallbacks");

        const $container = $("#container");
        const page = $container.data("page");

        if (page.length > 0
            && typeof oPublic.triggers.views[page] === "function"
        ) {
            oPublic.triggers.views[page]($container);
        }

        if ($("footer").length > 0) {
            oPublic.triggers.views.footer();
        }

        $("#btnBack").click(function () {
            oPublic.util.redirect("index.php");
        });

        oPublic.triggers.bodyHooks();
    };

    // Mostrar mensaje de aviso
    const msg = {
        ok: function (msg) {
            toastr.success(msg);
        },
        error: function (msg) {
            toastr.error(msg);
        },
        warn: function (msg) {
            toastr.warning(msg);
        },
        info: function (msg) {
            toastr.info(msg);
        },
        sticky: function (msg, callback) {
            const opts = {timeOut: 0};

            if (typeof callback === "function") {
                opts.onHidden = callback;
            }

            toastr.warning(msg, oPublic.config.LANG[60], opts);
        },
        out: function (data) {
            if (typeof data === "object") {
                const status = data.status;
                let description = data.description;

                if (data.messages !== undefined
                    && data.messages.length > 0
                ) {
                    description = description + "<br>" + data.messages.join("<br>");
                }

                switch (status) {
                    case 0:
                        this.ok(description);
                        break;
                    case 1:
                        this.error(description);
                        break;
                    case 2:
                        this.warn(description);
                        break;
                    case 10:
                        oPublic.actions.main.logout();
                        break;
                    case 100:
                        this.ok(description);
                        this.sticky(description);
                        break;
                    case 101:
                        this.error(description);
                        this.sticky(description);
                        break;
                    case 102:
                        this.warn(description);
                        this.sticky(description);
                        break;
                    default:
                        this.error(description);
                }
            }
        },
        html: {
            error: function (msg) {
                return "<p class=\"error round\">Oops...<br>" + oPublic.config.LANG[1] + "<br>" + msg + "</p>";
            }
        }
    };

    Object.freeze(msg);

    if (!String.format) {
        String.format = function (format) {
            const args = Array.prototype.slice.call(arguments, 1);

            return format.replace(/{(\d+)}/g, function (match, number) {
                return typeof args[number] !== "undefined" ? args[number] : match;
            });
        };
    }

    /**
     * Obtener las variables de entorno de sysPass
     */
    const getEnvironment = function () {
        log.info("getEnvironment");

        const path = window.location.pathname.split("/");
        const rootPath = function () {
            let fullPath = "";

            for (let i = 1; i <= path.length - 2; i++) {
                fullPath += "/" + path[i];
            }

            return fullPath;
        };

        const configHandler = sysPass.Config();
        const root = window.location.protocol + "//" + window.location.host + rootPath();
        configHandler.setAppRoot(root);

        const opts = oPublic.requests.getRequestOpts();
        opts.url = root + "/index.php?r=bootstrap/getEnvironment";
        opts.method = "get";
        // opts.async = false;
        opts.useLoading = false;
        opts.data = {isAjax: 1};

        return oPublic.requests.getActionCall(opts, function (json) {
            if (json.data !== undefined) {
                configHandler.setLang(json.data.lang);
                configHandler.setSessionTimeout(json.data.session_timeout);
                configHandler.setPkiKey(json.data.pki_key);
                configHandler.setPkiSize(json.data.pki_max_size);
                configHandler.setCheckUpdates(json.data.check_updates);
                configHandler.setCheckNotices(json.data.check_notices);
                configHandler.setCheckNotifications(json.data.check_notifications);
                configHandler.setTimezone(json.data.timezone);
                configHandler.setLocale(json.data.locale);
                configHandler.setDebugEnabled(json.data.debug);
                configHandler.setFileMaxSize(json.data.max_file_size);
                configHandler.setFileAccountAllowedMime(json.data.files_allowed_mime);
                configHandler.setFileImportAllowedMime(json.data.import_allowed_mime);
                configHandler.setCookiesEnabled(json.data.cookies_enabled);
                configHandler.setPlugins(json.data.plugins);
                configHandler.setLoggedIn(json.data.loggedin);
                configHandler.setAuthBasicAutologinEnabled(json.data.authbasic_autologin);

                configHandler.initialize();

                oPublic.config = configHandler.getConfig();
            }
        }).fail(function () {
            msg.error("Error while getting sysPass config<br/>Please try again or check web server logs");
        });
    };

    // Objeto con métodos y propiedades públicas
    const oPublic = {
        config: sysPass.Config().getConfig(),
        actions: sysPass.Actions(log),
        triggers: sysPass.Triggers(log),
        util: sysPass.Util(log),
        theme: {},
        plugins: {},
        sk: sk,
        msg: msg,
        log: log,
        encryptFormValue: encryptFormValue,
    };

    /**
     * Inicialización
     */
    const init = function () {
        log.info("init");

        if (typeof sysPass.Theme === "function") {
            oPublic.theme = sysPass.Theme(log);
        }

        // Late init
        oPublic.requests = sysPass.Requests(oPublic);

        getEnvironment().then(function () {
            if (!checkLogout()) {
                if (oPublic.config.PKI.AVAILABLE) {
                    bindPassEncrypt();
                }

                if (oPublic.config.BROWSER.COOKIES_ENABLED === false) {
                    msg.sticky(oPublic.config.LANG[64]);
                }

                initializeClipboard();
                setupCallbacks();

                if (oPublic.config.PLUGINS.length > 0) {
                    oPublic.plugins = initPlugins();

                    if (oPublic.config.AUTH.LOGGEDIN === true
                        && oPublic.config.STATUS.CHECK_UPDATES === true
                    ) {
                        checkPluginUpdates();
                    }
                }
            }

            Object.freeze(oPublic);
        });

        return oPublic;
    };


    // Función para obtener las variables de la URL y parsearlas a un array.
    const getUrlVars = function (param) {
        let vars = [], hash;
        const hashes = window.location.href.slice(window.location.href.indexOf("?") + 1).split("&");
        for (let i = 0; i < hashes.length; i++) {
            hash = hashes[i].split("=");
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }

        return param !== undefined && vars[param] !== undefined ? vars[param] : vars;
    };

    return init();
};