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

namespace SP\Modules\Web\Controllers\Account;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\Account\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Helpers\HelperException;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class CopyPassController
 */
final class CopyPassController extends AccountControllerBase
{
    public function __construct(
        Application                            $application,
        WebControllerHelper                    $webControllerHelper,
        private readonly AccountService        $accountService,
        private readonly AccountPasswordHelper $accountPasswordHelper
    ) {
        parent::__construct($application, $webControllerHelper);
    }

    /**
     * Copy account's password
     *
     * @param int $id Account's ID
     *
     * @return ActionResponse
     * @throws ConstraintException
     * @throws CryptException
     * @throws HelperException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    #[Action(ResponseType::JSON)]
    public function copyPassAction(int $id): ActionResponse
    {
        $account = $this->accountService->getPasswordForId($id);

        $data = [
            'accpass' => $this->accountPasswordHelper->getPasswordClear($account),
        ];

        $this->eventDispatcher->notify(
            'copy.account.pass',
            new Event(
                $this,
                EventMessage::build(__u('Password copied'))
                            ->addDetail(__u('Account'), $account->getName())
            )
        );

        return ActionResponse::ok(__u('Password copied'), $data);
    }
}
