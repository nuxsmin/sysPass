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

namespace SP\Modules\Web\Controllers\Account;


use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class SaveDeleteController
 */
final class SaveDeleteController extends AccountControllerBase
{
    use ItemTrait;
    use JsonTrait;

    private AccountService         $accountService;
    private CustomFieldDataService $customFieldService;

    public function __construct(
        Application         $application,
        WebControllerHelper $webControllerHelper,
        AccountService      $accountService,
        CustomFieldDataService $customFieldService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountService = $accountService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * Saves delete action
     *
     * @param  int  $id  Account's ID
     *
     * @return bool
     * @throws JsonException
     */
    public function saveDeleteAction(int $id): bool
    {
        try {
            $accountDetails = $this->accountService->getByIdEnriched($id)->getAccountVData();

            $this->accountService->delete($id);

            $this->eventDispatcher->notify(
                'delete.account',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Account removed'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            $this->deleteCustomFieldsForItem(AclActionsInterface::ACCOUNT, $id, $this->customFieldService);

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Account removed'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
