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
use SP\Domain\Export\Ports\XmlExportService;
use SP\Infrastructure\File\DirectoryHandler;
use SP\Modules\Api\Controllers\ApiControllerHelper;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\ConfigHelp;

use function SP\__u;
use function SP\__;

/**
 * Class ExportController
 */
final class ExportController extends ControllerBase
{

    /**
     * @throws InvalidClassException
     */
    public function __construct(
        ApiControllerHelper               $controllerHelper,
        ApiService                        $apiService,
        private readonly XmlExportService $xmlExportService,
        private readonly PathsContext     $pathsContext
    ) {
        parent::__construct($controllerHelper, $apiService);

        $this->apiService->setHelpClass(ConfigHelp::class);
    }

    #[Action(ResponseType::JSON_RPC)]
    public function exportAction(): ActionResponse
    {
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

        return ActionResponse::ok(__('Export process finished'), $exportFiles);
    }
}
