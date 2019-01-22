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

namespace SP\Tests\Repositories;

use DI\DependencyException;
use SP\Core\Exceptions\ConstraintException;
use SP\Repositories\Plugin\PluginDataModel;
use SP\Repositories\Plugin\PluginDataRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class PluginDataRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class PluginDataRepositoryTest extends DatabaseTestCase
{
    /**
     * @var PluginDataRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_plugin.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(PluginDataRepository::class);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $data = new PluginDataModel();
        $data->setItemId(1);
        $data->setName('Authenticator');
        $data->setData('data_updated');

        $this->assertEquals(1, self::$repository->update($data));

        $result = self::$repository->getByItemId($data->getName(), $data->getItemId());

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals($data, $result->getData());

        $data = new PluginDataModel();
        $data->setItemId(0);
        $data->setName('Authenticator');
        $data->setData('data_updated');

        $this->assertEquals(0, self::$repository->update($data));
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

        $this->assertEquals(0, self::$repository->update($data));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();
        /** @var PluginDataModel[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(4, $result->getNumRows());
        $this->assertCount(4, $data);
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals('Authenticator', $data[0]->getName());
        $this->assertEquals('data_item1', $data[0]->getData());

        $this->assertEquals(2, $data[1]->getItemId());
        $this->assertEquals(3, $data[2]->getItemId());
        $this->assertEquals(2, $data[3]->getItemId());
    }

    /**
     * @requires testGetById
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDelete()
    {
        $this->assertEquals(2, self::$repository->delete('Authenticator'));
        $this->assertEquals(0, self::$repository->getById('Authenticator')->getNumRows());

        $this->assertEquals(1, self::$repository->delete('DokuWiki'));
        $this->assertEquals(0, self::$repository->getById('DokuWiki')->getNumRows());

        $this->assertEquals(0, self::$repository->delete('Test'));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByItemId()
    {
        $this->assertEquals(1, self::$repository->deleteByItemId('Authenticator', 1));
        $this->assertEquals(1, self::$repository->getById('Authenticator')->getNumRows());

        $this->assertEquals(0, self::$repository->delete('Test'));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetById()
    {
        $result = self::$repository->getById('Authenticator');
        /** @var PluginDataModel[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertInstanceOf(PluginDataModel::class, $data[0]);
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals('Authenticator', $data[0]->getName());
        $this->assertEquals('data_item1', $data[0]->getData());

        $this->assertInstanceOf(PluginDataModel::class, $data[1]);
        $this->assertEquals(2, $data[1]->getItemId());
        $this->assertEquals('Authenticator', $data[1]->getName());
        $this->assertEquals('plugin_data', $data[1]->getData());

        $this->assertEquals(1, self::$repository->getById('XML Exporter')->getNumRows());

        $this->assertEquals(0, self::$repository->getById('Test')->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByItemId()
    {
        $result = self::$repository->getByItemId('Authenticator', 1);
        /** @var PluginDataModel $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(PluginDataModel::class, $data);
        $this->assertEquals(1, $data->getItemId());
        $this->assertEquals('Authenticator', $data->getName());
        $this->assertEquals('data_item1', $data->getData());

        $this->assertEquals(0, self::$repository->getByItemId('Test', 1)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(3, self::$repository->deleteByIdBatch(['Authenticator', 'XML Exporter', 'Test']));
        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreate()
    {
        $data = new PluginDataModel();
        $data->setItemId(4);
        $data->setName('Authenticator');
        $data->setData('data');

        self::$repository->create($data);

        $result = self::$repository->getByItemId($data->getName(), $data->getItemId());

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals($data, $result->getData());

        $this->expectException(ConstraintException::class);

        self::$repository->create($data);
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

        self::$repository->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByIdBatch()
    {
        $result = self::$repository->getByIdBatch(['Authenticator', 'XML Exporter', 'Test']);
        /** @var \SP\Repositories\Plugin\PluginDataModel[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $data);
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals(2, $data[1]->getItemId());
        $this->assertEquals(2, $data[2]->getItemId());

        $result = self::$repository->getByIdBatch([]);

        $this->assertEquals(0, $result->getNumRows());
    }
}
