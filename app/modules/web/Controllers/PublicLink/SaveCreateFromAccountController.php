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

namespace SP\Modules\Web\Controllers\PublicLink;

use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\DataModel\PublicLinkData;
use SP\Domain\Account\Services\PublicLinkService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Util\PasswordUtil;

/**
 * Class SaveCreateFromAccountController
 */
final class SaveCreateFromAccountController extends PublicLinkSaveBase
{
    use JsonTrait;

    /**
     * Saves create action
     *
     * @param  int  $accountId
     * @param  int  $notify
     *
     * @return bool
     * @throws JsonException
     */
    public function saveCreateFromAccountAction(int $accountId, int $notify): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::PUBLICLINK_CREATE)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $publicLinkData = new PublicLinkData();
            $publicLinkData->setTypeId(PublicLinkService::TYPE_ACCOUNT);
            $publicLinkData->setItemId($accountId);
            $publicLinkData->setNotify((bool)$notify);
            $publicLinkData->setHash(PasswordUtil::generateRandomBytes());

            $this->publicLinkService->create($publicLinkData);

            $this->eventDispatcher->notify('create.publicLink.account', new Event($this));

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Link created'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
