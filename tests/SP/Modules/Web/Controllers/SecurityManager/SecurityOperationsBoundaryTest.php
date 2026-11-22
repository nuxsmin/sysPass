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

namespace SP\Tests\Modules\Web\Controllers\SecurityManager;

use Error;
use Klein\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Security\Ports\EventlogService;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Tests\IntegrationTestCase;

use function DI\factory;

/**
 * Boundary & HTTP dispatch characterization tests for SecurityManager, Eventlog, and Track controllers:
 * - Anonymous & incomplete-session rejection across eventlog/*, track/*, securityManager/index
 * - Real Acl gating on eventlog/{index,search} and track/{search,unlock,clear}
 * - Eventlog\ClearController missing EVENTLOG_CLEAR ACL check (any logged-in user can clear)
 * - ClearEventlogUseCase & ClearTracksUseCase emitting audit events & OK responses even when clear() returns false
 * - SecurityManager\IndexController lacking direct SECURITY_MANAGE check and hiding EventLog tab when isLogEnabled=false
 * - Track\UnlockController catching only Exception so Error (e.g. QueryFactory::COMMON Update::limit()) returns HTTP 500
 */
#[Group('integration')]
final class SecurityOperationsBoundaryTest extends IntegrationTestCase
{
    private bool $sessionLoggedIn = true;
    private bool $sessionAuthCompleted = true;
    private ?UserDto $sessionUser = null;
    private ?ProfileData $sessionProfile = null;
    private array $configOverrides = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(1, false);
        $this->sessionProfile = (new ProfileData())->setEvl(true)->setMgmUsers(true);
        $this->configOverrides = [
            'isLogEnabled' => true,
            'getAccountCount' => 10,
        ];
    }

    public static function securityRoutesProvider(): array
    {
        return [
            'securityManager/index' => ['get', 'securityManager/index'],
            'eventlog/index' => ['get', 'eventlog/index'],
            'eventlog/search' => ['get', 'eventlog/search'],
            'eventlog/clear' => ['post', 'eventlog/clear'],
            'track/search' => ['get', 'track/search'],
            'track/unlock/1' => ['post', 'track/unlock/1'],
            'track/clear' => ['post', 'track/clear'],
        ];
    }

    #[Test]
    #[DataProvider('securityRoutesProvider')]
    public function anonymousAndIncompleteSessionsAreBlockedAcrossSecurityRoutes(string $method, string $route): void
    {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $eventlog = $this->createMock(EventlogService::class);
            $eventlog->expects(self::never())->method('search');
            $eventlog->expects(self::never())->method('clear');

            $track = $this->createMock(TrackService::class);
            $track->expects(self::never())->method('search');
            $track->expects(self::never())->method('unlock');
            $track->expects(self::never())->method('clear');

            $result = $this->dispatchWeb($method, $route, $method === 'post' ? ['sk' => 'csrf-token-test'] : [], [
                EventlogService::class => $eventlog,
                TrackService::class => $track,
            ]);

            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    public function unprivilegedUserIsDeniedEventlogIndexSearchAndTrackActionsUnderRealAcl(): void
    {
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = new ProfileData();

        $eventlog = $this->createMock(EventlogService::class);
        $eventlog->expects(self::never())->method('search');

        $track = $this->createMock(TrackService::class);
        $track->expects(self::never())->method('search');
        $track->expects(self::never())->method('unlock');
        $track->expects(self::never())->method('clear');

        $indexResult = $this->dispatchWeb('get', 'eventlog/index', [], [
            EventlogService::class => $eventlog,
            TrackService::class => $track,
        ]);
        self::assertStringContainsString('You don\'t have permission to do this operation', $indexResult['body']);

        foreach (
            [
                ['get', 'eventlog/search'],
                ['get', 'track/search'],
                ['post', 'track/unlock/1'],
                ['post', 'track/clear'],
            ] as [$method, $route]
        ) {
            $result = $this->dispatchWeb($method, $route, $method === 'post' ? ['sk' => 'csrf-token-test'] : [], [
                EventlogService::class => $eventlog,
                TrackService::class => $track,
            ]);

            $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('ERROR', $json['status'] ?? null, $route);
            self::assertSame('You don\'t have permission to do this operation', $json['description'] ?? null, $route);
        }
    }

    #[Test]
    public function eventlogClearLacksAclCheckAndPermitsBlankProfileUserAndEmptyNoOp(): void
    {
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = new ProfileData(); // isEvl=false, isAdminApp=false

        $eventlog = $this->createMock(EventlogService::class);
        $eventlog->expects(self::once())->method('clear')->willReturn(false);

        $emitted = [];
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('notify')->willReturnCallback(static function (string $eventName) use (&$emitted): void {
            $emitted[] = $eventName;
        });

        $result = $this->dispatchWeb('post', 'eventlog/clear', ['sk' => 'csrf-token-test'], [
            EventlogService::class => $eventlog,
            EventDispatcherInterface::class => $dispatcher,
        ]);

        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status'] ?? null);
        self::assertSame('Event log cleared', $json['description'] ?? null);
        self::assertContains('clear.eventlog', $emitted);
    }

    #[Test]
    public function trackClearWithEmptyTableStillEmitsAuditEventAndReturnsOk(): void
    {
        $this->sessionUser = $this->makeUserDto(21, false);
        $this->sessionProfile = (new ProfileData())->setMgmUsers(true);

        $track = $this->createMock(TrackService::class);
        $track->expects(self::once())->method('clear')->willReturn(false);

        $emitted = [];
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('notify')->willReturnCallback(static function (string $eventName) use (&$emitted): void {
            $emitted[] = $eventName;
        });

        $result = $this->dispatchWeb('post', 'track/clear', ['sk' => 'csrf-token-test'], [
            TrackService::class => $track,
            EventDispatcherInterface::class => $dispatcher,
        ]);

        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status'] ?? null);
        self::assertSame('Tracks cleared out', $json['description'] ?? null);
        self::assertContains('clear.track', $emitted);
    }

    #[Test]
    public function trackUnlockMapsNotFoundToErrorJsonAndLetsPhpErrorBubbleToHttp500(): void
    {
        $this->sessionUser = $this->makeUserDto(21, false);
        $this->sessionProfile = (new ProfileData())->setMgmUsers(true);

        $trackNotFound = $this->createMock(TrackService::class);
        $trackNotFound->expects(self::once())
            ->method('unlock')
            ->with(999)
            ->willThrowException(NoSuchItemException::info('Track not found'));

        $notFoundResult = $this->dispatchWeb('post', 'track/unlock/999', ['sk' => 'csrf-token-test'], [
            TrackService::class => $trackNotFound,
        ]);
        $notFoundJson = json_decode($notFoundResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $notFoundJson['status'] ?? null);
        self::assertSame('Track not found', $notFoundJson['description'] ?? null);

        $trackError = $this->createMock(TrackService::class);
        $trackError->expects(self::once())
            ->method('unlock')
            ->with(1)
            ->willThrowException(new Error('Call to undefined method Aura\SqlQuery\Common\Update::limit()'));

        $errorResult = $this->dispatchWeb('post', 'track/unlock/1', ['sk' => 'csrf-token-test'], [
            TrackService::class => $trackError,
        ]);
        self::assertSame(500, $errorResult['code']);
        $errorJson = json_decode($errorResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $errorJson['status'] ?? null);
        self::assertSame('Internal error', $errorJson['description'] ?? null);
    }

    #[Test]
    public function securityManagerIndexRespectsTabPermissionsAndLogEnabledFlag(): void
    {
        $eventlog = $this->createStub(EventlogService::class);
        $eventlog->method('search')->willReturn(new QueryResult());

        $track = $this->createStub(TrackService::class);
        $track->method('search')->willReturn(new QueryResult());

        // Blank profile: renders no security tabs, returns empty OK payload
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = new ProfileData();
        $blankResult = $this->dispatchWeb('get', 'securityManager/index', [], [
            EventlogService::class => $eventlog,
            TrackService::class => $track,
        ]);
        self::assertSame(200, $blankResult['code']);
        self::assertStringNotContainsString('tblEventLog', $blankResult['body']);
        self::assertStringNotContainsString('tblTracks', $blankResult['body']);

        // isEvl=true but isLogEnabled=false: hides EventLog tab
        $this->sessionProfile = (new ProfileData())->setEvl(true);
        $this->configOverrides['isLogEnabled'] = false;
        $disabledLogResult = $this->dispatchWeb('get', 'securityManager/index', [], [
            EventlogService::class => $eventlog,
            TrackService::class => $track,
        ]);
        self::assertStringNotContainsString('tblEventLog', $disabledLogResult['body']);
    }

    protected function getConfigData(): array
    {
        return array_merge(parent::getConfigData(), $this->configOverrides);
    }

    private function makeUserDto(int $id, bool $isAdminApp): UserDto
    {
        return new UserDto(
            id: $id,
            login: 'user_' . $id,
            userGroupId: 1,
            userGroupName: 'Admins',
            userProfileId: 1,
            isAdminApp: $isAdminApp,
            lastUpdateMPass: 1000
        );
    }

    /**
     * @param array<string, mixed> $post
     * @param array<class-string, mixed> $overrides
     * @return array{body: string, code: int}
     */
    private function dispatchWeb(string $method, string $route, array $post = [], array $overrides = []): array
    {
        $request = IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route], $post);

        $user = $this->sessionUser ?? $this->makeUserDto(1, false);
        $profile = $this->sessionProfile ?? new ProfileData();
        $loggedIn = $this->sessionLoggedIn;
        $authCompleted = $this->sessionAuthCompleted;

        $sessionStub = $this->createStub(SessionContext::class);
        $sessionStub->method('isLoggedIn')->willReturn($loggedIn);
        $sessionStub->method('getAuthCompleted')->willReturn($authCompleted);
        $sessionStub->method('getUserData')->willReturn($user);
        $sessionStub->method('getUserProfile')->willReturn($profile);
        $sessionStub->method('getLocale')->willReturn('en_US');
        $sessionStub->method('getCSRF')->willReturn(null);

        $baseOverrides = [
            Context::class => $sessionStub,
            SessionContext::class => $sessionStub,
            AclInterface::class => factory(
                static fn(ContainerInterface $c): Acl => new Acl(
                    $c->get(Context::class),
                    $c->get(EventDispatcherInterface::class),
                    $c->get(ActionsInterface::class)
                )
            ),
            'paths' => \DI\decorate(static function (array $paths): array {
                $paths[] = [
                    \SP\Core\Bootstrap\Path::VIEW,
                    \SP\Infrastructure\File\FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'web', 'themes'),
                ];

                return $paths;
            }),
        ];

        $container = $this->buildContainer($request, array_merge($baseOverrides, $overrides));

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
            $output = (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        /** @var Response $response */
        $response = $container->get(Response::class);

        return [
            'body' => $output,
            'code' => $response->status()->getCode(),
        ];
    }
}
