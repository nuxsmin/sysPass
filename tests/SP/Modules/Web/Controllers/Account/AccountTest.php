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

namespace SP\Tests\Modules\Web\Controllers\Account;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName;
use SP\Domain\Account\Dtos\PublicLinkKey;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountHistory;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\Common\Models\Item;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\CategoryGenerator;
use SP\Tests\Generators\ClientGenerator;
use SP\Tests\Generators\PublicLinkDataGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\InjectConfigParam;
use SP\Tests\InjectCrypt;
use SP\Tests\InjectVault;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AccountTest
 */
#[Group('integration')]
#[InjectVault]
class AccountTest extends IntegrationTestCase
{
    private ?bool $legacyWikiEnabled = null;
    private bool  $filesEnabled      = false;

    protected function getConfigData(): array
    {
        $config = parent::getConfigData();

        if ($this->legacyWikiEnabled !== null) {
            $config = array_merge($config, [
                'isWikiEnabled' => true,
                'isDokuwikiEnabled' => $this->legacyWikiEnabled,
                'getWikiFilter' => ['Wiki'],
                'getWikiPageurl' => 'https://wiki.example.test/page/',
            ]);
        }

        if ($this->filesEnabled) {
            $config = array_merge($config, [
                'isFilesEnabled' => true,
                'getFilesAllowedSize' => 1024,
                'getFilesAllowedExts' => ['TXT', 'PNG'],
            ]);
        }

        return $config;
    }

    protected function setUp(): void
    {
        $this->legacyWikiEnabled = null;
        $this->filesEnabled = false;
        parent::setUp();

        $this->databaseQueryResolver = function (QueryData $queryData): QueryResult {
            $statement = $queryData->getQuery()->getStatement();

            if (str_contains($statement, "SELECT\n    `Account`.`id`\nFROM\n    `Account`")) {
                return new QueryResult([new Simple(['id' => 1])]);
            }

            return $this->resolveDatabaseQuery($queryData);
        };
    }

    protected function getUserDataDto(): UserDto
    {
        $userPreferences = UserDataGenerator::factory()->buildUserPreferencesData()->mutate(['topNavbar' => true]);
        return parent::getUserDataDto()->mutate([
            'id' => self::$faker->numberBetween(1, 999),
            'userGroupId' => self::$faker->numberBetween(1, 999),
            'preferences' => $userPreferences,
        ]);
    }

