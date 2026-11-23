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
use SP\Domain\User\Models\UserProfile;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\UseCases\Dtos\DeleteUserProfileCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUserProfileResult;

/**
 * Delete UserProfile use case.
 *
 * @implements UseCase<DeleteUserProfileCommand, DeleteUserProfileResult>
 */
final readonly class DeleteUserProfileUseCase implements UseCase
{
    /**
     * @param UserProfileService $userProfileService
     * @param CustomFieldDataService<CustomFieldDataModel> $customFieldDataService
     */
    public function __construct(
        private UserProfileService $userProfileService,
        private EventEmitter $eventEmitter,
        private CustomFieldDataService $customFieldDataService
    ) {
    }

    public function execute(Command $command): DeleteUserProfileResult
    {
        if (!$command instanceof DeleteUserProfileCommand) {
            throw new LogicException(sprintf('Expected command %s', DeleteUserProfileCommand::class));
        }

        $this->userProfileService->delete($command->id);

        $this->customFieldDataService->delete([$command->id], AclActionsInterface::PROFILE);

        $this->eventEmitter->emit(
            'delete.userProfile',
            $this,
            EventMessage::build('Profile deleted')
                ->addDetail('Profile', $command->id)
                ->setExtra('userProfileId', [$command->id])
        );

        return new DeleteUserProfileResult($command->id);
    }
}
