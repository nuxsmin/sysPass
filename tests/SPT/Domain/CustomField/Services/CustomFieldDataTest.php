<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Domain\CustomField\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Domain\CustomField\Ports\CustomFieldDataRepository;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionRepository;
use SP\Domain\CustomField\Services\CustomFieldData;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\CustomFieldDataGenerator;
use SPT\UnitaryTestCase;
use TypeError;

/**
 * Class CustomFieldDataTest
 *
 */
#[Group('unitary')]
class CustomFieldDataTest extends UnitaryTestCase
{

    private CustomFieldDataRepository|MockObject       $customFieldDataRepository;
    private CustomFieldDefinitionRepository|MockObject $customFieldDefinitionRepository;
    private CryptInterface|MockObject                  $crypt;
    private CustomFieldData                            $customFieldData;

    /**
     * @throws ServiceException
     */
    public function testDelete()
    {
        $itemsId = array_map(static fn() => self::$faker->randomNumber(), range(0, 5));
        $moduleId = self::$faker->randomNumber();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('deleteBatch')
            ->with($itemsId, $moduleId);

        $this->customFieldData->delete($itemsId, $moduleId);
    }

    /**
     * @throws ServiceException
     */
    public function testDeleteWithConstraintError()
    {
        $itemsId = array_map(static fn() => self::$faker->randomNumber(), range(0, 5));
        $moduleId = self::$faker->randomNumber();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('deleteBatch')
            ->willThrowException(ConstraintException::error('test'));

        $this->expectException(ServiceException::class);

        $this->customFieldData->delete($itemsId, $moduleId);
    }

