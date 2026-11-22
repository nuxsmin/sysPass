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

namespace SP\Tests\Modules\Web\Controllers\Plugin;

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
use SP\Domain\Plugin\Models\Plugin;
use SP\Domain\Plugin\Ports\PluginDataService;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Tests\IntegrationTestCase;

use function DI\factory;

/**
 * Boundary & HTTP dispatch characterization tests for Plugin Web controllers:
 * - Anonymous & incomplete-session rejection across plugin/{index,search,view,enable,disable,reset,delete}
 * - Real Acl gating on plugin/{index,search,view,delete} (requires configGeneral)
 * - Missing Acl checks on plugin/{enable,disable,reset} (any logged-in user with blank ProfileData succeeds)
 * - ResetPluginUseCase clears PluginDataService only and never calls PluginManagerService::resetById()
 * - Batch delete with empty items vs mixed items forwarded verbatim to DeletePluginsUseCase
 */
#[Group('integration')]
final class PluginOperationsBoundaryTest extends IntegrationTestCase
{
    private bool $sessionLoggedIn = true;
    private bool $sessionAuthCompleted = true;
    private ?UserDto $sessionUser = null;
    private ?ProfileData $sessionProfile = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(1, false);
        $this->sessionProfile = (new ProfileData())->setConfigGeneral(true);
    }

    public static function pluginRoutesProvider(): array
    {
        return [
            'plugin/index' => ['get', 'plugin/index'],
            'plugin/search' => ['get', 'plugin/search'],
            'plugin/view/1' => ['get', 'plugin/view/1'],
            'plugin/enable/1' => ['post', 'plugin/enable/1'],
            'plugin/disable/1' => ['post', 'plugin/disable/1'],
            'plugin/reset/1' => ['post', 'plugin/reset/1'],
            'plugin/delete/1' => ['post', 'plugin/delete/1'],
            'plugin/delete' => ['post', 'plugin/delete'],
        ];
    }

    #[Test]
    #[DataProvider('pluginRoutesProvider')]
    public function anonymousAndIncompleteSessionsAreBlockedAcrossPluginRoutes(string $method, string $route): void
    {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $manager = $this->createMock(PluginManagerService::class);
            $manager->expects(self::never())->method('search');
            $manager->expects(self::never())->method('getById');
            $manager->expects(self::never())->method('toggleEnabled');
            $manager->expects(self::never())->method('delete');
            $manager->expects(self::never())->method('deleteByIdBatch');

            $dataService = $this->createMock(PluginDataService::class);
            $dataService->expects(self::never())->method('delete');

            $result = $this->dispatchWeb($method, $route, $method === 'post' ? ['sk' => 'csrf-token-test'] : [], [
                PluginManagerService::class => $manager,
                PluginDataService::class => $dataService,
            ]);

            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    public function unprivilegedUserIsDeniedPluginIndexSearchViewAndDeleteUnderRealAcl(): void
    {
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = new ProfileData();

        $manager = $this->createMock(PluginManagerService::class);
        $manager->expects(self::never())->method('search');
        $manager->expects(self::never())->method('getById');
        $manager->expects(self::never())->method('delete');
        $manager->expects(self::never())->method('deleteByIdBatch');

        $indexResult = $this->dispatchWeb('get', 'plugin/index', [], [
            PluginManagerService::class => $manager,
        ]);
        self::assertStringContainsString('You don\'t have permission to do this operation', $indexResult['body']);

        foreach (
            [
                ['get', 'plugin/search'],
                ['get', 'plugin/view/1'],
                ['post', 'plugin/delete/1'],
                ['post', 'plugin/delete'],
            ] as [$method, $route]
        ) {
            $result = $this->dispatchWeb($method, $route, $method === 'post' ? ['sk' => 'csrf-token-test'] : [], [
                PluginManagerService::class => $manager,
            ]);

            $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('ERROR', $json['status'] ?? null, $route);
            self::assertSame('You don\'t have permission to do this operation', $json['description'] ?? null, $route);
        }
    }

    #[Test]
    public function enableDisableAndResetLackAclChecksAndResetSkipsResetById(): void
    {
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = new ProfileData(); // lacks configGeneral & isAdminApp

        $plugin = (new Plugin())->mutate(['id' => 7, 'name' => 'SamplePlugin', 'data' => 'serialized-config']);

        $manager = $this->createMock(PluginManagerService::class);
        $manager->expects(self::exactly(2))
            ->method('toggleEnabled')
            ->willReturnCallback(static function (int $id, bool $enabled): void {
                self::assertSame(7, $id);
            });
        $manager->expects(self::once())->method('getById')->with(7)->willReturn($plugin);
        $manager->expects(self::never())->method('resetById');

        $dataService = $this->createMock(PluginDataService::class);
        $dataService->expects(self::once())->method('delete')->with('SamplePlugin');

        $enableRes = $this->dispatchWeb('post', 'plugin/enable/7', ['sk' => 'csrf-token-test'], [
            PluginManagerService::class => $manager,
            PluginDataService::class => $dataService,
        ]);
        $enableJson = json_decode($enableRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $enableJson['status'] ?? null);
        self::assertSame('Plugin enabled', $enableJson['description'] ?? null);

        $disableRes = $this->dispatchWeb('post', 'plugin/disable/7', ['sk' => 'csrf-token-test'], [
            PluginManagerService::class => $manager,
            PluginDataService::class => $dataService,
        ]);
        $disableJson = json_decode($disableRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $disableJson['status'] ?? null);
        self::assertSame('Plugin disabled', $disableJson['description'] ?? null);

        $resetRes = $this->dispatchWeb('post', 'plugin/reset/7', ['sk' => 'csrf-token-test'], [
            PluginManagerService::class => $manager,
            PluginDataService::class => $dataService,
        ]);
        $resetJson = json_decode($resetRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $resetJson['status'] ?? null);
        self::assertSame('Plugin reset', $resetJson['description'] ?? null);
    }

    #[Test]
    public function missingPluginOnToggleOrResetMapsNoSuchItemToErrorJson(): void
    {
        $manager = $this->createMock(PluginManagerService::class);
        $manager->expects(self::once())
            ->method('toggleEnabled')
            ->with(999, true)
            ->willThrowException(NoSuchItemException::info('Plugin not found'));
        $manager->expects(self::once())
            ->method('getById')
            ->with(999)
            ->willThrowException(NoSuchItemException::info('Plugin not found'));

        $enableRes = $this->dispatchWeb('post', 'plugin/enable/999', ['sk' => 'csrf-token-test'], [
            PluginManagerService::class => $manager,
        ]);
        $enableJson = json_decode($enableRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $enableJson['status'] ?? null);
        self::assertSame('Plugin not found', $enableJson['description'] ?? null);

        $resetRes = $this->dispatchWeb('post', 'plugin/reset/999', ['sk' => 'csrf-token-test'], [
            PluginManagerService::class => $manager,
        ]);
        $resetJson = json_decode($resetRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $resetJson['status'] ?? null);
        self::assertSame('Plugin not found', $resetJson['description'] ?? null);
    }

    #[Test]
    public function emptyBatchDeleteInvokesServiceWithEmptyArrayAndDuplicateIdsAreNotDeduplicatedAndAclOmitsPluginDelete(): void
    {
        // configGeneral=true without isAdminApp=true is denied plugin/delete because Acl::checkUserAccess() omits PLUGIN_DELETE
        $deniedManager = $this->createMock(PluginManagerService::class);
        $deniedManager->expects(self::never())->method('deleteByIdBatch');
        $deniedRes = $this->dispatchWeb('post', 'plugin/delete', ['sk' => 'csrf-token-test'], [
            PluginManagerService::class => $deniedManager,
        ]);
        $deniedJson = json_decode($deniedRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $deniedJson['status'] ?? null);
        self::assertSame('You don\'t have permission to do this operation', $deniedJson['description'] ?? null);

        $this->sessionUser = $this->makeUserDto(1, true);

        $capturedBatches = [];
        $manager = $this->createMock(PluginManagerService::class);
        $manager->expects(self::exactly(2))
            ->method('deleteByIdBatch')
            ->willReturnCallback(static function (array $ids) use (&$capturedBatches): void {
                $capturedBatches[] = $ids;
            });

        $emptyRes = $this->dispatchWeb('post', 'plugin/delete', ['sk' => 'csrf-token-test'], [
            PluginManagerService::class => $manager,
        ]);
        $emptyJson = json_decode($emptyRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $emptyJson['status'] ?? null);

        $dupRes = $this->dispatchWeb('post', 'plugin/delete', ['sk' => 'csrf-token-test', 'items' => ['5', '5', '6']], [
            PluginManagerService::class => $manager,
        ]);
        $dupJson = json_decode($dupRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $dupJson['status'] ?? null);

        self::assertSame([[], [5, 5, 6]], $capturedBatches);
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
