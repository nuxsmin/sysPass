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

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Context\Session;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ItemsImportService;
use SP\Http\JsonMessage;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class ImportController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ImportController extends SimpleControllerBase
{
    use JsonTrait;

    private ItemsImportService $importService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        ItemsImportService $importService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->importService = $importService;
    }

    /**
     * @throws JsonException
     * @throws SPException
     */
    public function importAction(): bool
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(JsonMessage::JSON_WARNING, __u('Ey, this is a DEMO!!'));
        }

        try {
            $this->eventDispatcher->notify('run.import.start', new Event($this));

            Session::close();

            $counter = $this->importService->doImport($this->getImportParams())->getCounter();

            $this->eventDispatcher->notify(
                'run.import.end',
                new Event(
                    $this,
                    EventMessage::factory()->addDetail(__u('Accounts imported'), $counter)
                )
            );

            if ($counter > 0) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_SUCCESS,
                    __u('Import finished'),
                    [__u('Please check out the event log for more details')]
                );
            }

            return $this->returnJsonResponse(
                JsonMessage::JSON_WARNING,
                __u('No accounts were imported'),
                [__u('Please check out the event log for more details')]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return ImportParamsDto
     * @throws FileException
     */
    private function getImportParams(): ImportParamsDto
    {
        return new ImportParamsDto(
            $this->getFileFromRequest('inFile'),
            $this->request->analyzeInt('import_defaultuser', $this->session->getUserData()->getId()),
            $this->request->analyzeInt('import_defaultgroup', $this->session->getUserData()->getUserGroupId()),
            $this->request->analyzeEncrypted('importPwd'),
            $this->request->analyzeEncrypted('importMasterPwd'),
            $this->request->analyzeString('csvDelimiter')
        );
    }

    /**
     * @param string $filename
     * @return FileHandler
     * @throws FileException
     */
    public function getFileFromRequest(string $filename): FileHandler
    {
        $file = $this->request->getFile($filename);

        if (!is_array($file)) {
            throw FileException::error(
                __u('File successfully uploaded'),
                __u('Please check the web server user permissions')
            );
        }

        return new FileHandler($file['tmp_name']);
    }

    /**
     * @return void
     * @throws SPException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_IMPORT);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
