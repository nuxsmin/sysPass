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

namespace SP\Tests\Modules\Web\Controllers\Account;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName;
use SP\Domain\Account\Dtos\PublicLinkKey;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountHistory;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Common\Models\Item;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\PublicLinkDataGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\IntegrationTestCase;
use SP\Tests\OutputChecker;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AccountTest
 */
#[Group('integration')]
class AccountTest extends IntegrationTestCase
{
    private array $definitions;

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerViewPassHistory')]
    public function viewPassHistory()
    {
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )
                            ])
        );
        $crypt = $this->createStub(CryptInterface::class);
        $crypt->method('decrypt')->willReturn('some_data');
        $crypt->method('encrypt')->willReturn('some_data');

        $this->definitions[CryptInterface::class] = $crypt;

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest(
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

        $this->runApp($container);

        $this->expectOutputRegex(
            '/\{"status":0,"description":null,"data":\{"useimage":false,"html":".*"\},"messages":\[\]\}/'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerViewPass')]
    public function viewPass()
    {
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )
                            ])
        );
        $crypt = $this->createStub(CryptInterface::class);
        $crypt->method('decrypt')->willReturn('some_data');
        $crypt->method('encrypt')->willReturn('some_data');

        $this->definitions[CryptInterface::class] = $crypt;

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest(
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

        $this->runApp($container);

        $this->expectOutputRegex(
            '/\{"status":0,"description":null,"data":\{"useimage":false,"html":".*"\},"messages":\[\]\}/'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CryptException
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerViewLink')]
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
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/viewLink/' . self::$faker->sha1()])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerViewHistory')]
    public function viewHistory()
    {
        $accountHistory = AccountDataGenerator::factory()
                                              ->buildAccountHistoryData()
                                              ->mutate([
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
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/viewHistory/id/' . self::$faker->randomNumber(3)])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerView')]
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
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/view/id/' . self::$faker->randomNumber(3)])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerSearch')]
    public function search()
    {
        $accountSearchView = AccountDataGenerator::factory()->buildAccountSearchView();

        $this->addDatabaseMapperResolver(
            AccountSearchView::class,
            QueryResult::withTotalNumRows([$accountSearchView], 1)
        );

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest(
                'post',
                'index.php',
                ['r' => 'account/search'],
                ['search' => $accountSearchView->getName()]
            )
        );

        $this->expectOutputRegex(
            '/\{"status":0,"description":null,"data":\{"html":".*"\},"messages":\[\]\}/'
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveRequest()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $this->definitions[ConfigService::class] = $configService;

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveRequest/100'],
                ['description' => self::$faker->text()]
            )
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Request done","data":{"itemId":100,"nextAction":"1"},"messages":[]}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEditRestore()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountHistory::class,
            new QueryResult([$accountDataGenerator->buildAccountHistoryData()])
        );

        $this->addDatabaseMapperResolver(
            Account::class,
            new QueryResult([$accountDataGenerator->buildAccount()])
        );

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $this->definitions[ConfigService::class] = $configService;

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
        ];

        $historyId = self::$faker->randomNumber(3);
        $accountId = self::$faker->randomNumber(3);

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest(
                'post',
                'index.php',
                ['r' => sprintf("account/saveEditRestore/%d/%d", $historyId, $accountId)],
                $paramsPost
            )
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Account restored","data":{"itemId":'
            . $accountId .
            ',"nextAction":"3"},"messages":[]}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
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

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $this->definitions[ConfigService::class] = $configService;

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
        ];

        $accountId = self::$faker->randomNumber(3);

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'account/saveEditPass/' . $accountId], $paramsPost)
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Password updated","data":{"itemId":' . $accountId .
            ',"nextAction":"3"},"messages":[]}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEdit()
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

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $this->definitions[ConfigService::class] = $configService;

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'name' => $account->getName(),
            'login' => $account->getLogin(),
            'client_id' => $account->getClientId(),
            'category_id' => $account->getCategoryId(),
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
            'owner_id' => $account->getUserId(),
            'notes' => $account->getNotes(),
            'private_enabled' => $account->getIsPrivate(),
            'private_group_enabled' => $account->getIsPrivateGroup(),
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
            $this->definitions,
            $this->buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveEdit/' . $accountId],
                $paramsPost
            )
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Account updated","data":{"itemId":' . $accountId .
            ',"nextAction":"3"},"messages":[]}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
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

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $this->definitions[ConfigService::class] = $configService;

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'account/saveDelete/1'])
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Account removed","data":[],"messages":[]}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerCopy')]
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
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/copy/id/' . self::$faker->randomNumber(3)])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function copyPass()
    {
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )
                            ])
        );
        $crypt = $this->createStub(CryptInterface::class);
        $crypt->method('decrypt')->willReturn('some_data');
        $crypt->method('encrypt')->willReturn('some_data');

        $this->definitions[CryptInterface::class] = $crypt;

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/copyPass/id/' . self::$faker->randomNumber(3)])
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":null,"data":{"accpass":"some_data"},"messages":[]}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function copyPassHistory()
    {
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )
                            ])
        );

        $crypt = $this->createStub(CryptInterface::class);
        $crypt->method('decrypt')->willReturn('some_data');
        $crypt->method('encrypt')->willReturn('some_data');

        $this->definitions[CryptInterface::class] = $crypt;

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest(
                'get',
                'index.php',
                ['r' => 'account/copyPassHistory/id/' . self::$faker->randomNumber(3)]
            )
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":null,"data":{"accpass":"some_data"},"messages":[]}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerCreate')]
    public function create()
    {
        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/create'])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerDelete')]
    public function delete()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/delete/100'])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerEdit')]
    public function edit()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/edit/' . self::$faker->randomNumber(3)])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerIndex')]
    public function index()
    {
        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account'])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerRequestAccess')]
    public function requestAccess()
    {
        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountDataView()])
        );

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/requestAccess/' . self::$faker->randomNumber(3)])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveCopy()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $crypt = $this->createStub(CryptInterface::class);
        $crypt->method('decrypt')->willReturn('some_data');
        $crypt->method('encrypt')->willReturn('some_data');

        $this->definitions[CryptInterface::class] = $crypt;

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'name' => $account->getName(),
            'login' => $account->getLogin(),
            'client_id' => $account->getClientId(),
            'category_id' => $account->getCategoryId(),
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
            'owner_id' => $account->getUserId(),
            'notes' => $account->getNotes(),
            'private_enabled' => $account->getIsPrivate(),
            'private_group_enabled' => $account->getIsPrivateGroup(),
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
            $this->definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'account/saveCopy'], $paramsPost)
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Account created","data":{"itemId":100,"nextAction":"5"},"messages":[]}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveCreate()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $crypt = $this->createStub(CryptInterface::class);
        $crypt->method('decrypt')->willReturn('some_data');
        $crypt->method('encrypt')->willReturn('some_data');

        $this->definitions[CryptInterface::class] = $crypt;

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'name' => $account->getName(),
            'login' => $account->getLogin(),
            'client_id' => $account->getClientId(),
            'category_id' => $account->getCategoryId(),
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
            'owner_id' => $account->getUserId(),
            'notes' => $account->getNotes(),
            'private_enabled' => $account->getIsPrivate(),
            'private_group_enabled' => $account->getIsPrivateGroup(),
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
            $this->definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'account/saveCreate'], $paramsPost)
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Account created","data":{"itemId":100,"nextAction":"5"},"messages":[]}'
        );
    }

    /**
     * @throws FileException
     * @throws InvalidClassException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->definitions = $this->getModuleDefinitions();
    }

    protected function getUserDataDto(): UserDto
    {
        $userPreferences = UserDataGenerator::factory()->buildUserPreferencesData()->mutate(['topNavbar' => true]);
        return parent::getUserDataDto()->mutate(['preferences' => $userPreferences]);
    }

    protected function getContext(): SessionContext|Stub
    {
        $vault = self::createStub(VaultInterface::class);
        $vault->method('getData')->willReturn('some_data');

        $context = parent::getContext();
        $context->method('getVault')->willReturn($vault);

        return $context;
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
     * @param string $output
     * @return void
     */
    private function outputCheckerCopy(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@class="data-container"]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertNotEmpty($output);
        self::assertCount(3, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewPassHistory(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup" and @class="box-password-view"]//table//td[starts-with(@class,"dialog-text")]|//button'
        )->extract(['_name']);

        self::assertNotEmpty($output);
        self::assertCount(4, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewPass(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup" and @class="box-password-view"]//table//td[starts-with(@class,"dialog-text")]|//button'
        )->extract(['_name']);

        self::assertNotEmpty($output);
        self::assertCount(4, $filter);
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

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerViewHistory(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@class="data-container"]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerView(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@class="data-container"]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerSearch(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath('//div[@id="res-content"]/div')->extract(['id']);

        self::assertNotEmpty($output);
        self::assertCount(4, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerRequestAccess(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@class="data-container"]//form[@name="requestmodify" and @data-action-route="account/saveRequest"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertNotEmpty($output);
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
            '//div[@class="data-container"]//form[@name="frmaccount" and @data-action-route="account/saveDelete"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerEdit(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@class="data-container"]//form[@name="frmaccount" and @data-action-route="account/saveEdit"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertNotEmpty($output);
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

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCreate(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@class="data-container"]//form[@name="frmaccount" and @data-action-route="account/saveCreate"]|//div[@class="item-actions"]//button'
        )->extract(['id']);

        self::assertNotEmpty($output);
        self::assertCount(3, $filter);
    }
}
