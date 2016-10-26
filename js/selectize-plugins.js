(function () {
    "use strict";

    Selectize.define("clear_selection", function (options) {
        var self = this;

        //Overriding because, ideally you wouldn't use header & clear_selection simultaneously
        self.plugins.settings.dropdown_header = {
            title: options.title
        };

        this.require("dropdown_header");

        self.setup = function () {
            var original = self.setup;

            return function () {
                original.apply(this, arguments);
                this.$dropdown.on("mousedown", ".selectize-dropdown-header", function (e) {
                    self.clear();
                    self.close();
                    self.blur();

                    return false;
                });
            };
        }();
    });
}());
