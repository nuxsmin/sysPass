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

namespace SP\Modules\Web\Controllers\Category;


use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__u;

/**
 * DeleteController
 */
final class DeleteController extends CategorySaveBase
{
    /**
     * Delete action
     *
     * @param int|null $id
     *
     * @return ActionResponse
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    #[Action(ResponseType::JSON)]
    public function deleteAction(?int $id = null): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::CATEGORY_DELETE)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        if ($id === null) {
            $ids = $this->getItemsIdFromRequest($this->request);
            $this->categoryService->deleteByIdBatch($ids);

            $this->deleteCustomFieldsForItem(AclActionsInterface::CATEGORY, $ids, $this->customFieldService);

            $this->eventDispatcher->notify(
                'delete.category',
                new Event(
                    $this,
                    EventMessage::build()->addDescription(__u('Categories deleted'))
                )
            );

            return ActionResponse::ok(__u('Categories deleted'));
        }

        $this->categoryService->delete($id);

        $this->deleteCustomFieldsForItem(AclActionsInterface::CATEGORY, $id, $this->customFieldService);

        $this->eventDispatcher->notify(
            'delete.category',
            new Event(
                $this,
                EventMessage::build(__u('Category deleted'))->addDetail(__u('Category'), $id)
            )
        );

        return ActionResponse::ok(__u('Category deleted'));
    }
}
