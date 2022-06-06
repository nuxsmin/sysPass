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

namespace SP\Modules\Web\Controllers\AccountFile;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\AccountFileServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class DeleteController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DeleteController extends ControllerBase
{
    private const MIME_VIEW = ['text/plain'];

    use JsonTrait, ItemTrait;

    private AccountFileServiceInterface $accountFileService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountFileServiceInterface $accountFileService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->accountFileService = $accountFileService;
    }

    /**
     * Delete action
     *
     * @param  int|null  $id
     *
     * @return bool
     * @throws \JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if ($id === null) {
                $this->accountFileService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.accountFile.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Files deleted')))
                );

                return $this->returnJsonResponse(0, __u('Files deleted'));
            }

            $this->eventDispatcher->notifyEvent(
                'delete.accountFile',
                new Event(
                    $this,
                    EventMessage::factory()->addDescription(__u('File deleted'))->addDetail(__u('File'), $id)
                )
            );

            $this->accountFileService->delete($id);

            return $this->returnJsonResponse(0, __u('File Deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
