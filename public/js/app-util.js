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

sysPass.Util = function (log) {
    "use strict";

    /**
     * @author https://stackoverflow.com/users/82548/david-thomas
     * @link http://stackoverflow.com/questions/5796718/html-entity-decode
     */
    const decodeEntities = function (str) {
        return $('<textarea />').html(str).text();
    };

    /**
     * Resizes an image to viewport size
     *
     * @param $obj
     */
    const resizeImage = function ($obj) {
        const viewport = {
            width: $(window).width() * 0.90,
            height: $(window).height() * 0.90
        };

        const image = {
            width: $obj.width(),
            height: $obj.height()
        };

        const dimension = {
            calc: 0,
            main: 0,
            secondary: 0,
            factor: 0.90,
            rel: image.width / image.height
        };

        /**
         * Fits the image aspect ratio
         *
         * It takes into account the maximum dimension in the opposite axis
         *
         * @param dimension
         * @returns {*}
         */
        const adjustRel = function (dimension) {
            if (dimension.main > dimension.secondary) {
                dimension.calc = dimension.main / dimension.rel;
            } else if (dimension.main < dimension.secondary) {
                dimension.calc = dimension.main * dimension.rel;
            }

            if (dimension.calc > dimension.secondary) {
                dimension.main *= dimension.factor;

                adjustRel(dimension);
            }

            return dimension;
        };

        /**
         * Resize from width
         */
        const resizeWidth = function () {
            dimension.main = viewport.width;
            dimension.secondary = viewport.height;

            const adjust = adjustRel(dimension);

            $obj.css({
                "width": adjust.main,
                "height": adjust.calc
            });

            image.width = adjust.main;
            image.height = adjust.calc;
        };

        /**
         * Resize from height
         */
        const resizeHeight = function () {
            dimension.main = viewport.height;
            dimension.secondary = viewport.width;

            const adjust = adjustRel(dimension);

            $obj.css({
                "width": adjust.calc,
                "height": adjust.main
            });

            image.width = adjust.calc;
            image.height = adjust.main;
        };

        if (image.width > viewport.width) {
            resizeWidth();
        } else if (image.height > viewport.height) {
            resizeHeight();
        }

        return image;
    };

    /**
     * Function to enable file uploading through a drag&drop or form
     * @param $obj
     * @returns {{requestDoneAction: string, setRequestData: setRequestData, getRequestData: function(): {actionId: *, itemId: *, sk: *}, beforeSendAction: string, url: string, allowedExts: Array}}
     */
    const fileUpload = function ($obj) {

        /**
         * Initializes the files form in legacy mode
         *
         * @param display
         * @returns {*}
         */
        const initForm = function (display) {
            const $form = $("#fileUploadForm");

            if (display === false) {
                $form.hide();
            }

            const $input = $form.find("input[type='file']");

            $input.on("change", function () {
                if (typeof options.beforeSendAction === "function") {
                    options.beforeSendAction();
                }

                handleFiles(this.files);
            });

            return $input;
        };

        const requestData = {
            actionId: $obj.data("action-id"),
            itemId: $obj.data("item-id"),
            sk: sysPassApp.sk.get()
        };

        const options = {
            requestDoneAction: "",
            setRequestData: function (data) {
                $.extend(requestData, data);
            },
            getRequestData: function () {
                return requestData;
            },
            beforeSendAction: "",
            url: "",
            allowedMime: []
        };

        /**
         * Uploads a file
         * @param file
         * @returns {boolean}
         */
        const sendFile = function (file) {
            if (options.url === undefined || options.url === "") {
                return false;
            }

            // Objeto FormData para crear datos de un formulario
            const fd = new FormData();
            fd.append("inFile", file);
            fd.append("isAjax", 1);

            requestData.sk = sysPassApp.sk.get();

            Object.keys(requestData).forEach(function (key) {
                fd.append(key, requestData[key]);
            });

            const opts = sysPassApp.requests.getRequestOpts();
            opts.url = options.url;
            opts.processData = false;
            opts.contentType = false;
            opts.data = fd;

            sysPassApp.requests.getActionCall(opts, function (json) {
                const status = json.status;
                const description = json.description;

                if (status === 0) {
                    if (typeof options.requestDoneAction === "function") {
                        options.requestDoneAction();
                    }

                    sysPassApp.msg.ok(description);
                } else if (status === 10) {
                    sysPassApp.appActions().main.logout();
                } else {
                    sysPassApp.msg.error(description);
                }
            });

        };

        const checkFileSize = function (size) {
            return (size / 1000 > sysPassApp.config.FILES.MAX_SIZE);
        };

        const checkFileMimeType = function (mimeType) {
            if (mimeType === '') {
                return true;
            }

            for (let mime in options.allowedMime) {
                if (mimeType.indexOf(options.allowedMime[mime]) !== -1) {
                    return true;
                }
            }

            return false;
        };

        /**
         * Checks the files and upload them
         */
        const handleFiles = function (filesArray) {
            if (filesArray.length > 5) {
                sysPassApp.msg.error(sysPassApp.config.LANG[17] + " (Max: 5)");
                return;
            }

            for (let i = 0; i < filesArray.length; i++) {
                const file = filesArray[i];

                if (checkFileSize(file.size)) {
                    sysPassApp.msg.error(sysPassApp.config.LANG[18] + "<br>" + file.name + " (Max: " + sysPassApp.config.FILES.MAX_SIZE + ")");
                } else if (!checkFileMimeType(file.type)) {
                    sysPassApp.msg.error(sysPassApp.config.LANG[19] + "<br>" + file.type);
                } else {
                    sendFile(filesArray[i]);
                }
            }
        };

        /**
         * Initializes the Drag&Drop zone
         */
        const init = function () {
            log.info("fileUpload:init");

            const fallback = initForm(false);

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

                handleFiles(e.originalEvent.dataTransfer.files);
            });

            $obj.on("click", function () {
                fallback.click();
            });
        };


        if (window.File && window.FileList && window.FileReader) {
            init();
        } else {
            initForm(true);
        }

        return options;
    };

    /**
     *
     * @type {{md5: function(*=): String}}
     */
    const hash = {
        md5: function (data) {
            return SparkMD5.hash(data, false);
        }
    };

    /**
     * Scrolls to the top of the viewport
     */
    const scrollUp = function () {
        $("html, body").animate({scrollTop: 0}, "slow");
    };

    // Función para establecer la altura del contenedor ajax
    const setContentSize = function () {
        const $container = $("#container");

        if ($container.hasClass("content-no-auto-resize")) {
            return;
        }

        //console.info($("#content").height());

        // Calculate total height for full body resize
        $container.css("height", $("#content").height() + 200);
    };

    // Función para obtener el tiempo actual en milisegundos
    const getTime = function () {
        const t = new Date();
        return t.getTime();
    };

    /**
     *
     * @type {{config: {passLength: number, minPasswordLength: number, complexity: {chars: boolean, numbers: boolean, symbols: boolean, uppercase: boolean, numlength: number}}, random: random, output: output, checkLevel: checkLevel}}
     */
    const password = {
        config: {
            passLength: 0,
            minPasswordLength: 12,
            complexity: {
                chars: true,
                numbers: true,
                symbols: true,
                uppercase: true,
                numlength: 12
            },
            charset: {
                special: "!\"\\·@|#$~%&/()=?'¿¡^*[]·;,_-{}<>",
                number: "1234567890",
                char: "abcdefghijklmnopqrstuvwxyz"
            }
        },
        /**
         * Function to generate random password and call a callback sending the generated string
         * and a zxcvbn object
         *
         * @param callback
         */
        random: function (callback) {
            log.info("password:random");

            let chars = "";

            if (this.config.complexity.symbols) {
                chars += this.config.charset.special;
            }

            if (this.config.complexity.numbers) {
                chars += this.config.charset.number;
            }

            if (this.config.complexity.chars) {
                chars += this.config.charset.char;

                if (this.config.complexity.uppercase) {
                    chars += this.config.charset.char.toUpperCase();
                }
            }

            const getRandomChar = function (min, max) {
                return chars.charAt(Math.floor((Math.random() * max) + min));
            };

            const generateRandom = function () {
                let out = "";
                let i = 0;

                for (; i++ < password.config.complexity.numlength;) {
                    out += getRandomChar(0, chars.length - 1);
                }

                return out;
            };

            const checkComplexity = function (inPass) {
                log.info("password:random:checkComplexity");

                const inPassArray = inPass.split("");

                if (password.config.complexity.symbols) {
                    const res = inPassArray.some(
                        function (el) {
                            return password.config.charset.special.indexOf(el) > 0;
                        });

                    if (res === false) {
                        return res;
                    }
                }

                if (password.config.complexity.numbers) {
                    const res = inPassArray.some(
                        function (el) {
                            return password.config.charset.number.indexOf(el) > 0;
                        });

                    if (res === false) {
                        return res;
                    }
                }

                if (password.config.complexity.chars) {
                    const res = inPassArray.some(
                        function (el) {
                            return password.config.charset.char.indexOf(el) > 0;
                        });

                    if (res === false) {
                        return res;
                    }
                }

                if (password.config.complexity.uppercase) {
                    const chars = password.config.charset.char.toUpperCase();
                    const res = inPassArray.some(
                        function (el) {
                            return chars.indexOf(el) > 0;
                        });

                    if (res === false) {
                        return res;
                    }
                }

                return true;
            };

            let outPassword = "";

            do {
                outPassword = generateRandom();
            } while (!checkComplexity(outPassword));

            this.config.passLength = outPassword.length;

            if (typeof callback === "function") {
                callback(outPassword, zxcvbn(outPassword));
            }
        },
        output: function (level, $target) {
            log.info("password:outputResult");

            const $passLevel = $("#password-level-" + $target.attr("id"));
            const score = level.score;

            $passLevel.removeClass("weak good strong strongest");

            if (this.config.passLength === 0) {
                $passLevel.attr("data-level-msg", "");
            } else if (this.config.passLength < this.config.minPasswordLength) {
                $passLevel.attr("data-level-msg", sysPassApp.config.LANG[11]).addClass("weak");
            } else if (score === 0) {
                $passLevel.attr("data-level-msg", sysPassApp.config.LANG[9] + " - " + level.feedback.warning).addClass("weak");
            } else if (score === 1 || score === 2) {
                $passLevel.attr("data-level-msg", sysPassApp.config.LANG[8] + " - " + level.feedback.warning).addClass("good");
            } else if (score === 3) {
                $passLevel.attr("data-level-msg", sysPassApp.config.LANG[7]).addClass("strong");
            } else if (score === 4) {
                $passLevel.attr("data-level-msg", sysPassApp.config.LANG[10]).addClass("strongest");
            }
        },
        checkLevel: function ($target) {
            log.info("password:checkPassLevel");

            this.config.passLength = $target.val().length;

            password.output(zxcvbn($target.val()), $target);
        }
    };

    /**
     * Redirect to a given URL
     *
     * @param url
     */
    const redirect = function (url) {
        window.location.replace(url);
    };

    /**
     * Generates an unique id
     *
     * @see https://stackoverflow.com/questions/3231459/create-unique-id-with-javascript
     * @returns {string}
     */
    const uniqueId = function () {
        // always start with a letter (for DOM friendlyness)
        let idstr = String.fromCharCode(Math.floor((Math.random() * 25) + 65));

        do {
            // between numbers and characters (48 is 0 and 90 is Z (42-48 = 90)
            const ascicode = Math.floor((Math.random() * 42) + 48);
            if (ascicode < 58 || ascicode > 64) {
                // exclude all chars between : (58) and @ (64)
                idstr += String.fromCharCode(ascicode);
            }
        } while (idstr.length < 32);

        return idstr.toLowerCase();
    };

    /**
     * Sends a browser notification
     */
    const notifications = {
        state: {
            lastHash: ''
        },
        send: function (title, message, id) {
            log.info("sendNotification");

            if (!("Notification" in window)) {
                log.info("Notifications not supported");
                return;
            }

            if (id === notifications.state.lastHash) {
                return;
            }

            const fireMessage = function () {
                log.info("sendNotification:fireMessage");

                notifications.state.lastHash = id;

                const options = {};

                if (message !== undefined) {
                    options.body = message;
                }

                const notification = new Notification(title, options);
            };


            if (Notification.permission === "granted") {
                fireMessage();
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(function (result) {
                    if (result === "granted") {
                        fireMessage();
                    } else {
                        log.info("Notifications disabled");
                    }
                });
            }
        }
    };

    /**
     * Returns a serialized URL for a GET request
     * @param base
     * @param parts
     * @returns {string}
     */
    const getUrl = function (base, parts) {
        return base + "?" + Object.keys(parts).map(function (key) {
            if (Array.isArray(parts[key])) {
                return key + "=" + parts[key].join("/");
            }

            return key + "=" + parts[key];
        }).join("&");
    };

    /**
     *
     * @param $container
     */
    const focus = function ($container) {
        log.debug("focus");

        $container.find("input:not([id*=selectized]):visible:first").focus();
    };

    return {
        decodeEntities: decodeEntities,
        resizeImage: resizeImage,
        fileUpload: fileUpload,
        scrollUp: scrollUp,
        setContentSize: setContentSize,
        redirect: redirect,
        uniqueId: uniqueId,
        getUrl: getUrl,
        focus: focus,
        sendNotification: notifications.send,
        password: password,
        hash: hash
    };
};