    protected function getUserProfile(): ProfileData
    {
        return new ProfileData(
            [
                'accAdd' => true,
                'accViewPass' => true,
                'accViewHistory' => true,
                'accDelete' => true
            ]
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewPassHistory')]
    #[InjectCrypt]
    public function viewPassHistory()
    {
        $accountGenerator = AccountDataGenerator::factory();
        $this->addDatabaseMapperResolver(
            Account::class,
            new QueryResult([$accountGenerator->buildAccount()->mutate(['id' => 1, 'parentId' => 0])])
        );
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    $accountGenerator->buildAccountDataView()
                                )->mutate(['accountId' => 1, 'parentId' => 0])
                            ])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                [
                    'r' => sprintf(
                        "account/viewPassHistory/id/%d",
                        self::$faker->randomNumber(3)
                    )
                ]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewPass')]
    #[InjectCrypt]
    public function viewPass()
    {
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )->mutate(['parentId' => 0])
                            ])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                [
                    'r' => sprintf(
                        "account/viewPass/id/%d",
                        self::$faker->randomNumber(3)
                    )
                ]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CryptException
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewLink')]
    public function viewLink()
    {
        $account = serialize(Simple::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccount()));
        $publicLinkKey = new PublicLinkKey($this->passwordSalt);

        $vault = Vault::factory(new Crypt())->saveData($account, $publicLinkKey->getKey());

        $publicLink = PublicLinkDataGenerator::factory()
                                             ->buildPublicLink()
                                             ->mutate(
                                                 [
                                                     'dateExpire' => time() + 100,
                                                     'maxCountViews' => 3,
                                                     'countViews' => 0,
                                                     'hash' => $publicLinkKey->getHash(),
                                                     'data' => $vault->getSerialized()
                                                 ]
                                             );
        $this->addDatabaseMapperResolver(PublicLink::class, new QueryResult([$publicLink]));

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CryptException
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewLinkEscapedPassword')]
    public function viewLinkEscapesPassword(): void
    {
        $payload = '"><script>alert(1)</script>';
        $properties = AccountDataGenerator::factory()
                                          ->buildAccount()
                                          ->toArray(includeOuter: true);
        $properties['pass'] = $payload;

        $account = serialize(new Simple($properties));
        $publicLinkKey = new PublicLinkKey($this->passwordSalt);

        $vault = Vault::factory(new Crypt())->saveData($account, $publicLinkKey->getKey());

        $publicLink = PublicLinkDataGenerator::factory()
                                             ->buildPublicLink()
                                             ->mutate(
                                                 [
                                                     'dateExpire' => time() + 100,
                                                     'maxCountViews' => 3,
                                                     'countViews' => 0,
                                                     'hash' => $publicLinkKey->getHash(),
                                                     'data' => $vault->getSerialized()
                                                 ]
                                             );
        $this->addDatabaseMapperResolver(PublicLink::class, new QueryResult([$publicLink]));

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unauthenticatedAccountMutationRoutesProvider(): array
    {
        return [
            'saveDelete' => ['account/saveDelete/100', 'delete'],
            'saveEdit' => ['account/saveEdit/100', 'update'],
            'saveEditPass' => ['account/saveEditPass/100', 'editPassword'],
            'saveEditRestore' => ['account/saveEditRestore/1/100', 'restoreModified'],
        ];
    }

    /**
     * @return array<string, array{string, string, int[]}>
     */
    public static function accountMutationActionsProvider(): array
    {
        return [
            'saveDelete' => ['account/saveDelete/100', 'delete', [AclActionsInterface::ACCOUNT_DELETE]],
            'saveEdit' => ['account/saveEdit/100', 'update', [AclActionsInterface::ACCOUNT_EDIT]],
            'saveEditPass' => ['account/saveEditPass/100', 'editPassword', [AclActionsInterface::ACCOUNT_EDIT_PASS]],
            'saveEditRestoreWithoutEdit' => [
                'account/saveEditRestore/1/100',
                'restoreModified',
                [AclActionsInterface::ACCOUNT_EDIT],
            ],
            'saveEditRestoreWithoutViewHistory' => [
                'account/saveEditRestore/1/100',
                'restoreModified',
                [AclActionsInterface::ACCOUNT_HISTORY_VIEW],
            ],
            'saveEditRestoreWithoutEditRestore' => [
                'account/saveEditRestore/1/100',
                'restoreModified',
                [AclActionsInterface::ACCOUNT_EDIT_RESTORE],
            ],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function accountMutationRepositoryMethodsProvider(): array
    {
        return [
            'saveDelete' => ['account/saveDelete/100', 'delete'],
            'saveEdit' => ['account/saveEdit/100', 'update'],
            'saveEditPass' => ['account/saveEditPass/100', 'editPassword'],
            'saveEditRestore' => ['account/saveEditRestore/1/100', 'restoreModified'],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[DataProvider('unauthenticatedAccountMutationRoutesProvider')]
    public function unauthenticatedAccountMutationsAreRejected(string $route, string $serviceMethod): void
    {
        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method($serviceMethod);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => $route]),
            [
                Context::class => $this->getUnauthenticatedContext(),
                AccountService::class => $accountService,
            ]
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[DataProvider('accountMutationActionsProvider')]
    public function accountMutationWithoutActionIsRejected(
        string $route,
        string $serviceMethod,
        array  $actions
    ): void {
        $acl = $this->createMock(AclInterface::class);
        $acl->method('checkUserAccess')
            ->willReturnCallback(static fn(int $actionId) => !in_array($actionId, $actions, true));

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method($serviceMethod);
        $accountService->expects(self::never())->method('isVisible');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => $route]),
            [
                AclInterface::class => $acl,
                AccountService::class => $accountService,
            ]
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"ERROR","description":"You don\'t have permission to do this operation","data":null}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function accountCreateWithoutActionIsRejected(): void
    {
        $password = self::$faker->password();

        $acl = $this->createMock(AclInterface::class);
        $acl->method('checkUserAccess')
            ->willReturnCallback(
                static fn(int $actionId) => $actionId !== AclActionsInterface::ACCOUNT_CREATE
            );

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('create');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveCreate'],
                [
                    'name' => 'Denied Create Attempt',
                    'login' => 'denied-create',
                    'client_id' => 1,
                    'category_id' => 1,
                    'password' => $password,
                    'password_repeat' => $password,
                ]
            ),
            [
                AclInterface::class => $acl,
                AccountService::class => $accountService,
            ]
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"ERROR","description":"You don\'t have permission to do this operation","data":null}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function accountCopyWithoutActionIsRejected(): void
    {
        $password = self::$faker->password();

        $acl = $this->createMock(AclInterface::class);
        $acl->method('checkUserAccess')
            ->willReturnCallback(
                static fn(int $actionId) => $actionId !== AclActionsInterface::ACCOUNT_CREATE
            );

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('create');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveCopy'],
                [
                    'name' => 'Denied Copy Attempt',
                    'login' => 'denied-copy',
                    'client_id' => 1,
                    'category_id' => 1,
                    'password' => $password,
                    'password_repeat' => $password,
                ]
            ),
            [
                AclInterface::class => $acl,
                AccountService::class => $accountService,
            ]
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"ERROR","description":"You don\'t have permission to do this operation","data":null}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[DataProvider('accountMutationRepositoryMethodsProvider')]
    public function accountMutationWithoutVisibilityIsRejected(string $route, string $repositoryMethod): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('isVisible')->willReturn(false);
        $accountRepository->expects(self::never())->method($repositoryMethod);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => $route]),
            [AccountRepository::class => $accountRepository]
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"ERROR","description":"You don\'t have permission to access this account","data":null}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function serviceRejectsInvisibleAccountMutations(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('isVisible')->willReturn(false);
        $accountRepository->expects(self::never())->method('delete');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php'),
            [AccountRepository::class => $accountRepository]
        );

        $this->expectException(AccountPermissionException::class);

        $container->get(AccountService::class)->delete(100);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CryptException
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewLink')]
    public function viewLinkWithoutLogin(): void
    {
        $account = serialize(Simple::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccount()));
        $publicLinkKey = new PublicLinkKey($this->passwordSalt);

        $vault = Vault::factory(new Crypt())->saveData($account, $publicLinkKey->getKey());

        $publicLink = PublicLinkDataGenerator::factory()
                                             ->buildPublicLink()
                                             ->mutate(
                                                 [
                                                     'dateExpire' => time() + 100,
                                                     'maxCountViews' => 3,
                                                     'countViews' => 0,
                                                     'hash' => $publicLinkKey->getHash(),
                                                     'data' => $vault->getSerialized()
                                                 ]
                                             );
        $this->addDatabaseMapperResolver(PublicLink::class, new QueryResult([$publicLink]));

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()]),
            [Context::class => $this->getUnauthenticatedContext()]
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CryptException
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewLink')]
    public function viewLinkLegacyPayload()
    {
        $account = serialize(Simple::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccount()));
        $publicLinkKey = new PublicLinkKey($this->passwordSalt);

        $vault = Vault::factory(new Crypt())->saveData($account, $publicLinkKey->getKey());

        $legacyPayload = SerializedPayloadRewriter::rewrite(
            $vault->getSerialized(),
            [
                Vault::class => 'SP\Core\Crypt\Vault',
                Crypt::class => 'SP\Core\Crypt\Crypt',
            ]
        );

        $publicLink = PublicLinkDataGenerator::factory()
                                             ->buildPublicLink()
                                             ->mutate(
                                                 [
                                                     'dateExpire' => time() + 100,
                                                     'maxCountViews' => 3,
                                                     'countViews' => 0,
                                                     'hash' => $publicLinkKey->getHash(),
                                                     'data' => $legacyPayload
                                                 ]
                                             );
        $this->addDatabaseMapperResolver(PublicLink::class, new QueryResult([$publicLink]));

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()]),
            [DatabaseUtilInterface::class => $this->databaseUtilStub()]
        );

        IntegrationTestCase::runApp($container);

        self::assertSame($legacyPayload, $publicLink->getData());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public static function unavailablePublicLinks(): array
    {
        return [
            'expired' => [-60, 0, 3],
            'exhausted' => [60, 3, 3],
        ];
    }

    #[Test]
    public function unreadableVaultAfterParsingDoesNotRecordViewBeforeFailure(): void
    {
        $correctKey = new PublicLinkKey($this->passwordSalt);
        $wrongKey = new PublicLinkKey($this->passwordSalt);
        $payload = serialize(new Simple(['name' => 'synthetic-account-marker']));
        $vault = Vault::factory(new Crypt())->saveData($payload, $wrongKey->getKey());
        $link = $this->buildPublicLinkWithPayload($vault->getSerialized())->mutate(['hash' => $correctKey->getHash()]);
        $service = $this->createMock(PublicLinkService::class);
        $service->method('getByHash')->willReturn($link);
        $service->method('getPublicLinkKey')->willReturn($correctKey);
        $service->expects(self::never())->method('addLinkView');
        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::never())->method('incrementViewCounter');
        $accounts->expects(self::never())->method('incrementDecryptCounter');
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . $correctKey->getHash()]),
            [PublicLinkService::class => $service, AccountService::class => $accounts,
                DatabaseUtilInterface::class => $this->databaseUtilStub(), Context::class => $this->getUnauthenticatedContext()]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }
        self::assertStringNotContainsString('synthetic-account-marker', $output);
        self::assertSame(500, $container->get(\Klein\Response::class)->code());
    }

    #[Test]
    public function failedAtomicViewUpdateDoesNotExposeAccountOrIncrementAccountCounters(): void
    {
        $key = new PublicLinkKey($this->passwordSalt);
        $accountModel = AccountDataGenerator::factory()->buildAccountDataView();
        $payload = serialize(
            new Simple(
                array_merge(
                    $accountModel->toArray(),
                    [
                        'name' => 'synthetic-account-marker',
                        'pass' => 'synthetic-secret',
                        'key' => '',
                    ]
                )
            )
        );
        $vault = Vault::factory(new Crypt())->saveData($payload, $key->getKey());
        $link = $this->buildPublicLinkWithPayload($vault->getSerialized())->mutate(['hash' => $key->getHash()]);
        $service = $this->createMock(PublicLinkService::class);
        $service->method('getByHash')->willReturn($link);
        $service->method('getPublicLinkKey')->willReturn($key);
        $service->expects(self::once())->method('addLinkView')->willReturn(false);
        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::never())->method('incrementViewCounter');
        $accounts->expects(self::never())->method('incrementDecryptCounter');
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . $key->getHash()]),
            [PublicLinkService::class => $service, AccountService::class => $accounts,
                DatabaseUtilInterface::class => $this->databaseUtilStub(), Context::class => $this->getUnauthenticatedContext()]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }
        self::assertStringNotContainsString('synthetic-account-marker', $output);
        self::assertStringNotContainsString('synthetic-secret', $output);
        self::assertStringContainsString('permission to access this page', $output);
        self::assertSame(200, $container->get(\Klein\Response::class)->code());
    }

    #[Test]
    public function generatedPublicLinkForAnonymousVisitorReachesHashLookup(): void
    {
        $hash = str_repeat('a', 64);
        $url = \SP\Domain\Account\Services\PublicLink::getLinkForHash('http://localhost', $hash);
        self::assertSame('http://localhost/index.php?r=account/viewLink/' . $hash, $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        $service = $this->createMock(PublicLinkService::class);
        $service->expects(self::once())->method('getByHash')->with($hash)
            ->willReturn($this->buildPublicLinkWithPayload('')->mutate(['dateExpire' => time() - 60]));
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', $params),
            [PublicLinkService::class => $service, Context::class => $this->getUnauthenticatedContext(),
                DatabaseUtilInterface::class => $this->databaseUtilStub()]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }
        self::assertSame(200, $container->get(\Klein\Response::class)->code());
        self::assertStringContainsString('permission to access this page', $output);
    }

    #[Test]
    #[DataProvider('unavailablePublicLinks')]
    public function unavailablePublicLinkDoesNotRecordAView(int $expiryOffset, int $views, int $maximum): void
    {
        $link = $this->buildPublicLinkWithPayload('synthetic-unavailable-payload')->mutate([
            'dateExpire' => time() + $expiryOffset,
            'countViews' => $views,
            'maxCountViews' => $maximum,
        ]);
        $service = $this->createMock(PublicLinkService::class);
        $service->method('getByHash')->willReturn($link);
        $service->expects(self::never())->method('addLinkView');
        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::never())->method('incrementViewCounter');
        $accounts->expects(self::never())->method('incrementDecryptCounter');
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()]),
            [PublicLinkService::class => $service, AccountService::class => $accounts,
                DatabaseUtilInterface::class => $this->databaseUtilStub(), Context::class => $this->getUnauthenticatedContext()]
        );

        ob_start();
        self::runApp($container);
        $output = ob_get_clean();

        self::assertStringContainsString('permission to access this page', $output);
        self::assertStringNotContainsString('synthetic-unavailable-payload', $output);
    }

    #[Test]
    #[BodyChecker('outputCheckerViewLinkError')]
    public function viewLinkUnreadablePayload()
    {
        $payload = 'not-a-serialized-payload';
        $publicLink = $this->buildPublicLinkWithPayload($payload);

        $publicLinkService = $this->createMock(PublicLinkService::class);
        $publicLinkService->method('getByHash')->willReturn($publicLink);
        $publicLinkService->expects(self::never())->method('addLinkView');

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('incrementViewCounter');
        $accountService->expects(self::never())->method('incrementDecryptCounter');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()]),
            [
                PublicLinkService::class => $publicLinkService,
                AccountService::class => $accountService,
                DatabaseUtilInterface::class => $this->databaseUtilStub(),
            ]
        );

        IntegrationTestCase::runApp($container);

        self::assertSame($payload, $publicLink->getData());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewLinkError')]
    public function viewLinkEmptyPayload()
    {
        $payload = '';
        $publicLink = $this->buildPublicLinkWithPayload($payload);

        $publicLinkService = $this->createMock(PublicLinkService::class);
        $publicLinkService->method('getByHash')->willReturn($publicLink);
        $publicLinkService->expects(self::never())->method('addLinkView');

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('incrementViewCounter');
        $accountService->expects(self::never())->method('incrementDecryptCounter');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()]),
            [
                PublicLinkService::class => $publicLinkService,
                AccountService::class => $accountService,
                DatabaseUtilInterface::class => $this->databaseUtilStub(),
            ]
        );

        IntegrationTestCase::runApp($container);

        self::assertSame($payload, $publicLink->getData());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewHistory')]
    public function viewHistoryWithNoEditDate(): void
    {
        $history = AccountDataGenerator::factory()->buildAccountHistoryData()->mutate([
            'dateEdit' => null,
            'isDeleted' => 1,
            'parentId' => 0,
            'userName' => self::$faker->userName(),
            'userGroupName' => self::$faker->userName(),
            'userEditName' => self::$faker->userName(),
            'userEditLogin' => self::$faker->userName(),
        ]);
        $this->addDatabaseMapperResolver(AccountHistory::class, new QueryResult([$history]));
        $this->addDatabaseMapperResolver(Item::class, new QueryResult([
            new Item(['id' => self::$faker->randomNumber(3), 'name' => self::$faker->colorName()]),
        ]));
        $container = $this->buildContainer(IntegrationTestCase::buildRequest(
            'get', 'index.php', ['r' => 'account/viewHistory/id/' . self::$faker->randomNumber(3)]
        ));
        IntegrationTestCase::runApp($container);
    }

    #[Test]
    #[BodyChecker('outputCheckerViewHistory')]
    public function viewHistoryPassesValidEditDateToAcl(): void
    {
        $date = '2024-02-10 12:34:56';
        $history = AccountDataGenerator::factory()->buildAccountHistoryData()->mutate([
            'dateEdit' => $date,
            'isDeleted' => 1,
            'parentId' => 0,
            'userName' => self::$faker->userName(),
            'userGroupName' => self::$faker->userName(),
            'userEditName' => self::$faker->userName(),
            'userEditLogin' => self::$faker->userName(),
        ]);
        $this->addDatabaseMapperResolver(AccountHistory::class, new QueryResult([$history]));
        $this->addDatabaseMapperResolver(Item::class, new QueryResult([
            new Item(['id' => 1, 'name' => 'Synthetic item']),
        ]));
        $permission = new AccountPermission(AclActionsInterface::ACCOUNT_HISTORY_VIEW, true);
        $permission->setCompiledAccountAccess(true)->setResultView(true);
        $acl = $this->createMock(AccountAclService::class);
        $acl->expects(self::once())->method('getAcl')->with(
            AclActionsInterface::ACCOUNT_HISTORY_VIEW,
            self::callback(static fn(AccountAclDto $dto) => $dto->getDateEdit() === strtotime($date)),
            true
        )->willReturn($permission);
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewHistory/id/1']),
            [AccountAclService::class => $acl]
        );
        IntegrationTestCase::runApp($container);
    }

    public static function malformedHistoryEditDates(): array
    {
        return [['not-a-date'], ["2024-02-10 12:34:56\0"]];
    }

    #[Test]
    #[DataProvider('malformedHistoryEditDates')]
    public function viewHistoryRejectsMalformedEditDateBeforeAcl(string $date): void
    {
        $history = AccountDataGenerator::factory()->buildAccountHistoryData()->mutate(['dateEdit' => $date]);
        $this->addDatabaseMapperResolver(AccountHistory::class, new QueryResult([$history]));
        $acl = $this->createMock(AccountAclService::class);
        $acl->expects(self::never())->method('getAcl');
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/viewHistory/id/1']),
            [AccountAclService::class => $acl]
        );
        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            ob_end_clean();
        }
        self::assertSame(403, $container->get(\Klein\Response::class)->code());
    }

    #[Test]
    #[BodyChecker('outputCheckerViewHistory')]
    public function viewHistory()
    {
        $accountHistory = AccountDataGenerator::factory()
                                              ->buildAccountHistoryData()
                                              ->mutate([
                                                           'isDeleted' => 1,
                                                           'parentId' => 0,
                                                           'userName' => self::$faker->userName(),
                                                           'userGroupName' => self::$faker->userName(),
                                                           'userEditName' => self::$faker->userName(),
                                                           'userEditLogin' => self::$faker->userName(),
                                                       ]);
        $this->addDatabaseMapperResolver(
            AccountHistory::class,
            new QueryResult([$accountHistory])
        );

        $this->addDatabaseMapperResolver(
            Item::class,
            new QueryResult(
                [
                    new Item(
                        ['id' => self::$faker->randomNumber(3), 'name' => self::$faker->colorName()]
                    )
                ]
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/viewHistory/id/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerView')]
    public function view()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $this->addDatabaseMapperResolver(
            Item::class,
            new QueryResult(
                [
                    new Item(
                        ['id' => self::$faker->randomNumber(3), 'name' => self::$faker->colorName()]
                    )
                ]
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/view/id/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSearch')]
    public function search()
    {
        $accountSearchView = AccountDataGenerator::factory()->buildAccountSearchView();

        $this->addDatabaseMapperResolver(
            AccountSearchView::class,
            QueryResult::withTotalNumRows([$accountSearchView], 1)
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'account/search'],
                ['search' => $accountSearchView->getName()]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    public static function wikiSearchCases(): array
    {
        return [
            'legacy enabled' => [true, 'Wiki Account', true],
            'legacy disabled' => [false, 'Wiki Account', true],
            'nonmatching' => [true, 'Other Account', false],
        ];
    }

    #[Test]
    #[DataProvider('wikiSearchCases')]
    public function searchWikiLinkDoesNotOfferEmbeddedView(bool $legacyEnabled, string $name, bool $matches): void
    {
        $this->legacyWikiEnabled = $legacyEnabled;
        $generated = AccountDataGenerator::factory()->buildAccountSearchView();
        $account = new AccountSearchView(array_merge($generated->toArray(null, null, true), ['name' => $name]));
        $this->addDatabaseMapperResolver(AccountSearchView::class, QueryResult::withTotalNumRows([$account], 1));
        $permission = new AccountPermission(AclActionsInterface::ACCOUNT_SEARCH);
        $permission->setResultView(true);
        $permission->setShowView(true);
        $acl = self::createStub(AccountAclService::class);
        $acl->method('getAcl')->willReturn($permission);
        $container = $this->buildContainer(
            self::buildRequest('post', 'index.php', ['r' => 'account/search']),
            [AccountAclService::class => $acl]
        );

        ob_start();
        self::runApp($container);
        $output = json_decode(ob_get_clean(), true, flags: JSON_THROW_ON_ERROR)['data']['html'];

        self::assertStringNotContainsString('data-onclick="wiki/show"', $output);
        if ($matches) {
            self::assertStringContainsString('https://wiki.example.test/page/Wiki+Account', $output);
        } else {
            self::assertStringNotContainsString('https://wiki.example.test/page/', $output);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectConfigParam]
    public function saveRequest()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveRequest/100'],
                ['description' => self::$faker->text()]
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Request done","data":{"itemId":100,"nextAction":"1"}}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectConfigParam]
    public function saveEditRestore()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $historyId = self::$faker->randomNumber(3);
        $accountId = self::$faker->randomNumber(3);

        $history = $accountDataGenerator->buildAccountHistoryData()->mutate([
            'id' => $historyId,
            'accountId' => $accountId,
            'parentId' => 0,
        ]);

        $this->addDatabaseMapperResolver(
            AccountHistory::class,
            new QueryResult([$history])
        );

        $this->addDatabaseMapperResolver(
            Account::class,
            new QueryResult([$accountDataGenerator->buildAccount()->mutate(['id' => $accountId, 'parentId' => 0])])
        );

        $this->addDatabaseMapperResolver(
            Client::class,
            new QueryResult([ClientGenerator::factory()->buildClient()->mutate(['id' => $history->getClientId()])])
        );

        $this->addDatabaseMapperResolver(
            Category::class,
            new QueryResult([CategoryGenerator::factory()->buildCategory()->mutate(['id' => $history->getCategoryId()])])
        );

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => sprintf("account/saveEditRestore/%d/%d", $historyId, $accountId)],
                $paramsPost
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Account restored","data":{"itemId":'
            . $accountId .
            ',"nextAction":"3"}}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectConfigParam]
    public function saveEditPass()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            Account::class,
            new QueryResult([$accountDataGenerator->buildAccount()])
        );

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
        ];

        $accountId = self::$faker->randomNumber(3);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveEditPass/' . $accountId],
                $paramsPost
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Password updated","data":{"itemId":' . $accountId .
            ',"nextAction":"3"}}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectConfigParam]
    public function saveEdit()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $existingAccount = $accountDataGenerator->buildAccount()->mutate([
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
        ]);

        $this->addDatabaseMapperResolver(
            Account::class,
            new QueryResult([$existingAccount])
        );

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $account = $accountDataGenerator->buildAccount()->mutate(['parentId' => 0]);

        $this->addDatabaseMapperResolver(
            \SP\Domain\Client\Models\Client::class,
            new QueryResult([new \SP\Domain\Client\Models\Client(['id' => $account->getClientId()])])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\Category\Models\Category::class,
            new QueryResult([new \SP\Domain\Category\Models\Category(['id' => $account->getCategoryId()])])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\User\Models\User::class,
            new QueryResult([
                new \SP\Domain\User\Models\User([
                    'id' => $existingAccount->getUserId(),
                    'userGroupId' => $existingAccount->getUserGroupId(),
                ]),
            ])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\User\Models\UserGroup::class,
            new QueryResult([new \SP\Domain\User\Models\UserGroup(['id' => $existingAccount->getUserGroupId()])])
        );

        $paramsPost = [
            'name' => $account->getName(),
            'login' => $account->getLogin(),
            'client_id' => $account->getClientId(),
            'category_id' => $account->getCategoryId(),
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
            'owner_id' => $account->getUserId(),
            'notes' => $account->getNotes(),
            'private_enabled' => 0,
            'private_group_enabled' => 0,
            'password_date_expire_unix' => $account->getPassDate(),
            'parent_account_id' => $account->getParentId(),
            'main_usergroup_id' => $account->getUserGroupId(),
            'other_users_view_update' => 1,
            'other_users_view' => [1, 2, 3],
            'other_users_edit_update' => 1,
            'other_users_edit' => [4, 5, 6],
            'other_usergroups_view_update' => 1,
            'other_usergroups_view' => [8, 9, 10],
            'other_usergroups_edit_update' => 1,
            'other_usergroups_edit' => [11, 12, 13],
            'tags_update' => 1,
            'tags' => [15, 16, 17],
        ];

        $accountId = self::$faker->randomNumber(3);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveEdit/' . $accountId],
                $paramsPost
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Account updated","data":{"itemId":' . $accountId .
            ',"nextAction":"3"}}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectConfigParam]
    public function saveDelete()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $this->addDatabaseMapperResolver(
            Account::class,
            new QueryResult([$accountDataGenerator->buildAccount()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'account/saveDelete/1'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Account removed","data":null}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerCopy')]
    public function copy()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $this->addDatabaseMapperResolver(
            Item::class,
            new QueryResult(
                [
                    new Item(
                        ['id' => self::$faker->randomNumber(3), 'name' => self::$faker->colorName()]
                    )
                ]
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/copy/id/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function copyPass()
    {
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )->mutate(['parentId' => 0])
                            ])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/copyPass/id/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Password copied","data":{"accpass":"some_data"}}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function copyPassHistory()
    {
        $accountGenerator = AccountDataGenerator::factory();
        $this->addDatabaseMapperResolver(
            Account::class,
            new QueryResult([$accountGenerator->buildAccount()->mutate(['id' => 1, 'parentId' => 0])])
        );
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    $accountGenerator->buildAccountDataView()
                                )->mutate(['accountId' => 1, 'parentId' => 0])
                            ])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/copyPassHistory/id/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Password copied","data":{"accpass":"some_data"}}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerCreate')]
    public function create()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/create'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerAccountContentPage')]
    public function viewDirectPageUsesAccountContentView(): void
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $this->addDatabaseMapperResolver(
            Item::class,
            new QueryResult(
                [
                    new Item(
                        ['id' => self::$faker->randomNumber(3), 'name' => self::$faker->colorName()]
                    )
                ]
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/view/id/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerCreateAccountContentPage')]
    public function createDirectPageUsesAccountContentView(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/create'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerAccountContentPage')]
    public function editDirectPageUsesAccountContentView(): void
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/edit/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerDelete')]
    public function delete()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account/delete/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerEdit')]
    public function edit()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/edit/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerIndex')]
    public function index()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'account'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerRequestAccess')]
    public function requestAccess()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/requestAccess/' . self::$faker->randomNumber(3)]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveCopy()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $account = $accountDataGenerator->buildAccount()->mutate(['parentId' => 0]);

        $this->addDatabaseMapperResolver(
            \SP\Domain\Client\Models\Client::class,
            new QueryResult([new \SP\Domain\Client\Models\Client(['id' => $account->getClientId()])])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\Category\Models\Category::class,
            new QueryResult([new \SP\Domain\Category\Models\Category(['id' => $account->getCategoryId()])])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\User\Models\User::class,
            new QueryResult([
                new \SP\Domain\User\Models\User([
                    'id' => $account->getUserId(),
                    'userGroupId' => $account->getUserGroupId(),
                ]),
            ])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\User\Models\UserGroup::class,
            new QueryResult([new \SP\Domain\User\Models\UserGroup(['id' => $account->getUserGroupId()])])
        );

        $paramsPost = [
            'name' => $account->getName(),
            'login' => $account->getLogin(),
            'client_id' => $account->getClientId(),
            'category_id' => $account->getCategoryId(),
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
            'owner_id' => $account->getUserId(),
            'notes' => $account->getNotes(),
            'private_enabled' => 0,
            'private_group_enabled' => 0,
            'password_date_expire_unix' => $account->getPassDate(),
            'parent_account_id' => $account->getParentId(),
            'main_usergroup_id' => $account->getUserGroupId(),
            'other_users_view_update' => 1,
            'other_users_view' => [1, 2, 3],
            'other_users_edit_update' => 1,
            'other_users_edit' => [4, 5, 6],
            'other_usergroups_view_update' => 1,
            'other_usergroups_view' => [8, 9, 10],
            'other_usergroups_edit_update' => 1,
            'other_usergroups_edit' => [11, 12, 13],
            'tags_update' => 1,
            'tags' => [15, 16, 17],
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'account/saveCopy'], $paramsPost)
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Account created","data":{"itemId":100,"nextAction":"5"}}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveCreate()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $account = $accountDataGenerator->buildAccount()->mutate(['parentId' => 0]);

        $this->addDatabaseMapperResolver(
            \SP\Domain\Client\Models\Client::class,
            new QueryResult([new \SP\Domain\Client\Models\Client(['id' => $account->getClientId()])])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\Category\Models\Category::class,
            new QueryResult([new \SP\Domain\Category\Models\Category(['id' => $account->getCategoryId()])])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\User\Models\User::class,
            new QueryResult([
                new \SP\Domain\User\Models\User([
                    'id' => $account->getUserId(),
                    'userGroupId' => $account->getUserGroupId(),
                ]),
            ])
        );
        $this->addDatabaseMapperResolver(
            \SP\Domain\User\Models\UserGroup::class,
            new QueryResult([new \SP\Domain\User\Models\UserGroup(['id' => $account->getUserGroupId()])])
        );

        $paramsPost = [
            'name' => $account->getName(),
            'login' => $account->getLogin(),
            'client_id' => $account->getClientId(),
            'category_id' => $account->getCategoryId(),
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
            'owner_id' => $account->getUserId(),
            'notes' => $account->getNotes(),
            'private_enabled' => 0,
            'private_group_enabled' => 0,
            'password_date_expire_unix' => $account->getPassDate(),
            'parent_account_id' => $account->getParentId(),
            'main_usergroup_id' => $account->getUserGroupId(),
            'other_users_view_update' => 1,
            'other_users_view' => [1, 2, 3],
            'other_users_edit_update' => 1,
            'other_users_edit' => [4, 5, 6],
            'other_usergroups_view_update' => 1,
            'other_usergroups_view' => [8, 9, 10],
            'other_usergroups_edit_update' => 1,
            'other_usergroups_edit' => [11, 12, 13],
            'tags_update' => 1,
            'tags' => [15, 16, 17],
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'account/saveCreate'], $paramsPost)
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Account created","data":{"itemId":100,"nextAction":"5"}}'
        );
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCopy(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[contains(concat(" ", normalize-space(@class), " "), " data-container ")]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(3, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewPassHistory(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup" and @class="box-password-view"]//table//td[starts-with(@class,"dialog-text")]|//button'
        )->extract(['_name']);

        self::assertCount(4, $filter);
        self::assertFalse($json->data->useimage);
        self::assertEquals('OK', $json->status);
        self::assertStringNotContainsString('mdl-color-text--amber-A200', $json->data->html);
        self::assertStringNotContainsString('mdl-color-text--amber-A100', $json->data->html);
        self::assertStringNotContainsString('mdl-color--amber-A200', $json->data->html);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewPass(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup" and @class="box-password-view"]//table//td[starts-with(@class,"dialog-text")]|//button'
        )->extract(['_name']);

        self::assertCount(4, $filter);
        self::assertFalse($json->data->useimage);
        self::assertEquals('OK', $json->status);
        self::assertStringNotContainsString('mdl-color-text--amber-A200', $json->data->html);
        self::assertStringNotContainsString('mdl-color-text--amber-A100', $json->data->html);
        self::assertStringNotContainsString('mdl-color--amber-A200', $json->data->html);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewLink(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@id="actions" and @class="public-link"]//table[@class="data"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(2, $filter);
        self::assertCount(1, $crawler->filterXPath('//button[@id="btnClipPass" and contains(@class, "sp-btn-primary") and @aria-label]'));
        self::assertStringNotContainsString('mdl-color--indigo-A200', $output);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewLinkEscapedPassword(string $output): void
    {
        self::assertStringNotContainsString('<script>alert(1)</script>', $output);
        self::assertStringContainsString(
            '&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;',
            $output
        );

        $crawler = new Crawler($output);
        $password = $crawler->filterXPath('//input[@id="password"]');

        self::assertCount(1, $password);
        self::assertSame('"><script>alert(1)</script>', $password->attr('value'));
        self::assertCount(0, $crawler->filterXPath('//script[contains(text(),"alert(1)")]'));
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewLinkError(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath('//ul[contains(@class,"errors")]/li[contains(@class,"msg-warning")]');

        self::assertCount(1, $filter);
    }

    /**
     * @return SessionContext
     * @throws Exception
     * @throws \SP\Domain\Core\Exceptions\SPException
     */
    private function getUnauthenticatedContext(): SessionContext
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getUserData')->willReturn($this->getUserDataDto());
        $context->method('getUserProfile')->willReturn($this->getUserProfile());

        return $context;
    }

    /**
     * @return DatabaseUtilInterface
     * @throws Exception
     */
    private function databaseUtilStub(): DatabaseUtilInterface
    {
        $databaseUtil = $this->createStub(DatabaseUtilInterface::class);
        $databaseUtil->method('checkDatabaseConnection')->willReturn(true);
        $databaseUtil->method('checkDatabaseTables')->willReturn(true);

        return $databaseUtil;
    }

    /**
     * @param string $payload
     * @return PublicLink
     */
    private function buildPublicLinkWithPayload(string $payload): PublicLink
    {
        $publicLinkKey = new PublicLinkKey($this->passwordSalt);

        return PublicLinkDataGenerator::factory()
                                      ->buildPublicLink()
                                      ->mutate(
                                          [
                                              'dateExpire' => time() + 100,
                                              'maxCountViews' => 3,
                                              'countViews' => 0,
                                              'hash' => $publicLinkKey->getHash(),
                                              'data' => $payload
                                          ]
                                      );
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewHistory(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[contains(concat(" ", normalize-space(@class), " "), " data-container ")]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(3, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerView(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[contains(concat(" ", normalize-space(@class), " "), " data-container ")]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(2, $filter);
        self::assertStringNotContainsString('mdl-color--indigo-A200', $output);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerSearch(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath('//div[@id="res-content"]/div')->extract(['id']);

        self::assertCount(4, $filter);
        self::assertEquals('OK', $json->status);
        self::assertStringNotContainsString('mdl-color-text--amber-A200', $json->data->html);
        self::assertStringNotContainsString('mdl-color-text--amber-A100', $json->data->html);
        self::assertStringNotContainsString('mdl-color--amber-A200', $json->data->html);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerRequestAccess(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[contains(concat(" ", normalize-space(@class), " "), " data-container ")]//form[@name="requestmodify" and @data-action-route="account/saveRequest"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(3, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerDelete(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[contains(concat(" ", normalize-space(@class), " "), " data-container ")]//form[@name="frmaccount" and @data-action-route="account/saveDelete"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(2, $filter);
        self::assertStringNotContainsString('mdl-color--indigo-A200', $output);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerEdit(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[contains(concat(" ", normalize-space(@class), " "), " data-container ")]//form[@name="frmaccount" and @data-action-route="account/saveEdit"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(3, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerIndex(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@id="searchbox"]/form[@name="frmSearch"]|//div[@id="res-content"]'
        )->extract(['id']);

        self::assertCount(2, $filter);
        self::assertStringNotContainsString('mdl-color--indigo-A200', $output);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCreate(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[contains(concat(" ", normalize-space(@class), " "), " data-container ")]//form[@name="frmaccount" and @data-action-route="account/saveCreate"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertCount(3, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerAccountContentPage(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(
            1,
            $crawler->filterXPath('//div[@id="content" and @data-page="account"]'),
            'Direct account pages must stamp the account view binder on #content'
        );
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCreateAccountContentPage(string $output): void
    {
        $this->outputCheckerAccountContentPage($output);

        $crawler = new Crawler($output);

        self::assertCount(
            1,
            $crawler->filterXPath('//select[@id="parent_account_id" and @data-item-id="0"]'),
            'The blank account create parent select must expose data-item-id="0"'
        );
    }

    #[Test]
    public function editRendersFileDropzoneWhenUploadIsAuthorized(): void
    {
        $output = $this->renderAccountRouteWithFiles('account/edit/100', true);
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//div[@id="file-panel"]//div[@id="list-account-files"]'));
        self::assertCount(1, $crawler->filterXPath('//div[@id="file-panel"]//div[@id="box-upload-files"]'));

        $dropzone = $crawler->filterXPath('//div[@id="box-upload-files"]//div[@id="drop-account-files"]');
        self::assertCount(1, $dropzone);
        self::assertSame('accountFile/upload', $dropzone->attr('data-action-route'));
        self::assertSame('100', $dropzone->attr('data-item-id'));
        self::assertSame('TXT,PNG', $dropzone->attr('data-files-ext'));

        self::assertCount(
            1,
            $crawler->filterXPath('//div[@id="box-upload-files"]//form[@id="fileUploadForm"]//input[@id="inFile"]')
        );
    }

    #[Test]
    public function editOmitsFileDropzoneWhenFileUploadActionIsDenied(): void
    {
        $output = $this->renderAccountRouteWithFiles('account/edit/100', false);
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//div[@id="file-panel"]//div[@id="list-account-files"]'));
        self::assertCount(0, $crawler->filterXPath('//*[@id="box-upload-files"]'));
        self::assertCount(0, $crawler->filterXPath('//*[@id="drop-account-files"]'));
        self::assertCount(0, $crawler->filterXPath('//*[@id="fileUploadForm"]'));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function nonEditAccountRoutesProvider(): array
    {
        return [
            'view' => ['account/view/100', true],
            'delete' => ['account/delete/100', false],
            'editPass' => ['account/editPass/100', false],
        ];
    }

    #[Test]
    #[DataProvider('nonEditAccountRoutesProvider')]
    public function nonEditAccountRoutesOmitFileDropzone(string $route, bool $expectsFileList): void
    {
        $output = $this->renderAccountRouteWithFiles($route, true);
        $crawler = new Crawler($output);

        if ($expectsFileList) {
            self::assertCount(1, $crawler->filterXPath('//div[@id="file-panel"]//div[@id="list-account-files"]'));
        }

        self::assertCount(0, $crawler->filterXPath('//*[@id="box-upload-files"]'));
        self::assertCount(0, $crawler->filterXPath('//*[@id="drop-account-files"]'));
        self::assertCount(0, $crawler->filterXPath('//*[@id="fileUploadForm"]'));
    }

    private function renderAccountRouteWithFiles(string $route, bool $allowFileUpload): string
    {
        $this->filesEnabled = true;

        $accountView = AccountDataGenerator::factory()
            ->buildAccountDataView()
            ->mutate(['id' => 100]);

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountView])
        );
        $this->addDatabaseMapperResolver(
            Item::class,
            new QueryResult([new Item(['id' => 101, 'name' => 'Linked Item'])])
        );

        $acl = $this->createMock(AclInterface::class);
        $acl->method('checkUserAccess')
            ->willReturnCallback(
                static fn(int $actionId): bool => $actionId !== AclActionsInterface::ACCOUNT_FILE_UPLOAD
                    || $allowFileUpload
            );
        $acl->method('getRouteFor')
            ->willReturnCallback(
                static fn(int $actionId): string => match ($actionId) {
                    AclActionsInterface::ACCOUNT_FILE_LIST => 'accountFile/list',
                    AclActionsInterface::ACCOUNT_FILE_UPLOAD => 'accountFile/upload',
                    default => (string)$actionId,
                }
            );

        $accountAcl = self::createStub(AccountAclService::class);
        $accountAcl->method('getAcl')
            ->willReturnCallback(
                static fn(int $actionId): AccountPermission => (new AccountPermission($actionId))
                    ->setCompiledAccountAccess(true)
                    ->setCompiledShowAccess(true)
                    ->setResultView(true)
                    ->setResultEdit(true)
                    ->setShowFiles($acl->checkUserAccess(AclActionsInterface::ACCOUNT_FILE))
                    ->setShowDelete($acl->checkUserAccess(AclActionsInterface::ACCOUNT_DELETE))
            );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => $route]),
            [
                AclInterface::class => $acl,
                AccountAclService::class => $accountAcl,
            ]
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $output = (string)ob_get_clean();
        }

        return $output;
    }
}
