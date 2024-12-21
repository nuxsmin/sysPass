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

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Tag\Models\Tag as TagModel;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Tag\Repositories\Tag;
use SP\Tests\Generators\TagGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class TagTest
 */
#[Group('unitary')]
class TagTest extends UnitaryTestCase
{

    private Tag                          $tag;
    private DatabaseInterface|MockObject $database;

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === TagModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->tag->getAll();
    }

    public function testSearch()
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 1
                       && $params['name'] === $searchStringLike
                       && $arg->getMapClassName() === TagModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->tag->search($item);
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
                       && $arg->getMapClassName() === TagModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->tag->search(new ItemSearchDto());
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
                       && $arg->getMapClassName() === TagModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->tag->getById($id);
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

        $this->tag->deleteByIdBatch($ids);
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

        $this->tag->deleteByIdBatch([]);
    }

    public function testGetByName()
    {
        $name = self::$faker->colorName();

        $callback = new Callback(
            static function (QueryData $arg) use ($name) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === $name
                       && !empty($params['hash'])
                       && $arg->getMapClassName() === TagModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->tag->getByName($name);
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

        $this->tag->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testCreate()
    {
        $tag = TagGenerator::factory()->buildTag();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($tag) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === $tag->getName()
                       && $params['hash'] === $tag->getHash()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($tag) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === $tag->getName()
                       && !empty($params['hash'])
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('runQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackCreate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->tag->create($tag);
    }

    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreateWithDuplicate()
    {
        $tag = TagGenerator::factory()->buildTag();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($tag) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === $tag->getName()
                       && !empty($params['hash'])
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackDuplicate)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Duplicated tag');

        $this->tag->create($tag);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $tag = TagGenerator::factory()->buildTag();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($tag) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['id'] === $tag->getId()
                       && $params['name'] === $tag->getName()
                       && !empty($params['hash'])
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($tag) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['id'] === $tag->getId()
                       && $params['name'] === $tag->getName()
                       && !empty($params['hash'])
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('runQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackUpdate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->tag->update($tag);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateWithDuplicate()
    {
        $tag = TagGenerator::factory()->buildTag();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($tag) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['id'] === $tag->getId()
                       && $params['name'] === $tag->getName()
                       && !empty($params['hash'])
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackDuplicate)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Duplicated tag');

        $this->tag->update($tag);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->tag = new Tag(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
