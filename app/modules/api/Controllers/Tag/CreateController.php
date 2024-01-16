<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\TagData;
use SP\Domain\Api\Dtos\ApiResponse;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;

/**
 * Class CreateController
 */
final class CreateController extends TagBase
{
    /**
     * createAction
     */
    public function createAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::TAG_CREATE);

            $tagData = $this->buildTagData();

            $id = $this->tagService->create($tagData);

            $tagData->setId($id);

            $this->eventDispatcher->notify(
                'create.tag',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Tag added'))
                        ->addDetail(__u('Name'), $tagData->getName())
                        ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($tagData, $id, __('Tag added')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return TagData
     * @throws ServiceException
     */
    private function buildTagData(): TagData
    {
        $tagData = new TagData();
        $tagData->setName($this->apiService->getParamString('name', true));

        return $tagData;
    }
}
