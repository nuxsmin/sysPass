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
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Mvc\View\OutputHandlerInterface;
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
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidClassException
     * @throws FileException
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function deleteSingle()
    {
        $this->addDatabaseMapperResolver(
            AccountHistory::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountHistoryData()])
        );

        $definitions = $this->getModuleDefinitions();

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/delete/100'])
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":"Account removed","data":[],"messages":[]}');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidClassException
     * @throws FileException
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function deleteMultiple()
    {
        $this->addDatabaseMapperResolver(
            AccountHistory::class,
            new QueryResult([AccountDataGenerator::factory()->buildAccountHistoryData()])
        );

        $definitions = $this->getModuleDefinitions();

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest(
                'post',
                'index.php',
                ['r' => 'accountHistoryManager/delete'],
                ['items' => [100, 200, 300]]
            )
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":"Accounts removed","data":[],"messages":[]}');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidClassException
     * @throws FileException
     * @throws ContainerExceptionInterface
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

        $definitions = $this->getModuleDefinitions();

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/restore/100'])
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":"Account restored","data":[],"messages":[]}');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidClassException
     * @throws FileException
     * @throws ContainerExceptionInterface
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

        $definitions = $this->getModuleDefinitions();

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/restore/100'])
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":"Account restored","data":[],"messages":[]}');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidClassException
     * @throws FileException
     * @throws ContainerExceptionInterface
     */
    #[Test]
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

        $definitions = $this->getModuleDefinitions();
        $definitions[OutputHandlerInterface::class] = $this->setupOutputHandler(function (string $output): void {
            $crawler = new Crawler($output);
            $filter = $crawler->filterXPath(
                '//table/tbody[@id="data-rows-tblAccountsHistory"]//tr[string-length(@data-item-id) > 0]'
            )
                              ->extract(['data-item-id']);

            assert(!empty($output));
            assert(count($filter) === 2);

            $this->assertTrue(true);
        });

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountHistoryManager/search'])
        );

        $this->runApp($container);
    }
}
