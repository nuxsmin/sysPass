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
use SP\Domain\Plugin\Models\PluginData as PluginDataModel;
use SP\Infrastructure\Plugin\Repositories\PluginData;
use SP\Tests\DatabaseRepositoryTestCase;
use SP\Tests\Generators\PluginDataGenerator;

/**
 * DB-backed tests for the PluginData repository queries.
 */
#[Group('integration')]
final class PluginDataDbTest extends DatabaseRepositoryTestCase
{
    private PluginData $pluginData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginData = new PluginData(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function createPersistsThePluginData(): void
    {
        $this->pluginData->create(
            PluginDataGenerator::factory()->buildPluginData()->mutate([
                'name' => 'Authenticator',
                'itemId' => 4,
                'data' => 'encrypted-data',
                'key' => 'encryption-key',
            ])
        );

        $pluginData = $this->pluginData->getByItemId('Authenticator', 4)->getData(PluginDataModel::class);

        self::assertSame('encrypted-data', $pluginData->getData());
        self::assertSame('encryption-key', $pluginData->getKey());
    }

    #[Test]
    public function updateChangesTheDataMatchedByNameAndItemId(): void
    {
        $affected = $this->pluginData->update(
            PluginDataGenerator::factory()->buildPluginData()->mutate([
                'name' => 'Authenticator',
                'itemId' => 1,
                'data' => 'updated-data',
                'key' => 'updated-key',
            ])
        );

        self::assertSame(1, $affected);

        $pluginData = $this->pluginData->getByItemId('Authenticator', 1)->getData(PluginDataModel::class);

        self::assertSame('updated-data', $pluginData->getData());
        self::assertSame('updated-key', $pluginData->getKey());
    }

    #[Test]
    public function deleteRemovesEveryRowOfThePlugin(): void
    {
        $this->pluginData->delete('Authenticator');

        self::assertSame(0, $this->pluginData->getByName('Authenticator')->getNumRows());
        self::assertSame(1, $this->pluginData->getByName('DokuWiki')->getNumRows());
    }

    #[Test]
    public function deleteByItemIdRemovesOnlyTheSelectedRow(): void
    {
        $this->pluginData->deleteByItemId('Authenticator', 2);

        self::assertSame(0, $this->pluginData->getByItemId('Authenticator', 2)->getNumRows());

        $remaining = $this->pluginData->getByName('Authenticator')->getDataAsArray(PluginDataModel::class);

        self::assertCount(1, $remaining);
        self::assertSame(1, $remaining[0]->getItemId());
    }

    #[Test]
    public function getByNameReturnsEveryRowOfThePlugin(): void
    {
        $items = $this->pluginData->getByName('Authenticator')->getDataAsArray(PluginDataModel::class);

        self::assertSame([1, 2], array_map(static fn(PluginDataModel $item) => $item->getItemId(), $items));

        self::assertSame(0, $this->pluginData->getByName('missing')->getNumRows());
    }

    #[Test]
    public function getAllReturnsEveryRowOrderedByName(): void
    {
        self::assertSame(
            ['Authenticator', 'Authenticator', 'DokuWiki', 'XML Exporter'],
            array_map(
                static fn(PluginDataModel $item) => $item->getName(),
                $this->pluginData->getAll()->getDataAsArray(PluginDataModel::class)
            )
        );
    }

    #[Test]
    public function getByNameBatchReturnsTheSelectedPluginsRows(): void
    {
        self::assertSame(
            3,
            $this->pluginData->getByNameBatch(['Authenticator', 'DokuWiki'])->getNumRows()
        );
    }

    #[Test]
    public function getByNameBatchWithAnEmptyListReturnsAnEmptyResult(): void
    {
        self::assertSame(0, $this->pluginData->getByNameBatch([])->getNumRows());
    }

    #[Test]
    public function deleteByNameBatchRemovesTheSelectedPluginsRows(): void
    {
        $this->pluginData->deleteByNameBatch(['Authenticator', 'XML Exporter']);

        $remaining = $this->pluginData->getAll()->getDataAsArray(PluginDataModel::class);

        self::assertCount(1, $remaining);
        self::assertSame('DokuWiki', $remaining[0]->getName());
    }

    #[Test]
    public function deleteByNameBatchWithAnEmptyListReturnsAnEmptyResult(): void
    {
        self::assertSame(0, $this->pluginData->deleteByNameBatch([])->getAffectedNumRows());
    }

    #[Test]
    public function getByItemIdReturnsTheRow(): void
    {
        $pluginData = $this->pluginData->getByItemId('DokuWiki', 3)->getData(PluginDataModel::class);

        self::assertSame('DokuWiki', $pluginData->getName());
        self::assertSame(3, $pluginData->getItemId());
        self::assertNotEmpty($pluginData->getData());

        self::assertSame(0, $this->pluginData->getByItemId('DokuWiki', 999)->getNumRows());
    }
}
