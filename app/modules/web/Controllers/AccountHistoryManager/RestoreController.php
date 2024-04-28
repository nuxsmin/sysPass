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

namespace SP\Modules\Web\Controllers\AccountHistoryManager;

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Http\Dtos\JsonMessage;
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

    private AccountHistoryService $accountHistoryService;
    private AccountService        $accountService;

    public function __construct(
        Application           $application,
        WebControllerHelper   $webControllerHelper,
        AccountHistoryService $accountHistoryService,
        AccountService        $accountService
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
     * @throws JsonException
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

            $this->eventDispatcher->notify(
                'restore.accountHistory',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account restored'))
                        ->addDetail(__u('Account'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Account restored'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
