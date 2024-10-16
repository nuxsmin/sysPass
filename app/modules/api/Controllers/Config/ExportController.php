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
use SP\Domain\Export\Ports\XmlExportService;
use SP\Infrastructure\File\DirectoryHandler;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\ConfigHelp;

use function SP\__u;
use function SP\processException;

/**
 * Class ExportController
 */
final class ExportController extends ControllerBase
{

    /**
     * @throws InvalidClassException
     */
    public function __construct(
        Application                       $application,
        Klein                             $router,
        ApiService                        $apiService,
        AclInterface                      $acl,
        private readonly XmlExportService $xmlExportService,
        private readonly PathsContext     $pathsContext
    ) {
        parent::__construct($application, $router, $apiService, $acl);

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
            $path = $this->apiService->getParamString('path', false, $this->pathsContext[Path::BACKUP]);

            $this->eventDispatcher->notify(
                'run.export.start',
                new Event(
                    $this,
                    EventMessage::build()
                        ->addDescription(__u('sysPass XML export'))
                        ->addDetail(__u('Path'), $path)
                )
            );

            $file = $this->xmlExportService->export(new DirectoryHandler($path), $password);


            $this->eventDispatcher->notify(
                'run.export.end',
                new Event($this, EventMessage::build()->addDescription(__u('Export process finished')))
            );

            $exportFiles = ['files' => ['xml' => $file]];

            $this->returnResponse(ApiResponse::makeSuccess($exportFiles, null, __('Export process finished')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }
}
