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

namespace SP\Tests\Services\Plugin;

use SP\Core\Exceptions\ConstraintException;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PluginData;
use SP\Repositories\NoSuchItemException;
use SP\Services\Plugin\PluginService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class PluginServiceTest
 *
 * @package SP\Tests\Services\Plugin
 */
class PluginServiceTest extends DatabaseTestCase
{
    /**
     * @var PluginService
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

        self::$dataset = 'syspass_plugin.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(PluginService::class);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $data = new PluginData();
        $data->setId(1);
        $data->setName('Authenticator 2');
        $data->setAvailable(1);
        $data->setEnabled(1);
        $data->setData('data');

        $this->assertEquals(1, self::$service->update($data));

        $result = self::$service->getById(1);

        $this->assertEquals($data, $result);

        $data->setId(null);
        $data->setName('Authenticator');

        $this->assertEquals(0, self::$service->update($data));

        $data->setId(2);
        $data->setName('DokuWiki');

        $this->expectException(ConstraintException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDeleteByIdBatch()
    {
        self::$service->deleteByIdBatch([1, 2]);

        $this->assertEquals(1, $this->conn->getRowCount('Plugin'));

        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([4]);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function testToggleAvailable()
    {
        self::$service->toggleAvailable(1, 0);

        $data = self::$service->getById(1);

        $this->assertEquals(0, $data->getAvailable());

        $this->expectException(NoSuchItemException::class);

        self::$service->toggleAvailable(4, 1);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws NoSuchItemException
     */
    public function testResetById()
    {
        $this->assertEquals(1, self::$service->resetById(2));

        $data = self::$service->getById(2);

        $this->assertNull($data->getData());

        $this->expectException(NoSuchItemException::class);

        self::$service->resetById(4);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByName()
    {
        $data = self::$service->getByName('Authenticator');

        $this->assertInstanceOf(PluginData::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Authenticator', $data->getName());
        $this->assertNull($data->getData());
        $this->assertEquals(1, $data->getAvailable());
        $this->assertEquals(0, $data->getEnabled());

        $this->expectException(NoSuchItemException::class);

        self::$service->getByName('Authenticator 2');
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDelete()
    {
        self::$service->delete(1);

        $this->assertEquals(2, $this->conn->getRowCount('Plugin'));

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(1);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Auth');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(1, $result->getNumRows());

        /** @var PluginData[] $data */
        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals('Authenticator', $data[0]->getName());
        $this->assertEquals(0, $data[0]->getEnabled());
        $this->assertEquals(1, $data[0]->getAvailable());

        $itemSearchData->setSeachString('test');

        $result = self::$service->search($itemSearchData);
        $this->assertEquals(0, $result->getNumRows());

        $itemSearchData->setSeachString('');

        $result = self::$service->search($itemSearchData);
        $this->assertEquals(3, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetById()
    {
        $data = self::$service->getById(1);

        $this->assertInstanceOf(PluginData::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Authenticator', $data->getName());
        $this->assertNull($data->getData());
        $this->assertEquals(1, $data->getAvailable());
        $this->assertEquals(0, $data->getEnabled());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(4);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreate()
    {
        $data = new PluginData();
        $data->setId(4);
        $data->setName('Authenticator 2');
        $data->setAvailable(1);
        $data->setEnabled(1);
        $data->setData('data');

        $this->assertEquals(4, self::$service->create($data));

        $this->assertEquals($data, self::$service->getById(4));

        $this->expectException(ConstraintException::class);

        self::$service->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreateBlank()
    {
        $this->expectException(ConstraintException::class);

        self::$service->create(new PluginData());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetEnabled()
    {
        $data = self::$service->getEnabled();

        $this->assertCount(2, $data);
        $this->assertInstanceOf(ItemData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals('XML Exporter', $data[0]->getName());
        $this->assertInstanceOf(ItemData::class, $data[1]);
        $this->assertEquals(3, $data[1]->getId());
        $this->assertEquals('DokuWiki', $data[1]->getName());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetAll()
    {
        $data = self::$service->getAll();

        $this->assertCount(3, $data);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals('Authenticator', $data[0]->getName());
        $this->assertNull($data[0]->getData());
        $this->assertEquals(1, $data[0]->getAvailable());
        $this->assertEquals(0, $data[0]->getEnabled());

        $this->assertEquals(3, $data[1]->getId());
        $this->assertEquals(2, $data[2]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testToggleEnabledByName()
    {
        self::$service->toggleEnabledByName('Authenticator', 1);

        $data = self::$service->getByName('Authenticator');

        $this->assertEquals(1, $data->getEnabled());

        $this->expectException(NoSuchItemException::class);

        self::$service->toggleEnabledByName('Test', 0);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testToggleAvailableByName()
    {
        self::$service->toggleAvailableByName('Authenticator', 0);

        $data = self::$service->getByName('Authenticator');

        $this->assertEquals(0, $data->getAvailable());

        $this->expectException(NoSuchItemException::class);

        self::$service->toggleAvailableByName('Authenticator 2', 1);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByIdBatch()
    {
        $data = self::$service->getByIdBatch([1, 2, 3]);

        $this->assertCount(3, $data);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(3, $data[2]->getId());

        $this->assertCount(0, self::$service->getByIdBatch([4]));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testToggleEnabled()
    {
        self::$service->toggleEnabled(1, 1);

        $data = self::$service->getById(1);

        $this->assertEquals(1, $data->getEnabled());

        $this->expectException(NoSuchItemException::class);

        self::$service->toggleEnabled(4, 0);
    }
}
