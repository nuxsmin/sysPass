<?php

declare(strict_types=1);
/**
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


namespace SP\Domain\User\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\UseCases\Dtos\DeleteUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUserGroupResult;

/**
 * Delete UserGroup use case.
 *
 * @implements UseCase<DeleteUserGroupCommand, DeleteUserGroupResult>
 */
final readonly class DeleteUserGroupUseCase implements UseCase
{
    /**
     * @param UserGroupService<UserGroup> $userGroupService
     * @param CustomFieldDataService<CustomFieldDataModel> $customFieldDataService
     */
    public function __construct(
        private UserGroupService $userGroupService,
        private EventEmitter $eventEmitter,
        private CustomFieldDataService $customFieldDataService
    ) {
    }

    public function execute(Command $command): DeleteUserGroupResult
    {
        if (!$command instanceof DeleteUserGroupCommand) {
            throw new LogicException(sprintf('Expected command %s', DeleteUserGroupCommand::class));
        }

        $this->userGroupService->delete($command->id);

        $this->customFieldDataService->delete([$command->id], AclActionsInterface::GROUP);

        $this->eventEmitter->emit(
            'delete.userGroup',
            $this,
            EventMessage::build('Group deleted')
                ->addDetail('Group', $command->id)
                ->setExtra('userGroupId', [$command->id])
        );

        return new DeleteUserGroupResult($command->id);
    }
}
