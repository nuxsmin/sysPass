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
use SP\Domain\User\UseCases\Dtos\CreateUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\GetUserGroupCommand;

use function SP\__;

/**
 * Class CreateController
 */
final class CreateController extends UserGroupBase
{
    #[Action(ResponseType::JSON_RPC)]
    public function createAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::GROUP_CREATE);

        $userGroup = new UserGroup(
            [
                'name'        => $this->apiService->getParamString('name', true),
                'description' => $this->apiService->getParamString('description'),
                'users'       => $this->apiService->getParamArray('usersId'),
            ]
        );

        $result = $this->createUserGroupUseCase->execute(new CreateUserGroupCommand($userGroup));

        $userGroup = $this->getUserGroupUseCase->execute(new GetUserGroupCommand($result->id))->model;

        return ActionResponse::ok(__('Group added'), $userGroup, $result->id);
    }
}
