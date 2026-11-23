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

function setup(options) {
    const opts = options || {};
    const record = {
        calls: [],
        getUrlCalls: [],
        out: [],
        notifications: [],
        magnificClosed: 0,
        dialogs: []
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
    const contentEl = makeElement();
    if (opts.activeTab) {
        const activeTab = makeElement(opts.activeTab);
        activeTab.length = 1;
        contentEl.find = function (selector) {
            if (selector === "[id^='tabs-'].is-active") {
                return activeTab;
            }
            return this;
        };
    }
    const listAccountFiles = makeElement({"action-route": "accountFile/list", "item-id": 10, "delete": 1});
    const badge = makeElement({"color-class": "danger"});
    const tooltip = makeElement();
    const elements = Object.assign({
        "#frmSearch": frmSearch,
        "#res-content": resContent,
        "#content": contentEl,
        "#list-account-files": listAccountFiles,
        "select.select-box-tags[data-hash][data-updated=true]": makeCollection([]),
        ".notifications-badge": makeCollection([badge]),
        ".notifications-tooltip": tooltip,
        "#updates": makeElement()
    }, opts.elements || {});

    globalThis.$ = function (selector) {
        if (selector !== null && typeof selector === "object") {
            return selector;
        }
        return elements[selector];
    };
    globalThis.$.magnificPopup = {
        close: function () {
            record.magnificClosed += 1;
        }
    };
    globalThis.mdlDialog = function () {
        return {
            show: function (options) {
                record.dialogs.push(options);
            }
        };
    };

    record.errors = [];

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
                record.getUrlCalls.push({base: base, data: data});
                if (data === undefined || data === null || data.r === undefined) {
                    return base;
                }
                const route = Array.isArray(data.r)
                    ? data.r.filter(function (part) {
                        return part !== null && part !== undefined && part !== "";
                    }).join("/")
                    : data.r;
                const params = ["r=" + route];
                if (data.tabIndex !== undefined) {
                    params.push("tabIndex=" + data.tabIndex);
                }
                return base + "?" + params.join("&");
            },
            sendNotification: function () {
                record.notifications.push(Array.from(arguments));
            }
        },
        requests: {
            getRequestOpts: function () {
                return {};
            },
            getActionCall: function (opts, callback, callbackError) {
                let resolveFn;
                const promise = new Promise(function (resolve) {
                    resolveFn = resolve;
                });
                record.calls.push({
                    opts: opts,
                    callback: callback,
                    callbackError: callbackError,
                    resolve: resolveFn
                });
                return promise;
            }
        },
        theme: {
            update: function () {}
        },
        msg: {
            error: function (message) {
                record.errors.push(message);
            },
            out: function (json) {
                record.out.push(json);
            }
        },
        triggers: {
            views: {
                common: function () {}
            }
        },
        config: {
            LANG: {
                1: "There was an error",
                12: "Delete selected items?",
                15: "Delete file?",
                43: "Accept",
                44: "Cancel",
                68: "No updates available",
                69: "Unable to check for updates",
                73: "API Token",
                74: "Copy and store this token now. It will not be shown again.",
                75: "Copy Token"
            }
        }
    };

    return {
        actions: globalThis.sysPass.Actions(log),
        badge: badge,
        content: contentEl,
        listAccountFiles: listAccountFiles,
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
    assert.equal(link.children[0].className, "icon material-icons sp-color-text--accent");
    assert.equal(tooltip.textContent, "<script>alert(1)</script>");
    assert.deepEqual(context.updates.htmlValues, []);
});

test("main.getUpdates displays a warning without update content on failure", function () {
    const context = setup();

    context.actions.main.getUpdates();
    context.record.calls[0].callback({status: 2, data: null});

    assert.equal(context.updates.children.length, 2);
    assert.equal(context.updates.children[0].textContent, "warning");
    assert.equal(context.updates.children[0].className, "icon material-icons sp-color-text--warning");
    assert.equal(context.updates.children[1].textContent, "Unable to check for updates");
    assert.deepEqual(context.updates.htmlValues, []);
});

