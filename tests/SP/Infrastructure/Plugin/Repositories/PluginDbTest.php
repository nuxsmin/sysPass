<?php
declare(strict_types=1);
/*
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

namespace SP\Tests\Infrastructure\Plugin\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Plugin\Models\Plugin as PluginModel;
use SP\Infrastructure\Plugin\Repositories\Plugin;
use SP\Tests\DatabaseRepositoryTestCase;
use SP\Tests\Generators\PluginGenerator;

/**
 * DB-backed tests for the Plugin repository queries.
 */
#[Group('integration')]
final class PluginDbTest extends DatabaseRepositoryTestCase
{
    private Plugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new Plugin($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function createPersistsThePlugin(): void
    {
        $result = $this->plugin->create($this->pluginModel([
            'id' => null,
            'name' => 'New Plugin',
            'data' => null,
            'enabled' => true,
            'versionLevel' => '1.0',
        ]));

        self::assertGreaterThan(0, $result->getLastId());

        $plugin = $this->plugin->getByName('New Plugin')->getData(PluginModel::class);

        self::assertSame('New Plugin', $plugin->getName());
        self::assertTrue($plugin->getEnabled());
        self::assertSame('1.0', $plugin->getVersionLevel());
        self::assertNull($plugin->getData());
    }

    #[Test]
    public function updateChangesThePluginMatchedById(): void
    {
        $affected = $this->plugin->update($this->pluginModel([
            'id' => 3,
            'name' => 'DokuWiki 2',
            'data' => 'new data',
            'enabled' => false,
            'versionLevel' => '2.0',
        ]));

        self::assertSame(1, $affected);

        $plugin = $this->plugin->getById(3)->getData(PluginModel::class);

        self::assertSame('DokuWiki 2', $plugin->getName());
        self::assertSame('new data', $plugin->getData());
        self::assertFalse($plugin->getEnabled());
        self::assertSame('2.0', $plugin->getVersionLevel());
    }

    #[Test]
    public function getByIdReturnsThePlugin(): void
    {
        $plugin = $this->plugin->getById(1)->getData(PluginModel::class);

        self::assertSame('Authenticator', $plugin->getName());
        self::assertFalse($plugin->getEnabled());
    }

    #[Test]
    public function getByNameReturnsThePlugin(): void
    {
        self::assertSame(
            'XML Exporter',
            $this->plugin->getByName('XML Exporter')->getData(PluginModel::class)->getName()
        );
        self::assertSame(0, $this->plugin->getByName('missing')->getNumRows());
    }

    #[Test]
    public function getAllReturnsEveryPluginOrderedByName(): void
    {
        self::assertSame(
            ['Authenticator', 'DokuWiki', 'XML Exporter'],
            array_map(
                static fn(PluginModel $plugin) => $plugin->getName(),
                $this->plugin->getAll()->getDataAsArray(PluginModel::class)
            )
        );
    }

    #[Test]
    public function getEnabledReturnsOnlyTheEnabledPlugins(): void
    {
        $ids = array_map(
            static fn(PluginModel $plugin) => $plugin->getId(),
            $this->plugin->getEnabled()->getDataAsArray(PluginModel::class)
        );

        sort($ids);

        self::assertSame([2, 3], $ids);
    }

    #[Test]
    public function getByIdBatchReturnsTheSelectedPlugins(): void
    {
        self::assertSame(
            [1, 3],
            array_map(
                static fn(PluginModel $plugin) => $plugin->getId(),
                $this->plugin->getByIdBatch([1, 3])->getDataAsArray(PluginModel::class)
            )
        );
    }

    #[Test]
    public function getByIdBatchWithAnEmptyListReturnsAnEmptyResult(): void
    {
        $result = $this->plugin->getByIdBatch([]);

        self::assertSame(0, $result->getNumRows());
        self::assertSame(0, $result->getAffectedNumRows());
    }

    #[Test]
    public function deleteByIdBatchRemovesTheSelectedPlugins(): void
    {
        $this->plugin->deleteByIdBatch([1, 3]);

        self::assertSame(
            [2],
            array_map(
                static fn(PluginModel $plugin) => $plugin->getId(),
                $this->plugin->getAll()->getDataAsArray(PluginModel::class)
            )
        );
    }

    #[Test]
    public function deleteByIdBatchWithAnEmptyListReturnsAnEmptyResult(): void
    {
        self::assertSame(0, $this->plugin->deleteByIdBatch([])->getAffectedNumRows());
    }

    #[Test]
    public function deleteRemovesASinglePlugin(): void
    {
        $this->plugin->delete(2);

        self::assertSame(0, $this->plugin->getById(2)->getNumRows());
        self::assertSame(2, $this->plugin->getAll()->getNumRows());
    }

    #[Test]
    public function searchFiltersByNameAndPaginates(): void
    {
        self::assertSame(
            [2],
            array_map(
                static fn(PluginModel $plugin) => $plugin->getId(),
                $this->plugin->search(new ItemSearchDto('XML'))->getDataAsArray(PluginModel::class)
            )
        );

        $page = $this->plugin->search(new ItemSearchDto(null, 1, 1));

        self::assertSame(1, $page->getNumRows());
        self::assertSame(3, $page->getTotalNumRows());
        self::assertSame('DokuWiki', $page->getDataAsArray(PluginModel::class)[0]->getName());
    }

    #[Test]
    public function toggleEnabledChangesTheStateById(): void
    {
        self::assertSame(1, $this->plugin->toggleEnabled(1, true));
        self::assertTrue($this->plugin->getById(1)->getData(PluginModel::class)->getEnabled());
    }

    #[Test]
    public function toggleEnabledByNameChangesTheStateByName(): void
    {
        self::assertSame(1, $this->plugin->toggleEnabledByName('DokuWiki', false));
        self::assertFalse($this->plugin->getByName('DokuWiki')->getData(PluginModel::class)->getEnabled());
    }

    #[Test]
    public function toggleAvailableChangesTheStateAndDisablesThePlugin(): void
    {
        self::assertSame(1, $this->plugin->toggleAvailable(3, true));

        self::assertSame(1, (int)$this->fetchValue('SELECT available FROM Plugin WHERE id = 3'));
        self::assertFalse($this->plugin->getById(3)->getData(PluginModel::class)->getEnabled());
    }

    #[Test]
    public function toggleAvailableByNameChangesTheStateAndDisablesThePlugin(): void
    {
        self::assertSame(1, $this->plugin->toggleAvailableByName('XML Exporter', false));

        self::assertSame(0, (int)$this->fetchValue("SELECT available FROM Plugin WHERE name = 'XML Exporter'"));
        self::assertFalse($this->plugin->getByName('XML Exporter')->getData(PluginModel::class)->getEnabled());
    }

    #[Test]
    public function resetByIdClearsThePluginData(): void
    {
        self::assertSame(1, $this->plugin->resetById(2));

        self::assertNull($this->plugin->getById(2)->getData(PluginModel::class)->getData());
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function pluginModel(array $properties): PluginModel
    {
        return PluginGenerator::factory()->buildPlugin()->mutate($properties);
    }
}
