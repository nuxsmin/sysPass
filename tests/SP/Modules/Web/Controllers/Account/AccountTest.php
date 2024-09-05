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
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\AccountDataGenerator;
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
    protected function getUserDataDto(): UserDto
    {
        $userPreferences = UserDataGenerator::factory()->buildUserPreferencesData()->mutate(['topNavbar' => true]);
        return parent::getUserDataDto()->mutate(['preferences' => $userPreferences]);
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
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )
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
                                )
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
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerViewHistory')]
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

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
        ];

        $historyId = self::$faker->randomNumber(3);
        $accountId = self::$faker->randomNumber(3);

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
                                )
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
        $this->addDatabaseMapperResolver(
            AccountPassItemWithIdAndName::class,
            new QueryResult([
                                AccountPassItemWithIdAndName::buildFromSimpleModel(
                                    AccountDataGenerator::factory()->buildAccountDataView()
                                )
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
            '//div[@class="data-container"]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
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

        self::assertCount(2, $filter);
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

        self::assertCount(3, $filter);
    }
}
