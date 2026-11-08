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

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\UseCases\Dtos\GetUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\UpdateUserGroupCommand;

use function SP\__;

/**
 * Class EditController
 */
final class EditController extends UserGroupBase
{
    #[Action(ResponseType::JSON_RPC)]
    public function editAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::GROUP_EDIT);

        $userGroup = new UserGroup(
            [
                'id'          => $this->apiService->getParamInt('id', true),
                'name'        => $this->apiService->getParamString('name', true),
                'description' => $this->apiService->getParamString('description'),
                'users'       => $this->apiService->getParamArray('usersId'),
            ]
        );

        $this->updateUserGroupUseCase->execute(new UpdateUserGroupCommand($userGroup));

        $id = (int)$userGroup->getId();

        $userGroup = $this->getUserGroupUseCase->execute(new GetUserGroupCommand($id))->model;

        return ActionResponse::ok(__('Group updated'), $userGroup, $id);
    }
}
