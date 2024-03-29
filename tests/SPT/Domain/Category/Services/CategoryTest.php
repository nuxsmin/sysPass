<?php
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

namespace SPT\Domain\Category\Services;

use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Category\Ports\CategoryRepository;
use SP\Domain\Category\Services\Category;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\CategoryGenerator;
use SPT\Generators\ItemSearchDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class CategoryTest
 *
 */
#[Group('unitary')]
class CategoryTest extends UnitaryTestCase
{

    private CategoryRepository|MockObject $categoryRepository;
    private Category                      $category;

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $category = CategoryGenerator::factory()->buildCategory();

        $this->categoryRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$category]));

        $out = $this->category->getById($id);

        $this->assertEquals($category, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByIdWithUnknownId()
    {
        $id = self::$faker->randomNumber();

        $category = CategoryGenerator::factory()->buildCategory();

        $this->categoryRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Category not found');

        $this->category->getById($id);
    }

    /**
     * @throws Exception
     */
    public function testSearch()
    {
        $itemSearch = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->categoryRepository
            ->expects(self::once())
            ->method('search')
            ->with($itemSearch);

        $this->category->search($itemSearch);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $this->categoryRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult(null, 1));

        $this->category->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteWithNotFound()
    {
        $id = self::$faker->randomNumber();

        $this->categoryRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Category not found');

        $this->category->delete($id);
    }

    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreate()
    {
        $category = CategoryGenerator::factory()->buildCategory();

        $queryResult = new QueryResult(null, 0, self::$faker->randomNumber());

        $this->categoryRepository
            ->expects(self::once())
            ->method('create')
            ->with($category)
            ->willReturn($queryResult);

        $out = $this->category->create($category);

        $this->assertEquals($queryResult->getLastId(), $out);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $category = CategoryGenerator::factory()->buildCategory();

        $this->categoryRepository
            ->expects(self::once())
            ->method('update')
            ->with($category)
            ->willReturn(1);

        $this->category->update($category);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $name = self::$faker->colorName();

        $category = CategoryGenerator::factory()->buildCategory();

        $this->categoryRepository
            ->expects(self::once())
            ->method('getByName')
            ->with($name)
            ->willReturn(new QueryResult([$category]));

        $out = $this->category->getByName($name);

        $this->assertEquals($category, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByNameWithUnknownName()
    {
        $name = self::$faker->colorName();

        $this->categoryRepository
            ->expects(self::once())
            ->method('getByName')
            ->with($name)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Category not found');

        $this->category->getByName($name);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $this->categoryRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(new QueryResult(null, 1));

        $this->category->deleteByIdBatch($ids);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testDeleteByIdBatchError()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $this->categoryRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting categories');

        $this->category->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $category = CategoryGenerator::factory()->buildCategory();

        $this->categoryRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([$category]));

        $out = $this->category->getAll();

        $this->assertEquals([$category], $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->category = new Category($this->application, $this->categoryRepository);
    }
}
