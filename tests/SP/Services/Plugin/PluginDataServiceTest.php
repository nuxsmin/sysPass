<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Plugin\PluginDataModel;
use SP\Services\Plugin\PluginDataService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class PluginDataServiceTest
 *
 * @package SP\Tests\Services\Plugin
 */
class PluginDataServiceTest extends DatabaseTestCase
{
    /**
     * @var PluginDataService
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
        self::$service = $dic->get(PluginDataService::class);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $data = new PluginDataModel();
        $data->setItemId(1);
        $data->setName('Authenticator');
        $data->setData('data_updated');

        $this->assertEquals(1, self::$service->update($data));

        $this->assertEquals($data, self::$service->getByItemId($data->getName(), $data->getItemId()));

        $data = new PluginDataModel();
        $data->setItemId(0);
        $data->setName('Authenticator');
        $data->setData('data_updated');

        $this->assertEquals(0, self::$service->update($data));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateUnkown()
    {
        $data = new PluginDataModel();
        $data->setItemId(2);
        $data->setName('Test');
        $data->setData('data');

        $this->assertEquals(0, self::$service->update($data));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetAll()
    {
        $data = self::$service->getAll();

        $this->assertCount(4, $data);
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals('Authenticator', $data[0]->getName());
        $this->assertEquals('data_item1', $data[0]->getData());

        $this->assertEquals(2, $data[1]->getItemId());
        $this->assertEquals(3, $data[2]->getItemId());
        $this->assertEquals(2, $data[3]->getItemId());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDelete()
    {
        self::$service->delete('Authenticator');

        $this->assertTableRowCount('PluginData', 2);

        self::$service->delete('DokuWiki');

        $this->assertTableRowCount('PluginData', 1);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDeleteUnkown()
    {
        $this->expectException(NoSuchItemException::class);

        self::$service->delete('Test');
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreate()
    {
        $data = new PluginDataModel();
        $data->setItemId(4);
        $data->setName('Authenticator');
        $data->setData('data');

        self::$service->create($data);

        $this->assertEquals($data, self::$service->getByItemId($data->getName(), $data->getItemId()));

        $this->expectException(ConstraintException::class);

        self::$service->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreateUnknown()
    {
        $this->expectException(ConstraintException::class);

        $data = new PluginDataModel();
        $data->setItemId(4);
        $data->setName('Test');
        $data->setData('data');

        self::$service->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByItemId()
    {
        $data = self::$service->getByItemId('Authenticator', 1);

        $this->assertInstanceOf(PluginDataModel::class, $data);
        $this->assertEquals(1, $data->getItemId());
        $this->assertEquals('Authenticator', $data->getName());
        $this->assertEquals('data_item1', $data->getData());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByItemIdUnkown()
    {
        $this->expectException(NoSuchItemException::class);

        self::$service->getByItemId('Test', 1);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetById()
    {
        $result = self::$service->getById('Authenticator');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(PluginDataModel::class, $result[0]);
        $this->assertEquals(1, $result[0]->getItemId());
        $this->assertEquals('Authenticator', $result[0]->getName());
        $this->assertEquals('data_item1', $result[0]->getData());

        $this->assertInstanceOf(PluginDataModel::class, $result[1]);
        $this->assertEquals(2, $result[1]->getItemId());
        $this->assertEquals('Authenticator', $result[1]->getName());
        $this->assertEquals('plugin_data', $result[1]->getData());

        $this->assertCount(1, self::$service->getById('XML Exporter'));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByIdUnkown()
    {
        $this->expectException(NoSuchItemException::class);

        self::$service->getById('Test');
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByItemId()
    {
        self::$service->deleteByItemId('Authenticator', 1);

        $this->assertCount(1, self::$service->getById('Authenticator'));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByItemIdUnkown()
    {
        $this->expectException(NoSuchItemException::class);

        self::$service->deleteByItemId('Test', 1);
    }
}
