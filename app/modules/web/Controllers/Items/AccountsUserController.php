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

namespace SP\Modules\Web\Controllers\Items;

use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Mvc\Controller\SimpleControllerHelper;
use stdClass;

/**
 * Class AccountsUserController
 */
final class AccountsUserController extends SimpleControllerBase
{
    public function __construct(
        SimpleControllerHelper $simpleControllerHelper,
        private readonly AccountService $accountService
    ) {
        parent::__construct($simpleControllerHelper);

        $this->checks();
    }

    /**
     * Devolver las cuentas visibles por el usuario
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function accountsUserAction(?int $accountId = null): ActionResponse
    {
        $outItems = [];

        foreach ($this->accountService->getForUser($accountId) as $account) {
            $obj = new stdClass();
            $obj->id = $account->id;
            $obj->name = $account->clientName.' - '.$account->name;

            $outItems[] = $obj;
        }

        return ActionResponse::ok('', $outItems);
    }
}
