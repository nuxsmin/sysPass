import assert from "node:assert/strict";
import { createRequire } from "node:module";
import test from "node:test";

globalThis.sysPass = {};

const require = createRequire(import.meta.url);
require("../../public/js/app-util.js");

const util = globalThis.sysPass.Util({
    debug: function () {},
    info: function () {},
    error: function () {}
});

test("getUrl omits null, undefined and empty array segments", function () {
    assert.equal(util.getUrl("index.php", {r: ["account/view", null]}), "index.php?r=account/view");
    assert.equal(util.getUrl("index.php", {r: ["account/view", undefined]}), "index.php?r=account/view");
    assert.equal(util.getUrl("index.php", {r: ["account/view", ""]}), "index.php?r=account/view");
});

test("getUrl omits an absent trailing item id", function () {
    assert.equal(
        util.getUrl("index.php", {r: ["account/saveDelete", undefined]}),
        "index.php?r=account/saveDelete"
    );
});

test("getUrl joins multiple array segments", function () {
    assert.equal(
        util.getUrl("index.php", {r: ["account/viewHistory", 12, 34]}),
        "index.php?r=account/viewHistory/12/34"
    );
});

test("getUrl preserves a numeric zero segment", function () {
    assert.equal(util.getUrl("index.php", {r: ["account/view", 0]}), "index.php?r=account/view/0");
    assert.equal(util.getUrl("index.php", {r: ["account/view", "0"]}), "index.php?r=account/view/0");
});

test("getUrl trims trailing slashes from a scalar route", function () {
    assert.equal(util.getUrl("index.php", {r: "account/view/"}), "index.php?r=account/view");
    assert.equal(util.getUrl("index.php", {r: "account/view///"}), "index.php?r=account/view");
});

test("getUrl produces no trailing slash for a scalar route without an id", function () {
    assert.equal(
        util.getUrl("index.php", {r: util.buildRoute(["account/view", undefined])}),
        "index.php?r=account/view"
    );
});

test("getUrl returns the base URL when parts are null or undefined", function () {
    assert.equal(util.getUrl("index.php", null), "index.php");
    assert.equal(util.getUrl("index.php", undefined), "index.php");
});

test("buildRoute joins valid segments", function () {
    assert.equal(util.buildRoute(["account/view", 12]), "account/view/12");
    assert.equal(util.buildRoute(["account/view"]), "account/view");
});

test("buildRoute preserves a numeric zero segment", function () {
    assert.equal(util.buildRoute(["account/view", 0]), "account/view/0");
    assert.equal(util.buildRoute(["account/view", "0"]), "account/view/0");
});

test("buildRoute omits absent trailing segments", function () {
    assert.equal(util.buildRoute(["account/saveDelete", undefined]), "account/saveDelete");
    assert.equal(util.buildRoute(["account/saveDelete", null]), "account/saveDelete");
    assert.equal(util.buildRoute(["account/saveDelete", ""]), "account/saveDelete");
    assert.equal(util.buildRoute(["account/view", null, undefined]), "account/view");
});

test("buildRoute rejects an empty middle segment", function () {
    assert.throws(function () {
        util.buildRoute(["account/view", null, 12]);
    }, /malformed route/i);

    assert.throws(function () {
        util.buildRoute(["account/view", "", 12]);
    }, /malformed route/i);
});

test("buildRoute rejects a malformed route", function () {
    assert.throws(function () {
        util.buildRoute([]);
    }, /malformed route/i);

    assert.throws(function () {
        util.buildRoute([undefined]);
    }, /malformed route/i);

    assert.throws(function () {
        util.buildRoute(["account/view", "bad segment", 12]);
    }, /malformed route/i);

    assert.throws(function () {
        util.buildRoute(["account/view", "bad?id", 12]);
    }, /malformed route/i);
});

