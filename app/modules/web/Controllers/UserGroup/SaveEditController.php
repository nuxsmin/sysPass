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

namespace SP\Modules\Web\Controllers\UserGroup;

use SP\Core\Application;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Domain\User\UseCases\Dtos\UpdateUserGroupCommand;
use SP\Domain\User\UseCases\UpdateUserGroupUseCase;

use function SP\__u;

/**
 * Class SaveEditController
 */
final class SaveEditController extends UserGroupSaveBase
{
    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        private readonly UpdateUserGroupUseCase $updateUserGroupUseCase,
        CustomFieldDataService $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper, $customFieldService);
    }

    #[Action(ResponseType::JSON)]
    public function saveEditAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::GROUP_EDIT)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->form->validateFor(AclActionsInterface::GROUP_EDIT, $id);

            $this->updateUserGroupUseCase->execute(
                new UpdateUserGroupCommand($this->form->getItemData())
            );

            $this->updateCustomFieldsForItem(
                AclActionsInterface::GROUP,
                $id,
                $this->request,
                $this->customFieldService
            );

        } catch (SPException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        }

        return ActionResponse::ok(__u('Group updated'));
    }
}
