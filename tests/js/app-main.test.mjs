/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

import assert from "node:assert/strict";
import { createRequire } from "node:module";
import test from "node:test";

globalThis.sysPass = {};

const require = createRequire(import.meta.url);
require("../../public/js/app-main.js");

const getActionResponseData = globalThis.sysPass.Main.getActionResponseData;

test("accepts a numeric success status", function () {
    const data = {lang: [], pki_key: "key"};

    assert.equal(getActionResponseData({status: 0, data: data}), data);
});

test("accepts a string success status", function () {
    const data = {lang: [], pki_key: "key"};

    assert.equal(getActionResponseData({status: "OK", data: data}), data);
});

test("accepts an empty-string required value", function () {
    const data = {accpass: ""};

    assert.equal(getActionResponseData({status: 0, data: data}, "accpass"), data);
});

test("rejects error envelopes with null data without throwing", function () {
    assert.doesNotThrow(function () {
        getActionResponseData({status: "ERROR", data: null});
        getActionResponseData({status: 1, data: null});
    });

    assert.equal(getActionResponseData({status: "ERROR", data: null}), undefined);
    assert.equal(getActionResponseData({status: 1, data: null}), undefined);
});

test("rejects non-success statuses", function () {
    const data = {lang: [], pki_key: "key"};

    assert.equal(getActionResponseData({status: "WARNING", data: data}), undefined);
    assert.equal(getActionResponseData({status: 2, data: data}), undefined);
    assert.equal(getActionResponseData({status: 10, data: data}), undefined);
    assert.equal(getActionResponseData({data: data}), undefined);
});

test("rejects invalid envelopes", function () {
    assert.equal(getActionResponseData(null), undefined);
    assert.equal(getActionResponseData(undefined), undefined);
    assert.equal(getActionResponseData([]), undefined);
    assert.equal(getActionResponseData({status: 0}), undefined);
    assert.equal(getActionResponseData({status: 0, data: null}), undefined);
    assert.equal(getActionResponseData({status: 0, data: []}), undefined);
});

test("rejects missing, null and non-string required values", function () {
    assert.equal(getActionResponseData({status: 0, data: {}}, "accpass"), undefined);
    assert.equal(getActionResponseData({status: 0, data: {accpass: null}}, "accpass"), undefined);
    assert.equal(getActionResponseData({status: 0, data: {accpass: 123}}, "accpass"), undefined);
    assert.equal(getActionResponseData({status: 0, data: {accpass: false}}, "accpass"), undefined);
    assert.equal(getActionResponseData({status: 0, data: {accpass: []}}, "accpass"), undefined);
});
