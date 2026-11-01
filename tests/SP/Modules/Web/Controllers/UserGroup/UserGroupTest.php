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

namespace SP\Modules\Web\Controllers\UserGroup;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Models\UserGroup;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\UserGroupGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the UserGroup web area.
 */
#[Group('integration')]
class UserGroupTest extends IntegrationTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function create()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userGroup/create'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerDeleteMultiple')]
    public function deleteMultiple()
    {
        $this->databaseQueryResolver = fn($queryData) => new QueryResult(null, 3);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'userGroup/delete', 'items' => [100, 200, 300]]
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
    #[BodyChecker('outputCheckerDeleteSingle')]
    public function deleteSingle()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userGroup/delete/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function edit()
    {
        $this->addDatabaseMapperResolver(
            UserGroup::class,
            new QueryResult([UserGroupGenerator::factory()->buildUserGroupData()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userGroup/edit/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSaveCreate')]
    public function saveCreate()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'userGroup/saveCreate'],
                $this->buildFormData()
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
    #[BodyChecker('outputCheckerSaveEdit')]
    public function saveEdit()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'userGroup/saveEdit/100'],
                $this->buildFormData()
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
        $generator = UserGroupGenerator::factory();

        $this->addDatabaseMapperResolver(
            UserGroup::class,
            QueryResult::withTotalNumRows(
                [
                    $generator->buildUserGroupData(),
                    $generator->buildUserGroupData()
                ],
                2
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userGroup/search', 'search' => 'test'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function view()
    {
        $this->addDatabaseMapperResolver(
            UserGroup::class,
            new QueryResult([UserGroupGenerator::factory()->buildUserGroupData()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userGroup/view/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    private function buildFormData(): array
    {
        return [
            'name' => self::$faker->name(),
            'description' => self::$faker->text(),
            'users' => [],
        ];
    }

    private function outputCheckerForm(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        $crawler = new Crawler($json['data']['html']);
        $filter = $crawler->filterXPath('//div[@id="box-popup"]//form[@name="frmGroups"]//input[@name="name"]');

        self::assertCount(1, $filter);
    }

    private function outputCheckerDeleteMultiple(string $output): void
    {
        $this->assertSuccessDescription($output, 'Groups deleted');
    }

    private function outputCheckerDeleteSingle(string $output): void
    {
        $this->assertSuccessDescription($output, 'Group deleted');
    }

    private function outputCheckerSaveCreate(string $output): void
    {
        $this->assertSuccessDescription($output, 'Group added');
    }

    private function outputCheckerSaveEdit(string $output): void
    {
        $this->assertSuccessDescription($output, 'Group updated');
    }

    private function outputCheckerSearch(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        self::assertStringContainsString('tblGroups', $json['data']['html']);
    }

    private function assertSuccessDescription(string $output, string $description): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        self::assertSame($description, $json['description']);
    }

    /**
     * Accepts both the legacy integer status and the ActionResponse status name.
     */
    private function decodeSuccessfulResponse(string $output): array
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertTrue(in_array($json['status'] ?? null, [0, 'OK'], true), $output);

        return $json;
    }
}
