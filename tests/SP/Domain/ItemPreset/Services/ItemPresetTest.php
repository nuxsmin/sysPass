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

namespace SP\Tests\Domain\ItemPreset\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Domain\ItemPreset\Ports\ItemPresetRepository;
use SP\Domain\ItemPreset\Services\ItemPreset;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\ItemPresetDataGenerator;
use SP\Tests\Generators\ItemSearchDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class ItemPresetTest
 *
 */
#[Group('unitary')]
class ItemPresetTest extends UnitaryTestCase
{

    private ItemPresetRepository|MockObject $itemPresetRepository;
    private ItemPreset                      $itemPreset;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData((object)['foo' => 'bar']);

        $this->itemPresetRepository
            ->expects(self::once())
            ->method('update')
            ->with($itemPreset)
            ->willReturn(1);

        $this->itemPreset->update($itemPreset);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForUser()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData((object)['foo' => 'bar']);

        $this->itemPresetRepository
            ->expects(self::once())
            ->method('getByFilter')
            ->with('test', 100, 200, 300)
            ->willReturn(new QueryResult([$itemPreset]));

        $out = $this->itemPreset->getForUser('test', 100, 200, 300);

        $this->assertEquals($itemPreset, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData((object)['foo' => 'bar']);


        $this->itemPresetRepository
            ->expects(self::once())
            ->method('create')
            ->with($itemPreset)
            ->willReturn(new QueryResult(null, 0, 100));

        $this->assertEquals(100, $this->itemPreset->create($itemPreset));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForCurrentUser()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData((object)['foo' => 'bar']);

        $userData = $this->context->getUserData();

        $this->itemPresetRepository
            ->expects(self::once())
            ->method('getByFilter')
            ->with('test', $userData->getId(), $userData->getUserGroupId(), $userData->getUserProfileId())
            ->willReturn(new QueryResult([$itemPreset]));

        $out = $this->itemPreset->getForCurrentUser('test');

        $this->assertEquals($itemPreset, $out);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(static fn() => self::$faker->randomNumber(3), range(0, 4));

        $queryResult = new QueryResult([]);

        $this->itemPresetRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(new QueryResult(null, 5));

        $this->assertEquals(5, $this->itemPreset->deleteByIdBatch($ids));
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithException()
    {
        $ids = array_map(static fn() => self::$faker->randomNumber(3), range(0, 4));

        $this->itemPresetRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(new QueryResult(null, 4));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the values');

        $this->itemPreset->deleteByIdBatch($ids);
    }

    /**
     * @throws Exception
     */
    public function testGetAll()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(ItemPresetModel::class)
                    ->willReturn([1]);

        $this->itemPresetRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($queryResult);

        $out = $this->itemPreset->getAll();

        $this->assertEquals([1], $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     */
    public function testGetById()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData((object)['foo' => 'bar']);

        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getNumRows')
                    ->willReturn(1);

        $queryResult->expects($this->once())
                    ->method('getData')
                    ->with(ItemPresetModel::class)
                    ->willReturn($itemPreset);

        $this->itemPresetRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn($queryResult);

        $out = $this->itemPreset->getById(100);

        $this->assertEquals($itemPreset, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     */
    public function testGetByIdWithNoRows()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getNumRows')
                    ->willReturn(0);

        $this->itemPresetRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Value not found');

        $this->itemPreset->getById(100);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData((object)['foo' => 'bar']);
        $itemSearchData = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $queryResult = new QueryResult([$itemPreset]);

        $this->itemPresetRepository
            ->expects($this->once())
            ->method('search')
            ->with($itemSearchData)
            ->willReturn($queryResult);

        $out = $this->itemPreset->search($itemSearchData);

        $this->assertEquals($queryResult, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getAffectedNumRows')
                    ->willReturn(1);

        $this->itemPresetRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn($queryResult);

        $this->itemPreset->delete(100);
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteWithNoRows()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getAffectedNumRows')
                    ->willReturn(0);

        $this->itemPresetRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Value not found');

        $this->itemPreset->delete(100);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->itemPresetRepository = $this->createMock(ItemPresetRepository::class);

        $this->itemPreset = new ItemPreset($this->application, $this->itemPresetRepository);
    }
}
