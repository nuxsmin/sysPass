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

namespace SP\Modules\Web\Controllers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Models\AccountHistory;
use SP\Domain\Common\Models\Item;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Mvc\View\OutputHandlerInterface;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ViewHistoryControllerTest
 */
#[Group('integration')]
class ViewHistoryControllerTest extends IntegrationTestCase
{

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidClassException
     * @throws FileException
     * @throws ContainerExceptionInterface
     */
    public function testViewHistoryAction()
    {
        $accountHistory = AccountDataGenerator::factory()
                                              ->buildAccountHistoryData()
                                              ->mutate([
                                                           'userName' => self::$faker->userName(),
                                                           'userGroupName' => self::$faker->userName(),
                                                           'userEditName' => self::$faker->userName(),
                                                           'userEditLogin' => self::$faker->userName(),
                                                       ]);
        $this->addDatabaseResolver(
            AccountHistory::class,
            new QueryResult([$accountHistory])
        );

        $this->addDatabaseResolver(
            Item::class,
            new QueryResult(
                [
                    new Item(
                        ['id' => self::$faker->randomNumber(3), 'name' => self::$faker->colorName()]
                    )
                ]
            )
        );
        $definitions = $this->getModuleDefinitions();
        $definitions[OutputHandlerInterface::class] = $this->setupOutputHandler(function (string $output): void {
            $crawler = new Crawler($output);
            $filter = $crawler->filterXPath(
                '//div[@class="data-container"]//form[@name="frmaccount"]|//div[@class="item-actions"]//button'
            )->extract(['id']);

            assert(!empty($output));
            assert(count($filter) === 2);

            $this->assertTrue(true);
        });

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'account/viewHistory/id/' . self::$faker->randomNumber(3)])
        );

        $this->runApp($container);
    }
}
