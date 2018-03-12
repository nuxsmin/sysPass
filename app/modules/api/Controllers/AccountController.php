<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Api\Controllers;

use SP\Api\ApiResponse;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\Modules\Api\Controllers\Traits\ResponseTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Services\Account\AccountService;

/**
 * Class AccountController
 * @package api\Controllers
 */
class AccountController extends ControllerBase
{
    use ResponseTrait;

    /**
     * @var AccountService
     */
    protected $accountService;

    /**
     * Saves create action
     */
    public function createAction()
    {
        try {
            $form = new AccountForm();
            $form->validate(ActionsInterface::ACCOUNT_CREATE);

            $itemData = $form->getItemData();
            $itemData->userId = $this->context->getUserData()->getId();

            $accountId = $this->accountService->create($itemData);

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent('create.account',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Cuenta creada'))
                        ->addDetail(__u('Cuenta'), $accountDetails->getName())
                        ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnResponse(new ApiResponse(__('Cuenta creada'), ApiResponse::RESULT_SUCCESS), $accountId);
        } catch (ValidationException $e) {
            $this->returnResponseException($e);
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function initialize()
    {
        $this->accountService = $this->dic->get(AccountService::class);
    }
}