import assert from "node:assert/strict";
import {createRequire} from "node:module";
import test from "node:test";

globalThis.sysPass = {};

const require = createRequire(import.meta.url);
require("../../public/js/app-actions.js");

const log = {
    debug: function () {},
    info: function () {},
    error: function () {}
};

function makeElement(dataMap) {
    return {
        attrs: {},
        classesAdded: [],
        classesRemoved: [],
        emptied: false,
        htmlValues: [],
        children: [],
        dataMap: dataMap || {},
        eachCb: null,
        find: function () {
            return this;
        },
        val: function (value) {
            if (value !== undefined) {
                this.value = value;
                return this;
            }
            return this.value;
        },
        data: function (key) {
            return this.dataMap[key];
        },
        serialize: function () {
            return "";
        },
        empty: function () {
            this.emptied = true;
            return this;
        },
        html: function (value) {
            if (value !== undefined) {
                this.htmlValues.push(value);
                return this;
            }
            return this.htmlValues[0];
        },
        append: function (...children) {
            this.children.push(...children);
            return this;
        },
        attr: function (key, value) {
            if (value !== undefined) {
                this.attrs[key] = value;
                return this;
            }
            return this.attrs[key];
        },
        addClass: function (name) {
            this.classesAdded.push(name);
            return this;
        },
        removeClass: function (name) {
            this.classesRemoved.push(name);
            return this;
        },
        each: function (callback) {
            if (this.eachCb !== null) {
                this.eachCb(callback);
            }
            return this;
        }
    };
}

function makeCollection(items) {
    return {
        each: function (callback) {
            items.forEach(function (item, index) {
                callback.call(item, index, item);
            });
            return this;
        }
    };
}

function setup() {
    const record = {
        calls: [],
        out: [],
        notifications: []
    };

    globalThis.document = {
        createElement: function (tagName) {
            const attrs = {};
            return {
                tagName: tagName,
                attrs: attrs,
                children: [],
                setAttribute: function (name, value) {
                    attrs[name] = value;
                },
                appendChild: function (child) {
                    this.children.push(child);
                }
            };
        }
    };

    const frmSearch = makeElement({"action-route": "account/search"});
    const resContent = makeElement();
    const badge = makeElement({"color-class": "danger"});
    const tooltip = makeElement();
    const elements = {
        "#frmSearch": frmSearch,
        "#res-content": resContent,
        "select.select-box-tags[data-hash][data-updated=true]": makeCollection([]),
        ".notifications-badge": makeCollection([badge]),
        ".notifications-tooltip": tooltip,
        "#updates": makeElement()
    };

    globalThis.$ = function (selector) {
        if (selector !== null && typeof selector === "object") {
            return selector;
        }
        return elements[selector];
    };

    globalThis.sysPassApp = {
        sk: {
            get: function () {
                return "sk";
            }
        },
        util: {
            buildRoute: function (parts) {
                return parts.filter(function (part) {
                    return part !== undefined && part !== null && part !== "";
                }).join("/");
            },
            getUrl: function (base, data) {
                if (data === undefined || data === null || data.r === undefined) {
                    return base;
                }
                return base + "?r=" + (Array.isArray(data.r) ? data.r.join("/") : data.r);
            },
            sendNotification: function () {
                record.notifications.push(Array.from(arguments));
            }
        },
        requests: {
            getRequestOpts: function () {
                return {};
            },
            getActionCall: function (opts, callback) {
                record.calls.push({opts: opts, callback: callback});
            }
        },
        theme: {
            update: function () {}
        },
        msg: {
            out: function (json) {
                record.out.push(json);
            }
        },
        triggers: {
            views: {}
        },
        config: {
            LANG: {
                68: "No updates available",
                69: "Unable to check for updates"
            }
        }
    };

    return {
        actions: globalThis.sysPass.Actions(log),
        badge: badge,
        record: record,
        resContent: resContent,
        tooltip: tooltip,
        updates: elements["#updates"]
    };
}

function makeForm(dataMap) {
    const element = makeElement(dataMap);
    return element;
}

test("history password copy uses the shared copy action without a recursive handler", function () {
    const context = setup();
    const control = makeElement({"action-route": "account/copyPassHistory", "parent-id": 0, "item-id": 5});

    assert.equal(context.actions.account.copyPassHistory, undefined);
    context.actions.account.copyPass(control);
    assert.equal(context.record.calls.length, 1);
    assert.equal(context.record.calls[0].opts.url, "/index.php?r=account/copyPassHistory/5");
    assert.equal(context.record.calls[0].opts.method, "get");
    assert.equal(context.record.calls[0].opts.async, false);
});

