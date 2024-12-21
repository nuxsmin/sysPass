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

namespace SP\Modules\Web\Controllers\AccountFile;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\SPException;
use SP\Mvc\Controller\ItemTrait;

use function SP\__u;

/**
 * Class DeleteController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DeleteController extends AccountFileBase
{
    use ItemTrait;

    /**
     * Delete action
     *
     * @param int|null $id
     *
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function deleteAction(?int $id): ActionResponse
    {
        if ($id === null) {
            $this->accountFileService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

            $this->eventDispatcher->notify(
                'delete.accountFile.selection',
                new Event($this, EventMessage::build()->addDescription(__u('Files deleted')))
            );

            return ActionResponse::ok(__u('Files deleted'));
        }

        $this->eventDispatcher->notify(
            'delete.accountFile',
            new Event(
                $this,
                EventMessage::build(__u('File deleted'))->addDetail(__u('File'), $id)
            )
        );

        $this->accountFileService->delete($id);

        return ActionResponse::ok(__u('File deleted'));
    }
}
