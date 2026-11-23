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

namespace SP\Modules\Web\Controllers\ItemPreset;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\ItemPreset\Models\SessionTimeout;
use SP\Domain\ItemPreset\Models\ItemPreset;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\ItemPresetDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the ItemPreset web area.
 */
#[Group('integration')]
class ItemPresetTest extends IntegrationTestCase
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
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'itemPreset/create/'.ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT]
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
    #[BodyChecker('outputCheckerDeleteMultiple')]
    public function deleteMultiple()
    {
        $this->databaseQueryResolver = fn($queryData) => new QueryResult(null, 3);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'itemPreset/delete', 'items' => [100, 200, 300]]
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'itemPreset/delete/100'])
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
            ItemPreset::class,
            new QueryResult([$this->buildItemPreset()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'itemPreset/edit/100'])
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
                ['r' => 'itemPreset/saveCreate'],
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
                ['r' => 'itemPreset/saveEdit/100'],
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
        $this->addDatabaseMapperResolver(
            ItemPreset::class,
            QueryResult::withTotalNumRows(
                [
                    $this->buildItemPreset(),
                    $this->buildItemPreset()
                ],
                2
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'itemPreset/search', 'search' => 'test']
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
    #[BodyChecker('outputCheckerForm')]
    public function view()
    {
        $this->addDatabaseMapperResolver(
            ItemPreset::class,
            new QueryResult([$this->buildItemPreset()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'itemPreset/view/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    private function buildItemPreset(): ItemPreset
    {
        $generator = ItemPresetDataGenerator::factory();

        return $generator->buildItemPresetData(new SessionTimeout('127.0.0.1', 300))
                         ->mutate([
                             'id' => 100,
                             'type' => ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT
                         ]);
    }

    private function buildFormData(): array
    {
        return [
            'type' => ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT,
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'timeout' => 300,
            'fixed_enabled' => self::$faker->boolean(),
            'priority' => self::$faker->randomNumber(2),
        ];
    }

    private function outputCheckerForm(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        $crawler = new Crawler($json['data']['html']);
        $filter = $crawler->filterXPath('//div[@id="box-popup"]//form[@name="frmItemPreset"]');

        self::assertCount(1, $filter);
    }

    private function outputCheckerDeleteMultiple(string $output): void
    {
        $this->assertSuccessDescription($output, 'Values deleted');
    }

    private function outputCheckerDeleteSingle(string $output): void
    {
        $this->assertSuccessDescription($output, 'Value deleted');
    }

    private function outputCheckerSaveCreate(string $output): void
    {
        $this->assertSuccessDescription($output, 'Value created');
    }

    private function outputCheckerSaveEdit(string $output): void
    {
        $this->assertSuccessDescription($output, 'Value updated');
    }

    private function outputCheckerSearch(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        self::assertStringContainsString('tblItemPreset', $json['data']['html']);
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
