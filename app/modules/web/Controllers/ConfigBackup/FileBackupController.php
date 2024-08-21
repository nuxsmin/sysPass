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

namespace SP\Modules\Web\Controllers\ConfigBackup;


use Exception;
use SP\Core\Application;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Context\Session;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class FileBackupController
 */
final class FileBackupController extends SimpleControllerBase
{
    use ConfigTrait;

    public function __construct(
        Application                        $application,
        SimpleControllerHelper             $simpleControllerHelper,
        private readonly BackupFileService $fileBackupService,
        private readonly PathsContext      $pathsContext
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function fileBackupAction(): bool
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(JsonMessage::JSON_WARNING, __u('Ey, this is a DEMO!!'));
        }

        try {
            Session::close();

            $this->fileBackupService->doBackup($this->pathsContext[Path::BACKUP], $this->pathsContext[Path::APP]);

            $this->eventDispatcher->notify(
                'run.backup.end',
                new Event(
                    $this,
                    EventMessage::build()->addDescription(
                        __u('Application and database backup completed successfully')
                    )
                )
            );

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Backup process finished'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * initialize
     *
     * @throws SPException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_BACKUP);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
