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

namespace SP\Modules\Web\Controllers\AccountFavorite;

use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class MarkController
 *
 * @package SP\Modules\Web\Controllers
 */
final class MarkController extends AccountFavoriteBase
{
    use JsonTrait;

    /**
     * @param  int  $accountId
     *
     * @return bool
     * @throws JsonException
     */
    public function markAction(int $accountId): bool
    {
        try {
            $this->accountToFavoriteService->add($accountId, $this->session->getUserData()->getId());

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Favorite added'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
