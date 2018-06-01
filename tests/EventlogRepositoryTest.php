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

namespace SP\Tests;

use PHPUnit\DbUnit\DataSet\IDataSet;
use SP\Core\Exceptions\ConstraintException;
use SP\DataModel\EventlogData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\EventLog\EventlogRepository;
use SP\Storage\DatabaseConnectionData;

/**
 * Class EventlogRepositoryTest
 *
 * @package SP\Tests
 */
class EventlogRepositoryTest extends DatabaseTestCase
{
    /**
     * @var EventlogRepository
     */
    private static $eventlogRepository;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$eventlogRepository = $dic->get(EventlogRepository::class);
    }

    /**
     * Comprobar la búsqueda de eventos por texto
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('login.auth.database');

        $search = self::$eventlogRepository->search($itemSearchData);
        $this->assertCount(5, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(4, $search['count']);
        $this->assertEquals('login.auth.database', $search[0]->action);

        $itemSearchData->setSeachString('login.auth.');

        $search = self::$eventlogRepository->search($itemSearchData);
        $this->assertCount(5, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(4, $search['count']);

        $itemSearchData->setSeachString('Tiempo inactivo : 0 min.');

        $search = self::$eventlogRepository->search($itemSearchData);
        $this->assertCount(2, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(1, $search['count']);

        $itemSearchData->setSeachString('prueba');

        $search = self::$eventlogRepository->search($itemSearchData);
        $this->assertCount(1, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(0, $search['count']);
    }

    /**
     * Comprobar la limpieza el registro de eventos
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testClear()
    {
        self::$eventlogRepository->clear();

        $this->assertEquals(0, $this->conn->getRowCount('EventLog'));
    }

    /**
     * Comprobar la creación de eventos
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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

        self::$eventlogRepository->create($eventlogData);

        $countAfter = $this->conn->getRowCount('EventLog');

        $this->assertEquals($countBefore + 1, $countAfter);

        $this->expectException(ConstraintException::class);

        self::$eventlogRepository->create(new EventlogData());
    }

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . 'syspass_eventlog.xml');
    }
}
