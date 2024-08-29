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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Config\Models\Config;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserGroup;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\CategoryGenerator;
use SP\Tests\Generators\ClientGenerator;
use SP\Tests\Generators\TagGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\Generators\UserGroupGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AccountManagerTest
 */
#[Group('integration')]
class AccountManagerTest extends IntegrationTestCase
{
    private array $definitions;

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
            $this->definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'accountManager/bulkEdit'], ['items' => [100, 200, 300]])
        );

        $this->runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
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

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $this->definitions[ConfigService::class] = $configService;

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountManager/delete/100'])
        );

        $this->runApp($container);

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
            /** @noinspection SqlWithoutWhere */
            if (str_starts_with($queryData->getQuery()->getStatement(), 'DELETE `Account`')) {
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
            $this->definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'accountManager/delete'], ['items' => [100, 200, 300]])
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Accounts removed","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
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

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $this->definitions[ConfigService::class] = $configService;

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
            $this->definitions,
            $this->buildRequest(
                'post',
                'index.php',
                ['r' => 'accountManager/saveBulkEdit'],
                $paramsPost
            )
        );

        $this->runApp($container);

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
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountManager/search', 'search' => 'test'])
        );

        $this->runApp($container);
    }

    /**
     * @throws InvalidClassException
     * @throws FileException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->definitions = $this->getModuleDefinitions();
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