test("appMgmt.save displays generated token once in a DOM-escaped modal on create or refresh", function () {
    const context = setup();
    const $form = makeForm({"action-route": "authToken/saveCreate"});

    context.actions.appMgmt.save($form);
    assert.equal(context.record.calls.length, 1);

    const syntheticToken = "<script>synthetic-token-123</script>";
    context.record.calls[0].callback({
        status: 0,
        description: "Authorization added",
        data: {itemId: 42, token: syntheticToken}
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.magnificClosed, 1);
    assert.equal(context.record.dialogs.length, 1);

    const dialog = context.record.dialogs[0];
    assert.equal(dialog.title, "API Token");
    assert.equal(dialog.cancelable, false);
    assert.equal(dialog.positive.title, "Accept");
    assert.equal(typeof dialog.text, "object");

    const row = dialog.text.children[1];
    const tokenNode = row.children[0];
    const copyButton = row.children[1];
    assert.equal(tokenNode.attrs.class, "dialog-text dialog-token-text");
    assert.equal(tokenNode.textContent, syntheticToken);
    assert.equal(copyButton.attrs["data-clipboard-target"], ".dialog-token-text");
});

test("appMgmt.save closes the form without opening a token modal on metadata-only update", function () {
    const context = setup();
    const $form = makeForm({"action-route": "authToken/saveEdit/42"});

    context.actions.appMgmt.save($form);
    context.record.calls[0].callback({
        status: "OK",
        description: "Authorization updated",
        data: null
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.magnificClosed, 1);
    assert.equal(context.record.dialogs.length, 0);
});

test("appMgmt.save does not close the form or open a token modal on error status", function () {
    const context = setup();
    const $form = makeForm({"action-route": "authToken/saveCreate"});

    context.actions.appMgmt.save($form);
    context.record.calls[0].callback({
        status: 1,
        description: "Validation error",
        data: null
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.magnificClosed, 0);
    assert.equal(context.record.dialogs.length, 0);
});

test("file.delete dispatches delete on confirm and reloads #list-account-files only on status 0", function () {
    const context = setup();
    const control = makeElement({"action-route": "accountFile/delete", "item-id": 77});

    context.actions.file.delete(control);
    assert.equal(context.record.dialogs.length, 1);
    assert.equal(context.record.calls.length, 0);

    context.record.dialogs[0].positive.onClick();
    assert.equal(context.record.calls.length, 1);
    assert.equal(context.record.calls[0].opts.method, "get");
    assert.equal(context.record.calls[0].opts.url, "/index.php?r=accountFile/delete/77");

    context.record.calls[0].callback({status: 0, description: "File deleted", data: null});
    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.out[0].description, "File deleted");
    assert.equal(context.record.calls.length, 2);
    assert.equal(context.record.calls[1].opts.method, "get");
    assert.equal(context.record.calls[1].opts.type, "html");
    assert.equal(context.record.calls[1].opts.url, "/index.php?r=accountFile/list/10");
});

test("file.delete passes ERROR and WARNING to msg.out without reloading #list-account-files", function () {
    for (const status of [1, 2]) {
        const context = setup();
        const control = makeElement({"action-route": "accountFile/delete", "item-id": 77});

        context.actions.file.delete(control);
        context.record.dialogs[0].positive.onClick();
        assert.equal(context.record.calls.length, 1);

        context.record.calls[0].callback({status: status, description: "Delete outcome", data: null});
        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, status);
        assert.equal(context.record.calls.length, 1);
    }
});

test("file.delete cancel reports cancellation without dispatching a request", function () {
    const context = setup();
    const control = makeElement({"action-route": "accountFile/delete", "item-id": 77});

    context.actions.file.delete(control);
    context.record.dialogs[0].negative.onClick({preventDefault: function () {}});

    assert.equal(context.record.calls.length, 0);
    assert.deepEqual(context.record.errors, ["Cancel"]);
});

test("appMgmt.delete dispatches single delete on confirm and refreshes the active tab on OK, ERROR and WARNING", function () {
    for (const outcome of [
        {status: 0, description: "Value deleted"},
        {status: 1, description: "Delete error"},
        {status: 2, description: "Delete warning"}
    ]) {
        const context = setup({
            activeTab: {"tab-index": 7, "tab-route": "itemManager/index"}
        });
        const control = makeElement({"action-route": "itemPreset/delete", "item-id": 42});

        context.actions.appMgmt.delete(control);
        assert.equal(context.record.dialogs.length, 1);
        assert.equal(context.record.calls.length, 0);

        context.record.dialogs[0].positive.onClick({preventDefault: function () {}});
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.calls[0].opts.method, "get");
        assert.equal(context.record.calls[0].opts.url, "/index.php?r=itemPreset/delete/42");
        assert.deepEqual(context.record.calls[0].opts.data, {items: []});

        context.record.calls[0].callback({
            status: outcome.status,
            description: outcome.description,
            data: null
        });
        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, outcome.status);
        assert.equal(context.record.out[0].description, outcome.description);
        assert.equal(context.record.calls.length, 2);
        assert.equal(context.record.calls[1].opts.method, "get");
        assert.equal(context.record.calls[1].opts.type, "html");
        assert.equal(context.record.calls[1].opts.url, "/index.php?r=itemManager/index&tabIndex=7");
    }
});

