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

sysPass.Config = function () {
    "use strict";

    let initialized = false;

    const config = {
        APP_ROOT: "",
        LANG: [], // Language strings
        PKI: {
            AVAILABLE: false,
            KEY: "", // RSA public key
            MAX_SIZE: 0, // Max data length
            CRYPTO: null // Crypt handler
        },
        FILES: {
            MAX_SIZE: 1024, // Max uploading file fize
            ACCOUNT_ALLOWED_MIME: [], // Allowed mime types for accounts' file uploading
            IMPORT_ALLOWED_MIME: [] // Allowed extensions for importing
        },
        STATUS: {
            CHECK_UPDATES: false, // Check for updates
            CHECK_NOTICES: false, // Check for notices
            CHECK_NOTIFICATIONS: false, // Check for notifications
        },
        BROWSER: {
            TIMEZONE: "UTC",
            LOCALE: "en_US",
            COOKIES_ENABLED: false
        },
        DEBUG: true,
        PLUGINS: [],
        AUTH: {
            LOGGEDIN: false,
            AUTHBASIC_AUTOLOGIN: false
        },
        SESSION_TIMEOUT: 0
    };

    return {
        setAppRoot: function (url) {
            config.APP_ROOT = url;
        },
        setLang: function (lang) {
            config.LANG = lang;
        },
        setSessionTimeout: function (timeout) {
            config.SESSION_TIMEOUT = parseInt(timeout);
        },
        setPkiKey: function (key) {
            if (key.length > 0) {
                config.PKI.KEY = key;
                config.PKI.CRYPTO = new JSEncrypt();
                config.PKI.CRYPTO.setPublicKey(key);
                config.PKI.AVAILABLE = true;
            }
        },
        setPkiSize: function (size) {
            config.PKI.MAX_SIZE = parseInt(size);
        },
        setFileMaxSize: function (size) {
            config.FILES.MAX_SIZE = parseInt(size);
        },
        setFileAccountAllowedMime: function (mimetypes) {
            config.FILES.ACCOUNT_ALLOWED_MIME = mimetypes;
        },
        setFileImportAllowedMime: function (mimetypes) {
            config.FILES.IMPORT_ALLOWED_MIME = mimetypes;
        },
        setCheckUpdates: function (bool) {
            config.STATUS.CHECK_UPDATES = bool;
        },
        setCheckNotices: function (bool) {
            config.STATUS.CHECK_NOTICES = bool;
        },
        setCheckNotifications: function (bool) {
            config.STATUS.CHECK_NOTIFICATIONS = bool;
        },
        setTimezone: function (timezone) {
            config.BROWSER.TIMEZONE = timezone;
        },
        setLocale: function (locale) {
            config.BROWSER.LOCALE = locale;
        },
        setCookiesEnabled: function (bool) {
            config.BROWSER.COOKIES_ENABLED = bool;
        },
        setDebugEnabled: function (bool) {
            config.DEBUG = bool;
        },
        setPlugins: function (plugins) {
            config.PLUGINS = plugins;
        },
        setLoggedIn: function (bool) {
            config.AUTH.LOGGEDIN = bool;
        },
        setAuthBasicAutologinEnabled: function (bool) {
            config.AUTH.AUTHBASIC_AUTOLOGIN = bool;
        },
        getConfig: function () {
            return config;
        },
        initialize: function () {
            Object.freeze(config);

            initialized = true;
        }
    };
};

