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
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class SaveEditPassController
 */
final class SaveEditPassController extends AccountControllerBase
{
    use JsonTrait;

    private \SP\Domain\Account\Ports\AccountServiceInterface $accountService;
    private AccountForm                                      $accountForm;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        \SP\Domain\Account\Ports\AccountServiceInterface $accountService,
        \SP\Domain\Account\Ports\AccountPresetServiceInterface $accountPresetService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountService = $accountService;
        $this->accountForm = new AccountForm($application, $this->request, $accountPresetService);
    }

    /**
     * Saves edit action
     *
     * @param  int  $id  Account's ID
     *
     * @return bool
     * @throws \JsonException
     */
    public function saveEditPassAction(int $id): bool
    {
        try {
            $this->accountForm->validateFor(ActionsInterface::ACCOUNT_EDIT_PASS, $id);

            $this->accountService->editPassword($this->accountForm->getItemData());

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent(
                'edit.account.pass',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Password updated'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            return $this->returnJsonResponseData(
                [
                    'itemId'     => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW),
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Password updated')
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
