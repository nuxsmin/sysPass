<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Api\Controllers;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Services\Api\ApiResponse;
use SP\Services\Backup\FileBackupService;
use SP\Services\Export\XmlExportService;

/**
 * Class ConfigController
 * @package SP\Modules\Api\Controllers
 */
class ConfigController extends ControllerBase
{
    /**
     * backupAction
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function backupAction()
    {
        try {
            $this->setupApi(ActionsInterface::BACKUP_CONFIG);

            $this->dic->get(FileBackupService::class)
                ->doBackup(BACKUP_PATH);

            $this->eventDispatcher->notifyEvent('run.backup.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Copia de la aplicación y base de datos realizada correctamente')))
            );

            $this->returnResponse(new ApiResponse(__u('Proceso de backup finalizado')));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * exportAction
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function exportAction()
    {
        try {
            $this->setupApi(ActionsInterface::EXPORT_CONFIG);

            $password = $this->apiService->getParamString('password');

            $this->eventDispatcher->notifyEvent('run.export.start',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Exportación de sysPass en XML')))
            );

            $this->dic->get(XmlExportService::class)
                ->doExport($password);

            $this->eventDispatcher->notifyEvent('run.export.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Proceso de exportación finalizado')))
            );

            $this->returnResponse(new ApiResponse(__u('Proceso de exportación finalizado')));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }
}