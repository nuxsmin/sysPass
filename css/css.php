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

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

$themeCssPath = VIEW_PATH . DIRECTORY_SEPARATOR . \SP\Init::$THEME . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'css.php';

$cssFilesBase = array(
    array('href' => 'css/reset.css', 'min' => true),
    array('href' => 'css/jquery-ui.min.css', 'min' => false),
    array('href' => 'css/jquery-ui.structure.min.css', 'min' => false),
    array('href' => 'css/jquery.powertip.css', 'min' => true),
    array('href' => 'css/jquery.powertip-yellow.min.css', 'min' => true),
    array('href' => 'css/chosen.min.css', 'min' => true),
    array('href' => 'css/chosen-custom.css', 'min' => true),
    array('href' => 'css/alertify-bootstrap-3.css', 'min' => false),
    array('href' => 'css/jquery.tagsinput.css', 'min' => true),
    array('href' => 'css/jquery.fancybox.css', 'min' => true),
    array('href' => 'css/fonts.css', 'min' => true),
    array('href' => 'css/material-icons.css', 'min' => true),
);

if (file_exists($themeCssPath)){
    include $themeCssPath;

    foreach ($cssFilesTheme as $file) {
        array_push($cssFilesBase, $file);
    }
}

SP\Util::getMinified('css', $cssFilesBase);