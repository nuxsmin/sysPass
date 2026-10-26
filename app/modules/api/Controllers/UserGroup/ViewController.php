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

namespace SP\Modules\Api\Controllers\UserGroup;

use Exception;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Dtos\ApiResponse;
use SP\Domain\Core\Acl\AclActionsInterface;

/**
 * Class ViewController
 *
 * @package SP\Modules\Api\Controllers
 */
final class ViewController extends UserGroupBase
{
    /**
     * viewAction
     */
    public function viewAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::GROUP_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $userGroupData = $this->userGroupService->getById($id);

            $this->eventDispatcher->notify(
                'show.userGroup',
                new Event(
                    $this,
                    EventMessage::build()
                        ->addDescription(__u('Group viewed'))
                        ->addDetail(__u('Name'), $userGroupData->getName())
                        ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(
                ApiResponse::makeSuccess($userGroupData, $id)
            );
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }
}
