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

//
// From http://www.kenneth-truyers.net/2013/04/27/javascript-namespaces-and-modules/
//
const sysPass = {};

// create a general purpose namespace method
// this will allow us to create namespace a bit easier
sysPass.createNS = function (namespace) {
    "use strict";

    let nsparts = namespace.split(".");
    let parent = sysPass;

    // we want to be able to include or exclude the root namespace
    // So we strip it if it's in the namespace
    if (nsparts[0] === "sysPass") {
        nsparts = nsparts.slice(1);
    }

    // loop through the parts and create
    // a nested namespace if necessary
    for (let i = 0; i < nsparts.length; i++) {
        const partname = nsparts[i];
        // check if the current parent already has
        // the namespace declared, if not create it
        if (typeof parent[partname] === "undefined") {
            parent[partname] = {};
        }
        // get a reference to the deepest element
        // in the hierarchy so far
        parent = parent[partname];
    }
    // the parent is now completely constructed
    // with empty namespaces and can be used.
    return parent;
};

sysPass.createNS("Config");
sysPass.createNS("Main");
sysPass.createNS("Triggers");
sysPass.createNS("Actions");
sysPass.createNS("Requests");
sysPass.createNS("Theme");
sysPass.createNS("Plugins");
sysPass.createNS("Util");

// Objeto con las funciones públicas de sysPass
let sysPassApp = {};

$(document).on("DOMContentLoaded", function (e) {
    "use strict";

    sysPassApp = sysPass.Main();
});

