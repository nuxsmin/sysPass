<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Export\FileBackupServiceInterface;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class FileBackupController
 */
final class FileBackupController extends SimpleControllerBase
{
    use ConfigTrait;

    private FileBackupServiceInterface $fileBackupService;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        FileBackupServiceInterface $fileBackupService
    ) {
        parent::__construct($application, $theme, $router, $acl, $request, $extensionChecker);

        $this->fileBackupService = $fileBackupService;
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    public function fileBackupAction(): bool
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
        }

        try {
            SessionContext::close();

            $this->fileBackupService->doBackup(BACKUP_PATH);

            $this->eventDispatcher->notifyEvent(
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

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * initialize
     *
     * @throws SessionTimeout
     * @throws \JsonException
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_BACKUP);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}