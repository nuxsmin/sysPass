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

namespace SP\Tests\Modules\Web\Controllers\AccountHistoryManager;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountHistory;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Config\Models\Config;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AccountHistoryManagerTest
 */
#[Group('integration')]
class AccountHistoryManagerTest extends IntegrationTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteSingle()
    {
        $this->addDatabaseMapperResolver(
            AccountHistory::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountHistoryData()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/delete/100'])
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
        $this->addDatabaseMapperResolver(
            AccountHistory::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountHistoryData()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'accountHistoryManager/delete'],
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
    public function restoreModified()
    {
        $this->databaseQueryResolver = function (QueryData $queryData): QueryResult {
            /** @noinspection SqlWithoutWhere */
            if (str_starts_with($queryData->getQuery()->getStatement(), 'UPDATE `Account`')) {
                return new QueryResult([], 1);
            }

            if ($queryData->getMapClassName() === AccountHistory::class) {
                $accountHistory = AccountDataGenerator::factory()
                                                      ->buildAccountHistoryData()
                                                      ->mutate(
                                                          ['isModify' => 1, 'isDeleted' => 0]
                                                      );

                return new QueryResult([$accountHistory]);
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/restore/100'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Account restored","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function restoreDeleted()
    {
        $this->databaseQueryResolver = function (QueryData $queryData): QueryResult {
            /** @noinspection SqlWithoutWhere */
            if (str_starts_with($queryData->getQuery()->getStatement(), 'UPDATE `Account`')) {
                return new QueryResult([], 1);
            }

            if ($queryData->getMapClassName() === AccountHistory::class) {
                $accountHistory = AccountDataGenerator::factory()
                                                      ->buildAccountHistoryData()
                                                      ->mutate(
                                                          ['isModify' => 0, 'isDeleted' => 1]
                                                      );

                return new QueryResult([$accountHistory]);
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/restore/100'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Account restored","data":null}');
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
            Simple::class,
            QueryResult::withTotalNumRows(
                [
                    $accountDataGenerator->buildAccountHistoryData(),
                    $accountDataGenerator->buildAccountHistoryData()
                ],
                2
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/search'])
        );

        IntegrationTestCase::runApp($container);
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
            '//table/tbody[@id="data-rows-tblAccountsHistory"]//tr[string-length(@data-item-id) > 0]'
        )
                          ->extract(['data-item-id']);

        self::assertCount(2, $filter);
        self::assertEquals('OK', $json->status);
    }
}
