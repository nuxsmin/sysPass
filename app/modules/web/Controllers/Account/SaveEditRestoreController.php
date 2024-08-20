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

namespace SP\Modules\Web\Controllers\Account;

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class SaveEditRestoreController
 */
final class SaveEditRestoreController extends AccountControllerBase
{
    use JsonTrait;

    public function __construct(
        Application                            $application,
        WebControllerHelper                    $webControllerHelper,
        private readonly AccountService        $accountService,
        private readonly AccountHistoryService $accountHistoryService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );
    }

    /**
     * Saves restore action
     *
     * @param int $historyId Account's history ID
     * @param int $id Account's ID
     *
     * @return bool
     * @throws JsonException
     * @throws SPException
     */
    public function saveEditRestoreAction(int $historyId, int $id): bool
    {
        try {
            $this->accountService->restoreModified($this->accountHistoryService->getById($historyId));

            $accountDetails = $this->accountService->getByIdEnriched($id);

            $this->eventDispatcher->notify(
                'edit.account.restore',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Account restored'))
                                ->addDetail(__u('Account'), $accountDetails->getName())
                                ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            return $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW),
                ],
                JsonMessage::JSON_SUCCESS,
                __u('Account restored')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
