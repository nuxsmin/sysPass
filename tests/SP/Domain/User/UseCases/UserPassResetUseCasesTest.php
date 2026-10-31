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

namespace SP\Tests\Domain\User\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Notification\Dtos\MailMessage;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\UseCases\Dtos\RequestPasswordResetCommand;
use SP\Domain\User\UseCases\Dtos\ResetPasswordCommand;
use SP\Domain\User\UseCases\RequestPasswordResetUseCase;
use SP\Domain\User\UseCases\ResetPasswordUseCase;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the password recovery use cases.
 */
#[Group('unitary')]
final class UserPassResetUseCasesTest extends TestCase
{
    private UserService&MockObject $userService;
    private UserPassRecoverService&MockObject $recoverService;
    private MailService&MockObject $mailService;
    private InMemoryEventEmitter $eventEmitter;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = $this->createMock(UserService::class);
        $this->recoverService = $this->createMock(UserPassRecoverService::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
        $this->user = UserDataGenerator::factory()
                                       ->buildUserData()
                                       ->mutate(
                                           [
                                               'email' => 'user@example.com',
                                               'isDisabled' => false,
                                               'isLdap' => false,
                                           ]
                                       );
    }

    #[Test]
    public function requestSendsTheMailAndEmitsTheEvent(): void
    {
        $this->userService->method('getByLogin')->willReturn($this->user);
        $this->recoverService->method('requestForUserId')->willReturn('hash');
        $this->recoverService->method('getMailMessage')->willReturn(new MailMessage());

        $this->mailService->expects($this->once())->method('send');

        $result = $this->useCase()->execute(
            new RequestPasswordResetCommand($this->user->getLogin(), 'user@example.com', 'https://localhost')
        );

        self::assertSame('hash', $result->hash);

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('request.user.passReset', $event['name']);
        self::assertSame(
            sprintf("Password Recovery\nRequested for: %s (user@example.com)", $this->user->getLogin()),
            (new EventMessageFormatter())->composeText($event['message'])
        );
    }

    #[Test]
    public function requestRejectsAWrongEmail(): void
    {
        $this->userService->method('getByLogin')->willReturn($this->user);

        $this->expectException(SPException::class);

        $this->useCase()->execute(
            new RequestPasswordResetCommand($this->user->getLogin(), 'other@example.com', 'https://localhost')
        );
    }

    #[Test]
    public function requestRejectsADisabledUser(): void
    {
        $this->userService->method('getByLogin')->willReturn(
            $this->user->mutate(['isDisabled' => true])
        );

        $this->expectException(SPException::class);

        $this->useCase()->execute(
            new RequestPasswordResetCommand($this->user->getLogin(), 'user@example.com', 'https://localhost')
        );
    }

    #[Test]
    public function resetUpdatesThePasswordAndEmitsTheEvent(): void
    {
        $this->recoverService->method('getUserIdForHash')->willReturn($this->user->getId());
        $this->userService->method('getById')->willReturn($this->user);

        $this->userService->expects($this->once())
                          ->method('updatePass')
                          ->with($this->user->getId(), 'new-password');
        $this->recoverService->expects($this->once())
                             ->method('toggleUsedByHash')
                             ->with('hash');

        $result = (new ResetPasswordUseCase(
            $this->userService,
            $this->recoverService,
            $this->eventEmitter
        ))->execute(new ResetPasswordCommand('hash', 'new-password', 'new-password'));

        self::assertSame($this->user->getId(), $result->userId);

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('edit.user.password', $event['name']);
    }

    #[Test]
    public function resetRejectsABlankPassword(): void
    {
        $this->expectException(ValidationException::class);

        (new ResetPasswordUseCase($this->userService, $this->recoverService, $this->eventEmitter))
            ->execute(new ResetPasswordCommand('hash', '', ''));
    }

    #[Test]
    public function resetRejectsDifferentPasswords(): void
    {
        $this->expectException(ValidationException::class);

        (new ResetPasswordUseCase($this->userService, $this->recoverService, $this->eventEmitter))
            ->execute(new ResetPasswordCommand('hash', 'one', 'two'));
    }

    private function useCase(): RequestPasswordResetUseCase
    {
        return new RequestPasswordResetUseCase(
            $this->userService,
            $this->recoverService,
            $this->mailService,
            $this->eventEmitter
        );
    }
}
