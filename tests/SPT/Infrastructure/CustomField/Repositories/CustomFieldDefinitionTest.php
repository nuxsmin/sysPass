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
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Infrastructure\CustomField\Repositories\CustomFieldDefinition;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\CustomFieldDefinitionGenerator;
use SPT\UnitaryTestCase;

/**
 * Class CustomFieldDefinitionTest
 *
 * @group unitary
 */
class CustomFieldDefinitionTest extends UnitaryTestCase
{

    private CustomFieldDefinition        $customFieldDefinition;
    private DatabaseInterface|MockObject $database;

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === CustomFieldDefinitionModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->customFieldDefinition->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $customFieldDefinition = CustomFieldDefinitionGenerator::factory()->buildCustomFieldDefinition();

        $callback = new Callback(
            static function (QueryData $arg) use ($customFieldDefinition) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 7
                       && $params['name'] === $customFieldDefinition->getName()
                       && $params['moduleId'] === $customFieldDefinition->getModuleId()
                       && $params['required'] === $customFieldDefinition->getRequired()
                       && $params['help'] === $customFieldDefinition->getHelp()
                       && $params['showInList'] === $customFieldDefinition->getShowInList()
                       && $params['typeId'] === $customFieldDefinition->getTypeId()
                       && $params['isEncrypted'] === $customFieldDefinition->getIsEncrypted()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([]));

        $this->customFieldDefinition->create($customFieldDefinition);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $customFieldDefinition = CustomFieldDefinitionGenerator::factory()->buildCustomFieldDefinition();

        $callback = new Callback(
            static function (QueryData $arg) use ($customFieldDefinition) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 7
                       && $params['id'] === $customFieldDefinition->getId()
                       && $params['name'] === $customFieldDefinition->getName()
                       && $params['required'] === $customFieldDefinition->getRequired()
                       && $params['help'] === $customFieldDefinition->getHelp()
                       && $params['showInList'] === $customFieldDefinition->getShowInList()
                       && $params['typeId'] === $customFieldDefinition->getTypeId()
                       && $params['isEncrypted'] === $customFieldDefinition->getIsEncrypted()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([]));

        $this->customFieldDefinition->update($customFieldDefinition);
    }

    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && is_a($query, SelectInterface::class)
                       && $arg->getMapClassName() === CustomFieldDefinitionModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult([]));

        $this->customFieldDefinition->getById($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $ids = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $query = $arg->getQuery();
                $bindValues = $query->getBindValues();

                return count($bindValues) === 1
                       && $bindValues['ids'] === $ids
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback);

        $this->customFieldDefinition->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteBatchWithNoItems()
    {
        $this->database
            ->expects(self::never())
            ->method('doQuery');

        $result = $this->customFieldDefinition->deleteByIdBatch([]);

        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $item = new ItemSearchData(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 2
                       && $params['name'] === $searchStringLike
                       && $params['description'] === $searchStringLike
                       && $arg->getMapClassName() === CustomFieldDefinitionModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback, true);

        $this->customFieldDefinition->search($item);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearchWithNoText()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 0
                       && $arg->getMapClassName() === CustomFieldDefinitionModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback, true);

        $this->customFieldDefinition->search(new ItemSearchData());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['id'] === $id
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())->method('doQuery')->with($callback);

        $this->customFieldDefinition->delete($id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->customFieldDefinition = new CustomFieldDefinition(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
