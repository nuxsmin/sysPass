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

namespace SP\Modules\Web\Controllers\Account;


use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\CustomField\CustomFieldServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

final class SaveCopyController extends AccountControllerBase
{
    use JsonTrait, ItemTrait;

    private AccountServiceInterface     $accountService;
    private AccountForm                 $accountForm;
    private CustomFieldServiceInterface $customFieldService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountServiceInterface $accountService,
        \SP\Domain\Account\AccountPresetServiceInterface $accountPresetService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountService = $accountService;
        $this->customFieldService = $customFieldService;
        $this->accountForm = new AccountForm($application, $this->request, $accountPresetService);
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    public function saveCopyAction(): ?bool
    {
        try {
            $this->accountForm->validate(ActionsInterface::ACCOUNT_CREATE);

            $accountId = $this->accountService->create($this->accountForm->getItemData());

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent(
                'create.account',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Account created'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            $this->addCustomFieldsForItem(
                ActionsInterface::ACCOUNT,
                $accountId,
                $this->request,
                $this->customFieldService
            );

            return $this->returnJsonResponseData(
                [
                    'itemId'     => $accountId,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_EDIT),
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Account created')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}