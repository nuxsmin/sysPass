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

namespace SP\Tests\Infrastructure\Category\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Category\Models\Category as CategoryModel;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\DuplicatedItemException;
use SP\Infrastructure\Category\Repositories\Category;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the Category repository queries.
 */
#[Group('integration')]
final class CategoryDbTest extends DatabaseRepositoryTestCase
{
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = new Category(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function createPersistsTheCategoryAndItsNormalisedHash(): void
    {
        $this->category->create(new CategoryModel([
            'name' => 'Storage',
            'description' => 'Storage services',
        ]));

        $category = $this->category->getByName('Storage')->getData(CategoryModel::class);

        self::assertSame('Storage', $category->getName());
        self::assertSame('Storage services', $category->getDescription());
        self::assertSame(sha1('storage'), $category->getHash());
    }

    #[Test]
    public function createRejectsADuplicatedName(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->category->create(new CategoryModel(['name' => 'Web']));
    }

    #[Test]
    public function updateRenamesTheCategoryAndRecomputesTheHash(): void
    {
        $affected = $this->category->update(new CategoryModel([
            'id' => 3,
            'name' => 'Remote',
            'description' => 'Remote access',
        ]));

        self::assertSame(1, $affected);

        $category = $this->category->getById(3)->getData(CategoryModel::class);

        self::assertSame('Remote', $category->getName());
        self::assertSame('Remote access', $category->getDescription());
        self::assertSame(sha1('remote'), $category->getHash());
    }

    #[Test]
    public function updateRejectsANameUsedByAnotherCategory(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->category->update(new CategoryModel(['id' => 3, 'name' => 'Web']));
    }

    #[Test]
    public function getByIdReturnsTheCategory(): void
    {
        self::assertSame('Web', $this->category->getById(1)->getData(CategoryModel::class)->getName());
        self::assertSame(0, $this->category->getById(999)->getNumRows());
    }

    #[Test]
    public function getByNameMatchesTheNameOrTheHashIgnoringCase(): void
    {
        self::assertSame(1, $this->category->getByName('Web')->getData(CategoryModel::class)->getId());
        self::assertSame(1, $this->category->getByName('web')->getData(CategoryModel::class)->getId());
        self::assertSame(0, $this->category->getByName('missing')->getNumRows());
    }

    #[Test]
    public function getAllReturnsEveryCategory(): void
    {
        $names = array_map(
            static fn(CategoryModel $category) => $category->getName(),
            $this->category->getAll()->getDataAsArray(CategoryModel::class)
        );

        sort($names);

        self::assertSame(['Linux', 'SSH', 'Web'], $names);
    }

    #[Test]
    public function deleteByIdBatchRemovesOnlyTheSelectedCategories(): void
    {
        $this->category->create(new CategoryModel(['name' => 'Temporary A']));
        $this->category->create(new CategoryModel(['name' => 'Temporary B']));

        $ids = [
            $this->category->getByName('Temporary A')->getData(CategoryModel::class)->getId(),
            $this->category->getByName('Temporary B')->getData(CategoryModel::class)->getId(),
        ];

        $this->category->deleteByIdBatch($ids);

        $names = array_map(
            static fn(CategoryModel $category) => $category->getName(),
            $this->category->getAll()->getDataAsArray(CategoryModel::class)
        );

        sort($names);

        self::assertSame(['Linux', 'SSH', 'Web'], $names);

        self::assertSame(0, $this->category->deleteByIdBatch([])->getAffectedNumRows());
    }

    #[Test]
    public function deleteRemovesASingleCategory(): void
    {
        $this->category->create(new CategoryModel(['name' => 'Temporary']));

        $id = $this->category->getByName('Temporary')->getData(CategoryModel::class)->getId();

        $this->category->delete($id);

        self::assertSame(0, $this->category->getById($id)->getNumRows());
        self::assertSame(3, $this->category->getAll()->getNumRows());
    }

    #[Test]
    public function deleteRejectsACategoryInUse(): void
    {
        $this->expectException(ConstraintException::class);

        $this->category->delete(1);
    }

    #[Test]
    public function searchFiltersByNameOrDescriptionAndPaginates(): void
    {
        $result = $this->category->search(new ItemSearchDto('SSH'));

        self::assertSame(1, $result->getNumRows());
        self::assertSame(3, $result->getDataAsArray(CategoryModel::class)[0]->getId());

        $all = $this->category->search(new ItemSearchDto());

        self::assertSame(
            ['Linux', 'SSH', 'Web'],
            array_map(
                static fn(CategoryModel $category) => $category->getName(),
                $all->getDataAsArray(CategoryModel::class)
            )
        );
        self::assertSame(3, $all->getTotalNumRows());

        $page = $this->category->search(new ItemSearchDto(null, 1, 1));

        self::assertSame(
            ['SSH'],
            array_map(
                static fn(CategoryModel $category) => $category->getName(),
                $page->getDataAsArray(CategoryModel::class)
            )
        );
    }
}
