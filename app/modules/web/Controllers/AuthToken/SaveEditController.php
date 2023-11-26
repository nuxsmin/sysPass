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
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Http\JsonResponse;

/**
 * Class SaveEditController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveEditController extends AuthTokenSaveBase
{
    /**
     * Saves edit action
     *
     * @param  int  $id
     *
     * @return bool
     * @throws JsonException
     */
    public function saveEditAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::AUTHTOKEN_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->form->validateFor(AclActionsInterface::AUTHTOKEN_EDIT, $id);

            if ($this->form->isRefresh()) {
                $this->authTokenService->refreshAndUpdate($this->form->getItemData());

                $this->eventDispatcher->notify(
                    'refresh.authToken',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Authorization updated'))
                            ->addDetail(__u('Authorization'), $id)
                    )
                );
            } else {
                $this->authTokenService->update($this->form->getItemData());

                $this->eventDispatcher->notify(
                    'edit.authToken',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Authorization updated'))
                            ->addDetail(__u('Authorization'), $id)
                    )
                );
            }

            $this->updateCustomFieldsForItem(
                AclActionsInterface::AUTHTOKEN,
                $id,
                $this->request,
                $this->customFieldService
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Authorization updated'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

}
