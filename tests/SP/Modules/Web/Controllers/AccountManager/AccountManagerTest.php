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
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserGroup;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\OutputChecker;
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

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws FileException
     * @throws InvalidClassException
     * @throws ContainerExceptionInterface
     */
    #[Test]
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
            $this->getModuleDefinitions(),
            $this->buildRequest('post', 'index.php', ['r' => 'accountManager/bulkEdit'], ['items' => [100, 200, 300]])
        );

        $this->runApp($container);
    }

    #[OutputChecker]
    private function outputChecker(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]//form[@name="frmAccountBulkEdit"]//select|//input|//div[@class="action-in-box"]/button'
        )->extract(['_name']);

        $this->assertNotEmpty($output);
        $this->assertCount(19, $filter);
    }
}
