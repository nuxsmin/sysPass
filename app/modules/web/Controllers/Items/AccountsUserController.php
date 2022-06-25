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

namespace SP\Modules\Web\Controllers\Items;

use SP\Core\Application;
use SP\Domain\Account\AccountServiceInterface;
use SP\Http\Json;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;
use stdClass;

/**
 * Class AccountsUserController
 */
final class AccountsUserController extends SimpleControllerBase
{
    private AccountServiceInterface $accountService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        AccountServiceInterface $accountService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->checks();

        $this->accountService = $accountService;
    }

    /**
     * Devolver las cuentas visibles por el usuario
     *
     * @param  int|null  $accountId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function accountsUserAction(?int $accountId = null): void
    {
        $outItems = [];

        foreach ($this->accountService->getForUser($accountId) as $account) {
            $obj = new stdClass();
            $obj->id = $account->id;
            $obj->name = $account->clientName.' - '.$account->name;

            $outItems[] = $obj;
        }

        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus(0);
        $jsonResponse->setData($outItems);

        Json::factory($this->router->response())->returnJson($jsonResponse);
    }
}