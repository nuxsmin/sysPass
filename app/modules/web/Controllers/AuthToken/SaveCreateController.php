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

namespace SP\Modules\Web\Controllers\AuthToken;

use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Dtos\JsonMessage;

/**
 * Class SaveCreateController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveCreateController extends AuthTokenSaveBase
{
    /**
     * @return bool
     * @throws JsonException
     */
    public function saveCreateAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::AUTHTOKEN_CREATE)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);

            $id = $this->authTokenService->create($this->form->getItemData());

            $this->addCustomFieldsForItem(
                AclActionsInterface::AUTHTOKEN,
                $id,
                $this->request,
                $this->customFieldService
            );

            $this->eventDispatcher->notify('create.authToken', new Event($this));

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Authorization added'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponse(JsonMessage::JSON_ERROR, $e->getMessage());
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
