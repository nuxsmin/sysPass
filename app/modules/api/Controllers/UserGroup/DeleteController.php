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
use SP\Domain\User\UseCases\Dtos\DeleteUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\GetUserGroupCommand;

use function SP\__;

/**
 * Class DeleteController
 */
final class DeleteController extends UserGroupBase
{
    #[Action(ResponseType::JSON_RPC)]
    public function deleteAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::GROUP_DELETE);

        $id = $this->apiService->getParamInt('id', true);

        $userGroup = $this->getUserGroupUseCase->execute(new GetUserGroupCommand($id))->model;

        $this->deleteUserGroupUseCase->execute(new DeleteUserGroupCommand($id));

        return ActionResponse::ok(__('Group deleted'), $userGroup, $id);
    }
}
