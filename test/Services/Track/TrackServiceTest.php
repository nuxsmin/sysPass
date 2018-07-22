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

use SP\DataModel\TrackData;
use SP\Http\Request;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Track\TrackRequest;
use SP\Services\Track\TrackService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Test\DatabaseTestCase;
use function SP\Test\setupContext;

/**
 * Class TrackServiceTest
 *
 * @package SP\Tests\Services\Track
 */
class TrackServiceTest extends DatabaseTestCase
{
    private static $request;
    /**
     * @var TrackService
     */
    private static $service;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_track.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(TrackService::class);
        self::$request = $dic->get(Request::class);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\QueryException
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \Exception
     */
    public function testCheckTracking()
    {
        $this->assertFalse(self::$service->checkTracking(TrackService::getTrackRequest('TEST', self::$request)));

        for ($i = 1; $i <= 10; $i++) {
            self::$service->add(TrackService::getTrackRequest('TEST', self::$request));
        }

        $this->assertTrue(self::$service->checkTracking(TrackService::getTrackRequest('TEST', self::$request)));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\QueryException
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
}
