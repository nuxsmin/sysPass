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
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\UseCases\Dtos\DeleteUsersCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUsersResult;

/**
 * Deletes multiple Users and their custom field data.
 *
 * @implements UseCase<DeleteUsersCommand, DeleteUsersResult>
 */
final readonly class DeleteUsersUseCase implements UseCase
{
    /**
     * @param CustomFieldDataService<CustomFieldDataModel> $customFieldDataService
     */
    public function __construct(
        private UserService $userService,
        private CustomFieldDataService $customFieldDataService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): DeleteUsersResult
    {
        if (!$command instanceof DeleteUsersCommand) {
            throw new LogicException(sprintf('Expected command %s', DeleteUsersCommand::class));
        }

        $this->userService->deleteByIdBatch($command->ids);

        $this->customFieldDataService->delete($command->ids, AclActionsInterface::USER);

        $this->eventEmitter->emit(
            'delete.user.selection',
            $this,
            EventMessage::build('Users deleted')->setExtra('userId', $command->ids)
        );

        return new DeleteUsersResult($command->ids);
    }
}
