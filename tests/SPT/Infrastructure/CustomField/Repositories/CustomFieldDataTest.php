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

namespace SPT\Infrastructure\CustomField\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use Exception;
use PHPUnit\Framework\Constraint\Callback;
use SP\Domain\Common\Models\Simple as SimpleModel;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Infrastructure\CustomField\Repositories\CustomFieldData;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\CustomFieldDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class CustomFieldDataTest
 *
 * @group unitary
 */
class CustomFieldDataTest extends UnitaryTestCase
{

    private CustomFieldData $customFieldData;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteBatch()
    {
        $itemIds = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];
        $moduleId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($itemIds, $moduleId) {
                $query = $arg->getQuery();
                $bindValues = $query->getBindValues();

                return count($bindValues) === 2
                       && $bindValues['itemIds'] === $itemIds
                       && $bindValues['moduleId'] === $moduleId
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback);

        $this->customFieldData->deleteBatch($itemIds, $moduleId);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteBatchWithNoItems()
    {
        $moduleId = self::$faker->randomNumber();

        $this->database
            ->expects(self::never())
            ->method('doQuery');

        $result = $this->customFieldData->deleteBatch([], $moduleId);

        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckExists()
    {
        $itemId = self::$faker->randomNumber();
        $moduleId = self::$faker->randomNumber();
        $definitionId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($itemId, $moduleId, $definitionId) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['moduleId'] === $moduleId
                       && $params['itemId'] === $itemId
                       && $params['definitionId'] === $definitionId
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([1]));

        self::assertTrue($this->customFieldData->checkExists($itemId, $moduleId, $definitionId));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckExistsWithNoResults()
    {
        $itemId = self::$faker->randomNumber();
        $moduleId = self::$faker->randomNumber();
        $definitionId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($itemId, $moduleId, $definitionId) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['moduleId'] === $moduleId
                       && $params['itemId'] === $itemId
                       && $params['definitionId'] === $definitionId
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([]));

        self::assertFalse($this->customFieldData->checkExists($itemId, $moduleId, $definitionId));
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === CustomFieldDataModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->customFieldData->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData(true);

        $callback = new Callback(
            static function (QueryData $arg) use ($customFieldData) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 5
                       && $params['itemId'] === $customFieldData->getItemId()
                       && $params['moduleId'] === $customFieldData->getModuleId()
                       && $params['definitionId'] === $customFieldData->getDefinitionId()
                       && $params['data'] === $customFieldData->getData()
                       && $params['key'] === $customFieldData->getKey()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([]));

        $this->customFieldData->create($customFieldData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData(true);

        $callback = new Callback(
            static function (QueryData $arg) use ($customFieldData) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 5
                       && $params['itemId'] === $customFieldData->getItemId()
                       && $params['moduleId'] === $customFieldData->getModuleId()
                       && $params['definitionId'] === $customFieldData->getDefinitionId()
                       && $params['data'] === $customFieldData->getData()
                       && $params['key'] === $customFieldData->getKey()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([]));

        $this->customFieldData->update($customFieldData);
    }

    /**
     * @throws Exception
     */
    public function testGetForModuleAndItemId()
    {
        $itemId = self::$faker->randomNumber();
        $moduleId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($moduleId, $itemId) {
                $query = $arg->getQuery();
                $values = $query->getBindValues();

                return $values['moduleId'] === $moduleId
                       && $values['itemId'] === $itemId
                       && $arg->getMapClassName() === SimpleModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->customFieldData->getForModuleAndItemId($moduleId, $itemId);
    }

    public function testGetAllEncrypted()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === CustomFieldDataModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->customFieldData->getAllEncrypted();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->customFieldData = new CustomFieldData(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
