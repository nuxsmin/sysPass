//
// From http://www.kenneth-truyers.net/2013/04/27/javascript-namespaces-and-modules/
//
var sysPass = sysPass || {};

// create a general purpose namespace method
// this will allow us to create namespace a bit easier
sysPass.createNS = function (namespace) {
    var nsparts = namespace.split(".");
    var parent = sysPass;

    // we want to be able to include or exclude the root namespace
    // So we strip it if it's in the namespace
    if (nsparts[0] === "sysPass") {
        nsparts = nsparts.slice(1);
    }

    // loop through the parts and create
    // a nested namespace if necessary
    for (var i = 0; i < nsparts.length; i++) {
        var partname = nsparts[i];
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

sysPass.createNS("Main");
sysPass.createNS("Triggers");
sysPass.createNS("Actions");
sysPass.createNS("Requests");
sysPass.createNS("Theme");
sysPass.createNS("Plugin");

// Objeto con las funciones públicas de sysPass
var sysPassApp = {};

$(document).on("DOMContentLoaded", function (e) {
    "use strict";

    sysPassApp = sysPass.Main();
});

