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

namespace SP\Modules\Web\Controllers\ConfigGeneral;

use Exception;
use JsonException;
use RuntimeException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Config\Ports\ConfigBackupService;
use SP\Domain\Config\Services\ConfigBackup;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class DownloadConfigBackup
 */
final class DownloadConfigBackup extends SimpleControllerBase
{
    use JsonTrait;

    private ConfigBackupService $configBackupService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        ConfigBackupService $configBackupService
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
            $this->eventDispatcher->notify(
                'download.configBackupFile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('File downloaded'))
                        ->addDetail(__u('File'), 'config.json')
                )
            );

            if ($type === 'json') {
                $data = ConfigBackup::configToJson($this->configBackupService->getBackup());
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

            $this->eventDispatcher->notify('exception', new Event($e));
        }

        return '';
    }

    /**
     * @throws JsonException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_GENERAL);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
