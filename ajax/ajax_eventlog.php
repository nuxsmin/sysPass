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
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'init.php';

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Util::logout();
}

SP_ACL::checkUserAccess('eventlog') || SP_Html::showCommonError('unavailable');

$start = SP_Common::parseParams('p', 'start', 0);
$clear = SP_Common::parseParams('p', 'clear', 0);
$sk = SP_Common::parseParams('p', 'sk', false);

if ($clear && $sk && SP_Common::checkSessionKey($sk)) {
    if (SP_Log::clearEvents()) {
        SP_Common::printJSON(_('Registro de eventos vaciado'), 0, "doAction('eventlog');scrollUp();");
    } else {
        SP_Common::printJSON(_('Error al vaciar el registro de eventos'));
    }
}

$tplvars = array('start' => $start);
SP_Html::getTemplate('eventlog', $tplvars);