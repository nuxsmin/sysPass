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

namespace SP\Tests\Infrastructure\Config\Repositories;

use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Config\Models\Config as ConfigModel;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Infrastructure\Config\Repositories\Config;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\ConfigGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class ConfigTest
 *
 */
#[Group('unitary')]
class ConfigTest extends UnitaryTestCase
{
    private Config $configRepository;

    public function testGetByParam()
    {
        $param = self::$faker->colorName();

        $callback = new Callback(
            static function (QueryData $arg) use ($param) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['parameter'] === $param
                       && $arg->getMapClassName() === ConfigModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->configRepository->getByParam($param);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $config = ConfigGenerator::factory()->buildConfig();

        $callback = new Callback(
            static function (QueryData $arg) use ($config) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['parameter'] === $config->getParameter()
                       && $params['value'] === $config->getValue()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $queryResult = new QueryResult([1]);

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callback)
            ->willReturn($queryResult);

        $out = $this->configRepository->create($config);

        $this->assertEquals($queryResult, $out);
    }

    public function testHas()
    {
        $param = self::$faker->colorName();

        $callback = new Callback(
            static function (QueryData $arg) use ($param) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['parameter'] === $param
                       && $arg->getMapClassName() === Simple::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult([1]));

        $this->assertTrue($this->configRepository->has($param));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $config = ConfigGenerator::factory()->buildConfig();

        $callback = new Callback(
            static function (QueryData $arg) use ($config) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['parameter'] === $config->getParameter()
                       && $params['value'] === $config->getValue()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $queryResult = new QueryResult([1]);

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callback)
            ->willReturn($queryResult);

        $out = $this->configRepository->update($config);

        $this->assertEquals($queryResult, $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->configRepository = new Config(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
