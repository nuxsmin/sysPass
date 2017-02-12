/*
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
