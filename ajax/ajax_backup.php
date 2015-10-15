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

use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Core\XmlExport;
use SP\Http\Request;
use SP\Http\Response;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$actionId = Request::analyze('actionId', 0);
$onCloseAction = Request::analyze('onCloseAction');
$activeTab = Request::analyze('activeTab', 0);
$exportPassword = Request::analyzeEncrypted('exportPwd');
$exportPasswordR = Request::analyzeEncrypted('exportPwdR');

$doActionOnClose = "sysPassUtil.Common.doAction($actionId,'',$activeTab);";

if ($actionId === ActionsInterface::ACTION_CFG_BACKUP) {
    if (Checks::demoIsEnabled()) {
        Response::printJSON(_('Ey, esto es una DEMO!!'));
    }

    if (!\SP\Core\Backup::doBackup()) {
        Response::printJSON(_('Error al realizar el backup') . ';;' . _('Revise el registro de eventos para más detalles'));
    }

    Response::printJSON(_('Proceso de backup finalizado'), 0, $doActionOnClose);
} elseif ($actionId === ActionsInterface::ACTION_CFG_EXPORT) {
    if (!empty($exportPassword) && $exportPassword !== $exportPasswordR){
        Response::printJSON(_('Las claves no coinciden'));
    }

    if(!XmlExport::doExport($exportPassword)){
        Response::printJSON(_('Error al realizar la exportación') . ';;' . _('Revise el registro de eventos para más detalles'));
    }

    Response::printJSON(_('Proceso de exportación finalizado'), 0, $doActionOnClose);
}