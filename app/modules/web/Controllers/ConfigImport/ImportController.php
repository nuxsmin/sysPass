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

namespace SP\Modules\Web\Controllers\ConfigImport;

use SP\Core\Application;
use SP\Core\Context\Session;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportService;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;

/**
 * Class ImportController
 */
final class ImportController extends SimpleControllerBase
{

    public function __construct(
        Application                    $application,
        SimpleControllerHelper         $simpleControllerHelper,
        private readonly ImportService $importService
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function importAction(): ActionResponse
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return ActionResponse::warning(__u('Ey, this is a DEMO!!'));
        }

        $this->eventDispatcher->notify('run.import.start', new Event($this));

        Session::close();

        $counter = $this->importService->doImport($this->getImportParams())->getCounter();

        $this->eventDispatcher->notify(
            'run.import.end',
            new Event(
                $this,
                EventMessage::build(__u('Accounts imported'))->addDetail(__u('Accounts imported'), $counter)
            )
        );

        if ($counter > 0) {
            return ActionResponse::ok(
                __u('Import finished'),
                __u('Please check out the event log for more details')
            );
        }

        return ActionResponse::warning(
            __u('No accounts were imported'),
            __u('Please check out the event log for more details')
        );
    }

    /**
     * @throws FileException
     */
    private function getImportParams(): ImportParamsDto
    {
        return new ImportParamsDto(
            $this->getFileFromRequest(),
            $this->request->analyzeInt('import_defaultuser', $this->session->getUserData()->id),
            $this->request->analyzeInt('import_defaultgroup', $this->session->getUserData()->userGroupId),
            $this->request->analyzeEncrypted('importPwd'),
            $this->request->analyzeEncrypted('importMasterPwd'),
            $this->request->analyzeString('csvDelimiter')
        );
    }

    /**
     * @return FileHandler
     * @throws FileException
     */
    private function getFileFromRequest(): FileHandler
    {
        $file = $this->request->getFile('inFile');

        if (!is_array($file)) {
            throw FileException::error(
                __u('File successfully uploaded'),
                __u('Please check the web server user permissions')
            );
        }

        return new FileHandler($file['tmp_name']);
    }

    /**
     * @throws SPException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checks();
        $this->checkAccess(AclActionsInterface::CONFIG_IMPORT);
    }
}