test("appMgmt.delete builds bulk delete route without trailing item id and sends items[]", function () {
    const selectedRows = [
        makeElement({"item-id": 11}),
        makeElement({"item-id": 22})
    ];
    const gridTable = makeElement();
    gridTable.find = function (selector) {
        if (selector === ".is-selected") {
            return makeCollection(selectedRows);
        }
        return this;
    };

    const context = setup({
        activeTab: {"tab-index": 7, "tab-route": "itemManager/index"},
        elements: {"#data-table-tblItemPreset": gridTable}
    });
    const control = makeElement({
        "action-route": "itemPreset/delete",
        "selection": "#data-table-tblItemPreset"
    });

    context.actions.appMgmt.delete(control);
    assert.equal(context.record.dialogs.length, 1);

    context.record.dialogs[0].positive.onClick({preventDefault: function () {}});
    assert.equal(context.record.calls.length, 1);
    assert.equal(context.record.calls[0].opts.method, "get");
    assert.equal(context.record.calls[0].opts.url, "/index.php?r=itemPreset/delete");
    assert.deepEqual(context.record.calls[0].opts.data, {items: [11, 22]});
});

test("appMgmt.delete aborts without dialog or request when bulk selection is empty", function () {
    const gridTable = makeElement();
    gridTable.find = function (selector) {
        if (selector === ".is-selected") {
            return makeCollection([]);
        }
        return this;
    };

    const context = setup({
        activeTab: {"tab-index": 7, "tab-route": "itemManager/index"},
        elements: {"#data-table-tblItemPreset": gridTable}
    });
    const control = makeElement({
        "action-route": "itemPreset/delete",
        "selection": "#data-table-tblItemPreset"
    });

    context.actions.appMgmt.delete(control);
    assert.equal(context.record.dialogs.length, 0);
    assert.equal(context.record.calls.length, 0);
});

test("appMgmt.delete cancel reports cancellation without dispatching a request", function () {
    const context = setup({
        activeTab: {"tab-index": 7, "tab-route": "itemManager/index"}
    });
    const control = makeElement({"action-route": "itemPreset/delete", "item-id": 42});

    context.actions.appMgmt.delete(control);
    assert.equal(context.record.dialogs.length, 1);
    context.record.dialogs[0].negative.onClick({preventDefault: function () {}});

    assert.equal(context.record.calls.length, 0);
    assert.deepEqual(context.record.errors, ["Cancel"]);
});

test("main.getNotices renders notice badge with semantic warning class", function () {
    const notices = makeElement();
    const context = setup({elements: {"#notices": notices}});

    context.actions.main.getNotices();
    context.record.calls[0].callback({
        status: 0,
        data: [{title: "Notice 1"}]
    });

    assert.equal(notices.htmlValues.length, 1);
    assert.match(notices.htmlValues[0], /sp-color-text--warning/);
    assert.doesNotMatch(notices.htmlValues[0], /mdl-color-text--amber-200/);
});

