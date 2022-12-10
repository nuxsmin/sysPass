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

namespace SP\Modules\Web\Controllers\AccountHistoryManager;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class RestoreController
 *
 * @package SP\Modules\Web\Controllers
 */
final class RestoreController extends ControllerBase
{
    use JsonTrait;

    private AccountHistoryServiceInterface $accountHistoryService;
    private AccountServiceInterface        $accountService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountHistoryServiceInterface $accountHistoryService,
        AccountServiceInterface $accountService
    ) {
        $this->accountHistoryService = $accountHistoryService;
        $this->accountService = $accountService;

        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }


    /**
     * Saves restore action
     *
     * @param  int  $id  Account's history ID
     *
     * @return bool
     * @throws \JsonException
     */
    public function restoreAction(int $id): bool
    {
        try {
            $accountDetails = $this->accountHistoryService->getById($id);

            if ($accountDetails->isModify) {
                $this->accountService->restoreModified($id, $accountDetails->getAccountId());
            } else {
                $this->accountService->restoreRemoved($accountDetails);
            }

            $this->eventDispatcher->notifyEvent(
                'restore.accountHistory',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account restored'))
                        ->addDetail(__u('Account'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Account restored'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
