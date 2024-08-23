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
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Util\Util;

use function SP\__u;
use function SP\processException;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveBulkEditController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    private readonly AccountForm $accountForm;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                            $application,
        WebControllerHelper                    $webControllerHelper,
        private readonly AccountService        $accountService,
        private readonly AccountHistoryService $accountHistoryService,
        AccountPresetService                   $accountPresetService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->accountForm = new AccountForm($application, $this->request, $accountPresetService);

        $this->checkLoggedIn();
    }

    /**
     * saveBulkEditAction
     *
     * @return bool
     * @throws SPException
     */
    public function saveBulkEditAction(): bool
    {
        try {
            $itemsId = Util::itemsIdAdapter($this->request->analyzeString('itemsId'));

            $accountBulkDto = new AccountUpdateBulkDto(
                $itemsId,
                array_map(
                    fn(int $id) => $this->accountForm
                        ->validateFor(AclActionsInterface::ACCOUNTMGR_BULK_EDIT, $id)
                        ->getItemData(),
                    $itemsId
                )
            );

            if ($this->request->analyzeBool('delete_history', false)) {
                $this->accountHistoryService->deleteByAccountIdBatch($itemsId);
            }

            $this->accountService->updateBulk($accountBulkDto);

            $this->eventDispatcher->notify(
                'edit.account.bulk',
                new Event($this, EventMessage::build(__u('Accounts updated')))
            );

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Accounts updated'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}
