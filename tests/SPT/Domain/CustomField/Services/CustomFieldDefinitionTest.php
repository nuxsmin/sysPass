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

namespace SPT\Domain\CustomField\Services;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionRepository;
use SP\Domain\CustomField\Services\CustomFieldDefinition;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\CustomFieldDefinitionGenerator;
use SPT\Generators\ItemSearchDataGenerator;
use SPT\UnitaryTestCase;
use TypeError;

/**
 * Class CustomFieldDefinitionTest
 *
 * @group unitary
 */
class CustomFieldDefinitionTest extends UnitaryTestCase
{

    private CustomFieldDefinitionRepository|MockObject $customFieldDefinitionRepository;
    private CustomFieldDefinition                      $customFieldDefinition;

    /**
     * @throws NoSuchItemException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $customFieldDefinition = CustomFieldDefinitionGenerator::factory()->buildCustomFieldDefinition();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $out = $this->customFieldDefinition->getById($id);

        $this->assertEquals($customFieldDefinition, $out);
    }

    /**
     * @throws NoSuchItemException
     */
    public function testGetByIdWithUnknownId()
    {
        $id = self::$faker->randomNumber();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Field not found');

        $this->customFieldDefinition->getById($id);
    }

    /**
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $queryResult = new QueryResult();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(new Callback(fn(callable $callable) => $callable() === null));

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn($queryResult->setAffectedNumRows(5));

        $this->customFieldDefinition->deleteByIdBatch($ids);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testDeleteByIdBatchError()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(
                new Callback(function (callable $callable) {
                    try {
                        $callable();
                    } catch (ServiceException $e) {
                        return $e->getMessage() === 'Error while deleting the fields';
                    }

                    return false;
                })
            );

        $queryResult = new QueryResult();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn($queryResult->setAffectedNumRows(0));

        $this->customFieldDefinition->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $customFieldDefinition = CustomFieldDefinitionGenerator::factory()->buildCustomFieldDefinition();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('update')
            ->with($customFieldDefinition)
            ->willReturn(1);

        $this->customFieldDefinition->update($customFieldDefinition);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult([1]);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn($queryResult->setAffectedNumRows(1));

        $this->customFieldDefinition->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteWithNotFound()
    {
        $id = self::$faker->randomNumber();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Field not found');

        $this->customFieldDefinition->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearch = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('search')
            ->with($itemSearch);

        $this->customFieldDefinition->search($itemSearch);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $customFieldDefinition = CustomFieldDefinitionGenerator::factory()->buildCustomFieldDefinition();

        $queryResult = new QueryResult();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('create')
            ->with($customFieldDefinition)
            ->willReturn($queryResult->setLastId(100));

        $this->assertEquals(100, $this->customFieldDefinition->create($customFieldDefinition));
    }

    /**
     * @throws ServiceException
     */
    public function testChangeModule()
    {
        $customFieldDefinition = CustomFieldDefinitionGenerator::factory()->buildCustomFieldDefinition();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(
                new Callback(static function (callable $callable) {
                    $result = $callable();

                    return is_a($result, QueryResult::class) && $result->getLastId() === 100;
                })
            )
            ->willReturn(100);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('delete')
            ->with($customFieldDefinition->getId());

        $queryResult = new QueryResult();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('create')
            ->with($customFieldDefinition)
            ->willReturn($queryResult->setLastId(100));

        $out = $this->customFieldDefinition->changeModule($customFieldDefinition);

        self::assertEquals(100, $out);
    }

    public function testGetAll()
    {
        $customFieldDefinition = CustomFieldDefinitionGenerator::factory()->buildCustomFieldDefinition();

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $out = $this->customFieldDefinition->getAll();

        $this->assertEquals([$customFieldDefinition], $out);
    }

    public function testGetAllWithInvalidClass()
    {
        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([1]));

        $this->expectException(TypeError::class);

        $this->customFieldDefinition->getAll();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldDefinitionRepository = $this->createMock(CustomFieldDefinitionRepository::class);

        $this->customFieldDefinition = new CustomFieldDefinition(
            $this->application,
            $this->customFieldDefinitionRepository
        );
    }


}
