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

namespace SP\Modules\Api\Controllers\Tag;


use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Services\ApiResponse;

/**
 * Class DeleteController
 */
final class DeleteController extends TagBase
{
    /**
     * deleteAction
     */
    public function deleteAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::TAG_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $tagData = $this->tagService->getById($id);

            $this->tagService->delete($id);

            $this->eventDispatcher->notifyEvent(
                'delete.tag',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Tag removed'))
                        ->addDetail(__u('Name'), $tagData->getName())
                        ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($tagData, $id, __('Tag removed')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

}