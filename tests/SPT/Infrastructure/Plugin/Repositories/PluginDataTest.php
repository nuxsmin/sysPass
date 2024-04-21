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

namespace SPT\Infrastructure\Plugin\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Plugin\Models\PluginData as PluginDataModel;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Plugin\Repositories\PluginData;
use SPT\Generators\PluginDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class PluginDataTest
 */
#[Group('unitary')]
class PluginDataTest extends UnitaryTestCase
{

    private PluginData $pluginData;

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();

                return $arg->getMapClassName() === PluginDataModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->pluginData->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByItemId()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === 'test_name'
                       && $params['itemId'] === 200
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->pluginData->deleteByItemId('test_name', 200);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['name'] === 'test_name'
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->pluginData->delete('test_name');
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $pluginData = PluginDataGenerator::factory()->buildPluginData();

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($pluginData) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['name'] === $pluginData->getName()
                       && $params['data'] === $pluginData->getData()
                       && $params['key'] === $pluginData->getKey()
                       && $params['itemId'] === $pluginData->getItemId()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult([]));

        $this->pluginData->create($pluginData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $pluginData = PluginDataGenerator::factory()->buildPluginData();

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($pluginData) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['name'] === $pluginData->getName()
                       && $params['itemId'] === $pluginData->getItemId()
                       && $params['key'] === $pluginData->getKey()
                       && $params['data'] === $pluginData->getData()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult(null, 1));

        $out = $this->pluginData->update($pluginData);

        $this->assertEquals(1, $out);
    }

    public function testGetByNameBatch()
    {
        $names = [self::$faker->colorName(), self::$faker->colorName(), self::$faker->colorName()];

        $callback = new Callback(
            static function (QueryData $arg) use ($names) {
                $query = $arg->getQuery();
                $values = $query->getBindValues();

                return count($values) === 3
                       && array_shift($values) === array_shift($names)
                       && array_shift($values) === array_shift($names)
                       && array_shift($values) === array_shift($names)
                       && $arg->getMapClassName() === PluginDataModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->pluginData->getByNameBatch($names);
    }

    public function testGetByNameBatchWithNoNames()
    {
        $this->database
            ->expects(self::never())
            ->method('runQuery');

        $this->pluginData->getByNameBatch([]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByNameBatch()
    {
        $names = [self::$faker->colorName(), self::$faker->colorName(), self::$faker->colorName()];

        $callback = new Callback(
            static function (QueryData $arg) use ($names) {
                $query = $arg->getQuery();
                $values = $query->getBindValues();

                return count($values) === 3
                       && array_shift($values) === array_shift($names)
                       && array_shift($values) === array_shift($names)
                       && array_shift($values) === array_shift($names)
                       && $arg->getMapClassName() === Simple::class
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->pluginData->deleteByNameBatch($names);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByNameBatchWithNoNames()
    {
        $this->database
            ->expects(self::never())
            ->method('runQuery');

        $this->pluginData->deleteByNameBatch([]);
    }

    public function testGetByItemId()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === 'test_name'
                       && $params['itemId'] === 100
                       && $arg->getMapClassName() === PluginDataModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->pluginData->getByItemId('test_name', 100);
    }

    public function testGetByName()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['name'] === 'test_name'
                       && $arg->getMapClassName() === PluginDataModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->pluginData->getByName('test_name');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->pluginData = new PluginData(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
