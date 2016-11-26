<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

use SP\Html\Minify;
use SP\Http\Request;

define('APP_ROOT', '..');

require APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

$file = Request::analyze('f');
$base = Request::analyze('b');
$group = Request::analyze('g', 0);

if (!$file) {
    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_JS);
    $Minify->setBase(__DIR__);

    if ($group === 0) {
        $Minify->addFile('jquery-1.11.2.min.js')
            ->addFile('jquery-ui.min.js')
            ->addFile('jquery.fileDownload.min.js')
            ->addFile('jquery.tagsinput.min.js')
            ->addFile('clipboard.min.js')
            ->addFile('selectize.min.js')
            ->addFile('selectize-plugins.min.js')
            ->addFile('zxcvbn-async.min.js')
            ->addFile('jsencrypt.min.js')
            ->addFile('spark-md5.min.js')
            ->addFile('moment.min.js')
            ->addFile('moment-timezone.min.js')
            ->addFile('toastr.min.js')
            ->addFile('jquery.magnific-popup.min.js');
    } elseif ($group === 1) {
        // FIXME: utilizar versiones .min
        $Minify->addFile('app.js')
            ->addFile('app-triggers.js')
            ->addFile('app-actions.js')
            ->addFile('app-requests.js')
            ->addFile('app-main.js');
    }

    $Minify->getMinified(true);
} elseif ($file && $base) {
    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_JS);
    $Minify->setBase(\SP\Core\Init::$SERVERROOT . urldecode($base));
    $Minify->addFile(urldecode($file));
    $Minify->getMinified();
}