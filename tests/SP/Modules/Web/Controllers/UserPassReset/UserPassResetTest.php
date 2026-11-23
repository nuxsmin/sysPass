<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Core\Bootstrap\UriContextInterface;
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
    private bool $applicationUrlConfigured = true;
    private bool $mailEnabled = true;

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
    #[BodyChecker('checkRequested')]
    public function saveRequestIgnoresSpoofedHostHeaders(): void
    {
        $user = $this->buildUser();

        $recover = $this->createMock(UserPassRecoverService::class);
        $recover->method('requestForUserId')->willReturn('hash');
        $recover->expects($this->once())
                ->method('getMailMessage')
                ->with('hash', 'https://syspass.example.com')
                ->willReturn(new MailMessage());

        $definitions = $this->definitions($user, $recover);

        $uriContext = $this->createStub(UriContextInterface::class);
        $uriContext->method('getWebUri')->willReturn('https://evil.example');
        $definitions[UriContextInterface::class] = $uriContext;

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'userPassReset/saveRequest'],
                ['login' => $user->getLogin(), 'email' => $user->getEmail()],
                [],
                [
                    'HTTP_FORWARDED' => 'proto=https;host=evil.example',
                    'HTTP_X_FORWARDED_HOST' => 'evil.example',
                    'HTTP_X_FORWARDED_PROTO' => 'https',
                ]
            ),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    #[BodyChecker('checkConfigurationError')]
    public function saveRequestRefusesWhenApplicationUrlIsMissing(): void
    {
        $this->applicationUrlConfigured = false;
        $user = $this->buildUser();

        $mailService = $this->createMock(MailService::class);
        $mailService->expects($this->never())->method('send');

        $definitions = $this->definitions($user);
        $definitions[MailService::class] = $mailService;

        $this->runRoute(
            'post',
            'userPassReset/saveRequest',
            $definitions,
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

    #[Test]
    public function disabledMailShowsUnavailableOnGetPagesButSaveRequestStillAttemptsSend(): void
    {
        $this->mailEnabled = false;
        $user = $this->buildUser();

        $indexResult = $this->dispatchRoute('get', 'userPassReset/index');
        self::assertSame(200, $indexResult['code']);
        self::assertStringContainsString('Option unavailable', $indexResult['body']);

        $resetResult = $this->dispatchRoute('get', 'userPassReset/reset/abc123');
        self::assertSame(200, $resetResult['code']);
        self::assertStringContainsString('Option unavailable', $resetResult['body']);

        // Characterization: SaveRequestController and RequestPasswordResetUseCase do not check isMailEnabled().
        $mailService = $this->createMock(MailService::class);
        $mailService->expects(self::once())->method('send');

        $definitions = $this->definitions($user);
        $definitions[MailService::class] = $mailService;

        $postResult = $this->dispatchRoute(
            'post',
            'userPassReset/saveRequest',
            $definitions,
            [],
            ['login' => $user->getLogin(), 'email' => $user->getEmail()]
        );

        self::assertSame(200, $postResult['code']);
        $json = $this->decode($postResult['body']);
        self::assertSame('OK', $json['status']);
        self::assertSame('Request sent', $json['description']);
    }

    #[Test]
    public function saveRequestExposesIdentityDependentErrorMessagesAndRecordsTrack(): void
    {
        $mailService = $this->createMock(MailService::class);
        $mailService->expects(self::never())->method('send');

        // 1. Unknown user
        $trackUnknown = $this->createMock(TrackService::class);
        $trackUnknown->method('buildTrackRequest')->willReturn(new TrackRequest(time(), 'saveRequest', '127.0.0.1'));
        $trackUnknown->method('checkTracking')->willReturn(false);
        $trackUnknown->expects(self::once())->method('add')->willReturn(1);

        $userUnknown = $this->createStub(UserService::class);
        $userUnknown->method('getByLogin')
                    ->willThrowException(new \SP\Domain\Core\Exceptions\NoSuchItemException('User not found'));

        $resUnknown = $this->dispatchRoute(
            'post',
            'userPassReset/saveRequest',
            [
                TrackService::class => $trackUnknown,
                UserService::class => $userUnknown,
                MailService::class => $mailService,
            ],
            [],
            ['login' => 'missing_user', 'email' => 'missing@example.com']
        );
        self::assertSame(200, $resUnknown['code']);
        $jsonUnknown = $this->decode($resUnknown['body']);
        self::assertSame('ERROR', $jsonUnknown['status']);
        self::assertSame('User not found', $jsonUnknown['description']);

        // 2. Email mismatch
        $user = $this->buildUser();
        $defsMismatch = $this->definitions($user);
        $defsMismatch[MailService::class] = $mailService;

        $resMismatch = $this->dispatchRoute(
            'post',
            'userPassReset/saveRequest',
            $defsMismatch,
            [],
            ['login' => $user->getLogin(), 'email' => 'wrong-email@example.com']
        );
        self::assertSame(200, $resMismatch['code']);
        $jsonMismatch = $this->decode($resMismatch['body']);
        self::assertSame('ERROR', $jsonMismatch['status']);
        self::assertSame('Wrong data', $jsonMismatch['description']);

        // 3. Disabled and LDAP users
        foreach ([['isDisabled' => true, 'isLdap' => false], ['isDisabled' => false, 'isLdap' => true]] as $flags) {
            $restrictedUser = $this->buildUser()->mutate($flags);
            $defsRestricted = $this->definitions($restrictedUser);
            $defsRestricted[MailService::class] = $mailService;

            $resRestricted = $this->dispatchRoute(
                'post',
                'userPassReset/saveRequest',
                $defsRestricted,
                [],
                ['login' => $restrictedUser->getLogin(), 'email' => $restrictedUser->getEmail()]
            );
            self::assertSame(200, $resRestricted['code']);
            $jsonRestricted = $this->decode($resRestricted['body']);
            self::assertSame('ERROR', $jsonRestricted['status']);
            self::assertSame('Unable to reset the password', $jsonRestricted['description']);
            self::assertSame('Please contact to the administrator', $jsonRestricted['data']);
        }
    }

    #[Test]
    public function saveRequestEmitsSuccessEventBeforeMailSendAndLeavesTokenOnMailFailure(): void
    {
        $user = $this->buildUser();

        $recover = $this->createMock(UserPassRecoverService::class);
        $recover->expects(self::once())->method('requestForUserId')->with($user->getId())->willReturn('hash123');
        $recover->expects(self::once())->method('getMailMessage')->willReturn(new MailMessage());

        $mailService = $this->createMock(MailService::class);
        $mailService->expects(self::once())
                    ->method('send')
                    ->willThrowException(new \SP\Domain\Common\Services\ServiceException('SMTP transport down'));

        $events = [];
        $dispatcher = $this->createStub(\SP\Domain\Core\Events\EventDispatcherInterface::class);
        $dispatcher->method('notify')
                   ->willReturnCallback(static function (string $name) use (&$events): void {
                       $events[] = $name;
                   });

        $definitions = $this->definitions($user, $recover);
        $definitions[MailService::class] = $mailService;
        $definitions[\SP\Domain\Core\Events\EventDispatcherInterface::class] = $dispatcher;

        $result = $this->dispatchRoute(
            'post',
            'userPassReset/saveRequest',
            $definitions,
            [],
            ['login' => $user->getLogin(), 'email' => $user->getEmail()]
        );

        self::assertSame(200, $result['code']);
        $json = $this->decode($result['body']);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('SMTP transport down', $json['description']);
        // Characterization: request.user.passReset emitted BEFORE mailService->send() failed
        self::assertContains('request.user.passReset', $events);
        self::assertContains('exception', $events);
    }

    #[Test]
    public function saveResetMarksTokenUsedBeforeUpdatePassFailsAndSkipsPasswordEvent(): void
    {
        $user = $this->buildUser();

        $recover = $this->createMock(UserPassRecoverService::class);
        $recover->expects(self::once())->method('getUserIdForHash')->with('validhash')->willReturn($user->getId());
        $recover->expects(self::once())->method('toggleUsedByHash')->with('validhash');

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::once())
                    ->method('updatePass')
                    ->willThrowException(new \SP\Domain\Common\Services\ServiceException('Error while updating the password'));

        $events = [];
        $dispatcher = $this->createStub(\SP\Domain\Core\Events\EventDispatcherInterface::class);
        $dispatcher->method('notify')
                   ->willReturnCallback(static function (string $name) use (&$events): void {
                       $events[] = $name;
                   });

        $definitions = $this->definitions($user, $recover);
        $definitions[UserService::class] = $userService;
        $definitions[\SP\Domain\Core\Events\EventDispatcherInterface::class] = $dispatcher;

        $result = $this->dispatchRoute(
            'post',
            'userPassReset/saveReset',
            $definitions,
            [],
            [
                'hash' => 'validhash',
                'password' => 'new-password',
                'password_repeat' => 'new-password',
            ]
        );

        self::assertSame(200, $result['code']);
        $json = $this->decode($result['body']);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Error while updating the password', $json['description']);
        self::assertNotContains('edit.user.password', $events);
        self::assertContains('exception', $events);
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

    public function checkConfigurationError(string $output): void
    {
        $decoded = $this->decode($output);

        self::assertSame('ERROR', $decoded['status']);
        self::assertSame('Password reset is not available', $decoded['description']);
        self::assertSame(
            'The application URL is not configured. Please contact the administrator.',
            $decoded['data']
        );
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

    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isMailEnabled'] = $this->mailEnabled;

        if ($this->applicationUrlConfigured) {
            $configData['getApplicationUrl'] = 'https://syspass.example.com';
        }

        return $configData;
    }

    private function definitions(User $user, ?UserPassRecoverService $recover = null): array
    {
        $track = $this->createStub(TrackService::class);
        $track->method('buildTrackRequest')->willReturn(new TrackRequest(time(), 'test', '127.0.0.1'));
        $track->method('checkTracking')->willReturn(false);

        $userService = $this->createStub(UserService::class);
        $userService->method('getByLogin')->willReturn($user);
        $userService->method('getById')->willReturn($user);

        if ($recover === null) {
            $recover = $this->createStub(UserPassRecoverService::class);
            $recover->method('requestForUserId')->willReturn('hash');
            $recover->method('getUserIdForHash')->willReturn($user->getId());
            $recover->method('getMailMessage')->willReturn(new MailMessage());
        }

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

    /**
     * @return array{body: string, code: int}
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function dispatchRoute(
        string $method,
        string $route,
        array $definitions = [],
        array $paramsGet = [],
        array $paramsPost = []
    ): array {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                $method,
                'index.php',
                array_merge(['r' => $route], $paramsGet),
                $paramsPost,
                [],
                ['REMOTE_ADDR' => '127.0.0.1']
            ),
            $definitions
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $echoed = (string)ob_get_clean();
        }

        /** @var \Klein\Response $response */
        $response = $container->get(\Klein\Response::class);
        $body = (string)$response->body();

        return [
            'body' => $body !== '' ? $body : $echoed,
            'code' => (int)$response->code(),
        ];
    }
}