test("notification.save closes popup, refreshes list, and reloads badge after list resolves on status 0", async function () {
    for (const route of ["notification/saveCreate", "notification/saveEdit/42"]) {
        const context = setup();
        const $form = makeForm({
            "route": route,
            "action-next": "notification/index"
        });
        $form.serialize = function () {
            return "notification_type=notice&notification_component=test";
        };

        context.actions.notification.save($form);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.calls[0].opts.url, "/index.php?r=" + route);
        assert.equal(
            context.record.calls[0].opts.data,
            "notification_type=notice&notification_component=test&sk=sk"
        );

        context.record.calls[0].callback({
            status: 0,
            description: "Notification saved",
            data: null
        });

        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].description, "Notification saved");
        assert.equal(context.record.magnificClosed, 1);
        assert.equal(context.record.calls.length, 2);
        assert.equal(context.record.calls[1].opts.method, "get");
        assert.equal(context.record.calls[1].opts.type, "html");
        assert.equal(context.record.calls[1].opts.url, "/index.php?r=notification/index");

        await Promise.resolve();
        assert.equal(context.record.calls.length, 2);

        context.record.calls[1].resolve();
        await Promise.resolve();
        assert.equal(context.record.calls.length, 3);
        assert.equal(context.record.calls[2].opts.method, "get");
        assert.equal(context.record.calls[2].opts.useLoading, false);
        assert.equal(context.record.calls[2].opts.url, "/index.php?r=items/notifications");
    }
});

test("notification.save keeps popup open and skips list/badge reload on ERROR and WARNING", async function () {
    for (const outcome of [
        {status: 1, description: "Validation error"},
        {status: 2, description: "Save warning"}
    ]) {
        const context = setup();
        const $form = makeForm({
            "route": "notification/saveCreate",
            "action-next": "notification/index"
        });
        $form.serialize = function () {
            return "notification_type=notice";
        };

        context.actions.notification.save($form);
        assert.equal(context.record.calls.length, 1);

        context.record.calls[0].callback({
            status: outcome.status,
            description: outcome.description,
            data: null
        });
        await Promise.resolve();

        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, outcome.status);
        assert.equal(context.record.out[0].description, outcome.description);
        assert.equal(context.record.magnificClosed, 0);
        assert.equal(context.record.calls.length, 1);
    }
});

test("notification.delete dispatches single delete on confirm and reloads list then badge on OK, ERROR and WARNING", async function () {
    for (const outcome of [
        {status: 0, description: "Notification deleted"},
        {status: 1, description: "Delete error"},
        {status: 2, description: "Delete warning"}
    ]) {
        const context = setup();
        const control = makeElement({
            "action-route": "notification/delete",
            "action-next": "notification/index",
            "item-id": 42
        });

        context.actions.notification.delete(control);
        assert.equal(context.record.dialogs.length, 1);
        assert.equal(context.record.calls.length, 0);

        context.record.dialogs[0].positive.onClick({preventDefault: function () {}});
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.calls[0].opts.method, "get");
        assert.equal(context.record.calls[0].opts.url, "/index.php?r=notification/delete/42");
        assert.deepEqual(context.record.calls[0].opts.data, {items: []});

        context.record.calls[0].callback({
            status: outcome.status,
            description: outcome.description,
            data: null
        });
        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, outcome.status);
        assert.equal(context.record.out[0].description, outcome.description);
        assert.equal(context.record.calls.length, 2);
        assert.equal(context.record.calls[1].opts.method, "get");
        assert.equal(context.record.calls[1].opts.type, "html");
        assert.equal(context.record.calls[1].opts.url, "/index.php?r=notification/index");

        await Promise.resolve();
        assert.equal(context.record.calls.length, 2);

        context.record.calls[1].resolve();
        await Promise.resolve();
        assert.equal(context.record.calls.length, 3);
        assert.equal(context.record.calls[2].opts.url, "/index.php?r=items/notifications");
    }
});

test("notification.delete builds bulk delete route without trailing item id and sends items[]", function () {
    const selectedRows = [
        makeElement({"item-id": 11}),
        makeElement({"item-id": 22})
    ];
    const gridTable = makeElement();
    gridTable.find = function (selector) {
        if (selector === ".is-selected") {
            return makeCollection(selectedRows);
        }
        return this;
    };

    const context = setup({
        elements: {"#data-table-tblNotifications": gridTable}
    });
    const control = makeElement({
        "action-route": "notification/delete",
        "action-next": "notification/index",
        "selection": "#data-table-tblNotifications"
    });

    context.actions.notification.delete(control);
    assert.equal(context.record.dialogs.length, 1);

    context.record.dialogs[0].positive.onClick({preventDefault: function () {}});
    assert.equal(context.record.calls.length, 1);
    assert.equal(context.record.calls[0].opts.method, "get");
    assert.equal(context.record.calls[0].opts.url, "/index.php?r=notification/delete");
    assert.deepEqual(context.record.calls[0].opts.data, {items: [11, 22]});
});

