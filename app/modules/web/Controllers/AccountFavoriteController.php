<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Events\Event;
use SP\Core\Exceptions\SessionTimeout;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Account\AccountToFavoriteService;

/**
 * Class AccountFavoriteController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountFavoriteController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @var AccountToFavoriteService
     */
    private $accountFavoriteService;

    /**
     * @param $accountId
     *
     * @return bool
     */
    public function markAction($accountId)
    {
        try {
            $this->accountFavoriteService->add($accountId, $this->session->getUserData()->getId());

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Favorite added'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @param $accountId
     *
     * @return bool
     */
    public function unmarkAction($accountId)
    {
        try {
            $this->accountFavoriteService->delete($accountId, $this->session->getUserData()->getId());

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Favorite deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        $this->checks();

        $this->accountFavoriteService = $this->dic->get(AccountToFavoriteService::class);
    }

}