<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Modules\Api\Controllers\Config;

use Exception;
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Ports\ApiServiceInterface;
use SP\Domain\Api\Services\ApiResponse;
use SP\Domain\Export\Ports\FileBackupServiceInterface;
use SP\Domain\Export\Services\BackupFiles;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\ConfigHelp;

/**
 * Class BackupController
 *
 * @package SP\Modules\Api\Controllers
 */
final class BackupController extends ControllerBase
{
    private FileBackupServiceInterface $fileBackupService;

    /**
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct(
        Application $application,
        Klein $router,
        ApiServiceInterface $apiService,
        Acl $acl,
        FileBackupServiceInterface $fileBackupService
    ) {
        parent::__construct($application, $router, $apiService, $acl);

        $this->fileBackupService = $fileBackupService;

        $this->apiService->setHelpClass(ConfigHelp::class);
    }

    /**
     * backupAction
     */
    public function backupAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::CONFIG_BACKUP_RUN);

            $path = $this->apiService->getParamString('path', false, BACKUP_PATH);

            $this->fileBackupService->doBackup($path);

            $this->eventDispatcher->notify(
                'run.backup.end',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Application and database backup completed successfully'))
                        ->addDetail(__u('Path'), $path)
                )
            );

            $this->returnResponse(
                ApiResponse::makeSuccess($this->buildBackupFiles($path), null, __('Backup process finished'))
            );
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @param  string|null  $path
     *
     * @return array[]
     */
    private function buildBackupFiles(?string $path): array
    {
        return [
            'files' => [
                'app' => BackupFiles::getAppBackupFilename(
                    $path,
                    $this->fileBackupService->getHash(),
                    true
                ),
                'db'  => BackupFiles::getDbBackupFilename(
                    $path,
                    $this->fileBackupService->getHash(),
                    true
                ),
            ],
        ];
    }
}
