<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPreset\Password;
use SP\Domain\Account\Ports\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Account\Services\AccountPresetService;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetServiceInterface;
use SP\Mvc\Controller\Validators\ValidatorInterface;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\ItemPresetDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountPresetServiceTest
 *
 * @group unitary
 */
class AccountPresetServiceTest extends UnitaryTestCase
{

    private ItemPresetServiceInterface|MockObject $itemPresetService;
    private AccountPresetService                  $accountPresetService;
    private ValidatorInterface|MockObject         $passwordValidator;

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     */
    public function testCheckPasswordPreset(): void
    {
        $this->config->getConfigData()->setAccountExpireEnabled(true);

        $itemPresetDataGenerator = ItemPresetDataGenerator::factory();
        $itemPreset = $itemPresetDataGenerator->buildItemPresetData($itemPresetDataGenerator->buildPassword())
                                              ->mutate(['fixed' => 1]);

        $this->itemPresetService
            ->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD)
            ->willReturn($itemPreset);
        $this->passwordValidator
            ->expects(self::once())
            ->method('validate')
            ->with(self::callback(static fn($password) => $password instanceof Password));

        $this->accountPresetService->checkPasswordPreset(AccountDataGenerator::factory()->buildAccountCreateDto());
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     */
    public function testCheckPasswordPresetThrowsValidatorException(): void
    {
        $this->config->getConfigData()->setAccountExpireEnabled(true);

        $itemPresetDataGenerator = ItemPresetDataGenerator::factory();
        $itemPreset = $itemPresetDataGenerator->buildItemPresetData($itemPresetDataGenerator->buildPassword())
                                              ->mutate(['fixed' => 1]);

        $this->itemPresetService
            ->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD)
            ->willReturn($itemPreset);
        $this->passwordValidator
            ->expects(self::once())
            ->method('validate')
            ->with(self::callback(static fn($password) => $password instanceof Password))
            ->willThrowException(new ValidationException('test'));

        $this->expectException(ValidationException::class);

        $this->accountPresetService->checkPasswordPreset(AccountDataGenerator::factory()->buildAccountCreateDto());
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     */
    public function testCheckPasswordPresetWithoutFixed(): void
    {
        $itemPresetDataGenerator = ItemPresetDataGenerator::factory();
        $itemPreset = $itemPresetDataGenerator->buildItemPresetData($itemPresetDataGenerator->buildPassword())
                                              ->mutate(['fixed' => 0]);

        $this->itemPresetService
            ->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD)
            ->willReturn($itemPreset);
        $this->passwordValidator
            ->expects(self::never())
            ->method('validate');

        $this->accountPresetService->checkPasswordPreset(AccountDataGenerator::factory()->buildAccountCreateDto());
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     */
    public function testCheckPasswordPresetWithPassDateChangeModified(): void
    {
        $itemPresetDataGenerator = ItemPresetDataGenerator::factory();
        $passwordPreset = $itemPresetDataGenerator->buildPassword();

        $itemPreset = $itemPresetDataGenerator->buildItemPresetData($passwordPreset)->mutate(['fixed' => 1]);

        $this->itemPresetService
            ->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD)
            ->willReturn($itemPreset);
        $this->passwordValidator
            ->expects(self::once())
            ->method('validate');

        $accountDto = AccountDataGenerator::factory()->buildAccountCreateDto();
        $accountDto = $accountDto->set('passDateChange', 0);

        $out = $this->accountPresetService->checkPasswordPreset($accountDto);

        $this->assertGreaterThan(0, $out->getPassDateChange());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $configData = $this->config->getConfigData();
        $configData->setAccountExpireEnabled(true);

        $this->itemPresetService = $this->createMock(ItemPresetServiceInterface::class);
        $this->passwordValidator = $this->createMock(ValidatorInterface::class);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepositoryInterface::class);
        $this->accountToUserRepository = $this->createMock(AccountToUserRepositoryInterface::class);

        $this->accountPresetService =
            new AccountPresetService(
                $this->application,
                $this->itemPresetService,
                $this->accountToUserGroupRepository,
                $this->accountToUserRepository,
                $configData,
                $this->passwordValidator
            );
    }
}
