import assert from "node:assert/strict";
import {createRequire} from "node:module";
import test from "node:test";

globalThis.sysPass = {};

const require = createRequire(import.meta.url);
require("../../public/js/app-triggers.js");

function makeNode(tag, attrs = {}) {
    return {
        tagName: tag.toLowerCase(),
        attrs: Object.assign({}, attrs),
        classes: new Set(),
        listeners: {},
        value: attrs.value || "",
        checked: Boolean(attrs.checked),
        parentNode: null,
        closestMap: {},
        siblingsList: [],
        selectizeConfig: null,
        length: 1,
        attr(k, v) {
            if (v === undefined) {
                return this.attrs[k];
            }
            this.attrs[k] = String(v);
            return this;
        },
        removeAttr(k) {
            delete this.attrs[k];
            return this;
        },
        addClass(c) {
            c.split(/\s+/).filter(Boolean).forEach((x) => this.classes.add(x));
            return this;
        },
        removeClass(c) {
            c.split(/\s+/).filter(Boolean).forEach((x) => this.classes.delete(x));
            return this;
        },
        hasClass(c) {
            return this.classes.has(c);
        },
        parent() {
            return this.parentNode || makeNode("div");
        },
        closest(sel) {
            return this.closestMap[sel] || makeNode("div");
        },
        siblings() {
            return {length: 0};
        },
        append() {
            return this;
        },
        data() {
            return undefined;
        },
        each(cb) {
            cb.call(this, 0, this);
            return this;
        },
        selectize(opts) {
            this.selectizeConfig = opts;
            return this;
        },
        on(events, cb) {
            events.split(/\s+/).filter(Boolean).forEach((ev) => {
                this.listeners[ev] = this.listeners[ev] || [];
                this.listeners[ev].push(cb);
            });
            return this;
        },
        trigger(ev) {
            (this.listeners[ev] || []).forEach((cb) => cb.call(this, {preventDefault: () => {}}));
            return this;
        }
    };
}

test("views.upgrade syncs #btnChange aria-disabled and toggles invalid styling on input, change, and invalid events", function () {
    const keyWrap = makeNode("div", {class: "mdl-textfield"});
    const keyInput = makeNode("input", {id: "key"});
    keyInput.parentNode = keyWrap;
    keyInput[0] = keyInput;

    const confirmBox = makeNode("div", {class: "sp-upgrade-confirm"});
    const chkConfirm = makeNode("input", {id: "chkConfirm"});
    chkConfirm.closestMap[".sp-upgrade-confirm"] = confirmBox;
    chkConfirm[0] = chkConfirm;

    const btnChange = makeNode("button", {id: "btnChange"});
    const feedback = makeNode("div", {id: "upgrade-feedback", hidden: "hidden"});

    const frmUpgrade = makeNode("form", {id: "frmUpgrade"});
    frmUpgrade.checkValidity = () => keyInput.value.trim() !== "" && chkConfirm.checked;
    frmUpgrade[0] = frmUpgrade;
    frmUpgrade.find = (sel) => {
        if (sel === "#key") return keyInput;
        if (sel === "#chkConfirm") return chkConfirm;
        if (sel === "#btnChange") return btnChange;
        return makeNode("div");
    };

    const elements = {
        "#frmUpgrade": frmUpgrade,
        "#upgrade-feedback": feedback,
        "#btnChange": btnChange,
        "#key": keyInput,
        "#chkConfirm": chkConfirm
    };

    globalThis.$ = (sel) => (typeof sel === "object" && sel !== null ? sel : elements[sel] || makeNode("div"));
    globalThis.$.extend = Object.assign;

    const triggers = globalThis.sysPass.Triggers({info: () => {}, debug: () => {}});
    triggers.views.upgrade();

    assert.equal(btnChange.attr("aria-disabled"), "true");

    // Invalid events highlight both controls
    keyInput.trigger("invalid");
    assert.equal(keyInput.attr("aria-invalid"), "true");
    assert.equal(keyWrap.hasClass("is-invalid"), true);

    chkConfirm.trigger("invalid");
    assert.equal(chkConfirm.attr("aria-invalid"), "true");
    assert.equal(confirmBox.hasClass("is-invalid"), true);

    // Entering key only leaves btnChange aria-disabled="true" until chkConfirm is checked
    keyInput.value = "upgrade-key";
    keyInput.trigger("input");
    assert.equal(keyInput.attr("aria-invalid"), undefined);
    assert.equal(keyWrap.hasClass("is-invalid"), false);
    assert.equal(btnChange.attr("aria-disabled"), "true");

    chkConfirm.checked = true;
    chkConfirm.trigger("change");
    assert.equal(chkConfirm.attr("aria-invalid"), undefined);
    assert.equal(confirmBox.hasClass("is-invalid"), false);
    assert.equal(btnChange.attr("aria-disabled"), undefined);

    // Clearing key or unchecking re-applies aria-disabled="true"
    chkConfirm.checked = false;
    chkConfirm.trigger("change");
    assert.equal(btnChange.attr("aria-disabled"), "true");
});

test("selectDetect forwards aria-describedby from original select to selectize control input", function () {
    const select = makeNode("select", {id: "sel-sitelang", "aria-describedby": "sitelang-hint"});
    const emptySet = {each: () => emptySet, selectize: () => emptySet};
    const container = {
        find(sel) {
            if (sel === ".select-box") return select;
            return emptySet;
        }
    };

    globalThis.$ = (sel) => (typeof sel === "object" && sel !== null ? sel : makeNode("div"));
    globalThis.$.extend = Object.assign;
    globalThis.sysPassApp = {config: {LANG: {51: "Clear"}}};

    const triggers = globalThis.sysPass.Triggers({info: () => {}, debug: () => {}});
    triggers.selectDetect(container);

    const wrapper = makeNode("div");
    const controlInput = makeNode("input", {id: "sel-sitelang-selectized"});
    select.selectizeConfig.onInitialize.call({
        $wrapper: [wrapper],
        $input: [select],
        $control_input: controlInput
    });

    assert.equal(controlInput.attr("aria-describedby"), "sitelang-hint");
});
