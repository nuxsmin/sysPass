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
    SP_Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$doBackup = SP_Common::parseParams('p', 'backup', 0);
$frmOnCloseAction = SP_Common::parseParams('p', 'onCloseAction');
$frmActiveTab = SP_Common::parseParams('p', 'activeTab', 0);

$doActionOnClose = "doAction('$frmOnCloseAction','',$frmActiveTab);";

if ($doBackup) {
    if (!SP_Backup::doBackup()) {
        SP_Common::printJSON(_('Error al realizar el backup') . ';;' . _('Revise el registro de eventos para más detalles'));
    }

    $message['action'] = _('Realizar Backup');
    $message['text'][] = _('Copia de la aplicación y base de datos realizada correctamente');

    SP_Log::wrLogInfo($message);
    SP_Common::sendEmail($message);

    SP_Common::printJSON(_('Proceso de backup finalizado'), 0, $doActionOnClose);
}