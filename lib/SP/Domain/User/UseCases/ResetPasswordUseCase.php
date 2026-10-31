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
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\UseCases\Dtos\ResetPasswordCommand;
use SP\Domain\User\UseCases\Dtos\ResetPasswordResult;

/**
 * Reset a user's password use case.
 *
 * @implements UseCase<ResetPasswordCommand, ResetPasswordResult>
 */
final readonly class ResetPasswordUseCase implements UseCase
{
    public function __construct(
        private UserService $userService,
        private UserPassRecoverService $userPassRecoverService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): ResetPasswordResult
    {
        if (!$command instanceof ResetPasswordCommand) {
            throw new LogicException(sprintf('Expected command %s', ResetPasswordCommand::class));
        }

        if (!$command->password || !$command->passwordRepeat) {
            throw new ValidationException('Password cannot be blank');
        }

        if ($command->password !== $command->passwordRepeat) {
            throw new ValidationException('Passwords do not match');
        }

        $userId = $this->userPassRecoverService->getUserIdForHash($command->hash);

        $this->userPassRecoverService->toggleUsedByHash($command->hash);

        $this->userService->updatePass($userId, $command->password);

        $user = $this->userService->getById($userId);

        $this->eventEmitter->emit(
            'edit.user.password',
            $this,
            EventMessage::build()
                ->addDescription('Password updated')
                ->addDetail('User', $user->getLogin())
                ->setExtra('userId', [$userId])
                ->setExtra('email', [$user->getEmail()])
        );

        return new ResetPasswordResult($userId);
    }
}
