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

namespace SP\Tests\Modules\Web\Controllers\User;

use Klein\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SP\Core\Acl\Acl;
use SP\Core\Bootstrap\Path;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Domain\Tag\Ports\TagService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\File\FileSystem;
use SP\Tests\IntegrationTestCase;

use function DI\decorate;
use function DI\factory;

/**
 * Boundary & HTTP dispatch characterization tests for Identity, UserSettings, AccessManager, and Items/* controllers:
 * - Anonymous & incomplete-session rejection across User, UserGroup, UserProfile, UserSettings, AccessManager, Items/*
 * - Real Acl gating on User/UserGroup/UserProfile vs self USER_EDIT_PASS exception in Acl::checkUserAccess
 * - User\DeleteController bypassing UserForm::checkDelete() (allows self-deletion and empty batch [])
 * - Privilege escalation: non-isAdminApp manager with isMgmUsers can submit adminapp_enabled=1 & adminacc_enabled=1
 * - UserForm::checkCommon() skipping name/login/email checks when isLdap=1
 * - Fatal PHP Errors in UserForm::isDemo() (getIsAdminApp()), UserSaveBase (unimported __()), and SaveEdit (processException())
 * - UserSettingsGeneral/SaveController accepting invalid theme/locale & non-positive resultsPerPage
 * - Items/* controllers lacking ACL checks (categories, clients, tags, accountsUser) and skipping NO_SESSION_ACTIVITY timeout
 */
