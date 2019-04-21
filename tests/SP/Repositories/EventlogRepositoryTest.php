<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Repositories;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\EventlogData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\EventLog\EventlogRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class EventlogRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a los registros de eventos
 *
 * @package SP\Tests
 */
class EventlogRepositoryTest extends DatabaseTestCase
{
    /**
     * @var EventlogRepository
     */
    private static $repository;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_eventlog.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(EventlogRepository::class);
    }

    /**
     * Comprobar la búsqueda de eventos por texto
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('login.auth.database');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(4, $result->getNumRows());
        $this->assertCount(4, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals('login.auth.database', $data[0]->action);

        $itemSearchData->setSeachString('login.auth.');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(4, $result->getNumRows());
        $this->assertCount(4, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);

        $itemSearchData->setSeachString('Tiempo inactivo : 0 min.');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);

        $itemSearchData->setSeachString('prueba');

        $result = self::$repository->search($itemSearchData);

        $this->assertCount(0, $result->getDataAsArray());
        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * Comprobar la limpieza el registro de eventos
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testClear()
    {
        self::$repository->clear();

        $this->assertEquals(0, $this->conn->getRowCount('EventLog'));
    }

    /**
     * Comprobar la creación de eventos
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $eventlogData = new EventlogData();
        $eventlogData->setAction('test');
        $eventlogData->setLevel('INFO');
        $eventlogData->setUserId(1);
        $eventlogData->setLogin('Admin');
        $eventlogData->setIpAddress('127.0.0.1');
        $eventlogData->setDescription('Prueba');

        $countBefore = $this->conn->getRowCount('EventLog');

        self::$repository->create($eventlogData);

        $countAfter = $this->conn->getRowCount('EventLog');

        $this->assertEquals($countBefore + 1, $countAfter);

        $this->expectException(ConstraintException::class);

        self::$repository->create(new EventlogData());
    }
}
