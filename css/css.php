<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

$cssFiles = array(
    array('href' => 'css/reset.css', 'min' => true),
    array('href' => 'css/jquery-ui.min.css', 'min' => false),
    array('href' => 'css/jquery-ui.structure.min.css', 'min' => false),
    array('href' => 'css/jquery-ui.theme.min.css', 'min' => false),
    array('href' => 'css/jquery.powertip.css', 'min' => true),
    array('href' => 'css/jquery.powertip-yellow.min.css', 'min' => true),
    array('href' => 'css/chosen.css', 'min' => true),
    array('href' => 'css/alertify.core.css', 'min' => true),
    array('href' => 'css/alertify.default.css', 'min' => true),
    array('href' => 'css/jquery.tagsinput.css', 'min' => true),
    array('href' => 'js/fancybox/jquery.fancybox.css', 'min' => true),
    array('href' => 'css/styles.css', 'min' => true)
);

if (!SP\Util::resultsCardsIsEnabled()) {
    array_push($cssFiles, array('href' => 'css/search-grid.css', 'min' => true));
}

SP\Util::getMinified('css', $cssFiles);