#[Group('integration')]
final class UserOperationsBoundaryTest extends IntegrationTestCase
{
    private bool $sessionLoggedIn = true;
    private bool $sessionAuthCompleted = true;
    private ?UserDto $sessionUser = null;
    private ?ProfileData $sessionProfile = null;
    private int $sessionLastActivity = 0;
    private ?int $sessionTimeout = null;
    private array $configOverrides = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(1, false);
        $this->sessionProfile = (new ProfileData())
            ->setMgmUsers(true)
            ->setMgmGroups(true)
            ->setMgmProfiles(true);
        $this->sessionLastActivity = time();
        $this->sessionTimeout = 3600;
        $this->configOverrides = [
            'isDemoEnabled' => false,
            'getAccountCount' => 10,
            'getSessionTimeout' => 3600,
        ];
    }

    public static function identityAndItemsRoutesProvider(): array
    {
        return [
            'accessManager/index' => ['get', 'accessManager/index'],
            'user/search' => ['get', 'user/search'],
            'user/create' => ['get', 'user/create'],
            'user/edit/2' => ['get', 'user/edit/2'],
            'user/editPass/2' => ['get', 'user/editPass/2'],
            'user/saveCreate' => ['post', 'user/saveCreate'],
            'user/saveEdit/2' => ['post', 'user/saveEdit/2'],
            'user/saveEditPass/2' => ['post', 'user/saveEditPass/2'],
            'user/delete/2' => ['post', 'user/delete/2'],
            'user/delete' => ['post', 'user/delete'],
            'userGroup/search' => ['get', 'userGroup/search'],
            'userGroup/view/2' => ['get', 'userGroup/view/2'],
            'userGroup/delete/2' => ['post', 'userGroup/delete/2'],
            'userProfile/search' => ['get', 'userProfile/search'],
            'userProfile/view/2' => ['get', 'userProfile/view/2'],
            'userProfile/delete/2' => ['post', 'userProfile/delete/2'],
            'userSettingsManager/index' => ['get', 'userSettingsManager/index'],
            'userSettingsGeneral/save' => ['post', 'userSettingsGeneral/save'],
            'items/categories' => ['get', 'items/categories'],
            'items/clients' => ['get', 'items/clients'],
            'items/tags' => ['get', 'items/tags'],
            'items/accountsUser' => ['get', 'items/accountsUser'],
            'items/notifications' => ['get', 'items/notifications'],
        ];
    }

    #[Test]
    #[DataProvider('identityAndItemsRoutesProvider')]
    public function anonymousAndIncompleteSessionsAreBlockedAcrossIdentityAndItemsRoutes(
        string $method,
        string $route
    ): void {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $userService = $this->createMock(UserService::class);
            $userService->expects(self::never())->method('updatePreferencesById');
            $userService->expects(self::never())->method('create');
            $userService->expects(self::never())->method('delete');

            $categoryService = $this->createMock(CategoryService::class);
            $categoryService->expects(self::never())->method('getAll');

            $result = $this->dispatchWeb($method, $route, [], [
                UserService::class => $userService,
                CategoryService::class => $categoryService,
            ]);

            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    public function unprivilegedUserIsDeniedIdentityMutationsAndPermittedOnlySelfPasswordChange(): void
    {
        $this->sessionUser = $this->makeUserDto(25, false);
        $this->sessionProfile = new ProfileData();

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::never())->method('delete');
        $userService->expects(self::once())
            ->method('updatePass')
            ->with(25, 'NewSecret123!');

        foreach (
            [
                ['post', 'user/saveCreate'],
                ['post', 'user/saveEdit/25'],
                ['post', 'user/delete/2'],
                ['post', 'user/editPass/2'],
                ['post', 'user/saveEditPass/2'],
                ['post', 'userGroup/delete/2'],
                ['post', 'userProfile/delete/2'],
            ] as [$method, $route]
        ) {
            $res = $this->dispatchWeb($method, $route, [], [
                UserService::class => $userService,
            ]);
            $json = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('ERROR', $json['status'] ?? null, $route);
            self::assertSame('You don\'t have permission to do this operation', $json['description'] ?? null, $route);
        }

        // Self password change (id === 25) is permitted by Acl::USER_EDIT_PASS even with blank ProfileData
        $selfPassRes = $this->dispatchWeb('post', 'user/saveEditPass/25', [
            'password' => 'NewSecret123!',
            'password_repeat' => 'NewSecret123!',
        ], [
            UserService::class => $userService,
        ]);
        $selfPassJson = json_decode($selfPassRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $selfPassJson['status'] ?? null);
        self::assertSame('Password updated', $selfPassJson['description'] ?? null);
    }

    #[Test]
    public function userDeleteControllerBypassesSelfDeleteCheckAndAllowsEmptyBatch(): void
    {
        $this->sessionUser = $this->makeUserDto(25, false);
        $this->sessionProfile = (new ProfileData())->setMgmUsers(true);

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::once())->method('delete')->with(25);
        $userService->expects(self::once())->method('deleteByIdBatch')->with([])->willReturn(0);

        $customFieldData = $this->createStub(CustomFieldDataService::class);

        $selfDeleteRes = $this->dispatchWeb('post', 'user/delete/25', [], [
            UserService::class => $userService,
            CustomFieldDataService::class => $customFieldData,
        ]);
        $selfDeleteJson = json_decode($selfDeleteRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $selfDeleteJson['status'] ?? null);
        self::assertSame('User deleted', $selfDeleteJson['description'] ?? null);

        $emptyBatchRes = $this->dispatchWeb('post', 'user/delete', [], [
            UserService::class => $userService,
            CustomFieldDataService::class => $customFieldData,
        ]);
        $emptyBatchJson = json_decode($emptyBatchRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $emptyBatchJson['status'] ?? null);
        self::assertSame('Users deleted', $emptyBatchJson['description'] ?? null);
    }

    #[Test]
    public function nonAdminUserManagerCanEscalateAdminFlagsAndBypassFieldsWithIsLdap(): void
    {
        $this->sessionUser = $this->makeUserDto(30, false); // isAdminApp=false
        $this->sessionProfile = (new ProfileData())->setMgmUsers(true);

        /** @var User|null $createdModel */
        $createdModel = null;
        /** @var User|null $updatedModel */
        $updatedModel = null;

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::once())
            ->method('create')
            ->willReturnCallback(static function (User $user) use (&$createdModel): int {
                $createdModel = $user;

                return 101;
            });
        $userService->expects(self::once())
            ->method('update')
            ->willReturnCallback(static function (User $user) use (&$updatedModel): int {
                $updatedModel = $user;

                return 1;
            });

        $createRes = $this->dispatchWeb('post', 'user/saveCreate', [
            'isLdap' => '1',
            // name, login, email intentionally omitted — UserForm skips them when isLdap=1
            'usergroup_id' => '1',
            'userprofile_id' => '1',
            'adminapp_enabled' => '1',
            'adminacc_enabled' => '1',
            'password' => 'SecretPass1!',
            'password_repeat' => 'SecretPass1!',
        ], [
            UserService::class => $userService,
            CustomFieldDataService::class => $this->createStub(CustomFieldDataService::class),
        ]);

        $createJson = json_decode($createRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $createJson['status'] ?? null);
        self::assertNotNull($createdModel);
        self::assertTrue($createdModel->isAdminApp());
        self::assertTrue($createdModel->isAdminAcc());
        self::assertTrue($createdModel->isLdap());
        self::assertNull($createdModel->getLogin());

        $editRes = $this->dispatchWeb('post', 'user/saveEdit/30', [
            'name' => 'Escalated User',
            'login' => 'user_30',
            'email' => 'user30@example.com',
            'usergroup_id' => '1',
            'userprofile_id' => '1',
            'adminapp_enabled' => '1',
            'adminacc_enabled' => '1',
        ], [
            UserService::class => $userService,
            CustomFieldDataService::class => $this->createStub(CustomFieldDataService::class),
        ]);
        $editJson = json_decode($editRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $editJson['status'] ?? null);
        self::assertNotNull($updatedModel);
        self::assertTrue($updatedModel->isAdminApp());
        self::assertTrue($updatedModel->isAdminAcc());
    }

    #[Test]
    public function userFormDemoAndSaveEditErrorHandlersHitFatalUndefinedSymbols(): void
    {
        $userService = $this->createStub(UserService::class);

        // 1. UserForm::isDemo() calls undefined UserDto::getIsAdminApp() when isDemoEnabled=true & id=2 -> HTTP 500
        $this->configOverrides['isDemoEnabled'] = true;
        $demoEditRes = $this->dispatchWeb('post', 'user/saveEdit/2', [
            'name' => 'Demo User',
            'login' => 'demo',
            'email' => 'demo@example.com',
            'usergroup_id' => '1',
            'userprofile_id' => '1',
        ], [
            UserService::class => $userService,
        ]);
        self::assertSame(500, $demoEditRes['code']);
        self::assertSame(
            '{"status":"ERROR","description":"Internal error","data":null}',
            $demoEditRes['body']
        );

        // 2. UserSaveBase::checkChangeUserPass() calls unimported __() when changepass_enabled=1 -> HTTP 500
        $this->configOverrides['isDemoEnabled'] = false;
        $changePassRes = $this->dispatchWeb('post', 'user/saveEdit/30', [
            'name' => 'Normal User',
            'login' => 'normal',
            'email' => 'normal@example.com',
            'usergroup_id' => '1',
            'userprofile_id' => '1',
            'changepass_enabled' => '1',
        ], [
            UserService::class => $userService,
            CustomFieldDataService::class => $this->createStub(CustomFieldDataService::class),
            UserPassRecoverService::class => $this->createStub(UserPassRecoverService::class),
        ]);
        self::assertSame(500, $changePassRes['code']);
        self::assertSame(
            '{"status":"ERROR","description":"Internal error","data":null}',
            $changePassRes['body']
        );

        // 3. SaveEditController catch(Exception) calls unimported processException() -> HTTP 500
        $throwingService = $this->createStub(UserService::class);
        $throwingService->method('update')->willThrowException(new RuntimeException('boom'));
        $runtimeErrRes = $this->dispatchWeb('post', 'user/saveEdit/30', [
            'name' => 'Normal User',
            'login' => 'normal',
            'email' => 'normal@example.com',
            'usergroup_id' => '1',
            'userprofile_id' => '1',
        ], [
            UserService::class => $throwingService,
        ]);
        self::assertSame(500, $runtimeErrRes['code']);
        self::assertSame(
            '{"status":"ERROR","description":"Internal error","data":null}',
            $runtimeErrRes['body']
        );
    }

    #[Test]
    public function userSettingsGeneralAcceptsUnvalidatedThemeLangAndNonPositiveResultsPerPage(): void
    {
        $this->sessionUser = $this->makeUserDto(42, false);
        $this->sessionProfile = new ProfileData();

        /** @var UserPreferences|null $capturedPrefs */
        $capturedPrefs = null;
        $userService = $this->createMock(UserService::class);
        $userService->expects(self::once())
            ->method('updatePreferencesById')
            ->with(
                42,
                self::callback(static function (UserPreferences $prefs) use (&$capturedPrefs): bool {
                    $capturedPrefs = $prefs;

                    return true;
                })
            );

        $res = $this->dispatchWeb('post', 'userSettingsGeneral/save', [
            'usertheme' => '../../nonexistent-theme',
            'userlang' => 'xx_INVALID',
            'resultsperpage' => '-5',
        ], [
            UserService::class => $userService,
        ]);

        $json = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status'] ?? null);
        self::assertSame('Preferences updated', $json['description'] ?? null);
        self::assertNotNull($capturedPrefs);
        self::assertSame('../../nonexistent-theme', $capturedPrefs->getTheme());
        self::assertSame('xx_INVALID', $capturedPrefs->getLang());
        self::assertSame(-5, $capturedPrefs->getResultsPerPage());
    }

    #[Test]
    public function itemsEndpointsLackAclChecksAndIgnoreExpiredSessionTimeoutViaNoSessionActivity(): void
    {
        $this->sessionUser = $this->makeUserDto(50, false);
        $this->sessionProfile = new ProfileData(); // zero permissions
        $this->sessionLastActivity = 1; // expired hours ago vs timeout=10
        $this->sessionTimeout = 10;

        $categoryService = $this->createMock(CategoryService::class);
        $categoryService->expects(self::once())->method('getAll')->willReturn([]);

        $clientService = $this->createMock(ClientService::class);
        $clientService->expects(self::once())->method('getAllForUser')->willReturn([]);

        $tagService = $this->createMock(TagService::class);
        $tagService->expects(self::once())->method('getAll')->willReturn([]);

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())->method('getForUser')->willReturn([]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects(self::once())->method('getAllActiveForCurrentUser')->willReturn([]);

        $overrides = [
            CategoryService::class => $categoryService,
            ClientService::class => $clientService,
            TagService::class => $tagService,
            AccountService::class => $accountService,
            NotificationService::class => $notificationService,
        ];

        self::assertSame(200, $this->dispatchWeb('get', 'items/categories', [], $overrides)['code']);
        self::assertSame(200, $this->dispatchWeb('get', 'items/clients', [], $overrides)['code']);
        self::assertSame(200, $this->dispatchWeb('get', 'items/tags', [], $overrides)['code']);

        $accountsRes = $this->dispatchWeb('get', 'items/accountsUser', [], $overrides);
        $accountsJson = json_decode($accountsRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $accountsJson['status'] ?? null);
        self::assertSame([], $accountsJson['data'] ?? null);

        self::assertSame(200, $this->dispatchWeb('get', 'items/notifications', [], $overrides)['code']);
    }

    #[Test]
    public function accessManagerAndUserSettingsManagerReturnOkWithNoPermissionsAndRenderEmptyContainer(): void
    {
        $this->sessionUser = $this->makeUserDto(50, false);
        $this->sessionProfile = new ProfileData();

        $accessRes = $this->dispatchWeb('get', 'accessManager/index');
        self::assertSame(200, $accessRes['code']);
        self::assertStringNotContainsString('tblUsers', $accessRes['body']);
        self::assertStringNotContainsString('tblUserGroups', $accessRes['body']);
        self::assertStringNotContainsString('tblUserProfiles', $accessRes['body']);

        $settingsRes = $this->dispatchWeb('get', 'userSettingsManager/index', [], [
            UserService::class => $this->createStub(UserService::class),
        ]);
        self::assertSame(200, $settingsRes['code']);
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
        $lastActivity = $this->sessionLastActivity;
        $sessionTimeout = $this->sessionTimeout;

        $sessionStub = $this->createStub(SessionContext::class);
        $sessionStub->method('isLoggedIn')->willReturn($loggedIn);
        $sessionStub->method('getAuthCompleted')->willReturn($authCompleted);
        $sessionStub->method('getUserData')->willReturn($user);
        $sessionStub->method('getUserProfile')->willReturn($profile);
        $sessionStub->method('getLocale')->willReturn('en_US');
        $sessionStub->method('getCSRF')->willReturn(null);
        $sessionStub->method('getLastActivity')->willReturn($lastActivity);
        $sessionStub->method('getSessionTimeout')->willReturn($sessionTimeout);

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
            'paths' => decorate(static function (array $paths): array {
                $paths[] = [
                    Path::VIEW,
                    FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'web', 'themes'),
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
