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

namespace SP\Modules\Web\Controllers\Tag;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Tag\Models\Tag;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\TagGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the Tag web area.
 */
#[Group('integration')]
class TagTest extends IntegrationTestCase
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'tag/create'])
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
                ['r' => 'tag/delete', 'items' => [100, 200, 300]]
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'tag/delete/100'])
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
            Tag::class,
            new QueryResult([TagGenerator::factory()->buildTag()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'tag/edit/100'])
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
                ['r' => 'tag/saveCreate'],
                ['name' => self::$faker->colorName()]
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
                ['r' => 'tag/saveEdit/100'],
                ['name' => self::$faker->colorName()]
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
        $tagGenerator = TagGenerator::factory();

        $this->addDatabaseMapperResolver(
            Tag::class,
            QueryResult::withTotalNumRows(
                [
                    $tagGenerator->buildTag(),
                    $tagGenerator->buildTag()
                ],
                2
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'tag/search', 'search' => 'test'])
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
            Tag::class,
            new QueryResult([TagGenerator::factory()->buildTag()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'tag/view/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    private function outputCheckerForm(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        $crawler = new Crawler($json['data']['html']);
        $filter = $crawler->filterXPath('//div[@id="box-popup"]//form[@name="frmTags"]//input[@name="name"]');

        self::assertCount(1, $filter);
    }

    private function outputCheckerDeleteMultiple(string $output): void
    {
        $this->assertSuccessDescription($output, 'Tags deleted');
    }

    private function outputCheckerDeleteSingle(string $output): void
    {
        $this->assertSuccessDescription($output, 'Tag removed');
    }

    private function outputCheckerSaveCreate(string $output): void
    {
        $this->assertSuccessDescription($output, 'Tag added');
    }

    private function outputCheckerSaveEdit(string $output): void
    {
        $this->assertSuccessDescription($output, 'Tag updated');
    }

    private function outputCheckerSearch(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        self::assertStringContainsString('tblTags', $json['data']['html']);
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
