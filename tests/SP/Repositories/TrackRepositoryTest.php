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
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\TrackData;
use SP\Repositories\Track\TrackRepository;
use SP\Repositories\Track\TrackRequest;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class TrackRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class TrackRepositoryTest extends DatabaseTestCase
{
    /**
     * @var TrackRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_track.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(TrackRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(1));

        $this->assertEquals(5, $this->conn->getRowCount('Track'));

        $this->assertEquals(0, self::$repository->delete(10));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws InvalidArgumentException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(1);
        /** @var TrackData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(TrackData::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals(0, $data->getUserId());
        $this->assertEquals('1529145183', $data->getTime());
        $this->assertEquals('login', $data->getSource());
        $this->assertEquals('172.22.0.1', $data->getIpv4());

        $this->assertEquals(0, self::$repository->getById(10)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws InvalidArgumentException
     */
    public function testAdd()
    {
        $data = new TrackRequest();
        $data->setTrackIp('192.168.0.1');
        $data->userId = 1;
        $data->time = time();
        $data->source = __METHOD__;

        $this->assertEquals(7, self::$repository->add($data));

        /** @var TrackData $resultData */
        $resultData = self::$repository->getById(7)->getData();

        $this->assertEquals(7, $resultData->getId());
        $this->assertEquals($data->userId, $resultData->getUserId());
        $this->assertEquals($data->time, $resultData->getTime());
        $this->assertEquals($data->source, $resultData->getSource());
        $this->assertEquals('192.168.0.1', $resultData->getIpv4());
    }


    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws InvalidArgumentException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();
        /** @var TrackData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(6, $result->getNumRows());
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

        $result = self::$repository->getTracksForClientFromTime($data);
        /** @var TrackData[] $resultData */
        $resultData = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $resultData);
        $this->assertInstanceOf(TrackData::class, $resultData[0]);
        $this->assertEquals(4, $resultData[0]->getId());
        $this->assertInstanceOf(TrackData::class, $resultData[1]);
        $this->assertEquals(5, $resultData[1]->getId());
        $this->assertInstanceOf(TrackData::class, $resultData[2]);
        $this->assertEquals(6, $resultData[2]->getId());

        $data->time = time();

        $result = self::$repository->getTracksForClientFromTime($data);

        $this->assertEquals(0, $result->getNumRows());
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
