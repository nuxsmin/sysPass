<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SPT\Services\CustomField;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Services\CustomFieldDefService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SPT\DatabaseTestCase;

use function SPT\setupContext;

/**
 * Class CustomFieldDefServiceTest
 *
 * @package SPT\Services\CustomField
 */
class CustomFieldDefServiceTest extends DatabaseTestCase
{
    /**
     * @var CustomFieldDefService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass(): void
    {
        $dic = setupContext();

        self::$loadFixtures = true;

        // Inicializar el repositorio
        self::$service = $dic->get(CustomFieldDefService::class);
    }

    /**
     * @throws ServiceException
     */
    public function testDelete()
    {
        self::$service->delete(3);

        $this->expectException(NoSuchItemException::class);

        self::$service->delete(10);

        $this->expectException(ConstraintException::class);

        self::$service->delete(1);

        $this->assertEquals(2, self::getRowCount('CustomFieldDefinition'));
        $this->assertEquals(3, self::getRowCount('CustomFieldData'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $data = self::$service->getAllBasic();

        $this->assertCount(3, $data);

        $expected = new CustomFieldDefinitionData();
        $expected->setId(1);
        $expected->setName('Prueba');
        $expected->setIsEncrypted(1);
        $expected->setHelp('Ayuda');
        $expected->setModuleId(AclActionsInterface::ACCOUNT);
        $expected->setRequired(true);
        $expected->setTypeId(1);
        $expected->setShowInList(0);

        $this->assertEquals($expected, $data[0]);

        $expected = new CustomFieldDefinitionData();
        $expected->setId(2);
        $expected->setName('RSA');
        $expected->setIsEncrypted(0);
        $expected->setModuleId(AclActionsInterface::CATEGORY);
        $expected->setRequired(false);
        $expected->setTypeId(2);
        $expected->setShowInList(0);

        $this->assertEquals($expected, $data[1]);
    }

    /**
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        self::$service->deleteByIdBatch([3]);

        self::$service->deleteByIdBatch([]);

        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([3, 4]);

        $this->expectException(ConstraintException::class);

        self::$service->deleteByIdBatch([1, 2]);

        $this->assertEquals(2, self::getRowCount('CustomFieldDefinition'));
        $this->assertEquals(3, self::getRowCount('CustomFieldData'));
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
        $data->setModuleId(AclActionsInterface::ACCOUNT);
        $data->setRequired(true);
        $data->setTypeId(6);
        $data->setShowInList(0);

        $this->assertEquals(4, self::$service->create($data));

        $this->assertEquals(4, self::getRowCount('CustomFieldDefinition'));

        $this->assertEquals($data, self::$service->getById(4));

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
        $expected->setModuleId(AclActionsInterface::ACCOUNT);
        $expected->setRequired(true);
        $expected->setTypeId(1);
        $expected->setShowInList(0);

        $this->assertEquals($expected, self::$service->getById(1));

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals($expected, self::$service->getById(10));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testUpdate()
    {
        $data = new CustomFieldDefinitionData();
        $data->setId(1);
        $data->setName('PIN');
        $data->setIsEncrypted(0);
        $data->setHelp('Pin code');
        $data->setModuleId(AclActionsInterface::CLIENT);
        $data->setRequired(false);
        $data->setTypeId(2);
        $data->setShowInList(1);

        self::$service->update($data);

        $dataUpdated = self::$service->getById(1);

        $this->assertEquals($data, $dataUpdated);

        $this->assertEquals(1, self::getRowCount('CustomFieldData'));

        $data->setTypeId(100);

        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$service->update($data));
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

        $result = self::$service->search($itemSearchData);
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

        $this->assertEquals(0, self::$service->search($itemSearchData)->getNumRows());
    }
}
