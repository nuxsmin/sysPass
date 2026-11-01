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

namespace SP\Tests\Modules\Web\Controllers\AccountManager;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Config\Models\Config;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserGroup;
use SP\Infrastructure\Database\QueryData;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Common\Models\Simple;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\CategoryGenerator;
use SP\Tests\Generators\ClientGenerator;
use SP\Tests\Generators\TagGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\Generators\UserGroupGenerator;
use SP\Tests\InjectConfigParam;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AccountManagerTest
 */
#[Group('integration')]
class AccountManagerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseQueryResolver = function (QueryData $queryData): QueryResult {
            $statement = $queryData->getQuery()->getStatement();

            if (str_contains($statement, "SELECT\n    `Account`.`id`\nFROM\n    `Account`")) {
                return new QueryResult([new Simple(['id' => 1])]);
            }

            return $this->resolveDatabaseQuery($queryData);
        };
    }

    /**
     * @return array<string, array{string, string, string|null, array, int}>
     */
    public static function accountManagerMutationActionsProvider(): array
    {
        return [
            'bulkEditView' => [
                'accountManager/bulkEdit',
                'post',
                null,
                ['items' => [100, 200, 300]],
                AclActionsInterface::ACCOUNTMGR_BULK_EDIT,
            ],
            'deleteSingle' => [
                'accountManager/delete/100',
                'get',
                'delete',
                [],
                AclActionsInterface::ACCOUNTMGR_DELETE,
            ],
            'deleteMultiple' => [
                'accountManager/delete',
                'post',
                'deleteByIdBatch',
                ['items' => [100, 200, 300]],
                AclActionsInterface::ACCOUNTMGR_DELETE,
            ],
            'saveBulkEdit' => [
                'accountManager/saveBulkEdit',
                'post',
                'updateBulk',
                ['itemsId' => '100,200,300'],
                AclActionsInterface::ACCOUNTMGR_BULK_EDIT,
            ],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[DataProvider('accountManagerMutationActionsProvider')]
    public function accountManagerMutationWithoutActionIsRejected(
        string  $route,
        string  $method,
        ?string $serviceMethod,
        array   $paramsPost,
        int     $deniedAction
    ): void {
        $acl = $this->createMock(AclInterface::class);
        $acl->method('checkUserAccess')
            ->willReturnCallback(static fn(int $actionId) => $actionId !== $deniedAction);

        $accountService = $this->createMock(AccountService::class);

        if ($serviceMethod !== null) {
            $accountService->expects(self::never())->method($serviceMethod);
        }

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route], $paramsPost),
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
    public function deleteMultipleWithoutVisibilityIsRejected(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('isVisible')->willReturn(false);
        $accountRepository->expects(self::never())->method('deleteByIdBatch');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'accountManager/delete'],
                ['items' => [100, 200, 300]]
            ),
            [AccountRepository::class => $accountRepository]
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputRegex("/You don't have permission to access this account/");
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectConfigParam]
    public function saveBulkEditWithoutVisibilityIsRejected(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('isVisible')->willReturn(false);
        $accountRepository->expects(self::never())->method('updateBulk');

        $accountHistoryService = $this->createMock(AccountHistoryService::class);
        $accountHistoryService->expects(self::never())->method('deleteByAccountIdBatch');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'accountManager/saveBulkEdit'],
                [
                    'itemsId' => '100,200,300',
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
                    'delete_history' => 'true',
                ]
            ),
            [
                AccountRepository::class => $accountRepository,
                AccountHistoryService::class => $accountHistoryService,
            ]
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputRegex("/You don't have permission to access this account/");
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerBulkEdit')]
    public function bulkEdit()
    {
        $this->addDatabaseMapperResolver(
            User::class,
            QueryResult::withTotalNumRows([UserDataGenerator::factory()->buildUserData()], 1)
        );

        $this->addDatabaseMapperResolver(
            UserGroup::class,
            QueryResult::withTotalNumRows([UserGroupGenerator::factory()->buildUserGroupData()], 1)
        );

        $this->addDatabaseMapperResolver(
            Client::class,
            QueryResult::withTotalNumRows([ClientGenerator::factory()->buildClient()], 1)
        );

        $this->addDatabaseMapperResolver(
            Tag::class,
            QueryResult::withTotalNumRows([TagGenerator::factory()->buildTag()], 1)
        );

        $this->addDatabaseMapperResolver(
            Category::class,
            QueryResult::withTotalNumRows([CategoryGenerator::factory()->buildCategory()], 1)
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'accountManager/bulkEdit'],
                ['items' => [100, 200, 300]]
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
    public function deleteSingle()
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountManager/delete/100'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Account removed","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteMultiple()
    {
        $this->databaseQueryResolver = function (QueryData $queryData): QueryResult {
            $statement = $queryData->getQuery()->getStatement();

            if (str_contains($statement, "SELECT\n    `Account`.`id`\nFROM\n    `Account`")) {
                return new QueryResult([new Simple(['id' => 1])]);
            }

            /** @noinspection SqlWithoutWhere */
            if (str_starts_with($statement, 'DELETE `Account`')) {
                return new QueryResult([], 1);
            }

            if ($queryData->getMapClassName() === AccountView::class) {
                $accountView = AccountDataGenerator::factory()
                                                   ->buildAccountDataView();

                return new QueryResult([$accountView]);
            } elseif ($queryData->getMapClassName() === Account::class) {
                $account = AccountDataGenerator::factory()
                                               ->buildAccount();

                return new QueryResult([$account]);
            } elseif ($queryData->getMapClassName() === Config::class) {
                return new QueryResult([new Config(['parameter' => 'masterPwd', 'value' => 'a_pass'])]);
            }

            return new QueryResult([], 1, 100);
        };

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'accountManager/delete'],
                ['items' => [100, 200, 300]]
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Accounts removed","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectConfigParam]
    public function saveBulkEdit()
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

        $paramsPost = [
            'itemsId' => '100,200,300',
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
            'delete_history' => 'true'
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'accountManager/saveBulkEdit'],
                $paramsPost
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Accounts updated","data":null}');
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
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            AccountSearchView::class,
            QueryResult::withTotalNumRows(
                [
                    $accountDataGenerator->buildAccountSearchView(),
                    $accountDataGenerator->buildAccountSearchView()
                ],
                2
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountManager/search', 'search' => 'test'])
        );

        IntegrationTestCase::runApp($container);
    }

    private function outputCheckerBulkEdit(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]//form[@name="frmAccountBulkEdit"]//select|//input|//div[@class="action-in-box"]/button'
        )->extract(['_name']);

        self::assertCount(19, $filter);
        self::assertEquals('OK', $json->status);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerSearch(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//table/tbody[@id="data-rows-tblAccounts"]//tr[string-length(@data-item-id) > 0]'
        )->extract(['data-item-id']);

        self::assertCount(2, $filter);
        self::assertEquals('OK', $json->status);
    }
}
