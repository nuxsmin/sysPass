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

namespace SP\Tests\Modules\Web\Controllers\Category;

use Klein\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Bootstrap\Path;
use SP\Domain\Category\Models\Category;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Models\Client;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\CustomField\Models\CustomFieldDefinition;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionService;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Domain\Tag\Ports\TagService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\File\FileSystem;
use SP\Tests\IntegrationTestCase;

use function DI\decorate;
use function DI\factory;

/**
 * Boundary & HTTP dispatch characterization tests for Vault Metadata & ItemManager controllers
 * (Category, Client, Tag, CustomField, ItemPreset, ItemManager) plus Notification\DeleteController ACL gap:
 * - Anonymous & incomplete-session rejection across all routes
 * - Real Acl gating (including CUSTOMFIELD_VIEW_PASS checking isAccViewPass without isAdminApp)
 * - Category\DeleteController & Client\DeleteController lacking try/catch -> SPException returns HTTP 500
 * - ClientForm dropping 'isglobal' because Client model keys on 'isGlobal'
 * - UpdateCustomFieldUseCase calling changeModule() (delete + create) on moduleId change
 * - Notification\DeleteController missing NOTIFICATION_DELETE ACL check (blank ProfileData succeeds)
 */
#[Group('integration')]
final class VaultMetadataBoundaryTest extends IntegrationTestCase
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
        $this->sessionProfile = (new ProfileData())
            ->setMgmCategories(true)
            ->setMgmCustomers(true)
            ->setMgmTags(true)
            ->setMgmCustomFields(true)
            ->setConfigGeneral(true);
        $this->configOverrides = [
            'getAccountCount' => 10,
            'isDemoEnabled' => true,
        ];
    }

    public static function metadataRoutesProvider(): array
    {
        return [
            'itemManager/index' => ['get', 'itemManager/index'],
            'category/search' => ['get', 'category/search'],
            'category/view/1' => ['get', 'category/view/1'],
            'category/saveCreate' => ['post', 'category/saveCreate'],
            'category/saveEdit/1' => ['post', 'category/saveEdit/1'],
            'category/delete/1' => ['post', 'category/delete/1'],
            'client/search' => ['get', 'client/search'],
            'client/view/1' => ['get', 'client/view/1'],
            'client/saveCreate' => ['post', 'client/saveCreate'],
            'client/saveEdit/1' => ['post', 'client/saveEdit/1'],
            'client/delete/1' => ['post', 'client/delete/1'],
            'tag/search' => ['get', 'tag/search'],
            'tag/view/1' => ['get', 'tag/view/1'],
            'tag/saveCreate' => ['post', 'tag/saveCreate'],
            'tag/saveEdit/1' => ['post', 'tag/saveEdit/1'],
            'tag/delete/1' => ['post', 'tag/delete/1'],
            'customField/search' => ['get', 'customField/search'],
            'customField/view/1' => ['get', 'customField/view/1'],
            'customField/saveCreate' => ['post', 'customField/saveCreate'],
            'customField/saveEdit/1' => ['post', 'customField/saveEdit/1'],
            'customField/delete/1' => ['post', 'customField/delete/1'],
            'itemPreset/search' => ['get', 'itemPreset/search'],
            'itemPreset/view/1' => ['get', 'itemPreset/view/1'],
            'itemPreset/saveCreate' => ['post', 'itemPreset/saveCreate'],
            'itemPreset/saveEdit/1' => ['post', 'itemPreset/saveEdit/1'],
            'itemPreset/delete/1' => ['post', 'itemPreset/delete/1'],
        ];
    }

    #[Test]
    #[DataProvider('metadataRoutesProvider')]
    public function anonymousAndIncompleteSessionsAreBlockedAcrossMetadataRoutes(string $method, string $route): void
    {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $categoryService = $this->createMock(CategoryService::class);
            $categoryService->expects(self::never())->method('delete');

            $clientService = $this->createMock(ClientService::class);
            $clientService->expects(self::never())->method('delete');

            $tagService = $this->createMock(TagService::class);
            $tagService->expects(self::never())->method('delete');

            $presetService = $this->createMock(ItemPresetService::class);
            $presetService->expects(self::never())->method('delete');

            $result = $this->dispatchWeb($method, $route, [], [
                CategoryService::class => $categoryService,
                ClientService::class => $clientService,
                TagService::class => $tagService,
                ItemPresetService::class => $presetService,
            ]);

            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    public function unprivilegedUserIsDeniedMetadataRoutesUnderRealAclWhileCustomFieldViewPassIgnoresAdminAcc(): void
    {
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = new ProfileData();

        foreach (
            [
                ['post', 'category/saveCreate'],
                ['post', 'category/delete/1'],
                ['post', 'client/saveCreate'],
                ['post', 'client/delete/1'],
                ['post', 'tag/saveCreate'],
                ['post', 'tag/delete/1'],
                ['post', 'customField/saveCreate'],
                ['post', 'customField/delete/1'],
                ['post', 'itemPreset/saveCreate'],
                ['post', 'itemPreset/delete/1'],
            ] as [$method, $route]
        ) {
            $res = $this->dispatchWeb($method, $route);
            $json = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('ERROR', $json['status'] ?? null, $route);
            self::assertSame('You don\'t have permission to do this operation', $json['description'] ?? null, $route);
        }

        // Real Acl check: CUSTOMFIELD_VIEW_PASS relies solely on accViewPass, ignoring isAdminAcc=true
        $sessionAccAdmin = $this->createStub(SessionContext::class);
        $sessionAccAdmin->method('getUserData')->willReturn(
            new UserDto(
                id: 21,
                login: 'accadmin',
                userGroupId: 1,
                userGroupName: 'Admins',
                userProfileId: 1,
                isAdminApp: false,
                isAdminAcc: true,
                lastUpdateMPass: 1000
            )
        );
        $sessionAccAdmin->method('getUserProfile')->willReturn(new ProfileData());

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php'),
            [
                Context::class => $sessionAccAdmin,
                SessionContext::class => $sessionAccAdmin,
                AclInterface::class => factory(
                    static fn(ContainerInterface $c): Acl => new Acl(
                        $c->get(Context::class),
                        $c->get(EventDispatcherInterface::class),
                        $c->get(ActionsInterface::class)
                    )
                ),
            ]
        );
        /** @var AclInterface $realAcl */
        $realAcl = $container->get(AclInterface::class);
        self::assertFalse($realAcl->checkUserAccess(AclActionsInterface::CUSTOMFIELD_VIEW_PASS));
    }

    #[Test]
    public function categoryAndClientDeleteLackExceptionHandlingReturningHttp500OnConstraintOrMissingItem(): void
    {
        $categoryService = $this->createMock(CategoryService::class);
        $categoryService->expects(self::once())
            ->method('delete')
            ->with(1)
            ->willThrowException(ConstraintException::error('Category in use'));

        $catRes = $this->dispatchWeb('post', 'category/delete/1', [], [
            CategoryService::class => $categoryService,
        ]);
        self::assertSame(500, $catRes['code']);
        self::assertSame(
            '{"status":"ERROR","description":"Category in use","data":null}',
            $catRes['body']
        );

        $clientService = $this->createMock(ClientService::class);
        $clientService->expects(self::once())
            ->method('delete')
            ->with(99)
            ->willThrowException(NoSuchItemException::info('Client not found'));

        $clientRes = $this->dispatchWeb('post', 'client/delete/99', [], [
            ClientService::class => $clientService,
        ]);
        self::assertSame(500, $clientRes['code']);
        self::assertSame(
            '{"status":"ERROR","description":"Client not found","data":null}',
            $clientRes['body']
        );
    }

    #[Test]
    public function clientFormDropsIsglobalBecauseModelKeysOnIsGlobalAndAllMetadataIgnoreDemoMode(): void
    {
        /** @var Client|null $createdClient */
        $createdClient = null;
        $clientService = $this->createMock(ClientService::class);
        $clientService->expects(self::once())
            ->method('create')
            ->willReturnCallback(static function (Client $client) use (&$createdClient): int {
                $createdClient = $client;

                return 77;
            });

        $clientRes = $this->dispatchWeb('post', 'client/saveCreate', [
            'name' => 'Acme Corp',
            'description' => 'Global client attempt',
            'isglobal' => '1',
        ], [
            ClientService::class => $clientService,
            CustomFieldDataService::class => $this->createStub(CustomFieldDataService::class),
        ]);

        // Even with isDemoEnabled=true in configOverrides, Client creates successfully
        $clientJson = json_decode($clientRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $clientJson['status'] ?? null);
        self::assertSame('Client added', $clientJson['description'] ?? null);
        self::assertNotNull($createdClient);
        // 'isglobal' goes into outer Model::$properties, leaving Client::$isGlobal null
        self::assertNull($createdClient->getIsGlobal());

        /** @var Category|null $createdCategory */
        $createdCategory = null;
        $categoryService = $this->createMock(CategoryService::class);
        $categoryService->expects(self::once())
            ->method('create')
            ->willReturnCallback(static function (Category $category) use (&$createdCategory): int {
                $createdCategory = $category;

                return 88;
            });

        $catRes = $this->dispatchWeb('post', 'category/saveCreate', [
            'name' => '  Infra  ',
        ], [
            CategoryService::class => $categoryService,
            CustomFieldDataService::class => $this->createStub(CustomFieldDataService::class),
        ]);
        $catJson = json_decode($catRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $catJson['status'] ?? null);
        self::assertNotNull($createdCategory);
        // Form does not compute hash pre-insert, so getHash() is null before CategoryService::create()
        self::assertNull($createdCategory->getHash());
    }

    #[Test]
    public function customFieldModuleChangeTriggersDestructiveChangeModuleViaSaveEditController(): void
    {
        $def = (new CustomFieldDefinition())->mutate([
            'id' => 11,
            'name' => 'API Key',
            'typeId' => 1,
            'moduleId' => AclActionsInterface::USER,
            'isEncrypted' => 1,
        ]);

        /** @var CustomFieldDefinition|null $changedDef */
        $changedDef = null;
        $cfDefService = $this->createMock(CustomFieldDefinitionService::class);
        $cfDefService->expects(self::once())->method('getById')->with(11)->willReturn($def);
        $cfDefService->expects(self::once())
            ->method('changeModule')
            ->willReturnCallback(static function (CustomFieldDefinition $newDef) use (&$changedDef): int {
                $changedDef = $newDef;

                return 99;
            });
        $cfDefService->expects(self::never())->method('update');

        $res = $this->dispatchWeb('post', 'customField/saveEdit/11', [
            'name' => 'API Key',
            'type' => '1',
            'module' => (string)AclActionsInterface::ACCOUNT,
            'required' => '1',
        ], [
            CustomFieldDefinitionService::class => $cfDefService,
        ]);

        $json = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status'] ?? null);
        self::assertNotNull($changedDef);
        self::assertSame(11, $changedDef->getId());
        self::assertSame(AclActionsInterface::ACCOUNT, $changedDef->getModuleId());
    }

    #[Test]
    public function notificationDeleteControllerLacksAclCheckAndAllowsBlankProfileUser(): void
    {
        $this->sessionUser = $this->makeUserDto(90, false);
        $this->sessionProfile = new ProfileData();

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects(self::once())->method('delete')->with(5);
        $notificationService->expects(self::never())->method('deleteAdmin');

        $res = $this->dispatchWeb('post', 'notification/delete/5', [], [
            NotificationService::class => $notificationService,
        ]);

        $json = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status'] ?? null);
        self::assertSame('Notification deleted', $json['description'] ?? null);
    }

    #[Test]
    public function itemManagerIndexRendersEmptyShellForBlankProfile(): void
    {
        $this->sessionUser = $this->makeUserDto(50, false);
        $this->sessionProfile = new ProfileData();

        $res = $this->dispatchWeb('get', 'itemManager/index');
        self::assertSame(200, $res['code']);
        self::assertStringNotContainsString('tblCategories', $res['body']);
        self::assertStringNotContainsString('tblClients', $res['body']);
        self::assertStringNotContainsString('tblTags', $res['body']);
        self::assertStringNotContainsString('tblCustomFields', $res['body']);
        self::assertStringNotContainsString('tblItemPresets', $res['body']);
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