test("account.search reports an error envelope with null data once", function () {
    const context = setup();

    context.actions.account.search();
    assert.equal(context.record.calls.length, 1);

    assert.doesNotThrow(function () {
        context.record.calls[0].callback({status: 1, description: "forced", data: null});
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.out[0].description, "forced");
    assert.equal(context.resContent.emptied, false);
    assert.deepEqual(context.resContent.htmlValues, []);
});

test("account.search renders successful results", function () {
    const context = setup();

    context.actions.account.search();
    context.record.calls[0].callback({status: 0, data: {html: "<div/>"}});

    assert.equal(context.record.out.length, 0);
    assert.equal(context.resContent.emptied, true);
    assert.deepEqual(context.resContent.htmlValues, ["<div/>"]);
});

test("account.search reports a status 10 envelope without clearing results", function () {
    const context = setup();

    context.actions.account.search();
    context.record.calls[0].callback({status: 10, description: "session", data: null});

    assert.equal(context.record.out.length, 1);
    assert.equal(context.resContent.emptied, false);
    assert.deepEqual(context.resContent.htmlValues, []);
});

test("account.save reports an error envelope with null data once", function () {
    const context = setup();
    const $obj = makeForm({"action-route": "account/save", "item-id": 5});

    context.actions.account.save($obj);
    assert.equal(context.record.calls.length, 1);

    assert.doesNotThrow(function () {
        context.record.calls[0].callback({status: 1, description: "forced", data: null});
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.calls.length, 1);
});

test("account.save navigates after a successful save", function () {
    const context = setup();
    const $obj = makeForm({"action-route": "account/save", "item-id": 5});

    context.actions.account.save($obj);
    context.record.calls[0].callback({status: 0, data: {itemId: 5, nextAction: "account/view"}});

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.calls.length, 2);
    assert.equal(context.record.calls[1].opts.url, "/index.php?r=account/view/5");
});

test("account.saveEditRestore reports an error envelope with null data once", function () {
    const context = setup();
    const $obj = makeForm({
        "action-route": "account/saveEditRestore",
        "history-id": 3,
        "item-id": 5
    });

    context.actions.account.saveEditRestore($obj);
    assert.equal(context.record.calls.length, 1);

    assert.doesNotThrow(function () {
        context.record.calls[0].callback({status: 1, description: "forced", data: null});
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.calls.length, 1);
});

test("account.saveEditRestore navigates after a successful restore", function () {
    const context = setup();
    const $obj = makeForm({
        "action-route": "account/saveEditRestore",
        "history-id": 3,
        "item-id": 5
    });

    context.actions.account.saveEditRestore($obj);
    context.record.calls[0].callback({status: 0, data: {itemId: 5, nextAction: "account/view"}});

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.calls.length, 2);
    assert.equal(context.record.calls[1].opts.url, "/index.php?r=account/view/5");
});

test("notification.getActive reports an error envelope with null data once", function () {
    const context = setup();

    context.actions.notification.getActive();
    assert.equal(context.record.calls.length, 1);

    assert.doesNotThrow(function () {
        context.record.calls[0].callback({status: 1, description: "forced", data: null});
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.badge.attrs["data-badge"], undefined);
    assert.equal(context.record.notifications.length, 0);
});

test("notification.getActive updates the badge without notifying for zero notifications", function () {
    const context = setup();

    context.actions.notification.getActive();
    context.record.calls[0].callback({status: 0, data: {count: 0, message: "empty"}});

    assert.equal(context.record.out.length, 0);
    assert.equal(context.badge.attrs["data-badge"], 0);
    assert.deepEqual(context.badge.classesRemoved, ["danger"]);
    assert.deepEqual(context.tooltip.htmlValues, ["empty"]);
    assert.equal(context.record.notifications.length, 0);
});

test("notification.getActive notifies for active notifications", function () {
    const context = setup();

    context.actions.notification.getActive();
    context.record.calls[0].callback({
        status: 0,
        data: {
            count: 2,
            message: "empty",
            message_has: "active",
            notifications: ["one", "two"],
            hash: "abc"
        }
    });

    assert.equal(context.record.out.length, 0);
    assert.equal(context.badge.attrs["data-badge"], 2);
    assert.deepEqual(context.badge.classesAdded, ["danger"]);
    assert.deepEqual(context.record.notifications, [["active", "one\ntwo", "abc"]]);
});

test("main.getUpdates renders remote release values as text and attributes", function () {
    const context = setup();

    context.actions.main.getUpdates();
    context.record.calls[0].callback({
        status: "OK",
        data: {
            version: "v5.2.10.23010101",
            url: "https://github.com/nuxsmin/sysPass/releases/tag/v5.2.10.23010101",
            title: "<img src=x onerror=alert(1)>",
            description: "<script>alert(1)</script>"
        }
    });

    const [link, tooltip] = context.updates.children;
    assert.equal(link.tagName, "a");
    assert.equal(link.attrs.href, "https://github.com/nuxsmin/sysPass/releases/tag/v5.2.10.23010101");
    assert.equal(link.attrs.rel, "noopener noreferrer");
    assert.equal(link.textContent, "<img src=x onerror=alert(1)>");
    assert.equal(link.children[0].textContent, "cloud_download");
    assert.equal(tooltip.textContent, "<script>alert(1)</script>");
    assert.deepEqual(context.updates.htmlValues, []);
});

test("main.getUpdates displays a warning without update content on failure", function () {
    const context = setup();

    context.actions.main.getUpdates();
    context.record.calls[0].callback({status: 2, data: null});

    assert.equal(context.updates.children.length, 2);
    assert.equal(context.updates.children[0].textContent, "warning");
    assert.equal(context.updates.children[1].textContent, "Unable to check for updates");
    assert.deepEqual(context.updates.htmlValues, []);
});
