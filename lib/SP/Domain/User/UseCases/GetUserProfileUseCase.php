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
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\User\Models\UserProfile;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\UseCases\Dtos\GetUserProfileCommand;
use SP\Domain\User\UseCases\Dtos\GetUserProfileResult;

/**
 * Get UserProfile use case.
 *
 * @implements UseCase<GetUserProfileCommand, GetUserProfileResult>
 */
final readonly class GetUserProfileUseCase implements UseCase
{
    /**
     * @param UserProfileService $userProfileService
     */
    public function __construct(
        private UserProfileService $userProfileService
    ) {
    }

    public function execute(Command $command): GetUserProfileResult
    {
        if (!$command instanceof GetUserProfileCommand) {
            throw new LogicException(sprintf('Expected command %s', GetUserProfileCommand::class));
        }

        return new GetUserProfileResult($this->userProfileService->getById($command->id));
    }
}