    /**
     * @throws ServiceException
     */
    public function testDeleteWithQueryError()
    {
        $itemsId = array_map(static fn() => self::$faker->randomNumber(), range(0, 5));
        $moduleId = self::$faker->randomNumber();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('deleteBatch')
            ->willThrowException(QueryException::error('test'));

        $this->expectException(ServiceException::class);

        $this->customFieldData->delete($itemsId, $moduleId);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws ContextException
     */
    public function testCreateWithEncrypted()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();
        $customFieldDefinition = $this->createMock(CustomFieldDefinitionModel::class);
        $customFieldDefinition->expects(self::once())
                              ->method('getIsEncrypted')
                              ->willReturn(1);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($customFieldData->getDefinitionId())
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'secret');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with('secret')
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', 'secret')
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (CustomFieldDataModel $current) use ($customFieldData) {
                    return $current->getData() === 'secret_data'
                           && $current->getKey() === 'secret_key'
                           && $customFieldData->getDefinitionId() === $current->getDefinitionId()
                           && $customFieldData->getItemId() === $current->getItemId()
                           && $customFieldData->getModuleId() === $current->getModuleId();
                })
            );

        $this->customFieldData->create($customFieldData);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testCreateWithNoEncrypted()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();
        $customFieldDefinition = $this->createMock(CustomFieldDefinitionModel::class);
        $customFieldDefinition->expects(self::once())
                              ->method('getIsEncrypted')
                              ->willReturn(0);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($customFieldData->getDefinitionId())
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $this->crypt
            ->expects(self::never())
            ->method('makeSecuredKey');

        $this->crypt
            ->expects(self::never())
            ->method('encrypt');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('create')
            ->with($customFieldData);

        $this->customFieldData->create($customFieldData);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws ContextException
     */
    public function testCreateWithEncryptedWithException()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();
        $customFieldDefinition = $this->createMock(CustomFieldDefinitionModel::class);
        $customFieldDefinition->expects(self::once())
                              ->method('getIsEncrypted')
                              ->willReturn(1);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($customFieldData->getDefinitionId())
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'secret');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with('secret')
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', 'secret')
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('create')
            ->willThrowException(SPException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->customFieldData->create($customFieldData);
    }

    /**
     * @throws ServiceException
     */
    public function testGetBy()
    {
        $moduleId = self::$faker->randomNumber();
        $itemId = self::$faker->randomNumber();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getForModuleAndItemId')
            ->with($moduleId, $itemId)
            ->willReturn(new QueryResult([1]));

        $out = $this->customFieldData->getBy($moduleId, $itemId);

        $this->assertEquals([1], $out);
    }

    /**
     * @throws ServiceException
     */
    public function testGetByWithException()
    {
        $moduleId = self::$faker->randomNumber();
        $itemId = self::$faker->randomNumber();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getForModuleAndItemId')
            ->with($moduleId, $itemId)
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->customFieldData->getBy($moduleId, $itemId);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     * @throws ContextException
     */
    public function testUpdateOrCreateWithNonExisting()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('checkExists')
            ->with($customFieldData->getItemId(), $customFieldData->getModuleId(), $customFieldData->getDefinitionId())
            ->willReturn(false);

        $customFieldDefinition = $this->createMock(CustomFieldDefinitionModel::class);
        $customFieldDefinition->expects(self::once())
                              ->method('getIsEncrypted')
                              ->willReturn(1);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($customFieldData->getDefinitionId())
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'secret');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with('secret')
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', 'secret')
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (CustomFieldDataModel $current) use ($customFieldData) {
                    return $current->getData() === 'secret_data'
                           && $current->getKey() === 'secret_key'
                           && $customFieldData->getDefinitionId() === $current->getDefinitionId()
                           && $customFieldData->getItemId() === $current->getItemId()
                           && $customFieldData->getModuleId() === $current->getModuleId();
                })
            );

        $this->customFieldData->updateOrCreate($customFieldData);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     * @throws ContextException
     */
    public function testUpdateOrCreateWithNonExistingAndException()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('checkExists')
            ->with($customFieldData->getItemId(), $customFieldData->getModuleId(), $customFieldData->getDefinitionId())
            ->willReturn(false);

        $customFieldDefinition = $this->createMock(CustomFieldDefinitionModel::class);
        $customFieldDefinition->expects(self::once())
                              ->method('getIsEncrypted')
                              ->willReturn(1);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($customFieldData->getDefinitionId())
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'secret');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with('secret')
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', 'secret')
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('create')
            ->willThrowException(SPException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->customFieldData->updateOrCreate($customFieldData);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     * @throws ContextException
     */
    public function testUpdateOrCreateWithExisting()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('checkExists')
            ->with($customFieldData->getItemId(), $customFieldData->getModuleId(), $customFieldData->getDefinitionId())
            ->willReturn(true);

        $customFieldDefinition = $this->createMock(CustomFieldDefinitionModel::class);
        $customFieldDefinition->expects(self::once())
                              ->method('getIsEncrypted')
                              ->willReturn(1);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($customFieldData->getDefinitionId())
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'secret');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with('secret')
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', 'secret')
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(static function (CustomFieldDataModel $current) use ($customFieldData) {
                    return $current->getData() === 'secret_data'
                           && $current->getKey() === 'secret_key'
                           && $customFieldData->getDefinitionId() === $current->getDefinitionId()
                           && $customFieldData->getItemId() === $current->getItemId()
                           && $customFieldData->getModuleId() === $current->getModuleId();
                })
            );

        $this->customFieldData->updateOrCreate($customFieldData);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     * @throws ContextException
     */
    public function testUpdateOrCreateWithExistingAndException()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('checkExists')
            ->with($customFieldData->getItemId(), $customFieldData->getModuleId(), $customFieldData->getDefinitionId())
            ->willReturn(true);

        $customFieldDefinition = $this->createMock(CustomFieldDefinitionModel::class);
        $customFieldDefinition->expects(self::once())
                              ->method('getIsEncrypted')
                              ->willReturn(1);

        $this->customFieldDefinitionRepository
            ->expects(self::once())
            ->method('getById')
            ->with($customFieldData->getDefinitionId())
            ->willReturn(new QueryResult([$customFieldDefinition]));

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'secret');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with('secret')
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', 'secret')
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('update')
            ->willThrowException(SPException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->customFieldData->updateOrCreate($customFieldData);
    }

    /**
     * @throws ServiceException
     * @throws ContextException
     */
    public function testDecrypt()
    {
        $data = self::$faker->text();
        $key = self::$faker->password();

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'secret');

        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->with($data, $key, 'secret')
            ->willReturn('secret_data');

        $out = $this->customFieldData->decrypt($data, $key);

        $this->assertEquals('secret_data', $out);
    }

    /**
     * @throws ServiceException
     */
    public function testDecryptWithNoData()
    {
        $key = self::$faker->password();

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $out = $this->customFieldData->decrypt('', $key);

        $this->assertEquals(null, $out);
    }

    /**
     * @throws ServiceException
     */
    public function testDecryptWithNoKey()
    {
        $data = self::$faker->text();

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $out = $this->customFieldData->decrypt($data, '');

        $this->assertEquals(null, $out);
    }

    public function testGetAll()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $queryResult = new QueryResult([$customFieldData]);

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn($queryResult);

        $out = $this->customFieldData->getAll();

        $this->assertEquals([$customFieldData], $out);
    }

    public function testGetAllWithWrongType()
    {
        $queryResult = new QueryResult([1]);

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn($queryResult);

        $this->expectException(TypeError::class);

        $this->customFieldData->getAll();
    }

    public function testGetAllEncrypted()
    {
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $queryResult = new QueryResult([$customFieldData]);

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getAllEncrypted')
            ->willReturn($queryResult);

        $out = $this->customFieldData->getAllEncrypted();

        $this->assertEquals([$customFieldData], $out);
    }

    public function testGetAllEncryptedWithWrongType()
    {
        $queryResult = new QueryResult([1]);

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getAllEncrypted')
            ->willReturn($queryResult);

        $this->expectException(TypeError::class);

        $this->customFieldData->getAllEncrypted();
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testUpdateMasterPass()
    {
        $password = self::$faker->password();
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with($password)
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', $password)
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(static function (CustomFieldDataModel $current) use ($customFieldData) {
                    return $current->getData() === 'secret_data'
                           && $current->getKey() === 'secret_key'
                           && $customFieldData->getDefinitionId() === $current->getDefinitionId()
                           && $customFieldData->getItemId() === $current->getItemId()
                           && $customFieldData->getModuleId() === $current->getModuleId();
                })
            );

        $this->customFieldData->updateMasterPass($customFieldData, $password);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testUpdateMasterPassWithException()
    {
        $password = self::$faker->password();
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with($password)
            ->willReturn('secret_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with($customFieldData->getData(), 'secret_key', $password)
            ->willReturn('secret_data');

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(static function (CustomFieldDataModel $current) use ($customFieldData) {
                    return $current->getData() === 'secret_data'
                           && $current->getKey() === 'secret_key'
                           && $customFieldData->getDefinitionId() === $current->getDefinitionId()
                           && $customFieldData->getItemId() === $current->getItemId()
                           && $customFieldData->getModuleId() === $current->getModuleId();
                })
            )
            ->willThrowException(SPException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->customFieldData->updateMasterPass($customFieldData, $password);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldDataRepository = $this->createMock(CustomFieldDataRepository::class);
        $this->customFieldDefinitionRepository = $this->createMock(CustomFieldDefinitionRepository::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->customFieldData = new CustomFieldData(
            $this->application,
            $this->customFieldDataRepository,
            $this->customFieldDefinitionRepository,
            $this->crypt
        );
    }

}
