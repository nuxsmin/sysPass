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

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

$file = \SP\Request::analyze('f');
$base = \SP\Request::analyze('b');

if (!$file) {
    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_CSS);
    $Minify->setBase(__DIR__);
    $Minify->addFile('reset.min.css');
    $Minify->addFile('jquery-ui.min.css');
    $Minify->addFile('jquery-ui.structure.min.css');
    $Minify->addFile('chosen.min.css');
    $Minify->addFile('chosen-custom.min.css');
    $Minify->addFile('alertify-bootstrap-3.min.css');
    $Minify->addFile('jquery.tagsinput.min.css');
    $Minify->addFile('jquery.fancybox.min.css');
    $Minify->addFile('fonts.min.css');
    $Minify->addFile('material-icons.min.css');
    $Minify->getMinified();
} elseif ($file && $base) {
    $base = \SP\Request::analyze('b');

    $Minify = new Minify();
    $Minify->setType(Minify::FILETYPE_CSS);
    $Minify->setBase(urldecode($base), true);
    $Minify->addFile(urldecode($file));
    $Minify->getMinified();
}