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

use SP\SessionUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

SP\Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    SP\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$actionId = SP\Request::analyze('actionId', 0);
$onCloseAction = SP\Request::analyze('onCloseAction');
$activeTab = SP\Request::analyze('activeTab', 0);
$exportPassword = SP\Request::analyzeEncrypted('exportPwd');
$exportPasswordR = SP\Request::analyzeEncrypted('exportPwdR');

$doActionOnClose = "sysPassUtil.Common.doAction($actionId,'',$activeTab);";

if ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_BACKUP) {
    if (SP\Util::demoIsEnabled()) {
        SP\Response::printJSON(_('Ey, esto es una DEMO!!'));
    }

    if (!SP\Backup::doBackup()) {
        SP\Log::writeNewLogAndEmail(_('Realizar Backup'), _('Error al realizar el backup'));

        SP\Response::printJSON(_('Error al realizar el backup') . ';;' . _('Revise el registro de eventos para más detalles'));
    }

    SP\Log::writeNewLogAndEmail(_('Realizar Backup'), _('Copia de la aplicación y base de datos realizada correctamente'));

    SP\Response::printJSON(_('Proceso de backup finalizado'), 0, $doActionOnClose);
} elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_EXPORT) {
    if (!empty($exportPassword) && $exportPassword !== $exportPasswordR){
        SP\Response::printJSON(_('Las claves no coinciden'));
    }

    if(!\SP\XmlExport::doExport($exportPassword)){
        SP\Log::writeNewLogAndEmail(_('Realizar Exportación'), _('Error al realizar la exportación de cuentas'));

        SP\Response::printJSON(_('Error al realizar la exportación') . ';;' . _('Revise el registro de eventos para más detalles'));
    }

    SP\Log::writeNewLogAndEmail(_('Realizar Exportación'), _('Exportación de cuentas realizada correctamente'));

    SP\Response::printJSON(_('Proceso de exportación finalizado'), 0, $doActionOnClose);
}