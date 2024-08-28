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

namespace SP\Modules\Web\Controllers\AccountHistoryManager;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class RestoreController
 *
 * @package SP\Modules\Web\Controllers
 */
final class RestoreController extends ControllerBase
{
    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                            $application,
        WebControllerHelper                    $webControllerHelper,
        private readonly AccountHistoryService $accountHistoryService,
        private readonly AccountService        $accountService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }


    /**
     * Saves restore action
     *
     * @param int $id Account's history ID
     *
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function restoreAction(int $id): ActionResponse
    {
        $accountHistoryDto = $this->accountHistoryService->getById($id);

        if ($accountHistoryDto->isModify) {
            $this->accountService->restoreModified($accountHistoryDto);
        } else {
            $this->accountService->restoreRemoved($accountHistoryDto);
        }

        $this->eventDispatcher->notify(
            'restore.accountHistory',
            new Event(
                $this,
                EventMessage::build(__u('Account restored'))
                            ->addDetail(__u('Data'), (string)$accountHistoryDto)
            )
        );

        return ActionResponse::ok(__u('Account restored'));
    }
}
