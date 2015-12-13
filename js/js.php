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

use SP\Minify;

define('APP_ROOT', '..');

require APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

$file = \SP\Request::analyze('f');
$base = \SP\Request::analyze('b');

if (!$file) {
    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_JS);
    $Minify->setBase(__DIR__);
    $Minify->addFile('jquery-1.11.2.min.js');
    $Minify->addFile('jquery-ui.min.js');
    $Minify->addFile('jquery.fancybox.pack.js');
    $Minify->addFile('jquery.powertip.min.js');
    $Minify->addFile('chosen.jquery.min.js');
    $Minify->addFile('alertify.min.js');
    $Minify->addFile('jquery.fileDownload.min.js');
    $Minify->addFile('jquery.tagsinput.min.js');
    $Minify->addFile('clipboard.min.js');
    $Minify->addFile('zxcvbn-async.min.js');
    $Minify->addFile('jsencrypt.min.js');
    $Minify->addFile('functions.min.js');
    $Minify->getMinified();
} elseif ($file && $base) {
    $base = \SP\Request::analyze('b');

    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_JS);
    $Minify->setBase(\SP\Init::$SERVERROOT . urldecode($base));
    $Minify->addFile(urldecode($file));
    $Minify->getMinified();
}
