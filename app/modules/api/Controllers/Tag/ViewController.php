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

namespace SP\Modules\Api\Controllers\Tag;

use Exception;
use SP\Core\Acl\AclActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Services\ApiResponse;

/**
 * Class ViewController
 *
 * @package SP\Modules\Api\Controllers
 */
final class ViewController extends TagBase
{
    /**
     * viewAction
     */
    public function viewAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::TAG_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $tagData = $this->tagService->getById($id);

            $this->eventDispatcher->notify(
                'show.tag',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Tag displayed'))
                    ->addDetail(__u('Name'), $tagData->getName())
                    ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($tagData, $id));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }
}
