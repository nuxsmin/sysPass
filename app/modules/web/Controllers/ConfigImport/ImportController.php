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

namespace SP\Modules\Web\Controllers\ConfigImport;

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Import\Ports\ImportServiceInterface;
use SP\Domain\Import\Services\FileImport;
use SP\Domain\Import\Services\ImportParams;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class ImportController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ImportController extends SimpleControllerBase
{
    use JsonTrait;

    private ImportServiceInterface $importService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        ImportServiceInterface $importService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->importService = $importService;
    }

    /**
     * @throws JsonException
     */
    public function importAction(): bool
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
        }

        try {
            $this->eventDispatcher->notify('run.import.start', new Event($this));

            SessionContext::close();

            $counter = $this->importService->doImport(
                $this->getImportParams(),
                FileImport::fromRequest('inFile', $this->request)
            );

            $this->eventDispatcher->notify(
                'run.import.end',
                new Event(
                    $this,
                    EventMessage::factory()->addDetail(__u('Accounts imported'), $counter)
                )
            );

            if ($counter > 0) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_SUCCESS,
                    __u('Import finished'),
                    [__u('Please check out the event log for more details')]
                );
            }

            return $this->returnJsonResponse(
                JsonResponse::JSON_WARNING,
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
     * @return ImportParams
     */
    private function getImportParams(): ImportParams
    {
        $importParams = new ImportParams();
        $importParams->setDefaultUser(
            $this->request->analyzeInt('import_defaultuser', $this->session->getUserData()->getId())
        );
        $importParams->setDefaultGroup(
            $this->request->analyzeInt('import_defaultgroup', $this->session->getUserData()->getUserGroupId())
        );
        $importParams->setImportPwd($this->request->analyzeEncrypted('importPwd'));
        $importParams->setImportMasterPwd($this->request->analyzeEncrypted('importMasterPwd'));
        $importParams->setCsvDelimiter($this->request->analyzeString('csvDelimiter'));

        return $importParams;
    }

    /**
     * @return void
     * @throws JsonException
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
