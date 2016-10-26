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
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Util\Checks;
use SP\Util\Json;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

$Json = new JsonResponse();

if (!Init::isLoggedIn()) {
    $Json->setStatus(10);
    $Json->setDescription(_('La sesión no se ha iniciado o ha caducado'));
    Json::returnJson($Json);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    $Json->setDescription(_('CONSULTA INVÁLIDA'));
    Json::returnJson($Json);
}

$actionId = Request::analyze('actionId', 0);
$onCloseAction = Request::analyze('onCloseAction');
$activeTab = Request::analyze('activeTab', 0);
$exportPassword = Request::analyzeEncrypted('exportPwd');
$exportPasswordR = Request::analyzeEncrypted('exportPwdR');

if ($actionId === ActionsInterface::ACTION_CFG_BACKUP) {
    if (Checks::demoIsEnabled()) {
        $Json->setDescription(_('Ey, esto es una DEMO!!'));
        Json::returnJson($Json);
    }

    if (!\SP\Core\Backup::doBackup()) {
        $Json->setDescription(_('Error al realizar el backup'));
        $Json->addMessage(_('Revise el registro de eventos para más detalles'));
        Json::returnJson($Json);
    }

    $Json->setStatus(0);
    $Json->setDescription(_('Proceso de backup finalizado'));
    Json::returnJson($Json);
} elseif ($actionId === ActionsInterface::ACTION_CFG_EXPORT) {
    if (!empty($exportPassword) && $exportPassword !== $exportPasswordR) {
        $Json->setDescription(_('Las claves no coinciden'));
        Json::returnJson($Json);
    }

    if (!XmlExport::doExport($exportPassword)) {
        $Json->setDescription(_('Error al realizar la exportación'));
        $Json->addMessage(_('Revise el registro de eventos para más detalles'));
        Json::returnJson($Json);
    }

    $Json->setStatus(0);
    $Json->setDescription(_('Proceso de exportación finalizado'));
    Json::returnJson($Json);
}