test("notification.delete aborts without dialog or request when bulk selection is empty", function () {
    const gridTable = makeElement();
    gridTable.find = function (selector) {
        if (selector === ".is-selected") {
            return makeCollection([]);
        }
        return this;
    };

    const context = setup({
        elements: {"#data-table-tblNotifications": gridTable}
    });
    const control = makeElement({
        "action-route": "notification/delete",
        "action-next": "notification/index",
        "selection": "#data-table-tblNotifications"
    });

    context.actions.notification.delete(control);
    assert.equal(context.record.dialogs.length, 0);
    assert.equal(context.record.calls.length, 0);
});

test("notification.delete cancel reports cancellation without dispatching a request", function () {
    const context = setup();
    const control = makeElement({
        "action-route": "notification/delete",
        "action-next": "notification/index",
        "item-id": 42
    });

    context.actions.notification.delete(control);
    assert.equal(context.record.dialogs.length, 1);
    context.record.dialogs[0].negative.onClick({preventDefault: function () {}});

    assert.equal(context.record.calls.length, 0);
    assert.deepEqual(context.record.errors, ["Cancel"]);
});

test("user.savePassword dispatches POST, displays status via msg.out, and closes popup on status 0", function () {
    const context = setup();
    const $form = makeForm({"action-route": "user/saveEditPass/42"});
    $form.serialize = function () {
        return "password=encrypted&password_repeat=encrypted";
    };

    context.actions.user.savePassword($form);
    assert.equal(context.record.calls.length, 1);
    assert.equal(context.record.calls[0].opts.method, "post");
    assert.equal(context.record.calls[0].opts.url, "/index.php?r=user/saveEditPass/42");
    assert.equal(
        context.record.calls[0].opts.data,
        "password=encrypted&password_repeat=encrypted&sk=sk"
    );

    context.record.calls[0].callback({
        status: 0,
        description: "Password updated",
        data: null
    });

    assert.equal(context.record.out.length, 1);
    assert.equal(context.record.out[0].description, "Password updated");
    assert.equal(context.record.magnificClosed, 1);
});

test("user.savePassword keeps popup open and displays status via msg.out on ERROR and WARNING", function () {
    for (const outcome of [
        {status: 1, description: "Password error"},
        {status: 2, description: "Password warning"}
    ]) {
        const context = setup();
        const $form = makeForm({"action-route": "user/saveEditPass/42"});
        $form.serialize = function () {
            return "password=encrypted&password_repeat=encrypted";
        };

        context.actions.user.savePassword($form);
        assert.equal(context.record.calls.length, 1);

        context.record.calls[0].callback({
            status: outcome.status,
            description: outcome.description,
            data: null
        });

        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, outcome.status);
        assert.equal(context.record.out[0].description, outcome.description);
        assert.equal(context.record.magnificClosed, 0);
    }
});

test("user.savePassword handles null, undefined, and non-object responses without throwing", function () {
    for (const malformed of [null, undefined, "", 42]) {
        const context = setup();
        const $form = makeForm({"action-route": "user/saveEditPass/42"});
        $form.serialize = function () {
            return "password=encrypted&password_repeat=encrypted";
        };

        context.actions.user.savePassword($form);
        assert.equal(context.record.calls.length, 1);

        assert.doesNotThrow(function () {
            context.record.calls[0].callback(malformed);
        });

        assert.equal(context.record.out.length, 0);
        assert.equal(context.record.magnificClosed, 0);
    }
});

