<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Import\FileImport;
use SP\Services\Import\ImportParams;
use SP\Services\Import\ImportService;

/**
 * Class ConfigImportController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigImportController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SPException
     */
    public function importAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
        }

        $importParams = new ImportParams();
        $importParams->setDefaultUser($this->request->analyzeInt('import_defaultuser', $this->session->getUserData()->getId()));
        $importParams->setDefaultGroup($this->request->analyzeInt('import_defaultgroup', $this->session->getUserData()->getUserGroupId()));
        $importParams->setImportPwd($this->request->analyzeEncrypted('importPwd'));
        $importParams->setImportMasterPwd($this->request->analyzeEncrypted('importMasterPwd'));
        $importParams->setCsvDelimiter($this->request->analyzeString('csvDelimiter'));

        try {
            $this->eventDispatcher->notifyEvent('run.import.start', new Event($this));

            SessionContext::close();

            $counter = $this->dic->get(ImportService::class)
                ->doImport($importParams, FileImport::fromRequest('inFile', $this->request));

            $this->eventDispatcher->notifyEvent('run.import.end',
                new Event($this, EventMessage::factory()->addDetail(__u('Accounts imported'), $counter))
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

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::CONFIG_IMPORT);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return true;
    }
}