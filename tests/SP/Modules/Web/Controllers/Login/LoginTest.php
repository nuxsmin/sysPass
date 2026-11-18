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

namespace SP\Tests\Modules\Web\Controllers\Login;

use Klein\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Auth\Dtos\LoginResponseDto;
use SP\Domain\Auth\Ports\LoginService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\Login as RealLoginService;
use SP\Domain\Auth\Services\LoginStatus;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the Login web area.
 */
#[Group('integration')]
final class LoginTest extends IntegrationTestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[BodyChecker('outputCheckerIndex')]
    public function index(): void
    {
        $this->runRoute('get', 'login/index');
    }

    #[Test]
    #[BodyChecker('outputCheckerLogin')]
    public function login(): void
    {
        $loginService = $this->createStub(LoginService::class);
        $loginService->method('doLogin')
                     ->willReturn(new LoginResponseDto(LoginStatus::OK, 'index.php'));

        $this->runRoute(
            'post',
            'login/login',
            [],
            ['user' => 'admin', 'pass' => 'secret'],
            [LoginService::class => $loginService]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerWrongLogin')]
    public function wrongLogin(): void
    {
        $loginService = $this->createStub(LoginService::class);
        $loginService->method('doLogin')
                     ->willThrowException(
                         AuthException::info('Wrong login', __FUNCTION__, LoginStatus::INVALID_LOGIN->value)
                     );

        $this->runRoute(
            'post',
            'login/login',
            [],
            ['user' => 'admin', 'pass' => 'wrong'],
            [LoginService::class => $loginService]
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[BodyChecker('outputCheckerLogout')]
    public function logout(): void
    {
        $this->runRoute('get', 'login/logout');
    }

    #[Test]
    public function blankCredentialsReturnWrongLoginAndRecordTrack(): void
    {
        $trackService = $this->createMock(TrackService::class);
        $trackService->method('buildTrackRequest')
                     ->willReturnCallback(static fn(string $src) => new TrackRequest(time(), $src, '127.0.0.1'));
        $trackService->expects(self::once())
                     ->method('add')
                     ->with(self::callback(static fn(TrackRequest $req) => $req->getSource() === RealLoginService::class))
                     ->willReturn(1);

        $result = $this->dispatchRoute(
            'post',
            'login/login',
            [],
            ['user' => '', 'pass' => ''],
            [TrackService::class => $trackService]
        );

        self::assertSame(200, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong login', $json['description']);
    }

    #[Test]
    public function unknownUserReturnsWrongLoginAndRecordsTrack(): void
    {
        $trackService = $this->createMock(TrackService::class);
        $trackService->method('buildTrackRequest')
                     ->willReturnCallback(static fn(string $src) => new TrackRequest(time(), $src, '127.0.0.1'));
        $trackService->expects(self::once())->method('checkTracking')->willReturn(false);
        $trackService->expects(self::once())->method('add')->willReturn(1);

        $userService = $this->createStub(UserService::class);
        $userService->method('getByLogin')
                    ->willThrowException(NoSuchItemException::error('User not found'));

        $result = $this->dispatchRoute(
            'post',
            'login/login',
            [],
            ['user' => 'nonexistent_user', 'pass' => 'synthetic-pass'],
            [
                TrackService::class => $trackService,
                UserService::class => $userService,
            ]
        );

        self::assertSame(200, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong login', $json['description']);
    }

    #[Test]
    public function wrongPasswordReturnsWrongLoginAndRecordsTrack(): void
    {
        $trackService = $this->createMock(TrackService::class);
        $trackService->method('buildTrackRequest')
                     ->willReturnCallback(static fn(string $src) => new TrackRequest(time(), $src, '127.0.0.1'));
        $trackService->expects(self::once())->method('checkTracking')->willReturn(false);
        $trackService->expects(self::once())->method('add')->willReturn(1);

        $user = UserDataGenerator::factory()->buildUserData()->mutate([
            'id' => 42,
            'login' => 'synthetic_user',
            'pass' => Hash::hashKey('correct-synthetic-pass'),
            'isMigrate' => false,
            'isDisabled' => false,
            'isChangePass' => false,
        ]);

        $userService = $this->createStub(UserService::class);
        $userService->method('getByLogin')->with('synthetic_user')->willReturn($user);

        $events = [];
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('notify')
                   ->willReturnCallback(static function (string $name) use (&$events): void {
                       $events[] = $name;
                   });

        $result = $this->dispatchRoute(
            'post',
            'login/login',
            [],
            ['user' => 'synthetic_user', 'pass' => 'wrong-synthetic-pass'],
            [
                TrackService::class => $trackService,
                UserService::class => $userService,
                EventDispatcherInterface::class => $dispatcher,
            ]
        );

        self::assertSame(200, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong login', $json['description']);
        self::assertContains('login.auth.database', $events);
    }

    #[Test]
    public function disabledUserReturnsUserDisabledRecordsTrackAndDoesNotCompleteSession(): void
    {
        $trackService = $this->createMock(TrackService::class);
        $trackService->method('buildTrackRequest')
                     ->willReturnCallback(static fn(string $src) => new TrackRequest(time(), $src, '127.0.0.1'));
        $trackService->expects(self::once())->method('checkTracking')->willReturn(false);
        $trackService->expects(self::once())->method('add')->willReturn(1);

        $user = UserDataGenerator::factory()->buildUserData()->mutate([
            'id' => 43,
            'login' => 'disabled_user',
            'pass' => Hash::hashKey('valid-pass'),
            'isMigrate' => false,
            'isDisabled' => true,
            'isChangePass' => false,
        ]);

        $userService = $this->createStub(UserService::class);
        $userService->method('getByLogin')->with('disabled_user')->willReturn($user);

        $context = $this->createMock(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getUserData')->willReturn(new \SP\Domain\User\Dtos\UserDto());
        $context->expects(self::never())->method('setAuthCompleted');
        $context->expects(self::never())->method('setUserData');

        $events = [];
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('notify')
                   ->willReturnCallback(static function (string $name) use (&$events): void {
                       $events[] = $name;
                   });

        $result = $this->dispatchRoute(
            'post',
            'login/login',
            [],
            ['user' => 'disabled_user', 'pass' => 'valid-pass'],
            [
                TrackService::class => $trackService,
                UserService::class => $userService,
                Context::class => $context,
                EventDispatcherInterface::class => $dispatcher,
            ]
        );

        self::assertSame(200, $result['code'], $result['body']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('User disabled', $json['description']);
        self::assertContains('login.checkUser.disabled', $events);
    }

    #[Test]
    public function changePassUserCreatesRecoverHashAndRedirectsWithoutCompletingSession(): void
    {
        $user = UserDataGenerator::factory()->buildUserData()->mutate([
            'id' => 44,
            'login' => 'changepass_user',
            'pass' => Hash::hashKey('valid-pass'),
            'isMigrate' => false,
            'isDisabled' => false,
            'isChangePass' => true,
        ]);

        $userService = $this->createStub(UserService::class);
        $userService->method('getByLogin')->with('changepass_user')->willReturn($user);

        $recover = $this->createMock(UserPassRecoverService::class);
        $recover->expects(self::once())
                ->method('add')
                ->with(44, self::matchesRegularExpression('/^[0-9a-f]{32}$/'));

        $context = $this->createMock(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getUserData')->willReturn(new \SP\Domain\User\Dtos\UserDto());
        $context->expects(self::never())->method('setAuthCompleted');
        $context->expects(self::never())->method('setUserData');

        $result = $this->dispatchRoute(
            'post',
            'login/login',
            [],
            ['user' => 'changepass_user', 'pass' => 'valid-pass'],
            [
                UserService::class => $userService,
                UserPassRecoverService::class => $recover,
                Context::class => $context,
            ]
        );

        self::assertSame(200, $result['code'], $result['body']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status']);
        self::assertMatchesRegularExpression('#^index\.php\?r=userPassReset%2Freset%2F[0-9a-f]{32}$#', $json['data']['url']);
    }

    #[Test]
    public function incompleteSessionIsRejectedOnProtectedWebRoutes(): void
    {
        $incompleteContext = self::createStub(SessionContext::class);
        $incompleteContext->method('isLoggedIn')->willReturn(true);
        $incompleteContext->method('getAuthCompleted')->willReturn(false);
        $incompleteContext->method('getUserData')->willReturn($this->getUserDataDto());
        $incompleteContext->method('getUserProfile')->willReturn($this->getUserProfile());

        $acl = $this->createMock(AclInterface::class);
        $acl->expects(self::never())->method('checkUserAccess');

        foreach (['account/search', 'publicLink/search'] as $route) {
            $result = $this->dispatchRoute(
                'get',
                $route,
                [],
                [],
                [
                    Context::class => $incompleteContext,
                    AclInterface::class => $acl,
                ],
                ['HTTP_ACCEPT' => 'application/json']
            );

            // Characterization: ControllerBase::checkLoggedIn() throws SessionTimeout without calling
            // sessionLogout(), and Bootstrap::manageWebRequest() catches SessionTimeout and emits empty 200.
            self::assertSame(200, $result['code'], $route);
            self::assertSame('', $result['body'], $route);
        }

        // Contrast with SimpleControllerBase::checks(), which calls sessionLogout() (JSON status 10)
        // and throws SessionTimeout while Bootstrap still calls $response->send(true) a second time.
        $statusResult = $this->dispatchRoute(
            'get',
            'status/checkRelease',
            [],
            [],
            [
                Context::class => $incompleteContext,
                AclInterface::class => $acl,
            ],
            ['HTTP_ACCEPT' => 'application/json']
        );
        self::assertSame(200, $statusResult['code']);
        $expectedChunk = json_encode([
            'status' => 10,
            'description' => 'Session not started or timed out',
            'data' => [],
            'messages' => [],
        ], JSON_THROW_ON_ERROR);
        self::assertSame($expectedChunk . $expectedChunk, $statusResult['echoed']);
    }

    #[Test]
    public function browserAuthMismatchReturns500WhileAbsentServerHeaderBypassesCheck(): void
    {
        $configData = self::createConfiguredStub(ConfigDataInterface::class, array_merge(
            $this->getConfigData(),
            [
                'isAuthBasicEnabled' => true,
                'getAuthBasicDomain' => '',
            ]
        ));
        $configFile = $this->createStub(ConfigFileService::class);
        $configFile->method('getConfigData')->willReturn($configData);

        $userDto = $this->getUserDataDto()->mutate([
            'login' => 'alice',
            'ssoLogin' => 'alice_sso',
            'isAdminApp' => true,
        ]);

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($userDto);
        $context->method('getUserProfile')->willReturn($this->getUserProfile());

        // Mismatched PHP_AUTH_USER throws AuthException('Invalid browser auth') -> HTTP 500 with trace
        $mismatchResult = $this->dispatchRoute(
            'get',
            'account/search',
            [],
            [],
            [
                ConfigFileService::class => $configFile,
                Context::class => $context,
            ],
            ['PHP_AUTH_USER' => 'bob']
        );

        self::assertSame(500, $mismatchResult['code']);
        $json = json_decode($mismatchResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Invalid browser auth', $json['description']);
        self::assertIsArray($json['data']);

        // Characterization: when PHP_AUTH_USER and REMOTE_USER are absent, checkServerAuthUser()
        // returns null (!== false), so the browser-auth check is bypassed even with isAuthBasicEnabled=true.
        $absentResult = $this->dispatchRoute(
            'get',
            'account/search',
            [],
            [],
            [
                ConfigFileService::class => $configFile,
                Context::class => $context,
            ]
        );

        self::assertSame(200, $absentResult['code']);
        $absentJson = json_decode($absentResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $absentJson['status']);
    }

    private function outputCheckerIndex(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//form[@id="frmLogin"]'));
    }

    private function outputCheckerLogin(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('OK', $json['status']);
        self::assertArrayHasKey('url', $json['data']);
    }

    private function outputCheckerWrongLogin(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong login', $json['description']);
    }

    private function outputCheckerLogout(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//div[@id="box-pub-noheader"]'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(
        string $method,
        string $route,
        array $paramsGet = [],
        array $paramsPost = [],
        array $definitions = []
    ): void {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route] + $paramsGet, $paramsPost),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @return array{body: string, echoed: string, code: int}
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function dispatchRoute(
        string $method,
        string $route,
        array $paramsGet = [],
        array $paramsPost = [],
        array $definitions = [],
        array $server = []
    ): array {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                $method,
                'index.php',
                ['r' => $route] + $paramsGet,
                $paramsPost,
                [],
                array_merge(['REMOTE_ADDR' => '127.0.0.1'], $server)
            ),
            $definitions
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $echoed = (string)ob_get_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $body = (string)$response->body();

        return [
            'body' => $body !== '' ? $body : $echoed,
            'echoed' => $echoed,
            'code' => (int)$response->code(),
        ];
    }
}
