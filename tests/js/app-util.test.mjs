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
