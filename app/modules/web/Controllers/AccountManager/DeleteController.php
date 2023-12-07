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

namespace SP\Modules\Web\Controllers\AccountManager;

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DeleteController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    private AccountServiceInterface     $accountService;
    private CustomFieldServiceInterface $customFieldService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountServiceInterface $accountService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->accountService = $accountService;
        $this->customFieldService = $customFieldService;

        $this->checkLoggedIn();
    }

    /**
     * Delete action
     *
     * @param  int|null  $id
     *
     * @return bool
     * @throws JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if ($id === null) {
                $this->accountService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(AclActionsInterface::ACCOUNT, $id, $this->customFieldService);

                $this->eventDispatcher->notify(
                    'delete.account.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Accounts removed')))
                );

                return $this->returnJsonResponseData(JsonMessage::JSON_SUCCESS, __u('Accounts removed'));
            }

            $accountDetails = $this->accountService->getByIdEnriched($id)->getAccountVData();

            $this->accountService->delete($id);

            $this->deleteCustomFieldsForItem(AclActionsInterface::ACCOUNT, $id, $this->customFieldService);

            $this->eventDispatcher->notify(
                'delete.account',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account removed'))
                        ->addDetail(__u('Account'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Account removed'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}