test("account.listFiles injects valid HTML list and empty-state fragments", function () {
    for (const html of [
        "<div id=\"files-wrap\"><ul class=\"mdl-list\"><li>file.txt</li></ul></div>",
        "<div id=\"files-wrap\"><ul class=\"mdl-list\"></ul></div>\n<div class=\"noRes round\">There are no linked files for the account</div>"
    ]) {
        const context = setup();

        context.actions.account.listFiles(context.listAccountFiles);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.calls[0].opts.method, "get");
        assert.equal(context.record.calls[0].opts.type, "html");
        assert.equal(context.record.calls[0].opts.url, "/index.php?r=accountFile/list/10");
        assert.equal(typeof context.record.calls[0].callbackError, "function");

        context.record.calls[0].callback(html);
        assert.deepEqual(context.listAccountFiles.htmlValues, [html]);
        assert.equal(context.record.out.length, 0);
        assert.equal(context.record.errors.length, 0);
        assert.equal(context.listAccountFiles.children.length, 0);
    }
});

test("account.listFiles injects data.html from a JSON OK envelope without injecting raw JSON", function () {
    for (const payload of [
        JSON.stringify({status: "OK", description: "", data: {html: "<div id=\"files-wrap\">ok</div>"}}),
        {status: 0, description: "", data: {html: "<div id=\"files-wrap\">ok</div>"}}
    ]) {
        const context = setup();

        context.actions.account.listFiles(context.listAccountFiles);
        context.record.calls[0].callback(payload);

        assert.deepEqual(context.listAccountFiles.htmlValues, ["<div id=\"files-wrap\">ok</div>"]);
        assert.equal(context.record.out.length, 0);
        assert.equal(context.record.errors.length, 0);
    }
});

test("account.listFiles handles JSON ActionResponse error, warning, and status 10 without injecting raw JSON", function () {
    const cases = [
        {
            raw: JSON.stringify({
                status: "ERROR",
                description: "You don't have permission to access this account <script>",
                messages: [],
                data: null
            }),
            expectedStatus: 1,
            expectedDescription: "You don't have permission to access this account <script>"
        },
        {
            raw: JSON.stringify({
                status: "WARNING",
                description: "File list warning",
                messages: [],
                data: null
            }),
            expectedStatus: 2,
            expectedDescription: "File list warning"
        },
        {
            raw: {status: 10, description: "Session finished", data: null},
            expectedStatus: 10,
            expectedDescription: "Session finished"
        }
    ];

    for (const item of cases) {
        const context = setup();

        context.actions.account.listFiles(context.listAccountFiles);
        context.record.calls[0].callback(item.raw);

        assert.deepEqual(context.listAccountFiles.htmlValues, []);
        assert.equal(context.listAccountFiles.emptied, true);
        assert.equal(context.listAccountFiles.children.length, 1);
        assert.equal(context.listAccountFiles.children[0].tagName, "p");
        assert.equal(context.listAccountFiles.children[0].className, "error round");
        assert.equal(context.listAccountFiles.children[0].textContent, item.expectedDescription);
        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, item.expectedStatus);
        assert.equal(context.record.out[0].description, item.expectedDescription);
        assert.equal(context.record.errors.length, 0);
    }
});

test("account.listFiles rejects OK JSON without HTML, non-object JSON, plain-text errors, and empty/malformed payloads", function () {
    const cases = [
        {
            payload: JSON.stringify({status: "OK", description: "Files management disabled", data: null}),
            expectedText: "Files management disabled"
        },
        {
            payload: "[]",
            expectedText: "There was an error"
        },
        {
            payload: "null",
            expectedText: "There was an error"
        },
        {
            payload: "{\"status\":\"ERROR\"",
            expectedText: "There was an error"
        },
        {
            payload: "You don't have permission to access this account",
            expectedText: "You don't have permission to access this account"
        },
        {
            payload: "Files management disabled",
            expectedText: "Files management disabled"
        },
        {
            payload: "",
            expectedText: "There was an error"
        },
        {
            payload: null,
            expectedText: "There was an error"
        },
        {
            payload: undefined,
            expectedText: "There was an error"
        }
    ];

    for (const item of cases) {
        const context = setup();

        context.actions.account.listFiles(context.listAccountFiles);
        assert.doesNotThrow(function () {
            context.record.calls[0].callback(item.payload);
        });

        assert.deepEqual(context.listAccountFiles.htmlValues, []);
        assert.equal(context.listAccountFiles.emptied, true);
        assert.equal(context.listAccountFiles.children.length, 1);
        assert.equal(context.listAccountFiles.children[0].className, "error round");
        assert.equal(context.listAccountFiles.children[0].textContent, item.expectedText);
        assert.equal(context.record.out.length, 0);
        assert.deepEqual(context.record.errors, [item.expectedText]);
    }
});

