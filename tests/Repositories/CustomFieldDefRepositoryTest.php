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

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ConstraintException;
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
    private static $customFieldDefRepository;

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
        self::$customFieldDefRepository = $dic->get(CustomFieldDefRepository::class);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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

        $this->assertEquals(1, self::$customFieldDefRepository->update($data));

        $dataUpdated = self::$customFieldDefRepository->getById(1);

        $this->assertEquals($data, $dataUpdated);

        $data->setTypeId(100);

        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$customFieldDefRepository->update($data));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$customFieldDefRepository->deleteByIdBatch([1, 2, 3]));
        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldDefinition'));
        $this->assertEquals(0, self::$customFieldDefRepository->deleteByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByIdBatch()
    {
        $data = self::$customFieldDefRepository->getByIdBatch([1, 2]);

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

        $this->assertCount(0, self::$customFieldDefRepository->getByIdBatch([10]));

        $this->assertCount(0, self::$customFieldDefRepository->getByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
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

        $this->assertEquals($expected, self::$customFieldDefRepository->getById(1));

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals($expected, self::$customFieldDefRepository->getById(10));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreate()
    {
        $data = new CustomFieldDefinitionData();
        $data->setId(3);
        $data->setName('Phone');
        $data->setIsEncrypted(0);
        $data->setHelp('Telefono');
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setRequired(true);
        $data->setTypeId(6);
        $data->setShowInList(0);

        $this->assertEquals(3, self::$customFieldDefRepository->create($data));

        $this->assertEquals($data, self::$customFieldDefRepository->getById(3));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetAll()
    {
        self::$customFieldDefRepository->resetCollection();

        $data = self::$customFieldDefRepository->getAll();

        $this->assertCount(2, $data);

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
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('RSA');
        $itemSearchData->setLimitCount(10);

        $result = self::$customFieldDefRepository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals(1, $result->getTotalNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(CustomFieldDefinitionData::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('password', $data[0]->typeName);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('test');
        $itemSearchData->setLimitCount(10);

        $this->assertEquals(0, self::$customFieldDefRepository->search($itemSearchData)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$customFieldDefRepository->delete(1));
        $this->assertEquals(0, self::$customFieldDefRepository->delete(10));
        $this->assertEquals(1, $this->conn->getRowCount('CustomFieldDefinition'));
    }

    /**
     * Performs operation returned by getSetUpOperation().
     */
    protected function setUp()
    {
        parent::setUp();

        self::$customFieldDefRepository->resetCollection();
    }
}
