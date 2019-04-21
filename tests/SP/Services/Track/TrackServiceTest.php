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

namespace SP\Tests\Services\Track;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\TrackData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Track\TrackRequest;
use SP\Services\ServiceException;
use SP\Services\Track\TrackService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class TrackServiceTest
 *
 * @package SP\Tests\Services\Track
 */
class TrackServiceTest extends DatabaseTestCase
{
    /**
     * @var TrackService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_track.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(TrackService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testDelete()
    {
        self::$service->delete(1);

        $this->assertEquals(5, $this->conn->getRowCount('Track'));

        $this->expectException(NoSuchItemException::class);

        self::$service->delete(10);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testAdd()
    {
        $data = new TrackRequest();
        $data->setTrackIp('192.168.0.1');
        $data->userId = 1;
        $data->time = time();
        $data->source = __METHOD__;

        $this->assertEquals(7, self::$service->add($data));

        /** @var TrackData $resultData */
        $resultData = self::$service->getById(7);

        $this->assertEquals(7, $resultData->getId());
        $this->assertEquals($data->userId, $resultData->getUserId());
        $this->assertEquals($data->time, $resultData->getTime());
        $this->assertEquals($data->source, $resultData->getSource());
        $this->assertEquals('192.168.0.1', $resultData->getIpv4());
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAddNoAddress()
    {
        $data = new TrackRequest();
        $data->userId = 1;
        $data->time = time();
        $data->source = __METHOD__;

        $this->expectException(ServiceException::class);

        self::$service->add($data);
    }

    /**
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $data = self::$service->getAll();

        $this->assertCount(6, $data);
        $this->assertInstanceOf(TrackData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals(0, $data[0]->getUserId());
        $this->assertEquals('1529145183', $data[0]->getTime());
        $this->assertEquals('login', $data[0]->getSource());
        $this->assertEquals('172.22.0.1', $data[0]->getIpv4());
        $this->assertEquals('', $data[0]->getIpv6());
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws QueryException
     */
    public function testGetById()
    {
        $data = self::$service->getById(1);

        $this->assertInstanceOf(TrackData::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals(0, $data->getUserId());
        $this->assertEquals('1529145183', $data->getTime());
        $this->assertEquals('login', $data->getSource());
        $this->assertEquals('172.22.0.1', $data->getIpv4());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCheckTracking()
    {
        $this->assertFalse(self::$service->checkTracking(self::$service->getTrackRequest(__CLASS__)));

        for ($i = 1; $i <= 10; $i++) {
            self::$service->add(self::$service->getTrackRequest(__CLASS__));
        }

        $this->assertTrue(self::$service->checkTracking(self::$service->getTrackRequest(__CLASS__)));
    }

    /**
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws QueryException
     */
    public function testGetTracksForClientFromTime()
    {
        $data = new TrackRequest();
        $data->setTrackIp('172.22.0.1');
        $data->time = 1529272367;
        $data->source = 'login';

        $this->assertEquals(3, self::$service->getTracksForClientFromTime($data));

        $data->time = time();

        $this->assertEquals(0, self::$service->getTracksForClientFromTime($data));
    }

    public function testClear()
    {
        $this->markTestIncomplete();
    }

    public function testUnlock()
    {
        $this->markTestIncomplete();
    }

    public function testSearch()
    {
        $this->markTestIncomplete();
    }
}
