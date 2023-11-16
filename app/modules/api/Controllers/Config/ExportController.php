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
use SP\Core\Acl\AclActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Ports\ApiServiceInterface;
use SP\Domain\Api\Services\ApiResponse;
use SP\Domain\Export\Ports\XmlExportServiceInterface;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\ConfigHelp;

/**
 * Class ExportController
 */
final class ExportController extends ControllerBase
{
    private XmlExportServiceInterface $xmlExportService;

    /**
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct(
        Application $application,
        Klein $router,
        ApiServiceInterface $apiService,
        Acl $acl,
        XmlExportServiceInterface $xmlExportService
    ) {
        parent::__construct($application, $router, $apiService, $acl);

        $this->xmlExportService = $xmlExportService;

        $this->apiService->setHelpClass(ConfigHelp::class);
    }

    /**
     * exportAction
     */
    public function exportAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::CONFIG_EXPORT_RUN);

            $password = $this->apiService->getParamString('password');
            $path = $this->apiService->getParamString('path', false, BACKUP_PATH);

            $this->eventDispatcher->notify(
                'run.export.start',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('sysPass XML export'))
                        ->addDetail(__u('Path'), $path)
                )
            );

            $this->xmlExportService->doExport($path, $password);


            $this->eventDispatcher->notify(
                'run.export.end',
                new Event($this, EventMessage::factory()->addDescription(__u('Export process finished')))
            );

            $exportFiles = ['files' => ['xml' => $this->xmlExportService->getExportFile()]];

            $this->returnResponse(ApiResponse::makeSuccess($exportFiles, null, __('Export process finished')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }
}
