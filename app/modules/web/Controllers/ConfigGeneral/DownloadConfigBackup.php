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

namespace SP\Modules\Web\Controllers\ConfigGeneral;

use Exception;
use RuntimeException;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Config\ConfigBackupServiceInterface;
use SP\Domain\Config\Services\ConfigBackupService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class DownloadConfigBackup
 */
final class DownloadConfigBackup extends SimpleControllerBase
{
    use JsonTrait;

    private ConfigBackupServiceInterface $configBackupService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        ConfigBackupServiceInterface $configBackupService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->configBackupService = $configBackupService;
    }

    public function downloadConfigBackupAction(string $type): string
    {
        if ($this->configData->isDemoEnabled()) {
            return __('Ey, this is a DEMO!!');
        }

        try {
            $this->eventDispatcher->notifyEvent(
                'download.configBackupFile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('File downloaded'))
                        ->addDetail(__u('File'), 'config.json')
                )
            );

            if ($type === 'json') {
                $data = ConfigBackupService::configToJson($this->configBackupService->getBackup());
            } else {
                throw new RuntimeException('Not implemented');
            }

            $response = $this->router->response();
            $response->header('Cache-Control', 'max-age=60, must-revalidate');
            $response->header('Content-length', strlen($data));
            $response->header('Content-type', 'application/json');
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-transfer-encoding', 'chunked');
            $response->header('Content-Disposition', 'attachment; filename="config.json"');
            $response->header('Set-Cookie', 'fileDownload=true; path=/');
            $response->header('Content-transfer-encoding', 'binary');
            $response->header('Set-Cookie', 'fileDownload=true; path=/');

            $response->body($data);
            $response->send(true);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
        }

        return '';
    }

    /**
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_GENERAL);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}