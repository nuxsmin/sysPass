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

SP\Util::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Util::logout();
}

$start = SP\Common::parseParams('p', 'start', 0);
$clear = SP\Common::parseParams('p', 'clear', 0);
$sk = SP\Common::parseParams('p', 'sk', false);

$tpl = new SP\Template();
$tpl->assign('limitStart', $start);
$tpl->assign('clear', $clear);
$tpl->assign('sk', $sk);
$controller = new SP\Controller\EventlogC($tpl);
$controller->checkClear();
$controller->getEventlog();
echo $tpl->render();