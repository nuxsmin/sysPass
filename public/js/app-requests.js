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

sysPass.Requests = function (sysPassApp) {
    "use strict";

    const log = sysPassApp.log;

    /**
     * Historial de consultas AJAX
     *
     * @type {Array}
     * @private
     */
    let _history = [];

    const requestOpts = {
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
        useLoading: true,
        useFullLoading: false
    };

    Object.seal(requestOpts);

    /**
     * Manejo del historial de consultas AJAX
     *
     * @type {{get: history.get, add: history.add, del: history.del, reset: history.reset, length: history.length}}
     */
    const history = {
        get: function () {
            return _history;
        },
        add: function (opts) {
            const hash = (opts.hash === "") ? sysPassApp.util.hash.md5(JSON.stringify(opts)) : opts.hash;

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
     * @returns {requestOpts}
     */
    const getRequestOpts = function () {
        return Object.create(requestOpts);
    };

    /**
     * Devolver la URL para peticiones Ajax
     *
     * @param url
     * @returns {*}
     */
    const getUrl = function (url) {
        return (url.indexOf("http") === -1 && url.indexOf("https") === -1) ? sysPassApp.config.APP_ROOT + url : url;
    };

    /**
     * Llama a una acción mediante AJAX
     *
     * @param opts
     * @param callbackOk
     * @param callbackError
     */
    const getActionCall = function (opts, callbackOk, callbackError) {
        log.info("getActionCall");

        return $.ajax({
            dataType: opts.type,
            url: getUrl(opts.url),
            method: opts.method,
            async: opts.async,
            data: opts.data,
            cache: opts.cache,
            processData: opts.processData,
            contentType: opts.contentType,
            timeout: opts.timeout,
            beforeSend: function () {
                if (opts.useLoading === true) {
                    sysPassApp.theme.loading.show(opts.useFullLoading);
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

                if (opts.type === "json"
                    && response['csrf'] !== undefined
                    && response.csrf !== ""
                ) {
                    sysPassApp.sk.set(response.csrf);
                }

                callbackOk(response);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (typeof callbackError !== "function") {
                    let txt;

                    if (sysPassApp.config.LANG[1] !== undefined) {
                        txt = sysPassApp.config.LANG[1];
                    } else {
                        txt = "An error occurred";
                    }

                    txt += `<p>${errorThrown}</p><p>${jqXHR.responseText}</p>`;

                    log.error(txt);

                    if (opts.type === "html") {
                        $("#content").html(sysPassApp.msg.html.error(txt));
                    }

                    sysPassApp.msg.error(txt);
                } else {
                    callbackError();
                }
            },
            complete: function (response) {
                if (opts.useLoading === true) {
                    sysPassApp.theme.loading.hide();
                }

                if (sysPassApp.theme !== undefined
                    && (opts.type === "html"
                        || (response.responseJSON !== undefined
                            && response.responseJSON.data !== undefined
                            && response.responseJSON.data.html !== undefined))
                ) {
                    sysPassApp.theme.ajax.complete();
                }
            }
        });
    };

    /**
     * Realizar una acción mediante envío de eventos
     * @param opts
     * @param callbackProgress
     * @param callbackEnd
     */
    const getActionEvent = function (opts, callbackProgress, callbackEnd) {
        const source = new EventSource(getUrl(opts.url));

        source.addEventListener("message", function (e) {
            const result = JSON.parse(e.data);

            log.debug(result);

            if (result.end === 1) {
                log.info("getActionEvent:Ending");
                source.close();

                if (typeof callbackEnd === "function") {
                    callbackEnd(result);
                }
            } else {
                if (typeof callbackProgress === "function") {
                    callbackProgress(result);
                }
            }
        });

        source.addEventListener("error", function (e) {
            log.error("getActionEvent:Error occured");

            source.close();
        });

        return source;
    };

    return {
        getRequestOpts: getRequestOpts,
        getActionCall: getActionCall,
        getActionEvent: getActionEvent,
        getUrl: getUrl,
        history: history
    };
};
