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

use SP\Controller\EventlogController;
use SP\Core\Init;
use SP\Core\Template;
use SP\Http\Request;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('GET');

if (!Init::isLoggedIn()) {
    \SP\Util\Util::logout();
}

$start = Request::analyze('start', 0);
$clear = Request::analyze('clear', 0);
$sk = Request::analyze('sk', false);

$Tpl = new Template();
$Tpl->assign('limitStart', $start);
$Tpl->assign('clear', $clear);
$Tpl->assign('sk', $sk);
$Tpl->assign('queryTimeStart', microtime());
$Controller = new EventlogController($Tpl);
$Controller->checkClear();
$Controller->getEventlog();
echo $Tpl->render();