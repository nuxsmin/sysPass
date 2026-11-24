import assert from "node:assert/strict";
import {createRequire} from "node:module";
import test from "node:test";

globalThis.sysPass = {};

const require = createRequire(import.meta.url);
require("../../public/js/app-requests.js");

function setupRequests(options) {
    const opts = options || {};
    const record = {
        ajaxCalls: [],
        logoutCalls: 0,
        errorToasts: [],
        contentHtml: [],
        loadingShow: 0,
        loadingHide: 0,
        themeComplete: 0,
        md5Calls: 0
    };

    const contentEl = {
        html: function (value) {
            record.contentHtml.push(value);
            return this;
        }
    };

    const $ = function (selector) {
        if (selector === "#content") {
            return contentEl;
        }
        return {};
    };
    $.ajax = function (ajaxConfig) {
        const entry = {config: ajaxConfig};
        record.ajaxCalls.push(entry);
        return {
            done: function () { return this; },
            fail: function () { return this; },
            then: function () { return this; }
        };
    };
    if (opts.withDeferred !== false) {
        $.Deferred = function () {
            const p = {
                done: function () { return this; },
                fail: function () { return this; },
                then: function () { return this; }
            };
            return {
                promise: function () { return p; }
            };
        };
    }

    globalThis.$ = $;

    const sysPassApp = {
        log: {
            info: function () {},
            error: function () {},
            debug: function () {}
        },
        config: {
            APP_ROOT: "https://localhost",
            CSRF: "csrf-token",
            LANG: {1: "An error occurred"}
        },
        util: {
            hash: {
                md5: function (value) {
                    record.md5Calls += 1;
                    return "hash:" + value.length;
                }
            }
        },
        actions: {
            main: {
                logout: function () {
                    record.logoutCalls += 1;
                }
            }
        },
        msg: {
            error: function (message) {
                record.errorToasts.push(message);
            },
            html: {
                error: function (message) {
                    return "<div class='err'>" + message + "</div>";
                }
            }
        },
        theme: {
            loading: {
                show: function () { record.loadingShow += 1; },
                hide: function () { record.loadingHide += 1; }
            },
            ajax: {
                complete: function () { record.themeComplete += 1; }
            }
        }
    };

    return {
        requests: globalThis.sysPass.Requests(sysPassApp),
        record: record,
        sysPassApp: sysPassApp
    };
}

test("getActionCall intercepts JSON status 10, logs out once, skips history and callbackOk", function () {
    const ctx = setupRequests();
    const opts = ctx.requests.getRequestOpts();
    opts.url = "/index.php?r=account/search";
    opts.type = "json";
    opts.addHistory = true;

    let callbackCalled = 0;
    ctx.requests.getActionCall(opts, function () {
        callbackCalled += 1;
    });

    assert.equal(ctx.record.ajaxCalls.length, 1);
    ctx.record.ajaxCalls[0].config.success({status: 10, description: "Session timed out", data: []});

    assert.equal(ctx.record.logoutCalls, 1);
    assert.equal(callbackCalled, 0);
    assert.equal(ctx.requests.history.length(), 0);
});

test("getActionCall intercepts JSON status 10 string when opts.type is html", function () {
    const ctx = setupRequests();
    const opts = ctx.requests.getRequestOpts();
    opts.url = "/index.php?r=configManager/index";
    opts.type = "html";
    opts.addHistory = true;

    let callbackCalled = 0;
    ctx.requests.getActionCall(opts, function () {
        callbackCalled += 1;
    });

    ctx.record.ajaxCalls[0].config.success('{"status":10,"description":"Session timed out","data":[]}');

    assert.equal(ctx.record.logoutCalls, 1);
    assert.equal(callbackCalled, 0);
    assert.equal(ctx.requests.history.length(), 0);
});

test("getActionCall intercepts inline logout script in html response", function () {
    const ctx = setupRequests();
    const opts = ctx.requests.getRequestOpts();
    opts.url = "/index.php?r=account/index";
    opts.type = "html";
    opts.addHistory = true;

    let callbackCalled = 0;
    ctx.requests.getActionCall(opts, function () {
        callbackCalled += 1;
    });

    ctx.record.ajaxCalls[0].config.success("  <script>sysPassApp.actions.main.logout();</script>\n");

    assert.equal(ctx.record.logoutCalls, 1);
    assert.equal(callbackCalled, 0);
    assert.equal(ctx.requests.history.length(), 0);
});

test("getActionCall does not trigger logout when active html content mentions logout text", function () {
    const ctx = setupRequests();
    const opts = ctx.requests.getRequestOpts();
    opts.url = "/index.php?r=account/view/1";
    opts.type = "html";

    let received = null;
    ctx.requests.getActionCall(opts, function (response) {
        received = response;
    });

    const html = "<div>Note: sysPassApp.actions.main.logout();</div>";
    ctx.record.ajaxCalls[0].config.success(html);

    assert.equal(ctx.record.logoutCalls, 0);
    assert.equal(received, html);
});

