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

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\ItemPreset\Repositories\ItemPreset;
use SP\Tests\Generators\ItemPresetDataGenerator;
use SP\Tests\UnitaryTestCase;
use stdClass;

/**
 * Class ItemPresetTest
 *
 */
#[Group('unitary')]
class ItemPresetTest extends UnitaryTestCase
{

    private ItemPreset $itemPreset;

    public function testGetByFilter()
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['type'] === 'test'
                       && $params['userId'] === 100
                       && $params['userGroupId'] === 200
                       && $params['userProfileId'] === 300
                       && $arg->getMapClassName() === ItemPresetModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, false);

        $this->itemPreset->getByFilter('test', 100, 200, 300);
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

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->itemPreset->delete($id);
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === ItemPresetModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->itemPreset->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData(new stdClass());

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($itemPreset) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 7
                       && $params['type'] === $itemPreset->getType()
                       && $params['userId'] === $itemPreset->getUserId()
                       && $params['userProfileId'] === $itemPreset->getUserProfileId()
                       && $params['userGroupId'] === $itemPreset->getUserGroupId()
                       && $params['data'] === $itemPreset->getData()
                       && $params['fixed'] === $itemPreset->getFixed()
                       && $params['priority'] === $itemPreset->getPriority()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult([]));

        $this->itemPreset->create($itemPreset);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $itemPreset = ItemPresetDataGenerator::factory()->buildItemPresetData(new stdClass());

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($itemPreset) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 8
                       && $params['id'] === $itemPreset->getId()
                       && $params['type'] === $itemPreset->getType()
                       && $params['userId'] === $itemPreset->getUserId()
                       && $params['userProfileId'] === $itemPreset->getUserProfileId()
                       && $params['userGroupId'] === $itemPreset->getUserGroupId()
                       && $params['data'] === $itemPreset->getData()
                       && $params['fixed'] === $itemPreset->getFixed()
                       && $params['priority'] === $itemPreset->getPriority()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult(null, 1));

        $out = $this->itemPreset->update($itemPreset);

        $this->assertEquals(1, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 4
                       && $params['type'] === $searchStringLike
                       && $params['userName'] === $searchStringLike
                       && $params['userProfileName'] === $searchStringLike
                       && $params['userGroupName'] === $searchStringLike
                       && $arg->getMapClassName() === ItemPresetModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->itemPreset->search($item);
    }

    /**
     * @throws Exception
     */
    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return count($query->getBindValues()) === 0
                       && $arg->getMapClassName() === ItemPresetModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->itemPreset->search(new ItemSearchDto());
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
                $values = $query->getBindValues();

                return count($values) === 3
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === Simple::class
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->itemPreset->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database
            ->expects(self::never())
            ->method('runQuery');

        $this->itemPreset->deleteByIdBatch([]);
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
                       && $arg->getMapClassName() === ItemPresetModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->itemPreset->getById($id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->itemPreset = new ItemPreset(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }


}
