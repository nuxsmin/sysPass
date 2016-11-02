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

sysPass.Requests = function (Common) {
    "use strict";

    var log = Common.log;

    /**
     * Historial de consultas AJAX
     *
     * @type {Array}
     * @private
     */
    var _history = [];

    /**
     * Manejo del historial de consultas AJAX
     *
     * @type {{get: history.get, add: history.add, del: history.del, reset: history.reset, length: history.length}}
     */
    var history = {
        get: function () {
            return _history;
        },
        add: function (opts) {
            var hash = (opts.hash === "") ? SparkMD5.hash(JSON.stringify(opts), false) : opts.hash;

            if (_history.length > 0 && _history[_history.length - 1].hash === hash) {
                return _history;
            }

            log.info("history:add");

            opts.hash = hash;
            _history.push(opts);

            if (_history.length >= 15) {
                _history.splice(0, 10);
            }

            return _history;
        },
        del: function () {
            log.info("history:del");

            if (typeof _history.pop() !== "undefined") {
                return _history[_history.length - 1];
            }
        },
        reset: function () {
            log.info("history:reset");

            _history = [];
        },
        length: function () {
            return _history.length;
        }
    };

    /**
     * Prototipo de objeto para peticiones
     *
     * @returns {opts}
     */
    var getRequestOpts = function () {
        var opts = {
            type: "json",
            url: "",
            method: "post",
            callback: "",
            async: true,
            data: "",
            cache: false,
            processData: true,
            contentType: "application/x-www-form-urlencoded; charset=UTF-8",
            timeout: 0,
            addHistory: false,
            hash: "",
            useLoading: true
        };

        return Object.create(opts);
    };

    /**
     * Llama a una acción mediante AJAX
     *
     * @param opts
     * @param callbackOk
     * @param callbackError
     */
    var getActionCall = function (opts, callbackOk, callbackError) {
        log.info("getActionCall");

        var url = !opts.url.startsWith("http", 0) ? Common.config().APP_ROOT + opts.url : opts.url;

        var $ajax = $.ajax({
            dataType: opts.type,
            url: url,
            method: opts.method,
            async: opts.async,
            data: opts.data,
            cache: opts.cache,
            processData: opts.processData,
            contentType: opts.contentType,
            timeout: opts.timeout,
            beforeSend: function () {
                if (opts.useLoading === true) {
                    Common.appTheme().loading.show();
                }
            },
            success: function (response) {
                if (typeof callbackOk !== "function") {
                    return true;
                }

                // Añadir entrada al historial
                if (opts.addHistory === true) {
                    opts.callback = callbackOk;
                    history.add(opts);
                }

                callbackOk(response);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (typeof callbackError !== "function") {
                    var txt = Common.config().LANG[1] + "<p>" + errorThrown + textStatus + "</p>";

                    log.error(txt);

                    if (opts.type === "html") {
                        $("#content").html(Common.msg.html.error(errorThrown));
                    }

                    Common.msg.error(txt);
                } else {
                    callbackError();
                }
            },
            complete: function () {
                if (opts.useLoading === true) {
                    Common.appTheme().loading.hide();
                }

                Common.appTheme().ajax.complete();
            }
        });

        // Common.log.info($ajax);

        return $ajax;
    };

    return {
        getRequestOpts: getRequestOpts,
        getActionCall: getActionCall,
        history: history
    };
};
