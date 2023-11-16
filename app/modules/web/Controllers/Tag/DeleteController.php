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

namespace SP\Modules\Web\Controllers\Tag;


use Exception;
use SP\Core\Acl\AclActionsInterface;
use SP\Core\Events\Event;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;

/**
 * Class DeleteController
 */
final class DeleteController extends TagSaveBase
{
    use JsonTrait, ItemTrait;

    /**
     * Delete action
     *
     * @param  int|null  $id
     *
     * @return bool
     * @throws \JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::TAG_DELETE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            if ($id === null) {
                $this->tagService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(AclActionsInterface::TAG, $id, $this->customFieldService);

                $this->eventDispatcher->notify('delete.tag.selection', new Event($this));

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Tags deleted'));
            }

            $this->tagService->delete($id);

            $this->deleteCustomFieldsForItem(AclActionsInterface::TAG, $id, $this->customFieldService);

            $this->eventDispatcher->notify('delete.tag', new Event($this));

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Tag removed')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
