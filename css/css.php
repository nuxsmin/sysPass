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

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

$file = Request::analyze('f');
$base = Request::analyze('b');

if (!$file) {
    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_CSS)
        ->setBase(__DIR__)
        ->addFile('reset.min.css')
        ->addFile('jquery-ui.min.css')
        ->addFile('jquery-ui.structure.min.css')
        ->addFile('jquery.tagsinput.min.css')
        ->addFile('fonts.min.css')
        ->addFile('material-icons.min.css')
        ->addFile('toastr.min.css')
        ->addFile('magnific-popup.min.css')
        ->getMinified();
} elseif ($file && $base) {
    $base = Request::analyze('b');

    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_CSS)
        ->setBase(urldecode($base), true)
        ->addFile(urldecode($file))
        ->getMinified();
}