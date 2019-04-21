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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\CustomField\CustomFieldDefRepository;
use SP\Repositories\NoSuchItemException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class CustomFieldDefRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class CustomFieldDefRepositoryTest extends DatabaseTestCase
{
    /**
     * @var CustomFieldDefRepository
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

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(CustomFieldDefRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $expected = new CustomFieldDefinitionData();
        $expected->setId(1);
        $expected->setName('Prueba');
        $expected->setIsEncrypted(1);
        $expected->setHelp('Ayuda');
        $expected->setModuleId(ActionsInterface::ACCOUNT);
        $expected->setRequired(true);
        $expected->setTypeId(1);
        $expected->setShowInList(0);

        $this->assertEquals($expected, self::$repository->getById(1));

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals($expected, self::$repository->getById(10));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testUpdate()
    {
        $data = new CustomFieldDefinitionData();
        $data->setId(1);
        $data->setName('PIN');
        $data->setIsEncrypted(0);
        $data->setHelp('Pin code');
        $data->setModuleId(ActionsInterface::CLIENT);
        $data->setRequired(false);
        $data->setTypeId(2);
        $data->setShowInList(1);

        $this->assertEquals(1, self::$repository->update($data));

        $dataUpdated = self::$repository->getById(1);

        $this->assertEquals($data, $dataUpdated);

        $data->setTypeId(100);

        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$repository->update($data));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(1, self::$repository->deleteByIdBatch([3, 4]));
        $this->assertEquals(2, $this->conn->getRowCount('CustomFieldDefinition'));
        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));

        $this->expectException(ConstraintException::class);

        self::$repository->deleteByIdBatch([1, 2]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $result = self::$repository->getByIdBatch([1, 2]);

        $this->assertEquals(2, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(2, $data);
        $this->assertInstanceOf(CustomFieldDefinitionData::class, $data[0]);
        $this->assertInstanceOf(CustomFieldDefinitionData::class, $data[1]);

        $expected = new CustomFieldDefinitionData();
        $expected->setId(1);
        $expected->setName('Prueba');
        $expected->setIsEncrypted(1);
        $expected->setHelp('Ayuda');
        $expected->setModuleId(ActionsInterface::ACCOUNT);
        $expected->setRequired(true);
        $expected->setTypeId(1);
        $expected->setShowInList(0);

        $this->assertEquals($expected, $data[0]);

        $expected = new CustomFieldDefinitionData();
        $expected->setId(2);
        $expected->setName('RSA');
        $expected->setIsEncrypted(0);
        $expected->setModuleId(ActionsInterface::CATEGORY);
        $expected->setRequired(false);
        $expected->setTypeId(2);
        $expected->setShowInList(0);

        $this->assertEquals($expected, $data[1]);

        $this->assertEquals(0, self::$repository->getByIdBatch([10])->getNumRows());

        $this->assertEquals(0, self::$repository->getByIdBatch([])->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testCreate()
    {
        $data = new CustomFieldDefinitionData();
        $data->setId(4);
        $data->setName('Phone');
        $data->setIsEncrypted(0);
        $data->setHelp('Telefono');
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setRequired(true);
        $data->setTypeId(6);
        $data->setShowInList(0);

        $this->assertEquals(4, self::$repository->create($data));

        $this->assertEquals($data, self::$repository->getById(4));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        self::$repository->resetCollection();

        $result = self::$repository->getAll();
        $this->assertEquals(3, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(3, $data);

        $expected = new CustomFieldDefinitionData();
        $expected->setId(1);
        $expected->setName('Prueba');
        $expected->setIsEncrypted(1);
        $expected->setHelp('Ayuda');
        $expected->setModuleId(ActionsInterface::ACCOUNT);
        $expected->setRequired(true);
        $expected->setTypeId(1);
        $expected->setShowInList(0);

        $this->assertEquals($expected, $data[0]);

        $expected = new CustomFieldDefinitionData();
        $expected->setId(2);
        $expected->setName('RSA');
        $expected->setIsEncrypted(0);
        $expected->setModuleId(ActionsInterface::CATEGORY);
        $expected->setRequired(false);
        $expected->setTypeId(2);
        $expected->setShowInList(0);

        $this->assertEquals($expected, $data[1]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('RSA');
        $itemSearchData->setLimitCount(10);

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertEquals(2, $result->getTotalNumRows());
        $this->assertCount(2, $data);
        $this->assertInstanceOf(CustomFieldDefinitionData::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('password', $data[0]->typeName);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('test');
        $itemSearchData->setLimitCount(10);

        $this->assertEquals(0, self::$repository->search($itemSearchData)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(3));
        $this->assertEquals(0, self::$repository->delete(10));
        $this->assertEquals(2, $this->conn->getRowCount('CustomFieldDefinition'));

        $this->expectException(ConstraintException::class);

        self::$repository->delete(1);
    }

    /**
     * Performs operation returned by getSetUpOperation().
     */
    protected function setUp()
    {
        parent::setUp();

        self::$repository->resetCollection();
    }
}
