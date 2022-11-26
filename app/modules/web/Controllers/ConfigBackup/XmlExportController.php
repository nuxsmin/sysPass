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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Export\Ports\XmlExportServiceInterface;
use SP\Domain\Export\Ports\XmlVerifyServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class XmlExportController
 */
final class XmlExportController extends SimpleControllerBase
{
    use JsonTrait;

    private XmlExportServiceInterface $xmlExportService;
    private XmlVerifyServiceInterface $xmlVerifyService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        XmlExportServiceInterface $xmlExportService,
        XmlVerifyServiceInterface $xmlVerifyService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->xmlExportService = $xmlExportService;
        $this->xmlVerifyService = $xmlVerifyService;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function xmlExportAction(): bool
    {
        $exportPassword = $this->request->analyzeEncrypted('exportPwd');
        $exportPasswordR = $this->request->analyzeEncrypted('exportPwdR');

        if (!empty($exportPassword) && $exportPassword !== $exportPasswordR) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Passwords do not match'));
        }

        try {
            $this->eventDispatcher->notifyEvent(
                'run.export.start',
                new Event($this, EventMessage::factory()->addDescription(__u('sysPass XML export')))
            );

            SessionContext::close();

            $this->xmlExportService->doExport(BACKUP_PATH, $exportPassword);

            $this->eventDispatcher->notifyEvent(
                'run.export.end',
                new Event($this, EventMessage::factory()->addDescription(__u('Export process finished')))
            );

            if ($this->xmlExportService->isEncrypted()) {
                $verifyResult =
                    $this->xmlVerifyService->verifyEncrypted(
                        $this->xmlExportService->getExportFile(),
                        $exportPassword
                    );
            } else {
                $verifyResult = $this->xmlVerifyService->verify($this->xmlExportService->getExportFile());
            }

            $nodes = $verifyResult->getNodes();

            $this->eventDispatcher->notifyEvent(
                'run.export.verify',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Verification of exported data finished'))
                        ->addDetail(__u('Version'), $verifyResult->getVersion())
                        ->addDetail(__u('Encrypted'), $verifyResult->isEncrypted() ? __u('Yes') : __u('No'))
                        ->addDetail(__u('Accounts'), $nodes['Account'])
                        ->addDetail(__u('Clients'), $nodes['Client'])
                        ->addDetail(__u('Categories'), $nodes['Category'])
                        ->addDetail(__u('Tags'), $nodes['Tag'])
                )
            );

            // Create the XML archive after verifying the export integrity
            $this->xmlExportService->createArchive();

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Export process finished'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * initialize
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\SessionTimeout
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
