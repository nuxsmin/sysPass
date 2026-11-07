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
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\UseCases\Dtos\GetUserCommand;
use SP\Domain\User\UseCases\Dtos\GetUserResult;

/**
 * Returns a User.
 *
 * @implements UseCase<GetUserCommand, GetUserResult>
 */
final readonly class GetUserUseCase implements UseCase
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function execute(Command $command): GetUserResult
    {
        if (!$command instanceof GetUserCommand) {
            throw new LogicException(sprintf('Expected command %s', GetUserCommand::class));
        }

        return new GetUserResult($this->userService->getById($command->id));
    }
}
