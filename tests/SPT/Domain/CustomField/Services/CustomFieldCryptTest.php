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

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldService;
use SP\Domain\CustomField\Services\CustomFieldCrypt;
use SP\Domain\Task\Ports\TaskInterface;
use SP\Domain\Task\Services\TaskFactory;
use SP\Infrastructure\File\FileException;
use SPT\Generators\CustomFieldDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class CustomFieldCryptTest
 *
 * @group unitary
 */
class CustomFieldCryptTest extends UnitaryTestCase
{

    private CustomFieldService|MockObject $customFieldService;
    private CryptInterface|MockObject     $crypt;
    private CustomFieldCrypt              $customFieldCrypt;

    /**
     * @throws ServiceException
     */
    public function testUpdateMasterPassword()
    {
        $hash = self::$faker->sha1;
        $request = new UpdateMasterPassRequest('secret', 'test_secret', $hash);
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldService
            ->expects(self::once())
            ->method('getAllEncrypted')
            ->willReturn([$customFieldData]);

        $data = self::$faker->text();

        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->with($customFieldData->getData(), $customFieldData->getKey(), $request->getCurrentMasterPass())
            ->willReturn($data);

        $this->customFieldService
            ->expects(self::once())
            ->method('updateMasterPass')
            ->with(
                new Callback(static function (CustomFieldDataModel $customFieldData) use ($data) {
                    return $customFieldData->getData() === $data;
                }),
                $request->getNewMasterPass()
            );

        $this->customFieldCrypt->updateMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateMasterPasswordWithNoData()
    {
        $hash = self::$faker->sha1;
        $request = new UpdateMasterPassRequest('secret', 'test_secret', $hash);

        $this->customFieldService
            ->expects(self::once())
            ->method('getAllEncrypted')
            ->willReturn([]);

        $data = self::$faker->text();

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $this->customFieldService
            ->expects(self::never())
            ->method('updateMasterPass');

        $this->customFieldCrypt->updateMasterPassword($request);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     * @throws FileException
     */
    public function testUpdateMasterPasswordWithTask()
    {
        $task = $this->createStub(TaskInterface::class);
        $task->method('getTaskId')->willReturn(self::$faker->colorName());
        $task->method('getUid')->willReturn(self::$faker->uuid());

        TaskFactory::register($task);

        $hash = self::$faker->sha1;
        $request = new UpdateMasterPassRequest('secret', 'test_secret', $hash, $task);
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldService
            ->expects(self::once())
            ->method('getAllEncrypted')
            ->willReturn([$customFieldData]);

        $data = self::$faker->text();

        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->with($customFieldData->getData(), $customFieldData->getKey(), $request->getCurrentMasterPass())
            ->willReturn($data);

        $this->customFieldService
            ->expects(self::once())
            ->method('updateMasterPass')
            ->with(
                new Callback(static function (CustomFieldDataModel $customFieldData) use ($data) {
                    return $customFieldData->getData() === $data;
                }),
                $request->getNewMasterPass()
            );

        $this->customFieldCrypt->updateMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateMasterPasswordWithCryptError()
    {
        $hash = self::$faker->sha1;
        $request = new UpdateMasterPassRequest('secret', 'test_secret', $hash);
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldService
            ->expects(self::once())
            ->method('getAllEncrypted')
            ->willReturn([$customFieldData]);

        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->willThrowException(new RuntimeException('test'));

        $this->customFieldService
            ->expects(self::never())
            ->method('updateMasterPass');

        $this->customFieldCrypt->updateMasterPassword($request);
    }

    public function testUpdateMasterPasswordWithError()
    {
        $hash = self::$faker->sha1;
        $request = new UpdateMasterPassRequest('secret', 'test_secret', $hash);
        $customFieldData = CustomFieldDataGenerator::factory()->buildCustomFieldData();

        $this->customFieldService
            ->expects(self::once())
            ->method('getAllEncrypted')
            ->willThrowException(new RuntimeException('test'));

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $this->customFieldService
            ->expects(self::never())
            ->method('updateMasterPass');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the custom fields data');

        $this->customFieldCrypt->updateMasterPassword($request);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldService = $this->createMock(CustomFieldService::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->customFieldCrypt = new CustomFieldCrypt($this->application, $this->customFieldService, $this->crypt);
    }
}
