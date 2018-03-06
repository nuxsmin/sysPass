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

namespace SP\Modules\Web\Controllers;

use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Account\AccountFavoriteService;

/**
 * Class AccountFavoriteController
 *
 * @package SP\Modules\Web\Controllers
 */
class AccountFavoriteController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @var AccountFavoriteService
     */
    private $accountFavoriteService;

    /**
     * @param $accountId
     */
    public function markAction($accountId)
    {
        try {
            $this->accountFavoriteService->add($accountId, $this->session->getUserData()->getId());

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Favorito añadido'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * @param $accountId
     */
    public function unmarkAction($accountId)
    {
        try {
            $this->accountFavoriteService->delete($accountId, $this->session->getUserData()->getId());

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Favorito eliminado'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    protected function initialize()
    {
        $this->checks();

        $this->accountFavoriteService = $this->dic->get(AccountFavoriteService::class);
    }

}