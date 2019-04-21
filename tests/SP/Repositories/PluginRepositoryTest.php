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
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Plugin\PluginModel;
use SP\Repositories\Plugin\PluginRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class PluginRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class PluginRepositoryTest extends DatabaseTestCase
{
    /**
     * @var PluginRepository
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

        self::$dataset = 'syspass_plugin.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(PluginRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $data = new PluginModel();
        $data->setId(1);
        $data->setName('Authenticator 2');
        $data->setAvailable(1);
        $data->setEnabled(1);
        $data->setData('data');

        $this->assertEquals(1, self::$repository->update($data));

        $result = self::$repository->getById(1);

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals($data, $result->getData());

        $data->setId(null);
        $data->setName('Authenticator');

        $this->assertEquals(0, self::$repository->update($data));

        $data->setId(2);
        $data->setName('DokuWiki');

        $this->expectException(ConstraintException::class);

        self::$repository->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();
        /** @var PluginModel[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
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
     * @throws QueryException
     */
    public function testGetByName()
    {
        $result = self::$repository->getByName('Authenticator');
        /** @var PluginModel $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(PluginModel::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Authenticator', $data->getName());
        $this->assertNull($data->getData());
        $this->assertEquals(1, $data->getAvailable());
        $this->assertEquals(0, $data->getEnabled());

        $this->assertEquals(0, self::$repository->getByName('Authenticator 2')->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testToggleAvailableByName()
    {
        $this->assertEquals(1, self::$repository->toggleAvailableByName('Authenticator', 0));

        /** @var PluginModel $data */
        $data = self::$repository->getByName('Authenticator')->getData();

        $this->assertEquals(0, $data->getAvailable());

        $this->assertEquals(0, self::$repository->toggleAvailableByName('Authenticator 2', 1));
    }

    /**
     * @requires testGetById
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(1));
        $this->assertEquals(0, self::$repository->getById(1)->getNumRows());

        $this->assertEquals(0, self::$repository->delete(4));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testToggleEnabled()
    {
        $this->assertEquals(1, self::$repository->toggleEnabled(1, 1));
        $this->assertEquals(0, self::$repository->toggleEnabled(4, 0));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(1);
        /** @var PluginModel $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(PluginModel::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Authenticator', $data->getName());
        $this->assertNull($data->getData());
        $this->assertEquals(1, $data->getAvailable());
        $this->assertEquals(0, $data->getEnabled());

        $this->assertEquals(0, self::$repository->getById(4)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$repository->deleteByIdBatch([1, 2, 4]));
        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetEnabled()
    {
        $result = self::$repository->getEnabled();
        /** @var ItemData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
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
     * @throws QueryException
     */
    public function testCreate()
    {
        $data = new PluginModel();
        $data->setId(4);
        $data->setName('Authenticator 2');
        $data->setAvailable(1);
        $data->setEnabled(1);
        $data->setData('data');

        $this->assertEquals(4, self::$repository->create($data)->getLastId());

        $result = self::$repository->getById(4);

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals($data, $result->getData());


        $this->expectException(ConstraintException::class);

        self::$repository->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreateBlank()
    {
        $this->expectException(ConstraintException::class);

        self::$repository->create(new PluginModel());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testResetById()
    {
        $this->assertEquals(1, self::$repository->resetById(2));

        /** @var PluginModel $data */
        $data = self::$repository->getById(2)->getData();

        $this->assertNull($data->getData());

        $this->assertEquals(0, self::$repository->resetById(4));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Auth');

        $result = self::$repository->search($itemSearchData);
        /** @var PluginModel[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals('Authenticator', $data[0]->getName());
        $this->assertEquals(0, $data[0]->getEnabled());
        $this->assertEquals(1, $data[0]->getAvailable());

        $itemSearchData->setSeachString('test');

        $result = self::$repository->search($itemSearchData);
        $this->assertEquals(0, $result->getNumRows());

        $itemSearchData->setSeachString('');

        $result = self::$repository->search($itemSearchData);
        $this->assertEquals(3, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testToggleEnabledByName()
    {
        $this->assertEquals(1, self::$repository->toggleEnabledByName('Authenticator', 1));

        /** @var PluginModel $data */
        $data = self::$repository->getByName('Authenticator')->getData();

        $this->assertEquals(1, $data->getEnabled());

        $this->assertEquals(0, self::$repository->toggleEnabledByName('Test', 0));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testToggleAvailable()
    {
        $this->assertEquals(1, self::$repository->toggleAvailable(1, 0));

        /** @var PluginModel $data */
        $data = self::$repository->getByName('Authenticator')->getData();

        $this->assertEquals(0, $data->getAvailable());

        $this->assertEquals(0, self::$repository->toggleAvailable(4, 1));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $result = self::$repository->getByIdBatch([1, 2, 4]);
        /** @var PluginModel[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());

        $result = self::$repository->getByIdBatch([]);

        $this->assertEquals(0, $result->getNumRows());
    }
}