test("account.listFiles handles transport and HTTP error callbacks without overwriting #content", function () {
    const context = setup();

    context.actions.account.listFiles(context.listAccountFiles);
    assert.equal(typeof context.record.calls[0].callbackError, "function");

    context.record.calls[0].callbackError();

    assert.deepEqual(context.content.htmlValues, []);
    assert.equal(context.content.emptied, false);
    assert.deepEqual(context.listAccountFiles.htmlValues, []);
    assert.equal(context.listAccountFiles.emptied, true);
    assert.equal(context.listAccountFiles.children.length, 1);
    assert.equal(context.listAccountFiles.children[0].className, "error round");
    assert.equal(context.listAccountFiles.children[0].textContent, "There was an error");
    assert.deepEqual(context.record.errors, ["There was an error"]);
});

test("config.import serializes form with sk and routes OK, ERROR, and WARNING envelopes to msg.out only", function () {
    for (const outcome of [
        {status: 0, description: "Import complete"},
        {status: 1, description: "Import failed"},
        {status: 2, description: "Import warning"}
    ]) {
        const context = setup();
        const $form = makeForm({"action-route": "configImport/import"});
        $form.serialize = function () {
            return "import_type=xml";
        };

        context.actions.config.import($form);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.calls[0].opts.method, undefined);
        assert.equal(context.record.calls[0].opts.url, "/index.php?r=configImport/import");
        assert.equal(context.record.calls[0].opts.data, "import_type=xml&sk=sk");

        context.record.calls[0].callback({
            status: outcome.status,
            description: outcome.description,
            data: null
        });

        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, outcome.status);
        assert.equal(context.record.out[0].description, outcome.description);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.magnificClosed, 0);
        assert.deepEqual(context.content.htmlValues, []);
        assert.deepEqual(context.resContent.htmlValues, []);
    }
});

test("config.import handles null, undefined, and non-object responses without calling msg.out or throwing", function () {
    for (const malformed of [null, undefined, "", 42]) {
        const context = setup();
        const $form = makeForm({"action-route": "configImport/import"});
        $form.serialize = function () {
            return "import_type=xml";
        };

        context.actions.config.import($form);
        assert.equal(context.record.calls.length, 1);

        assert.doesNotThrow(function () {
            context.record.calls[0].callback(malformed);
        });

        assert.equal(context.record.out.length, 0);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.magnificClosed, 0);
    }
});

test("config.refreshMpass dispatches GET with sk and isAjax=1 and routes OK, ERROR, and WARNING envelopes to msg.out only", function () {
    for (const outcome of [
        {status: 0, description: "Hash updated"},
        {status: 1, description: "Hash update failed"},
        {status: 2, description: "Hash update warning"}
    ]) {
        const context = setup();
        const control = makeElement({"action-route": "configEncryption/refresh"});

        context.actions.config.refreshMpass(control);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.calls[0].opts.method, "get");
        assert.equal(context.record.calls[0].opts.url, "/index.php?r=configEncryption/refresh");
        assert.deepEqual(context.record.getUrlCalls, [
            {
                base: "/index.php",
                data: {
                    r: "configEncryption/refresh",
                    sk: "sk",
                    isAjax: 1
                }
            }
        ]);

        context.record.calls[0].callback({
            status: outcome.status,
            description: outcome.description,
            data: null
        });

        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, outcome.status);
        assert.equal(context.record.out[0].description, outcome.description);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.magnificClosed, 0);
        assert.deepEqual(context.content.htmlValues, []);
        assert.deepEqual(context.resContent.htmlValues, []);
    }
});

test("config.refreshMpass handles null, undefined, and non-object responses without calling msg.out or throwing", function () {
    for (const malformed of [null, undefined, "", 42]) {
        const context = setup();
        const control = makeElement({"action-route": "configEncryption/refresh"});

        context.actions.config.refreshMpass(control);
        assert.equal(context.record.calls.length, 1);

        assert.doesNotThrow(function () {
            context.record.calls[0].callback(malformed);
        });

        assert.equal(context.record.out.length, 0);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.magnificClosed, 0);
    }
});

