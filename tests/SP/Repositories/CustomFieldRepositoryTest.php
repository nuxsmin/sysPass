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
use SP\DataModel\CustomFieldData;
use SP\Repositories\CustomField\CustomFieldRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class CustomFieldRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class CustomFieldRepositoryTest extends DatabaseTestCase
{
    /**
     * @var CustomFieldRepository
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
        self::$repository = $dic->get(CustomFieldRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldDataBatch()
    {
        $this->assertEquals(1, self::$repository->deleteCustomFieldDataBatch([1, 2, 3], ActionsInterface::ACCOUNT));

        $this->assertEquals(1, self::$repository->deleteCustomFieldDataBatch([1, 2, 3], ActionsInterface::CATEGORY));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));

        $this->assertEquals(0, self::$repository->deleteCustomFieldDataBatch([], ActionsInterface::CATEGORY));

        $this->assertEquals(0, self::$repository->deleteCustomFieldDataBatch([], ActionsInterface::USER));

    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldDataForDefinition()
    {
        $this->assertEquals(1, self::$repository->deleteCustomFieldDataForDefinition(1, ActionsInterface::ACCOUNT, 1));
        $this->assertEquals(0, self::$repository->deleteCustomFieldDataForDefinition(1, ActionsInterface::ACCOUNT, 2));
        $this->assertEquals(0, self::$repository->deleteCustomFieldDataForDefinition(10, ActionsInterface::ACCOUNT, 3));

        $this->assertEquals(1, self::$repository->deleteCustomFieldDataForDefinition(1, ActionsInterface::CATEGORY, 2));
        $this->assertEquals(0, self::$repository->deleteCustomFieldDataForDefinition(1, ActionsInterface::CATEGORY, 1));
        $this->assertEquals(0, self::$repository->deleteCustomFieldDataForDefinition(10, ActionsInterface::CATEGORY, 3));

        $this->assertEquals(0, self::$repository->deleteCustomFieldDataForDefinition(1, ActionsInterface::USER, 1));
        $this->assertEquals(0, self::$repository->deleteCustomFieldDataForDefinition(1, ActionsInterface::USER, 2));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckExists()
    {
        $data = new CustomFieldData();
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setId(1);

        $this->assertFalse(self::$repository->checkExists($data));

        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(1);
        $data->setId(1);

        $this->assertFalse(self::$repository->checkExists($data));

        $data->setModuleId(ActionsInterface::USER);
        $data->setDefinitionId(1);
        $data->setId(1);

        $this->assertFalse(self::$repository->checkExists($data));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllEncrypted()
    {
        $result = self::$repository->getAllEncrypted();
        $this->assertEquals(1, $result->getNumRows());

        /** @var CustomFieldData[] $data */
        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertInstanceOf(CustomFieldData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals(ActionsInterface::ACCOUNT, $data[0]->getModuleId());
        $this->assertEquals(1, $data[0]->getItemId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldDefinitionDataBatch()
    {
        $this->assertEquals(2, self::$repository->deleteCustomFieldDefinitionDataBatch([1, 2, 3]));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));

        $this->assertEquals(0, self::$repository->deleteCustomFieldDefinitionDataBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();
        $this->assertEquals(2, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(2, $data);
        $this->assertInstanceOf(CustomFieldData::class, $data[0]);
        $this->assertInstanceOf(CustomFieldData::class, $data[1]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldData()
    {
        $this->assertEquals(1, self::$repository->deleteCustomFieldData(1, ActionsInterface::ACCOUNT));
        $this->assertEquals(1, self::$repository->deleteCustomFieldData(1, ActionsInterface::CATEGORY));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));

        $this->assertEquals(0, self::$repository->deleteCustomFieldData(2, ActionsInterface::ACCOUNT));

        $this->assertEquals(0, self::$repository->deleteCustomFieldData(2, ActionsInterface::CATEGORY));

        $this->assertEquals(0, self::$repository->deleteCustomFieldData(2, ActionsInterface::USER));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForModuleById()
    {
        $result = self::$repository->getForModuleAndItemId(ActionsInterface::ACCOUNT, 1);
        $this->assertEquals(1, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals('Prueba', $data[0]->definitionName);
        $this->assertEquals(1, $data[0]->definitionId);
        $this->assertEquals(ActionsInterface::ACCOUNT, $data[0]->moduleId);
        $this->assertEquals(1, $data[0]->required);
        $this->assertEquals(0, $data[0]->showInList);
        $this->assertEquals('Ayuda', $data[0]->help);
        $this->assertEquals(1, $data[0]->isEncrypted);
        $this->assertEquals(1, $data[0]->typeId);
        $this->assertEquals('text', $data[0]->typeName);
        $this->assertEquals('Texto', $data[0]->typeText);
        $this->assertNotEmpty($data[0]->data);
        $this->assertNotEmpty($data[0]->key);

        $result = self::$repository->getForModuleAndItemId(ActionsInterface::ACCOUNT, 2);
        $this->assertEquals(1, $result->getNumRows());
        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals('Prueba', $data[0]->definitionName);
        $this->assertEquals(1, $data[0]->definitionId);
        $this->assertEquals(ActionsInterface::ACCOUNT, $data[0]->moduleId);
        $this->assertEquals(1, $data[0]->required);
        $this->assertEquals(0, $data[0]->showInList);
        $this->assertEquals('Ayuda', $data[0]->help);
        $this->assertEquals(1, $data[0]->isEncrypted);
        $this->assertEquals(1, $data[0]->typeId);
        $this->assertEquals('text', $data[0]->typeName);
        $this->assertEquals('Texto', $data[0]->typeText);
        $this->assertNull($data[0]->data);
        $this->assertNull($data[0]->key);

        $result = self::$repository->getForModuleAndItemId(ActionsInterface::ACCOUNT, 3);

        $this->assertEquals(1, $result->getNumRows());

        $result = self::$repository->getForModuleAndItemId(ActionsInterface::CATEGORY, 1);
        $this->assertEquals(2, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(2, $data);
        $this->assertEquals('RSA', $data[0]->definitionName);
        $this->assertEquals(2, $data[0]->definitionId);
        $this->assertEquals(ActionsInterface::CATEGORY, $data[0]->moduleId);
        $this->assertEquals(0, $data[0]->required);
        $this->assertEquals(0, $data[0]->showInList);
        $this->assertEquals(null, $data[0]->help);
        $this->assertEquals(0, $data[0]->isEncrypted);
        $this->assertEquals(2, $data[0]->typeId);
        $this->assertEquals('password', $data[0]->typeName);
        $this->assertEquals('Clave', $data[0]->typeText);
        $this->assertNotEmpty($data[0]->data);
        $this->assertNull($data[0]->key);

        $result = self::$repository->getForModuleAndItemId(ActionsInterface::CATEGORY, 2);
        $this->assertEquals(2, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(2, $data);
        $this->assertEquals('RSA', $data[0]->definitionName);
        $this->assertEquals(2, $data[0]->definitionId);
        $this->assertEquals(ActionsInterface::CATEGORY, $data[0]->moduleId);
        $this->assertEquals(0, $data[0]->required);
        $this->assertEquals(0, $data[0]->showInList);
        $this->assertEquals(null, $data[0]->help);
        $this->assertEquals(0, $data[0]->isEncrypted);
        $this->assertEquals(2, $data[0]->typeId);
        $this->assertEquals('password', $data[0]->typeName);
        $this->assertEquals('Clave', $data[0]->typeText);
        $this->assertNull($data[0]->data);
        $this->assertNull($data[0]->key);

        $result = self::$repository->getForModuleAndItemId(ActionsInterface::CATEGORY, 3);
        $this->assertEquals(2, $result->getNumRows());

        $result = self::$repository->getForModuleAndItemId(ActionsInterface::USER, 1);
        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');
        $data->setKey('nan');

        $this->assertEquals(3, self::$repository->create($data));

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');
        $data->setKey('nan');

        $this->assertEquals(4, self::$repository->create($data));

        $this->expectException(ConstraintException::class);

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');
        $data->setKey('nan');

        self::$repository->create($data);

        $data->setDefinitionId(3);

        self::$repository->create($data);

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');
        $data->setKey('nan');

        self::$repository->create($data);

        $data->setDefinitionId(4);

        self::$repository->create($data);

        $this->assertEquals(4, $this->conn->getRowCount('CustomFieldData'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldDefinitionData()
    {
        $this->assertEquals(1, self::$repository->deleteCustomFieldDefinitionData(1));
        $this->assertEquals(1, self::$repository->deleteCustomFieldDefinitionData(2));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $data = new CustomFieldData();
        $data->setId(1);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');
        $data->setKey('nan');

        $this->assertEquals(0, self::$repository->update($data));

        $data = new CustomFieldData();
        $data->setId(1);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');
        $data->setKey('nan');

        $this->assertEquals(0, self::$repository->update($data));


        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');
        $data->setKey('nan');

        $this->assertEquals(0, self::$repository->update($data));

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');
        $data->setKey('nan');

        $this->assertEquals(0, self::$repository->update($data));

        $this->assertEquals(0, self::$repository->update(new CustomFieldData()));

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::USER);
        $data->setDefinitionId(3);
        $data->setData('nan');
        $data->setKey('nan');

        $this->assertEquals(0, self::$repository->update($data));

        $this->assertEquals(2, $this->conn->getRowCount('CustomFieldData'));
    }
}
