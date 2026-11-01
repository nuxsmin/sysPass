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
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\UseCases\Dtos\UpdateUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\UpdateUserGroupResult;

/**
 * Update UserGroup use case.
 *
 * @implements UseCase<UpdateUserGroupCommand, UpdateUserGroupResult>
 */
final readonly class UpdateUserGroupUseCase implements UseCase
{
    /**
     * @param UserGroupService<UserGroup> $userGroupService
     */
    public function __construct(
        private UserGroupService $userGroupService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): UpdateUserGroupResult
    {
        if (!$command instanceof UpdateUserGroupCommand) {
            throw new LogicException(sprintf('Expected command %s', UpdateUserGroupCommand::class));
        }

        $this->userGroupService->update($command->model);

        $id = (int)$command->model->getId();

        $this->eventEmitter->emit(
            'edit.userGroup',
            $this,
            EventMessage::build('Group updated')
                ->addDetail('Name', $command->model->getName())
                ->setExtra('userGroupId', [$id])
        );

        return new UpdateUserGroupResult($id);
    }
}
