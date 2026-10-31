<?php
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

declare(strict_types=1);

namespace SP\Modules\Web\Controllers\UserPassReset;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Notification\Dtos\MailMessage;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the password reset area.
 */
#[Group('integration')]
final class UserPassResetTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('checkIndex')]
    public function index(): void
    {
        $this->runRoute('get', 'userPassReset/index');
    }

    #[Test]
    #[BodyChecker('checkReset')]
    public function reset(): void
    {
        $this->runRoute('get', 'userPassReset/reset/abc123');
    }

    #[Test]
    #[BodyChecker('checkRequested')]
    public function saveRequest(): void
    {
        $user = $this->buildUser();

        $this->runRoute(
            'post',
            'userPassReset/saveRequest',
            $this->definitions($user),
            [],
            ['login' => $user->getLogin(), 'email' => $user->getEmail()]
        );
    }

    #[Test]
    #[BodyChecker('checkPasswordUpdated')]
    public function saveReset(): void
    {
        $user = $this->buildUser();

        $this->runRoute(
            'post',
            'userPassReset/saveReset',
            $this->definitions($user),
            [],
            [
                'hash' => 'abc123',
                'password' => 'new-password',
                'password_repeat' => 'new-password',
            ]
        );
    }

    #[Test]
    #[BodyChecker('checkPasswordMismatch')]
    public function saveResetWithDifferentPasswords(): void
    {
        $this->runRoute(
            'post',
            'userPassReset/saveReset',
            $this->definitions($this->buildUser()),
            [],
            [
                'hash' => 'abc123',
                'password' => 'new-password',
                'password_repeat' => 'other-password',
            ]
        );
    }

    public function checkIndex(string $output): void
    {
        self::assertStringContainsString('frmPassReset', $output);
    }

    public function checkReset(string $output): void
    {
        self::assertStringContainsString('frmUserPassReset', $output);
    }

    public function checkRequested(string $output): void
    {
        self::assertSame('Request sent', $this->decode($output)['description']);
    }

    public function checkPasswordUpdated(string $output): void
    {
        self::assertSame('Password updated', $this->decode($output)['description']);
    }

    public function checkPasswordMismatch(string $output): void
    {
        self::assertSame('Passwords do not match', $this->decode($output)['description']);
    }

    private function buildUser(): User
    {
        return UserDataGenerator::factory()
                                ->buildUserData()
                                ->mutate(
                                    [
                                        'email' => 'user@example.com',
                                        'isDisabled' => false,
                                        'isLdap' => false,
                                    ]
                                );
    }

    private function definitions(User $user): array
    {
        $track = $this->createStub(TrackService::class);
        $track->method('buildTrackRequest')->willReturn(new TrackRequest(time(), 'test', '127.0.0.1'));
        $track->method('checkTracking')->willReturn(false);

        $userService = $this->createStub(UserService::class);
        $userService->method('getByLogin')->willReturn($user);
        $userService->method('getById')->willReturn($user);

        $recover = $this->createStub(UserPassRecoverService::class);
        $recover->method('requestForUserId')->willReturn('hash');
        $recover->method('getUserIdForHash')->willReturn($user->getId());
        $recover->method('getMailMessage')->willReturn(new MailMessage());

        return [
            TrackService::class => $track,
            UserService::class => $userService,
            UserPassRecoverService::class => $recover,
            MailService::class => $this->createStub(MailService::class),
        ];
    }

    private function decode(string $output): array
    {
        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            self::fail('Invalid JSON: '.substr($output, 0, 400));
        }

        return $decoded;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(
        string $method,
        string $route,
        array $definitions = [],
        array $paramsGet = [],
        array $paramsPost = []
    ): void {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                $method,
                'index.php',
                array_merge(['r' => $route], $paramsGet),
                $paramsPost
            ),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }
}
