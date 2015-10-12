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

use SP\Core\SessionUtil;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

\SP\Http\Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    \SP\Http\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = \SP\Http\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    \SP\Http\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

$actionId = \SP\Http\Request::analyze('actionId', 0);
$onCloseAction = \SP\Http\Request::analyze('onCloseAction');
$activeTab = \SP\Http\Request::analyze('activeTab', 0);
$exportPassword = \SP\Http\Request::analyzeEncrypted('exportPwd');
$exportPasswordR = \SP\Http\Request::analyzeEncrypted('exportPwdR');

$doActionOnClose = "sysPassUtil.Common.doAction($actionId,'',$activeTab);";

if ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_BACKUP) {
    if (Checks::demoIsEnabled()) {
        \SP\Http\Response::printJSON(_('Ey, esto es una DEMO!!'));
    }

    if (!\SP\Core\Backup::doBackup()) {
        \SP\Log\Log::writeNewLogAndEmail(_('Realizar Backup'), _('Error al realizar el backup'));

        \SP\Http\Response::printJSON(_('Error al realizar el backup') . ';;' . _('Revise el registro de eventos para más detalles'));
    }

    \SP\Log\Log::writeNewLogAndEmail(_('Realizar Backup'), _('Copia de la aplicación y base de datos realizada correctamente'));

    \SP\Http\Response::printJSON(_('Proceso de backup finalizado'), 0, $doActionOnClose);
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_EXPORT) {
    if (!empty($exportPassword) && $exportPassword !== $exportPasswordR){
        \SP\Http\Response::printJSON(_('Las claves no coinciden'));
    }

    if(!\SP\Core\XmlExport::doExport($exportPassword)){
        \SP\Log\Log::writeNewLogAndEmail(_('Realizar Exportación'), _('Error al realizar la exportación de cuentas'));

        \SP\Http\Response::printJSON(_('Error al realizar la exportación') . ';;' . _('Revise el registro de eventos para más detalles'));
    }

    \SP\Log\Log::writeNewLogAndEmail(_('Realizar Exportación'), _('Exportación de cuentas realizada correctamente'));

    \SP\Http\Response::printJSON(_('Proceso de exportación finalizado'), 0, $doActionOnClose);
}