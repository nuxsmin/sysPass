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

namespace SPT\Domain\Tag\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\Services\Tag;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\ItemSearchDataGenerator;
use SPT\Generators\TagGenerator;
use SPT\UnitaryTestCase;

/**
 * Class TagTest
 */
#[Group('unitary')]
class TagTest extends UnitaryTestCase
{

    private Tag                      $tag;
    private MockObject|TagRepository $tagRepository;

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $this->tagRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult(null, 1));

        $this->tag->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteWithNotFound()
    {
        $id = self::$faker->randomNumber();

        $this->tagRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult());

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Tag not found');

        $this->tag->delete($id);
    }

    /**
     * @throws NoSuchItemException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $tag = TagGenerator::factory()->buildTag();

        $this->tagRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$tag]));

        $out = $this->tag->getById($id);

        $this->assertEquals($tag, $out);
    }

    /**
     * @throws NoSuchItemException
     */
    public function testGetByIdWithUnknownId()
    {
        $id = self::$faker->randomNumber();

        $this->tagRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Tag not found');

        $this->tag->getById($id);
    }

    /**
     * @throws NoSuchItemException
     */
    public function testGetByName()
    {
        $name = self::$faker->colorName();

        $tag = TagGenerator::factory()->buildTag();

        $this->tagRepository
            ->expects(self::once())
            ->method('getByName')
            ->with($name)
            ->willReturn(new QueryResult([$tag]));

        $out = $this->tag->getByName($name);

        $this->assertEquals($tag, $out);
    }

    /**
     * @throws NoSuchItemException
     */
    public function testGetByNameWithUnknownName()
    {
        $name = self::$faker->colorName();

        $this->tagRepository
            ->expects(self::once())
            ->method('getByName')
            ->with($name)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Tag not found');

        $this->tag->getByName($name);
    }

    public function testGetAll()
    {
        $tag = TagGenerator::factory()->buildTag();

        $this->tagRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([$tag]));

        $out = $this->tag->getAll();

        $this->assertEquals([$tag], $out);
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testCreate()
    {
        $tag = TagGenerator::factory()->buildTag();

        $queryResult = new QueryResult(null, 0, self::$faker->randomNumber());

        $this->tagRepository
            ->expects(self::once())
            ->method('create')
            ->with($tag)
            ->willReturn($queryResult);

        $out = $this->tag->create($tag);

        $this->assertEquals($queryResult->getLastId(), $out);
    }

    /**
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $this->tagRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(new QueryResult(null, 5));

        $this->tag->deleteByIdBatch($ids);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testDeleteByIdBatchError()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $this->tagRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while removing the tags');

        $this->tag->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $tag = TagGenerator::factory()->buildTag();

        $this->tagRepository
            ->expects(self::once())
            ->method('update')
            ->with($tag)
            ->willReturn(1);

        $this->tag->update($tag);
    }

    public function testSearch()
    {
        $itemSearch = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->tagRepository
            ->expects(self::once())
            ->method('search')
            ->with($itemSearch);

        $this->tag->search($itemSearch);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagRepository = $this->createMock(TagRepository::class);

        $this->tag = new Tag($this->application, $this->tagRepository);
    }


}
