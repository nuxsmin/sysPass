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

namespace SP\Tests\Infrastructure\Tag\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\DuplicatedItemException;
use SP\Domain\Tag\Models\Tag as TagModel;
use SP\Infrastructure\Tag\Repositories\Tag;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the Tag repository queries.
 */
#[Group('integration')]
final class TagDbTest extends DatabaseRepositoryTestCase
{
    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tag = new Tag($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function createPersistsTheTagWithItsHash(): void
    {
        $this->tag->create(new TagModel(['name' => 'Ubuntu']));

        $result = $this->tag->getByName('Ubuntu');

        self::assertSame(1, $result->getNumRows());
        self::assertSame('Ubuntu', $result->getData(TagModel::class)->getName());
        self::assertNotEmpty($result->getData(TagModel::class)->getHash());
    }

    #[Test]
    public function createRejectsADuplicatedName(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->tag->create(new TagModel(['name' => 'www']));
    }

    #[Test]
    public function searchFiltersByNameAndPaginates(): void
    {
        $all = $this->tag->search(new ItemSearchDto('in'));

        self::assertSame(['Linux', 'windows'], array_map(
            static fn(TagModel $tag) => $tag->getName(),
            $all->getDataAsArray(TagModel::class)
        ));

        $page = $this->tag->search(new ItemSearchDto('in', 1, 1));

        self::assertSame(['windows'], array_map(
            static fn(TagModel $tag) => $tag->getName(),
            $page->getDataAsArray(TagModel::class)
        ));
    }

    #[Test]
    public function updateRenamesTheTag(): void
    {
        $affected = $this->tag->update(new TagModel(['id' => 3, 'name' => 'Debian']));

        self::assertSame(1, $affected);
        self::assertSame('Debian', $this->tag->getById(3)->getData(TagModel::class)->getName());
    }

    #[Test]
    public function updateRejectsANameUsedByAnotherTag(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->tag->update(new TagModel(['id' => 3, 'name' => 'www']));
    }

    #[Test]
    public function deleteByIdBatchRemovesOnlyTheSelectedTags(): void
    {
        $this->tag->deleteByIdBatch([1, 2]);

        self::assertSame(
            ['Linux'],
            array_map(
                static fn(TagModel $tag) => $tag->getName(),
                $this->tag->getAll()->getDataAsArray(TagModel::class)
            )
        );
    }

    #[Test]
    public function deleteRemovesASingleTag(): void
    {
        $this->tag->delete(3);

        self::assertSame(0, $this->tag->getById(3)->getNumRows());
    }
}
