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

use SP\Http\Request;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    \SP\Util\Util::logout();
}

$start = \SP\Http\Request::analyze('start', 0);
$clear = \SP\Http\Request::analyze('clear', 0);
$sk = \SP\Http\Request::analyze('sk', false);

$tpl = new \SP\Core\Template();
$tpl->assign('limitStart', $start);
$tpl->assign('clear', $clear);
$tpl->assign('sk', $sk);
$controller = new \SP\Controller\EventlogC($tpl);
$controller->checkClear();
$controller->getEventlog();
echo $tpl->render();