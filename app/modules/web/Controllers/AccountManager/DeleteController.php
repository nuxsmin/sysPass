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

namespace SP\Modules\Web\Controllers\AccountManager;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DeleteController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                             $application,
        WebControllerHelper                     $webControllerHelper,
        private readonly AccountService         $accountService,
        private readonly CustomFieldDataService $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Delete action
     *
     * @param int|null $id
     *
     * @return bool
     * @throws SPException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if ($id === null) {
                $ids = $this->getItemsIdFromRequest($this->request);
                $this->accountService->deleteByIdBatch($ids);

                $this->deleteCustomFieldsForItem(AclActionsInterface::ACCOUNT, $ids, $this->customFieldService);

                $this->eventDispatcher->notify(
                    'delete.account.selection',
                    new Event($this, EventMessage::build()->addDescription(__u('Accounts removed')))
                );

                return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Accounts removed'));
            }

            $accountView = $this->accountService->getByIdEnriched($id);

            $this->accountService->delete($id);

            $this->deleteCustomFieldsForItem(AclActionsInterface::ACCOUNT, $id, $this->customFieldService);

            $this->eventDispatcher->notify(
                'delete.account',
                new Event(
                    $this,
                    EventMessage::build(__u('Account removed'))
                                ->addDetail(__u('Account'), $accountView->getName())
                                ->addDetail(__u('Client'), $accountView->getClientName())
                )
            );

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Account removed'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}
