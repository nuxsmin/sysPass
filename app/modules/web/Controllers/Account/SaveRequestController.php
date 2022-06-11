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
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\User\UserServiceInterface;
use SP\Http\JsonResponse;
use SP\Http\Uri;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class SaveRequestController
 */
final class SaveRequestController extends AccountControllerBase
{
    use JsonTrait, ItemTrait;

    private AccountServiceInterface $accountService;
    private UserServiceInterface                       $userService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountServiceInterface $accountService,
        UserServiceInterface $userService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountService = $accountService;
        $this->userService = $userService;
    }

    /**
     * Saves a request action
     *
     * @param  int  $id  Account's ID
     *
     * @return bool
     * @throws \JsonException
     */
    public function saveRequestAction(int $id): bool
    {
        try {
            $description = $this->request->analyzeString('description');

            if (empty($description)) {
                throw new ValidationException(__u('A description is needed'));
            }

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapBase::$WEBURI).BootstrapBase::$SUBURI;

            $deepLink = new Uri($baseUrl);
            $deepLink->addParam('r', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW).'/'.$id);

            $usersId = [$accountDetails->userId, $accountDetails->userEditId];

            $this->eventDispatcher->notifyEvent(
                'request.account',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Request'))
                    ->addDetail(
                        __u('Requester'),
                        sprintf('%s (%s)', $this->userData->getName(), $this->userData->getLogin())
                    )
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail(__u('Description'), $description)
                    ->addDetail(__u('Link'), $deepLink->getUriSigned($this->configData->getPasswordSalt()))
                    ->addExtra('accountId', $id)
                    ->addExtra('whoId', $this->userData->getId())
                    ->setExtra('userId', $usersId)
                    ->setExtra(
                        'email',
                        array_map(
                            static fn($value) => $value->email,
                            $this->userService->getUserEmailById($usersId)
                        )
                    )
                )
            );

            return $this->returnJsonResponseData(
                [
                    'itemId'     => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT),
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Request done')
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