test("getActionCall intercepts redirected login page markup in html response", function () {
    const ctx = setupRequests();
    const opts = ctx.requests.getRequestOpts();
    opts.url = "/index.php?r=account/view/1";
    opts.type = "html";
    opts.addHistory = true;

    let callbackCalled = 0;
    ctx.requests.getActionCall(opts, function () {
        callbackCalled += 1;
    });

    ctx.record.ajaxCalls[0].config.success('<html><body><form id="frmLogin" method="post"></form></body></html>');

    assert.equal(ctx.record.logoutCalls, 1);
    assert.equal(callbackCalled, 0);
    assert.equal(ctx.requests.history.length(), 0);
});

test("getActionCall suppresses duplicate logouts, follow-up XHRs, error toasts, and theme completion during logout", function () {
    const ctx = setupRequests();

    const opts1 = ctx.requests.getRequestOpts();
    opts1.url = "/index.php?r=account/search";
    opts1.type = "json";

    const opts2 = ctx.requests.getRequestOpts();
    opts2.url = "/index.php?r=items/notifications";
    opts2.type = "html";

    let cb1 = 0;
    let cb2 = 0;
    let err2 = 0;

    ctx.requests.getActionCall(opts1, function () { cb1 += 1; });
    ctx.requests.getActionCall(opts2, function () { cb2 += 1; }, function () { err2 += 1; });

    ctx.record.ajaxCalls[0].config.success({status: 10, description: "expired"});
    // Second in-flight request completes or errors while redirect is pending
    ctx.record.ajaxCalls[1].config.success("<div>stale</div>");
    ctx.record.ajaxCalls[1].config.error({responseText: "aborted"}, "error", "Internal Server Error");
    ctx.record.ajaxCalls[1].config.complete({responseJSON: {data: {html: "<div/>"}}});

    // Attempting to fire another request after logout started is ignored
    const opts3 = ctx.requests.getRequestOpts();
    opts3.url = "/index.php?r=items/notifications";
    ctx.requests.getActionCall(opts3, function () {});

    assert.equal(ctx.record.ajaxCalls.length, 2);
    assert.equal(ctx.record.logoutCalls, 1);
    assert.equal(cb1, 0);
    assert.equal(cb2, 0);
    assert.equal(err2, 0);
    assert.equal(ctx.record.errorToasts.length, 0);
    assert.equal(ctx.record.contentHtml.length, 0);
    assert.equal(ctx.record.themeComplete, 0);
    assert.equal(ctx.record.loadingHide, 1);
});

test("getActionCall suppresses error toast on aborted requests and triggers logout on error payload status 10", function () {
    const ctx = setupRequests();

    const optsAbort = ctx.requests.getRequestOpts();
    optsAbort.url = "/index.php?r=items/notifications";
    ctx.requests.getActionCall(optsAbort, function () {});
    ctx.record.ajaxCalls[0].config.error({responseText: ""}, "abort", "abort");

    assert.equal(ctx.record.errorToasts.length, 0);
    assert.equal(ctx.record.logoutCalls, 0);

    const optsTimeoutErr = ctx.requests.getRequestOpts();
    optsTimeoutErr.url = "/index.php?r=account/search";
    ctx.requests.getActionCall(optsTimeoutErr, function () {});
    ctx.record.ajaxCalls[1].config.error({responseJSON: {status: 10}}, "parsererror", "");

    assert.equal(ctx.record.logoutCalls, 1);
    assert.equal(ctx.record.errorToasts.length, 0);
});

test("getActionCall preserves status normalization, history, and callback execution for active sessions", function () {
    const ctx = setupRequests();
    const opts = ctx.requests.getRequestOpts();
    opts.url = "/index.php?r=account/view/1";
    opts.type = "html";
    opts.addHistory = true;

    let received = null;
    ctx.requests.getActionCall(opts, function (response) {
        received = response;
    });

    ctx.record.ajaxCalls[0].config.success("<div>account details</div>");
    ctx.record.ajaxCalls[0].config.complete({});

    assert.equal(received, "<div>account details</div>");
    assert.equal(ctx.requests.history.length(), 1);
    assert.equal(ctx.record.logoutCalls, 0);
    assert.equal(ctx.record.themeComplete, 1);

    const jsonOpts = ctx.requests.getRequestOpts();
    jsonOpts.url = "/index.php?r=items/notifications";
    jsonOpts.type = "json";
    let jsonReceived = null;
    ctx.requests.getActionCall(jsonOpts, function (response) {
        jsonReceived = response;
    });
    ctx.record.ajaxCalls[1].config.success({status: "OK", data: {count: 0}});

    assert.equal(jsonReceived.status, 0);
    assert.equal(ctx.record.logoutCalls, 0);
});
