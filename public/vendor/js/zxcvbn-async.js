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

// cross-browser asynchronous script loading for zxcvbn.
// adapted from http://friendlybit.com/js/lazy-loading-asyncronous-javascript/

// You probably don't need this script; see README for bower/npm/requirejs setup
// instructions.

// If you do want to manually include zxcvbn, you'll likely only need to change
// ZXCVBN_SRC to point to the correct relative path from your index.html.
// (this script assumes index.html and zxcvbn.js sit next to each other.)

(function () {
    "use strict";

    const ZXCVBN_SRC = "public/vendor/js/zxcvbn.min.js";

    const async_load = function () {
        let first, s;
        s = document.createElement("script");
        s.src = ZXCVBN_SRC;
        s.type = "text/javascript";
        s.async = true;
        first = document.getElementsByTagName("script")[0];
        return first.parentNode.insertBefore(s, first);
    };

    window.addEventListener("load", async_load, false);

}).call(this);