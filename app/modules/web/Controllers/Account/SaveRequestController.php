<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Common\Providers\Html;
use SP\Domain\Http\Providers\Uri;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Mvc\Controller\ItemTrait;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class SaveRequestController
 */
final class SaveRequestController extends AccountControllerBase
{
    use ItemTrait;

    private const MAX_REQUESTS_PER_DAY     = 3;
    private const RATE_LIMIT_WINDOW_SECONDS = 86400;
    private const RATE_LIMIT_SOURCE_PREFIX = 'account.request:';

    public function __construct(
        WebControllerHelper             $webControllerHelper,
        private readonly AccountService $accountService,
        private readonly UserService    $userService,
        private readonly TrackService   $trackService
    ) {
        parent::__construct($webControllerHelper);
    }

    /**
     * Saves a request action
     *
     * @param int $id Account's ID
     *
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function saveRequestAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_REQUEST)) {
            throw new UnauthorizedPageException(SPException::INFO);
        }

        if (!$this->accountService->isVisible($id, true)) {
            throw new AccountPermissionException(SPException::INFO);
        }

        try {
            $accountView = $this->accountService->getByIdEnriched($id);
        } catch (NoSuchItemException) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $description = trim($this->request->analyzeString('description', ''));

        if ($description === '') {
            throw new ValidationException(__u('A description is needed'));
        }

        $trackSource = self::RATE_LIMIT_SOURCE_PREFIX . $id;
        $recentRequests = $this->trackService->countForUserAndSourceFromTime(
            $this->userDto->id,
            $trackSource,
            time() - self::RATE_LIMIT_WINDOW_SECONDS
        );

        if ($recentRequests >= self::MAX_REQUESTS_PER_DAY) {
            throw new ValidationException(__u('Maximum number of requests exceeded'));
        }

        $this->trackService->addUserTrack($this->userDto->id, $trackSource);

        $baseUrl = ($this->configData->getApplicationUrl() ?: $this->uriContext->getWebUri()) .
                   $this->uriContext->getSubUri();

        $deepLink = new Uri($baseUrl);
        $deepLink->addParam('r', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW) . '/' . $id);

        $deepLinkUrl = Html::getSafeUrl($deepLink->getUriSigned($this->configData->getPasswordSalt()));

        $usersId = [$accountView->getUserId()];

        $this->eventDispatcher->notify(
            'request.account',
            new Event(
                $this,
                EventMessage::build(__u('Request'))
                            ->addDetail(
                                __u('Requester'),
                                sprintf('%s (%s)', $this->userDto->name, $this->userDto->login)
                            )
                            ->addDetail(__u('Account'), $accountView->getName())
                            ->addDetail(__u('Client'), $accountView->getClientName())
                            ->addDetail(__u('Description'), $description)
                            ->addDetail(
                                __u('Link'),
                                $deepLinkUrl
                            )
                            ->addExtra('accountId', $id)
                            ->addExtra('whoId', $this->userDto->id)
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

        return ActionResponse::ok(
            __u('Request done'),
            [
                'itemId' => $id,
                'nextAction' => $this->acl->getRouteFor(AclActionsInterface::ACCOUNT),
            ]
        );
    }
}
