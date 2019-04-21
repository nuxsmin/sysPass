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

namespace SP\Tests\Services\CustomField;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\Repositories\NoSuchItemException;
use SP\Services\CustomField\CustomFieldService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Tests\Services\Account\AccountCryptServiceTest;
use function SP\Tests\setupContext;

/**
 * Class CustomFieldServiceTest
 *
 * @package SP\Tests\Services\CustomField
 */
class CustomFieldServiceTest extends DatabaseTestCase
{
    /**
     * @var CustomFieldService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_customField.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$service = $dic->get(CustomFieldService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldDefinitionDataBatch()
    {
        $this->assertEquals(3, self::$service->deleteCustomFieldDefinitionDataBatch([1, 2, 3]));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));

        $this->assertEquals(0, self::$service->deleteCustomFieldDefinitionDataBatch([]));
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testUpdateMasterPass()
    {
        $customFields = self::$service->getAllEncrypted();

        foreach ($customFields as $customField) {
            $data = Crypt::decrypt(
                $customField->getData(),
                $customField->getKey(),
                AccountCryptServiceTest::CURRENT_MASTERPASS);

            $customField->setData($data);

            $this->assertEquals(1, self::$service->updateMasterPass($customField, AccountCryptServiceTest::NEW_MASTERPASS));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllEncrypted()
    {
        $data = self::$service->getAllEncrypted();

        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]->getDefinitionId());
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals(1, $data[1]->getDefinitionId());
        $this->assertEquals(2, $data[1]->getItemId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldDataBatch()
    {
        $this->assertEquals(2, self::$service->deleteCustomFieldDataBatch([1, 2, 3], ActionsInterface::ACCOUNT));

        $this->assertEquals(1, self::$service->deleteCustomFieldDataBatch([1, 2, 3], ActionsInterface::CATEGORY));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));

        $this->assertEquals(0, self::$service->deleteCustomFieldDataBatch([], ActionsInterface::CATEGORY));

        $this->assertEquals(0, self::$service->deleteCustomFieldDataBatch([], ActionsInterface::USER));
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdateOrCreateData()
    {
        $data = new CustomFieldData();
        $data->setItemId(1);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');

        $this->assertTrue(self::$service->updateOrCreateData($data));

        $data = new CustomFieldData();
        $data->setItemId(1);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');

        $this->assertTrue(self::$service->updateOrCreateData($data));

        $data = new CustomFieldData();
        $data->setItemId(2);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');

        $this->assertTrue(self::$service->updateOrCreateData($data));

        $data = new CustomFieldData();
        $data->setItemId(2);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');

        $this->assertTrue(self::$service->updateOrCreateData($data));

        $this->assertTrue(self::$service->updateOrCreateData(new CustomFieldData()));

        $data = new CustomFieldData();
        $data->setItemId(2);
        $data->setModuleId(ActionsInterface::USER);
        $data->setDefinitionId(3);
        $data->setData('nan');

        $this->assertEquals(true, self::$service->updateOrCreateData($data));

        $this->assertEquals(5, $this->conn->getRowCount('CustomFieldData'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForModuleAndItemId()
    {
        $result = self::$service->getForModuleAndItemId(ActionsInterface::ACCOUNT, 1);

        $this->assertCount(1, $result);
        $this->assertEquals('Prueba', $result[0]->definitionName);
        $this->assertEquals(1, $result[0]->definitionId);
        $this->assertEquals(ActionsInterface::ACCOUNT, $result[0]->moduleId);
        $this->assertEquals(1, $result[0]->required);
        $this->assertEquals(0, $result[0]->showInList);
        $this->assertEquals('Ayuda', $result[0]->help);
        $this->assertEquals(1, $result[0]->isEncrypted);
        $this->assertEquals(1, $result[0]->typeId);
        $this->assertEquals('text', $result[0]->typeName);
        $this->assertEquals('Texto', $result[0]->typeText);
        $this->assertNotEmpty($result[0]->data);
        $this->assertNotEmpty($result[0]->key);

        $result = self::$service->getForModuleAndItemId(ActionsInterface::ACCOUNT, 2);

        $this->assertCount(1, $result);
        $this->assertEquals('Prueba', $result[0]->definitionName);
        $this->assertEquals(1, $result[0]->definitionId);
        $this->assertEquals(ActionsInterface::ACCOUNT, $result[0]->moduleId);
        $this->assertEquals(1, $result[0]->required);
        $this->assertEquals(0, $result[0]->showInList);
        $this->assertEquals('Ayuda', $result[0]->help);
        $this->assertEquals(1, $result[0]->isEncrypted);
        $this->assertEquals(1, $result[0]->typeId);
        $this->assertEquals('text', $result[0]->typeName);
        $this->assertEquals('Texto', $result[0]->typeText);
        $this->assertNotEmpty($result[0]->data);
        $this->assertNotEmpty($result[0]->key);

        $result = self::$service->getForModuleAndItemId(ActionsInterface::ACCOUNT, 3);

        $this->assertCount(1, $result);

        $result = self::$service->getForModuleAndItemId(ActionsInterface::CATEGORY, 1);

        $this->assertCount(2, $result);
        $this->assertEquals('SSL', $result[0]->definitionName);
        $this->assertEquals(3, $result[0]->definitionId);
        $this->assertEquals(ActionsInterface::CATEGORY, $result[0]->moduleId);
        $this->assertEquals(0, $result[0]->required);
        $this->assertEquals(0, $result[0]->showInList);
        $this->assertEquals(null, $result[0]->help);
        $this->assertEquals(1, $result[0]->isEncrypted);
        $this->assertEquals(10, $result[0]->typeId);
        $this->assertEquals('textarea', $result[0]->typeName);
        $this->assertEquals('Área de Texto', $result[0]->typeText);
        $this->assertNull($result[0]->data);
        $this->assertNull($result[0]->key);

        $this->assertEquals('RSA', $result[1]->definitionName);
        $this->assertEquals(2, $result[1]->definitionId);
        $this->assertEquals(ActionsInterface::CATEGORY, $result[1]->moduleId);
        $this->assertEquals(0, $result[1]->required);
        $this->assertEquals(0, $result[1]->showInList);
        $this->assertEquals(null, $result[1]->help);
        $this->assertEquals(0, $result[1]->isEncrypted);
        $this->assertEquals(2, $result[1]->typeId);
        $this->assertEquals('password', $result[1]->typeName);
        $this->assertEquals('Clave', $result[1]->typeText);
        $this->assertNotEmpty($result[1]->data);
        $this->assertNull($result[1]->key);

        $result = self::$service->getForModuleAndItemId(ActionsInterface::CATEGORY, 2);

        $this->assertEquals('SSL', $result[0]->definitionName);
        $this->assertEquals(3, $result[0]->definitionId);
        $this->assertEquals(ActionsInterface::CATEGORY, $result[0]->moduleId);
        $this->assertEquals(0, $result[0]->required);
        $this->assertEquals(0, $result[0]->showInList);
        $this->assertEquals(null, $result[0]->help);
        $this->assertEquals(1, $result[0]->isEncrypted);
        $this->assertEquals(10, $result[0]->typeId);
        $this->assertEquals('textarea', $result[0]->typeName);
        $this->assertEquals('Área de Texto', $result[0]->typeText);
        $this->assertNull($result[0]->data);
        $this->assertNull($result[0]->key);

        $this->assertCount(2, $result);
        $this->assertEquals('RSA', $result[1]->definitionName);
        $this->assertEquals(2, $result[1]->definitionId);
        $this->assertEquals(ActionsInterface::CATEGORY, $result[1]->moduleId);
        $this->assertEquals(0, $result[1]->required);
        $this->assertEquals(0, $result[1]->showInList);
        $this->assertEquals(null, $result[1]->help);
        $this->assertEquals(0, $result[1]->isEncrypted);
        $this->assertEquals(2, $result[1]->typeId);
        $this->assertEquals('password', $result[1]->typeName);
        $this->assertEquals('Clave', $result[1]->typeText);
        $this->assertNull($result[1]->data);
        $this->assertNull($result[1]->key);

        $result = self::$service->getForModuleAndItemId(ActionsInterface::CATEGORY, 3);

        $this->assertCount(2, $result);

        $result = self::$service->getForModuleAndItemId(ActionsInterface::USER, 1);

        $this->assertCount(0, $result);
    }

    /**
     * @throws SPException
     */
    public function testDeleteCustomFieldData()
    {
        $this->assertEquals(1, self::$service->deleteCustomFieldData(1, ActionsInterface::ACCOUNT));
        $this->assertEquals(1, self::$service->deleteCustomFieldData(2, ActionsInterface::ACCOUNT));
        $this->assertEquals(1, self::$service->deleteCustomFieldData(1, ActionsInterface::CATEGORY));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));

        $this->assertEquals(0, self::$service->deleteCustomFieldData(2, ActionsInterface::ACCOUNT));

        $this->assertEquals(0, self::$service->deleteCustomFieldData(2, ActionsInterface::CATEGORY));

        $this->assertEquals(0, self::$service->deleteCustomFieldData(2, ActionsInterface::USER));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteCustomFieldDefinitionData()
    {
        $this->assertEquals(2, self::$service->deleteCustomFieldDefinitionData(1));
        $this->assertEquals(1, self::$service->deleteCustomFieldDefinitionData(2));
        $this->assertEquals(0, self::$service->deleteCustomFieldDefinitionData(3));

        $this->assertEquals(0, $this->conn->getRowCount('CustomFieldData'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$service->getAll();

        $this->assertCount(3, $result);
        $this->assertInstanceOf(CustomFieldData::class, $result[0]);
        $this->assertInstanceOf(CustomFieldData::class, $result[1]);
    }

    /**
     * @throws ConstraintException
     * @throws CryptoException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function testCreate()
    {
        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');
        $data->setKey('nan');

        $this->assertEquals(3, self::$service->create($data));

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');
        $data->setKey('nan');

        $this->assertEquals(4, self::$service->create($data));

        $this->expectException(NoSuchItemException::class);

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::ACCOUNT);
        $data->setDefinitionId(1);
        $data->setData('cuenta');
        $data->setKey('nan');

        self::$service->create($data);

        $data->setDefinitionId(3);

        self::$service->create($data);

        $data = new CustomFieldData();
        $data->setId(2);
        $data->setModuleId(ActionsInterface::CATEGORY);
        $data->setDefinitionId(2);
        $data->setData('categoria');
        $data->setKey('nan');

        self::$service->create($data);

        $data->setDefinitionId(4);

        self::$service->create($data);

        $this->assertEquals(4, $this->conn->getRowCount('CustomFieldData'));
    }
}
