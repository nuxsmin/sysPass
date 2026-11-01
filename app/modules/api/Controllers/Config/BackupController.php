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

use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Export\Dtos\BackupFiles;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Modules\Api\Controllers\ApiControllerHelper;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\ConfigHelp;

use function SP\__;
use function SP\__u;

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
        ApiControllerHelper                $controllerHelper,
        ApiService                         $apiService,
        private readonly BackupFileService $fileBackupService,
        private readonly BackupFiles       $backupFiles,
        private readonly PathsContext      $pathsContext
    ) {
        parent::__construct($controllerHelper, $apiService);

        $this->apiService->setHelpClass(ConfigHelp::class);
    }

    #[Action(ResponseType::JSON_RPC)]
    public function backupAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::CONFIG_BACKUP_RUN);

        $path = $this->pathsContext[Path::BACKUP];

        $this->fileBackupService->doBackup($path, $this->pathsContext[Path::APP]);

        $this->eventDispatcher->notify(
            'run.backup.end',
            new Event(
                $this,
                EventMessage::build()
                    ->addDescription(__u('Application and database backup completed successfully'))
                    ->addDetail(__u('Path'), $path)
            )
        );

        return ActionResponse::ok(__('Backup process finished'), $this->buildBackupFiles());
    }

    /**
     * @return array{files: array{app: string, db: string}}
     */
    private function buildBackupFiles(): array
    {
        return [
            'files' => [
                'app' => (string)$this->backupFiles->getAppBackupFile(),
                'db' => (string)$this->backupFiles->getDbBackupFile(),
            ],
        ];
    }
}
