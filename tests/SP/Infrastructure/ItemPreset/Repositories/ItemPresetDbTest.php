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

namespace SP\Tests\Infrastructure\ItemPreset\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Infrastructure\ItemPreset\Repositories\ItemPreset;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the ItemPreset repository queries.
 */
#[Group('integration')]
final class ItemPresetDbTest extends DatabaseRepositoryTestCase
{
    private ItemPreset $itemPreset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemPreset = new ItemPreset($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function searchReturnsThePresetsOrderedByScore(): void
    {
        $result = $this->itemPreset->search(new ItemSearchDto());

        self::assertSame(5, $result->getNumRows());
        self::assertSame(5, $result->getTotalNumRows());
        self::assertSame([21, 12, 11, 3, 3], array_map(
            static fn(ItemPresetModel $preset) => $preset->getScore(),
            $result->getDataAsArray(ItemPresetModel::class)
        ));
    }

    #[Test]
    public function searchFiltersByUserNameAndPaginates(): void
    {
        $page = $this->itemPreset->search(new ItemSearchDto('demo', 0, 1));

        self::assertSame(1, $page->getNumRows());
        self::assertSame(1, $page->getTotalNumRows());
        self::assertSame(4, $page->getDataAsArray(ItemPresetModel::class)[0]->getId());
    }

    #[Test]
    public function createPersistsThePresetWithItsHash(): void
    {
        $preset = new ItemPresetModel([
            'type' => 'permission',
            'userId' => 3,
            'fixed' => 0,
            'priority' => 7,
        ]);

        $result = $this->itemPreset->create($preset);

        self::assertGreaterThan(0, $result->getLastId());

        $stored = $this->itemPreset->getById($result->getLastId())->getData(ItemPresetModel::class);

        self::assertSame('permission', $stored->getType());
        self::assertSame(3, $stored->getUserId());
        self::assertSame(7, $stored->getPriority());
        self::assertSame(
            $stored->getHash(),
            $this->fetchValue(sprintf('SELECT hash FROM ItemPreset WHERE id = %d', $result->getLastId()))
        );
    }

    #[Test]
    public function createRejectsADuplicatedHash(): void
    {
        $this->expectException(ConstraintException::class);

        $this->itemPreset->create(new ItemPresetModel([
            'type' => 'permission',
            'userId' => 1,
            'fixed' => 0,
            'priority' => 0,
        ]));
    }

    #[Test]
    public function updateChangesThePresetAndRecomputesTheHash(): void
    {
        $preset = new ItemPresetModel([
            'id' => 1,
            'type' => 'permission',
            'userId' => 1,
            'fixed' => 0,
            'priority' => 7,
        ]);

        self::assertSame(1, $this->itemPreset->update($preset));

        $stored = $this->itemPreset->getById(1)->getData(ItemPresetModel::class);

        self::assertSame(7, $stored->getPriority());
        self::assertSame(
            $preset->getHash(),
            $this->fetchValue('SELECT hash FROM ItemPreset WHERE id = 1')
        );
    }
}