function setupFileUploadTest() {
    const calls = [];
    const okMessages = [];
    const errorMessages = [];
    const warnMessages = [];
    let logoutCalls = 0;
    let doneCalls = 0;

    const handlers = {};
    const inputEl = {
        on: function (event, cb) {
            handlers["input:" + event] = cb;
            return this;
        },
        click: function () {}
    };
    const formEl = {
        hide: function () {
            return this;
        },
        find: function () {
            return inputEl;
        }
    };
    const dropEl = {
        data: function (key) {
            return key === "item-id" ? 42 : undefined;
        },
        on: function (event, cb) {
            handlers["drop:" + event] = cb;
            return this;
        }
    };

    globalThis.window = { File: function () {}, FileList: function () {}, FileReader: function () {} };
    globalThis.$ = function (selector) {
        if (selector === "#fileUploadForm") {
            return formEl;
        }
        return dropEl;
    };
    globalThis.$.extend = Object.assign;

    globalThis.sysPassApp = {
        sk: {
            get: function () {
                return "test-sk";
            }
        },
        requests: {
            getRequestOpts: function () {
                return {};
            },
            getActionCall: function (opts, callback) {
                calls.push({ opts: opts, callback: callback });
            }
        },
        actions: {
            main: {
                logout: function () {
                    logoutCalls += 1;
                }
            }
        },
        msg: {
            ok: function (message) {
                okMessages.push(message);
            },
            error: function (message) {
                errorMessages.push(message);
            },
            warn: function (message) {
                warnMessages.push(message);
            },
            out: function (data) {
                if (typeof data === "object" && data !== null) {
                    let description = data.description;
                    if (data.messages !== undefined && data.messages.length > 0) {
                        description = description + "<br>" + data.messages.join("<br>");
                    }
                    switch (data.status) {
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
                            globalThis.sysPassApp.actions.main.logout();
                            break;
                        default:
                            this.error(description);
                    }
                }
            }
        },
        config: {
            FILES: {
                MAX_SIZE: 1000
            },
            LANG: {
                17: "Too many files",
                18: "File too large",
                19: "Invalid mime"
            }
        }
    };

    const upload = util.fileUpload(dropEl);
    upload.url = "/index.php?r=accountFile/upload/42";
    upload.allowedMime = ["text/plain"];
    upload.requestDoneAction = function () {
        doneCalls += 1;
    };

    const triggerUpload = function () {
        handlers["input:change"].call({
            files: [{ name: "note.txt", size: 12, type: "text/plain" }]
        });
    };

    return {
        triggerUpload,
        calls,
        okMessages,
        errorMessages,
        warnMessages,
        getLogoutCalls: () => logoutCalls,
        getDoneCalls: () => doneCalls
    };
}

test("fileUpload sendFile handles OK by reloading and displaying ok message", function () {
    const ctx = setupFileUploadTest();
    ctx.triggerUpload();
    assert.equal(ctx.calls.length, 1);

    ctx.calls[0].callback({ status: 0, description: "File saved", messages: [] });
    assert.equal(ctx.getDoneCalls(), 1);
    assert.deepEqual(ctx.okMessages, ["File saved"]);
    assert.deepEqual(ctx.errorMessages, []);
    assert.deepEqual(ctx.warnMessages, []);
});

test("fileUpload sendFile handles ERROR without running requestDoneAction", function () {
    const ctx = setupFileUploadTest();
    ctx.triggerUpload();
    assert.equal(ctx.calls.length, 1);

    ctx.calls[0].callback({ status: 1, description: "Upload error", messages: ["Detail A"] });
    assert.equal(ctx.getDoneCalls(), 0);
    assert.deepEqual(ctx.okMessages, []);
    assert.deepEqual(ctx.errorMessages, ["Upload error<br>Detail A"]);
    assert.deepEqual(ctx.warnMessages, []);
});

test("fileUpload sendFile handles WARNING as warning without running requestDoneAction", function () {
    const ctx = setupFileUploadTest();
    ctx.triggerUpload();
    assert.equal(ctx.calls.length, 1);

    ctx.calls[0].callback({ status: 2, description: "Upload warning", messages: [] });
    assert.equal(ctx.getDoneCalls(), 0);
    assert.deepEqual(ctx.okMessages, []);
    assert.deepEqual(ctx.errorMessages, []);
    assert.deepEqual(ctx.warnMessages, ["Upload warning"]);
});

test("fileUpload sendFile handles status 10 by calling actions.main.logout", function () {
    const ctx = setupFileUploadTest();
    ctx.triggerUpload();
    assert.equal(ctx.calls.length, 1);

    ctx.calls[0].callback({ status: 10, description: "Session expired" });
    assert.equal(ctx.getDoneCalls(), 0);
    assert.equal(ctx.getLogoutCalls(), 1);
    assert.deepEqual(ctx.okMessages, []);
    assert.deepEqual(ctx.errorMessages, []);
    assert.deepEqual(ctx.warnMessages, []);
});
