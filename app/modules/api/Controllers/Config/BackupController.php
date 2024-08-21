<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Application;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Dtos\ApiResponse;
use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Export\Dtos\BackupFiles;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\ConfigHelp;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class BackupController
 *
 * @package SP\Modules\Api\Controllers
 */
final class BackupController extends ControllerBase
{
    /**
     * @throws InvalidClassException
     */
    public function __construct(
        Application                        $application,
        Klein                              $router,
        ApiService                         $apiService,
        AclInterface                       $acl,
        private readonly BackupFileService $fileBackupService,
        private readonly BackupFiles  $backupFiles,
        private readonly PathsContext $pathsContext
    ) {
        parent::__construct($application, $router, $apiService, $acl);

        $this->apiService->setHelpClass(ConfigHelp::class);
    }

    /**
     * backupAction
     */
    public function backupAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::CONFIG_BACKUP_RUN);

            $path = $this->apiService->getParamString('path', false, $this->pathsContext[Path::BACKUP]);

            $this->fileBackupService->doBackup($path);

            $this->eventDispatcher->notify(
                'run.backup.end',
                new Event(
                    $this,
                    EventMessage::build()
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
     * @param string|null $path
     *
     * @return array[]
     */
    private function buildBackupFiles(?string $path): array
    {
        $backupFiles = $this->backupFiles->withPath($path);

        return [
            'files' => [
                'app' => (string)$backupFiles->getAppBackupFile(),
                'db' => (string)$backupFiles->getDbBackupFile(),
            ],
        ];
    }
}
