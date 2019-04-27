<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\InvalidClassException;
use SP\Modules\Api\Controllers\Help\ConfigHelp;
use SP\Services\Api\ApiResponse;
use SP\Services\Backup\FileBackupService;
use SP\Services\Export\XmlExportService;

/**
 * Class ConfigController
 *
 * @package SP\Modules\Api\Controllers
 */
final class ConfigController extends ControllerBase
{
    /**
     * backupAction
     */
    public function backupAction()
    {
        try {
            $this->setupApi(ActionsInterface::CONFIG_BACKUP_RUN);

            $path = $this->apiService->getParamString('path', false, BACKUP_PATH);

            $this->dic->get(FileBackupService::class)
                ->doBackup($path);

            $this->eventDispatcher->notifyEvent('run.backup.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Application and database backup completed successfully'))
                    ->addDetail(__u('Path'), $path))
            );

            $this->returnResponse(ApiResponse::makeSuccess($path, null, __('Backup process finished')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * exportAction
     */
    public function exportAction()
    {
        try {
            $this->setupApi(ActionsInterface::CONFIG_EXPORT_RUN);

            $password = $this->apiService->getParamString('password');
            $path = $this->apiService->getParamString('path', false, BACKUP_PATH);

            $this->eventDispatcher->notifyEvent('run.export.start',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('sysPass XML export'))
                    ->addDetail(__u('Path'), $path))
            );

            $this->dic->get(XmlExportService::class)
                ->doExport($path, $password);

            $this->eventDispatcher->notifyEvent('run.export.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Export process finished')))
            );

            $this->returnResponse(ApiResponse::makeSuccess($path, null, __('Export process finished')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @throws InvalidClassException
     */
    protected function initialize()
    {
        $this->apiService->setHelpClass(ConfigHelp::class);
    }
}