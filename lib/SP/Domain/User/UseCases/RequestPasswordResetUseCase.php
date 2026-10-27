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
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\UseCases\Dtos\RequestPasswordResetCommand;
use SP\Domain\User\UseCases\Dtos\RequestPasswordResetResult;

/**
 * Request a password reset use case.
 *
 * @implements UseCase<RequestPasswordResetCommand, RequestPasswordResetResult>
 */
final readonly class RequestPasswordResetUseCase implements UseCase
{
    public function __construct(
        private UserService $userService,
        private UserPassRecoverService $userPassRecoverService,
        private MailService $mailService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): RequestPasswordResetResult
    {
        if (!$command instanceof RequestPasswordResetCommand) {
            throw new LogicException(sprintf('Expected command %s', RequestPasswordResetCommand::class));
        }

        $userData = $this->userService->getByLogin($command->login);

        if ($userData->getEmail() !== $command->email) {
            throw new SPException('Wrong data', SPException::WARNING);
        }

        if ($userData->isDisabled() || $userData->isLdap()) {
            throw new SPException(
                'Unable to reset the password',
                SPException::WARNING,
                'Please contact to the administrator'
            );
        }

        $hash = $this->userPassRecoverService->requestForUserId($userData->getId());

        $this->eventEmitter->emit(
            'request.user.passReset',
            $this,
            EventMessage::build()
                ->addDescription('Password Recovery')
                ->addDetail('Requested for', sprintf('%s (%s)', $command->login, $command->email))
        );

        $this->mailService->send(
            'Password Change',
            $command->email,
            $this->userPassRecoverService->getMailMessage($hash, $command->webUri)
        );

        return new RequestPasswordResetResult($hash);
    }
}