test("config.mailCheck serializes data-src form with sk and routes OK, ERROR, and WARNING envelopes to msg.out only", function () {
    for (const outcome of [
        {status: 0, description: "Mail configuration OK"},
        {status: 1, description: "Mail connection error"},
        {status: 2, description: "Mail check warning"}
    ]) {
        const $form = makeForm();
        $form.serialize = function () {
            return "mail_server=smtp.example.com";
        };
        const context = setup({
            elements: {"#frmMail": $form}
        });
        const control = makeElement({
            "action-route": "configMail/check",
            "src": "#frmMail"
        });

        context.actions.config.mailCheck(control);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.calls[0].opts.method, undefined);
        assert.equal(context.record.calls[0].opts.url, "/index.php?r=configMail/check");
        assert.equal(context.record.calls[0].opts.data, "mail_server=smtp.example.com&sk=sk");

        context.record.calls[0].callback({
            status: outcome.status,
            description: outcome.description,
            data: null
        });

        assert.equal(context.record.out.length, 1);
        assert.equal(context.record.out[0].status, outcome.status);
        assert.equal(context.record.out[0].description, outcome.description);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.magnificClosed, 0);
        assert.deepEqual(context.content.htmlValues, []);
        assert.deepEqual(context.resContent.htmlValues, []);
    }
});

test("config.mailCheck handles null, undefined, and non-object responses without calling msg.out or throwing", function () {
    for (const malformed of [null, undefined, "", 42]) {
        const $form = makeForm();
        $form.serialize = function () {
            return "mail_server=smtp.example.com";
        };
        const context = setup({
            elements: {"#frmMail": $form}
        });
        const control = makeElement({
            "action-route": "configMail/check",
            "src": "#frmMail"
        });

        context.actions.config.mailCheck(control);
        assert.equal(context.record.calls.length, 1);

        assert.doesNotThrow(function () {
            context.record.calls[0].callback(malformed);
        });

        assert.equal(context.record.out.length, 0);
        assert.equal(context.record.calls.length, 1);
        assert.equal(context.record.magnificClosed, 0);
    }
});

test("user.savePassword prevents duplicate submission while in flight, toggles button loading state, and closes dialog on OK", function () {
    const submitBtn = makeElement();
    submitBtn.removeAttr = function (key) {
        delete this.attrs[key];
        return this;
    };
    submitBtn.prop = function (key, value) {
        this[key] = value;
        return this;
    };
    const context = setup({
        elements: {
            "button[form='frmUpdUsrPass']": submitBtn
        }
    });
    const $form = makeElement({"action-route": "user/saveEditPass/1"});
    $form.attr("id", "frmUpdUsrPass");
    $form.removeAttr = function (key) {
        delete this.attrs[key];
        return this;
    };
    $form.serialize = function () {
        return "password=enc1&password_repeat=enc1&isAjax=1";
    };

    context.actions.user.savePassword($form);
    assert.equal(context.record.calls.length, 1);
    assert.equal($form.attr("data-submitting"), "1");
    assert.equal(submitBtn.attr("disabled"), "disabled");
    assert.equal(submitBtn.attr("aria-busy"), "true");
    assert.equal(submitBtn.classesAdded.includes("is-loading"), true);

    // Second invocation while in-flight is ignored
    context.actions.user.savePassword($form);
    assert.equal(context.record.calls.length, 1);

    // Error response re-enables form and button without closing dialog
    context.record.calls[0].callback({status: 1, description: "Passwords do not match"});
    assert.equal($form.attr("data-submitting"), undefined);
    assert.equal(submitBtn.attr("disabled"), undefined);
    assert.equal(submitBtn.attr("aria-busy"), undefined);
    assert.equal(context.record.magnificClosed, 0);

    // Re-submit and resolve with OK
    context.actions.user.savePassword($form);
    assert.equal(context.record.calls.length, 2);
    context.record.calls[1].callback({status: 0, description: "Password updated"});
    assert.equal($form.attr("data-submitting"), undefined);
    assert.equal(context.record.magnificClosed, 1);
});

