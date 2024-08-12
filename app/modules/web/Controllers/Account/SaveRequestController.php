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
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Http\Providers\Uri;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class SaveRequestController
 */
final class SaveRequestController extends AccountControllerBase
{
    use ItemTrait;
    use JsonTrait;

    public function __construct(
        Application                     $application,
        WebControllerHelper             $webControllerHelper,
        private readonly AccountService $accountService,
        private readonly UserService    $userService
    ) {
        parent::__construct($application, $webControllerHelper);
    }

    /**
     * Saves a request action
     *
     * @param int $id Account's ID
     *
     * @return bool
     * @throws JsonException
     * @throws SPException
     */
    public function saveRequestAction(int $id): bool
    {
        try {
            $description = $this->request->analyzeString('description');

            if (empty($description)) {
                throw new ValidationException(__u('A description is needed'));
            }

            $accountView = $this->accountService->getByIdEnriched($id);

            $baseUrl = ($this->configData->getApplicationUrl() ?: $this->uriContext->getWebUri()) .
                       $this->uriContext->getSubUri();

            $deepLink = new Uri($baseUrl);
            $deepLink->addParam('r', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW) . '/' . $id);

            $usersId = [$accountView->getUserId(), $accountView->getUserEditId()];

            $this->eventDispatcher->notify(
                'request.account',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Request'))
                                ->addDetail(
                                    __u('Requester'),
                                    sprintf('%s (%s)', $this->userData->getName(), $this->userData->getLogin())
                                )
                                ->addDetail(__u('Account'), $accountView->getName())
                                ->addDetail(__u('Client'), $accountView->getClientName())
                                ->addDetail(__u('Description'), $description)
                                ->addDetail(
                                    __u('Link'),
                                    $deepLink->getUriSigned($this->configData->getPasswordSalt())
                                )
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
                    'itemId' => $id,
                    'nextAction' => $this->acl->getRouteFor(AclActionsInterface::ACCOUNT),
                ],
                JsonMessage::JSON_SUCCESS,
                __u('Request done')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
