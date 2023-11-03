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

namespace SP\Modules\Web\Controllers\ConfigBackup;


use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Export\Ports\FileBackupServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class FileBackupController
 */
final class FileBackupController extends SimpleControllerBase
{
    use ConfigTrait;

    private FileBackupServiceInterface $fileBackupService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        FileBackupServiceInterface $fileBackupService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->fileBackupService = $fileBackupService;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function fileBackupAction(): bool
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
        }

        try {
            SessionContext::close();

            $this->fileBackupService->doBackup(BACKUP_PATH);

            $this->eventDispatcher->notify(
                'run.backup.end',
                new Event(
                    $this,
                    EventMessage::factory()->addDescription(
                        __u('Application and database backup completed successfully')
                    )
                )
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Backup process finished'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * initialize
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_BACKUP);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
