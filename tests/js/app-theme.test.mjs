import assert from "node:assert/strict";
import {createRequire} from "node:module";
import test from "node:test";

globalThis.sysPass = {};
globalThis.jQuery = undefined;

const require = createRequire(import.meta.url);
require("../../app/modules/web/themes/material-blue/js/app-theme.js");

function createFakeDom() {
    class FakeElement {
        constructor(tagName, attrs = {}) {
            this.tagName = tagName.toLowerCase();
            this.attrs = {};
            this.dataset = {};
            this.classes = new Set();
            this.children = [];
            this.parentNode = null;
            this.listeners = {};
            this.style = {};
            this.value = "";
            this.textContent = "";
            this.hidden = false;

            for (const [k, v] of Object.entries(attrs)) {
                this.setAttribute(k, v);
            }
        }

        setAttribute(key, value) {
            const strVal = String(value);
            this.attrs[key] = strVal;
            if (key === "id") {
                this.id = strVal;
            } else if (key === "class") {
                this.classes = new Set(strVal.split(/\s+/).filter(Boolean));
            } else if (key === "value") {
                this.value = strVal;
            } else if (key.startsWith("data-")) {
                const camel = key.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase());
                this.dataset[camel] = strVal;
            }
        }

        getAttribute(key) {
            if (key === "class") {
                return Array.from(this.classes).join(" ");
            }
            if (key === "value") {
                return this.value;
            }
            return Object.prototype.hasOwnProperty.call(this.attrs, key) ? this.attrs[key] : undefined;
        }

        removeAttribute(key) {
            delete this.attrs[key];
            if (key.startsWith("data-")) {
                const camel = key.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase());
                delete this.dataset[camel];
            }
        }

        appendChild(child) {
            if (child.parentNode) {
                child.parentNode.removeChild(child);
            }
            child.parentNode = this;
            this.children.push(child);
            return child;
        }

        prependChild(child) {
            if (child.parentNode) {
                child.parentNode.removeChild(child);
            }
            child.parentNode = this;
            this.children.unshift(child);
            return child;
        }

        insertAfter(newChild, refChild) {
            if (newChild.parentNode) {
                newChild.parentNode.removeChild(newChild);
            }
            const idx = this.children.indexOf(refChild);
            newChild.parentNode = this;
            if (idx === -1) {
                this.children.push(newChild);
            } else {
                this.children.splice(idx + 1, 0, newChild);
            }
            return newChild;
        }

        removeChild(child) {
            const idx = this.children.indexOf(child);
            if (idx !== -1) {
                this.children.splice(idx, 1);
                child.parentNode = null;
            }
        }

        matches(selector) {
            const parts = selector.trim().split(/\s+/);
            const simple = parts[parts.length - 1];
            const m = simple.match(/^([a-z0-9*]+)?((?:[#.][a-zA-Z0-9_-]+|\[[^\]]+\])*)$/);
            if (!m) {
                return false;
            }
            const tag = m[1];
            const rest = m[2] || "";
            if (tag && tag !== "*" && this.tagName !== tag.toLowerCase()) {
                return false;
            }
            const tokens = rest.match(/[#.][a-zA-Z0-9_-]+|\[[^\]]+\]/g) || [];
            for (const tok of tokens) {
                if (tok.startsWith("#")) {
                    if (this.attrs.id !== tok.slice(1)) return false;
                } else if (tok.startsWith(".")) {
                    if (!this.classes.has(tok.slice(1))) return false;
                } else if (tok.startsWith("[")) {
                    const eqIdx = tok.indexOf("=");
                    if (eqIdx === -1) {
                        const attrName = tok.slice(1, -1);
                        if (this.getAttribute(attrName) === undefined) return false;
                    } else {
                        const attrName = tok.slice(1, eqIdx);
                        const attrVal = tok.slice(eqIdx + 1, -1).replace(/^['"]|['"]$/g, "");
                        if (this.getAttribute(attrName) !== attrVal) return false;
                    }
                }
            }
            return true;
        }

        queryAll(selector) {
            const results = [];
            for (const sub of selector.split(",")) {
                const trimmed = sub.trim();
                const parts = trimmed.split(/\s+/);
                const walk = (node) => {
                    for (const child of node.children) {
                        if (parts.length === 1) {
                            if (child.matches(trimmed)) {
                                results.push(child);
                            }
                        } else if (child.matches(parts[parts.length - 1])) {
                            let cur = child.parentNode;
                            let idx = parts.length - 2;
                            while (cur && idx >= 0) {
                                if (cur.matches(parts[idx])) {
                                    idx -= 1;
                                }
                                cur = cur.parentNode;
                            }
                            if (idx < 0) {
                                results.push(child);
                            }
                        }
                        walk(child);
                    }
                };
                walk(this);
            }
            return results;
        }
    }

    function parseHtml(html) {
        const nodes = [];
        const stack = [];
        const regex = /<\/?([a-zA-Z0-9]+)([^>]*)>|([^<]+)/g;
        let match;
        while ((match = regex.exec(html)) !== null) {
            if (match[3] !== undefined) {
                const txt = match[3].trim();
                if (txt && stack.length > 0) {
                    stack[stack.length - 1].textContent += txt;
                }
                continue;
            }
            const full = match[0];
            const tag = match[1].toLowerCase();
            const rawAttrs = match[2] || "";
            if (full.startsWith("</")) {
                if (stack.length > 0 && stack[stack.length - 1].tagName === tag) {
                    const closed = stack.pop();
                    if (stack.length === 0) {
                        nodes.push(closed);
                    }
                }
            } else {
                const el = new FakeElement(tag);
                const attrRe = /([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|'([^']*)')/g;
                let am;
                while ((am = attrRe.exec(rawAttrs)) !== null) {
                    el.setAttribute(am[1], am[2] !== undefined ? am[2] : am[3]);
                }
                const selfClosing = rawAttrs.trim().endsWith("/") || tag === "input";
                if (stack.length > 0) {
                    stack[stack.length - 1].appendChild(el);
                }
                if (selfClosing) {
                    if (stack.length === 0) {
                        nodes.push(el);
                    }
                } else {
                    stack.push(el);
                }
            }
        }
        while (stack.length > 0) {
            const top = stack.pop();
            if (stack.length === 0) {
                nodes.push(top);
            }
        }
        return nodes;
    }

    class JQ {
        constructor(els) {
            this.elements = Array.isArray(els) ? els.filter(Boolean) : (els ? [els] : []);
            this.length = this.elements.length;
            this.elements.forEach((el, idx) => {
                this[idx] = el;
            });
        }

        each(cb) {
            this.elements.forEach((el, idx) => cb.call(el, idx, el));
            return this;
        }

        find(selector) {
            const found = [];
            this.elements.forEach((el) => {
                found.push(...el.queryAll(selector));
            });
            return new JQ(found);
        }

        parent() {
            return new JQ(this.elements.map((el) => el.parentNode).filter(Boolean));
        }

        closest(selector) {
            const found = [];
            this.elements.forEach((el) => {
                let cur = el.parentNode;
                while (cur) {
                    if (cur.matches && cur.matches(selector)) {
                        found.push(cur);
                        break;
                    }
                    cur = cur.parentNode;
                }
            });
            return new JQ(found);
        }

        next(selector) {
            const found = [];
            this.elements.forEach((el) => {
                if (!el.parentNode) return;
                const siblings = el.parentNode.children;
                const idx = siblings.indexOf(el);
                if (idx !== -1 && idx + 1 < siblings.length) {
                    const nxt = siblings[idx + 1];
                    if (!selector || nxt.matches(selector)) {
                        found.push(nxt);
                    }
                }
            });
            return new JQ(found);
        }

        after(content) {
            const nodes = typeof content === "string" ? parseHtml(content) : content.elements;
            this.elements.forEach((el) => {
                if (!el.parentNode) return;
                let ref = el;
                nodes.forEach((n) => {
                    el.parentNode.insertAfter(n, ref);
                    ref = n;
                });
            });
            return this;
        }

        prepend(content) {
            const nodes = typeof content === "string" ? parseHtml(content) : content.elements;
            this.elements.forEach((el) => {
                for (let i = nodes.length - 1; i >= 0; i -= 1) {
                    el.prependChild(nodes[i]);
                }
            });
            return this;
        }

        attr(key, value) {
            if (value === undefined) {
                return this.elements[0] ? this.elements[0].getAttribute(key) : undefined;
            }
            this.elements.forEach((el) => el.setAttribute(key, value));
            return this;
        }

        removeAttr(key) {
            this.elements.forEach((el) => el.removeAttribute(key));
            return this;
        }

        data(key, value) {
            const attrKey = "data-" + key.replace(/[A-Z]/g, (m) => "-" + m.toLowerCase());
            if (value === undefined) {
                const raw = this.attr(attrKey);
                if (raw === "1") return 1;
                if (raw === "0") return 0;
                return raw;
            }
            return this.attr(attrKey, value);
        }

        val(value) {
            if (value === undefined) {
                return this.elements[0] ? this.elements[0].value : undefined;
            }
            this.elements.forEach((el) => {
                el.value = String(value);
            });
            return this;
        }

        html(value) {
            if (value === undefined) {
                return this.elements[0] ? this.elements[0].textContent : "";
            }
            this.elements.forEach((el) => {
                el.textContent = String(value);
            });
            return this;
        }

        text(value) {
            return this.html(value);
        }

        addClass(cls) {
            this.elements.forEach((el) => {
                cls.split(/\s+/).filter(Boolean).forEach((c) => el.classes.add(c));
            });
            return this;
        }

        removeClass(cls) {
            this.elements.forEach((el) => {
                cls.split(/\s+/).filter(Boolean).forEach((c) => el.classes.delete(c));
            });
            return this;
        }

        hasClass(cls) {
            return this.elements.some((el) => el.classes.has(cls));
        }

        css(styles) {
            this.elements.forEach((el) => Object.assign(el.style, styles));
            return this;
        }

        show() {
            this.elements.forEach((el) => {
                el.hidden = false;
            });
            return this;
        }

        hide() {
            this.elements.forEach((el) => {
                el.hidden = true;
            });
            return this;
        }

        height() {
            return 50;
        }

        offset() {
            return {top: 0};
        }

        scrollTop(val) {
            const target = this.elements[0];
            if (val !== undefined) {
                if (target) target._scrollTop = val;
                return this;
            }
            return (target && target._scrollTop) || 0;
        }

        on(events, selectorOrFn, maybeFn) {
            const handler = typeof selectorOrFn === "function" ? selectorOrFn : maybeFn;
            const delegateSelector = typeof selectorOrFn === "string" ? selectorOrFn : null;
            events.split(/\s+/).filter(Boolean).forEach((ev) => {
                const evName = ev.split(".")[0];
                this.elements.forEach((el) => {
                    el.listeners[evName] = el.listeners[evName] || [];
                    el.listeners[evName].push({handler, delegateSelector});
                });
            });
            return this;
        }

        click(cb) {
            return cb ? this.on("click", cb) : this.trigger("click");
        }

        blur() {
            return this.trigger("blur");
        }

        submit() {
            return this.trigger("submit");
        }

        trigger(evName) {
            this.elements.forEach((el) => {
                const list = el.listeners[evName] || [];
                list.forEach(({handler, delegateSelector}) => {
                    if (!delegateSelector) {
                        handler.call(el, {preventDefault: () => {}});
                    }
                });
            });
            return this;
        }

        triggerDelegated(evName, targetEl) {
            this.elements.forEach((el) => {
                const list = el.listeners[evName] || [];
                list.forEach(({handler, delegateSelector}) => {
                    if (delegateSelector && targetEl.matches(delegateSelector)) {
                        handler.call(targetEl, {preventDefault: () => {}});
                    }
                });
            });
        }
    }

    const root = new FakeElement("body");
    const windowEl = new FakeElement("window");
    const $fn = function (sel) {
        if (sel instanceof FakeElement) {
            return new JQ(sel);
        }
        if (sel instanceof JQ) {
            return sel;
        }
        if (typeof sel === "object" && sel !== null) {
            return new JQ(windowEl);
        }
        if (typeof sel === "string") {
            if (sel.trim().startsWith("<")) {
                return new JQ(parseHtml(sel));
            }
            return new JQ(root.queryAll(sel));
        }
        return new JQ([]);
    };
    $fn.Event = function (name) {
        return {type: name};
    };

    return {FakeElement, JQ, root, windowEl, $fn};
}

function setupThemeEnv(options = {}) {
    const dom = createFakeDom();
    globalThis.$ = dom.$fn;
    globalThis.window = dom.windowEl;
    globalThis.MaterialTextfield = function () {
        this.CssClasses_ = {IS_DIRTY: "is-dirty", IS_INVALID: "is-invalid"};
    };
    globalThis.componentHandler = {upgradeDom: function () {}};
    globalThis.sysPassApp = {
        config: {
            LANG: Object.assign({
                27: "Password Actions",
                28: "Generate Random Password",
                29: "Password Complexity",
                30: "Clear",
                32: "Show Password",
                34: "Copy Password",
                49: "Mark as Favorite",
                50: "Delete Favorite",
                52: "Show Favorites",
                53: "Display All",
                76: "Hide Password"
            }, options.lang || {}),
            PKI: {
                AVAILABLE: options.pkiAvailable !== undefined ? options.pkiAvailable : false
            }
        },
        encryptFormValue: options.encryptFormValue || function ($input) {
            const cipher = "ENC:" + $input.val();
            $input.val(cipher);
            $input.attr("data-length", cipher.length);
        },
        actions: options.actions || {
            account: {
                saveFavorite: function ($el, cb) {
                    cb();
                },
                sort: function () {}
            }
        },
        util: {
            uniqueId: () => "u1",
            password: {
                output: () => {},
                random: (cb) => cb("Generated#Pass123", {score: 4})
            }
        }
    };

    const log = {info: () => {}, debug: () => {}, warn: () => {}, error: () => {}};
    const theme = globalThis.sysPass.Theme(log);
    return {dom, theme};
}

test("passwordDetect creates accessible button toggle for .passwordfield__input and toggles visibility", function () {
    const {dom, theme} = setupThemeEnv();
    const form = new dom.FakeElement("form", {id: "frmAccount"});
    const fieldWrap = new dom.FakeElement("div", {class: "mdl-textfield"});
    const input = new dom.FakeElement("input", {
        id: "pass",
        type: "password",
        class: "mdl-textfield__input passwordfield__input",
        value: "s3cret!"
    });
    fieldWrap.appendChild(input);
    form.appendChild(fieldWrap);
    dom.root.appendChild(form);

    theme.passwordDetect(dom.$fn(form));

    const toggleButtons = form.queryAll("button.sp-password-toggle.showpass");
    assert.equal(toggleButtons.length, 1);
    assert.equal(form.queryAll("i.showpass").length, 0);

    const btn = toggleButtons[0];
    assert.equal(btn.getAttribute("type"), "button");
    assert.equal(btn.getAttribute("aria-label"), "Show Password");
    assert.equal(btn.getAttribute("aria-pressed"), "false");
    assert.equal(btn.getAttribute("id"), "password-level-pass-u1");
    assert.equal(btn.getAttribute("data-clipboard-target"), "pass-u1");

    dom.$fn(btn).trigger("click");
    assert.equal(input.getAttribute("type"), "text");
    assert.equal(btn.getAttribute("aria-pressed"), "true");
    assert.equal(btn.getAttribute("aria-label"), "Hide Password");
    assert.equal(btn.children[0].textContent, "visibility_off");

    dom.$fn(btn).trigger("click");
    assert.equal(input.getAttribute("type"), "password");
    assert.equal(btn.getAttribute("aria-pressed"), "false");
    assert.equal(btn.getAttribute("aria-label"), "Show Password");
    assert.equal(btn.children[0].textContent, "visibility");
});

test("password toggle safely reveals plaintext and restores/re-encrypts PKI ciphertext on hide and submit", function () {
    const encryptedValues = [];
    const {dom, theme} = setupThemeEnv({
        pkiAvailable: true,
        encryptFormValue: function ($input) {
            const cipher = "PKI[" + $input.val() + "]";
            encryptedValues.push($input.val());
            $input.val(cipher);
            $input.attr("data-length", cipher.length);
        }
    });
    const form = new dom.FakeElement("form", {id: "frmAccount"});
    const fieldWrap = new dom.FakeElement("div", {class: "mdl-textfield"});
    const input = new dom.FakeElement("input", {
        id: "pass",
        type: "password",
        class: "mdl-textfield__input passwordfield__input",
        value: "myPlainPass"
    });
    fieldWrap.appendChild(input);
    form.appendChild(fieldWrap);
    dom.root.appendChild(form);

    theme.passwordDetect(dom.$fn(form));
    const btn = form.queryAll("button.sp-password-toggle.showpass")[0];

    // Simulate PKI blur encryption already having replaced value
    input.dataset.pass = "myPlainPass";
    input.value = "PKI[myPlainPass]";
    input.setAttribute("data-length", String("PKI[myPlainPass]".length));

    // Reveal shows plaintext, not ciphertext
    dom.$fn(btn).trigger("click");
    assert.equal(input.getAttribute("type"), "text");
    assert.equal(input.value, "myPlainPass");

    // Hide when unmodified restores exact ciphertext without re-encrypting
    dom.$fn(btn).trigger("click");
    assert.equal(input.getAttribute("type"), "password");
    assert.equal(input.value, "PKI[myPlainPass]");
    assert.equal(encryptedValues.length, 0);

    // Reveal, edit plaintext, and submit form while still revealed -> masks and re-encrypts
    dom.$fn(btn).trigger("click");
    assert.equal(input.getAttribute("type"), "text");
    input.value = "updatedPass";
    dom.$fn(form).trigger("submit");
    assert.equal(input.getAttribute("type"), "password");
    assert.equal(input.value, "PKI[updatedPass]");
    assert.deepEqual(encryptedValues, ["updatedPass"]);

    // Generating random password while revealed updates visible value too
    dom.$fn(btn).trigger("click");
    theme.password(dom.$fn(input));
    assert.equal(input.value, "Generated#Pass123");
});

test("passwordDetect creates accessible button toggles for .passwordfield__input-show and clipboard", function () {
    const {dom, theme} = setupThemeEnv();
    const form = new dom.FakeElement("form", {id: "frmCustom"});
    const fieldWrap = new dom.FakeElement("div", {class: "mdl-textfield"});
    const input = new dom.FakeElement("input", {
        id: "cf1",
        type: "password",
        class: "mdl-textfield__input passwordfield__input-show",
        "data-clipboard": "1",
        value: "customSecret"
    });
    fieldWrap.appendChild(input);
    form.appendChild(fieldWrap);
    dom.root.appendChild(form);

    theme.passwordDetect(dom.$fn(form));

    const toggleButtons = form.queryAll("button.sp-password-toggle.showpass");
    const clipButtons = form.queryAll("button.sp-password-toggle.clip-pass-icon");
    assert.equal(toggleButtons.length, 1);
    assert.equal(clipButtons.length, 1);
    assert.equal(form.queryAll("i.showpass").length, 0);

    dom.$fn(toggleButtons[0]).trigger("click");
    assert.equal(input.getAttribute("type"), "text");
    assert.equal(toggleButtons[0].getAttribute("aria-pressed"), "true");
});

test("viewsTriggers.search uses sp-color-text--warning instead of legacy amber MDL classes", function () {
    const {dom, theme} = setupThemeEnv();
    const frmSearch = new dom.FakeElement("form", {id: "frmSearch"});
    const searchFavBtn = new dom.FakeElement("button", {class: "icon-searchfav"});
    const searchFavIcon = new dom.FakeElement("i", {class: "material-icons"});
    const searchFavInput = new dom.FakeElement("input", {name: "searchfav", value: "0"});
    const clearBtn = new dom.FakeElement("button", {class: "btn-clear"});
    const showFilter = new dom.FakeElement("i", {class: "show-filter"});
    const tagsSelect = new dom.FakeElement("select", {id: "tags"});
    tagsSelect.selectedIndex = -1;
    tagsSelect.selectize = {addItem: () => {}};
    const tagsBar = new dom.FakeElement("div", {class: "search-filters-tags"});
    searchFavBtn.appendChild(searchFavIcon);
    frmSearch.appendChild(searchFavBtn);
    frmSearch.appendChild(searchFavInput);
    frmSearch.appendChild(clearBtn);
    frmSearch.appendChild(showFilter);
    frmSearch.appendChild(tagsSelect);
    frmSearch.appendChild(tagsBar);

    const resContent = new dom.FakeElement("div", {id: "res-content"});
    const favRowIcon = new dom.FakeElement("i", {class: "material-icons icon-favorite", "data-status": "on"});
    resContent.appendChild(favRowIcon);
    dom.root.appendChild(frmSearch);
    dom.root.appendChild(resContent);

    theme.viewsTriggers.search();

    dom.$fn(searchFavBtn).trigger("click");
    assert.equal(searchFavIcon.classes.has("sp-color-text--warning"), true);
    assert.equal(searchFavIcon.classes.has("mdl-color-text--amber-A200"), false);

    dom.$fn(clearBtn).trigger("click");
    assert.equal(searchFavIcon.classes.has("sp-color-text--warning"), false);

    dom.$fn(resContent).triggerDelegated("click", favRowIcon);
    assert.equal(favRowIcon.classes.has("sp-color-text--warning"), true);
    assert.equal(favRowIcon.classes.has("mdl-color-text--amber-A100"), false);
});

test("setFixedMenu toggles .is-fixed class rather than hardcoded inline colors", function () {
    const {dom, theme} = setupThemeEnv();
    const actionBar = new dom.FakeElement("div", {id: "actions-bar"});
    const actionLogo = new dom.FakeElement("div", {id: "actions-bar-logo"});
    actionBar.appendChild(actionLogo);
    dom.root.appendChild(actionBar);

    assert.equal(typeof theme.setFixedMenu, "function");
    theme.setFixedMenu();

    dom.windowEl._scrollTop = 100;
    dom.$fn(dom.windowEl).trigger("scroll");
    assert.equal(actionBar.classes.has("is-fixed"), true);
    assert.equal(actionBar.style.backgroundColor, undefined);
    assert.equal(actionBar.style.borderBottom, undefined);

    dom.windowEl._scrollTop = 0;
    dom.$fn(dom.windowEl).trigger("scroll");
    assert.equal(actionBar.classes.has("is-fixed"), false);
});
