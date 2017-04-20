<?php
/**
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
        $Minify->addFile('jquery-3.2.0.min.js')
            ->addFile('jquery-migrate-3.0.0.min.js')
            ->addFile('jquery.fileDownload.min.js')
            ->addFile('clipboard.min.js')
            ->addFile('selectize.min.js')
            ->addFile('selectize-plugins.min.js')
            ->addFile('zxcvbn-async.min.js')
            ->addFile('jsencrypt.min.js')
            ->addFile('spark-md5.min.js')
            ->addFile('moment.min.js')
            ->addFile('moment-timezone.min.js')
            ->addFile('toastr.min.js')
            ->addFile('jquery.magnific-popup.min.js')
            ->addFile('eventsource.min.js');
    } elseif ($group === 1) {
        $Minify->addFile('app.min.js')
            ->addFile('app-triggers.min.js')
            ->addFile('app-actions.min.js')
            ->addFile('app-requests.min.js')
            ->addFile('app-main.min.js');
    }

    $Minify->getMinified();
} elseif ($file && $base) {
    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_JS);
    $Minify->setBase(urldecode($base), true);
    $Minify->addFile(urldecode($file));
    $Minify->getMinified();
}