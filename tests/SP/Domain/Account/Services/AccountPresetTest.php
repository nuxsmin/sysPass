<?php
declare(strict_types=1);
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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Services\AccountPreset;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\ItemPreset\Models\Password;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Mvc\Controller\Validators\ValidatorInterface;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\ItemPresetDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountPresetServiceTest
 *
 */
#[Group('unitary')]
class AccountPresetTest extends UnitaryTestCase
{

    private ItemPresetService|MockObject $itemPresetService;
    private AccountPreset                $accountPreset;
    private ValidatorInterface|MockObject         $passwordValidator;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ValidationException
     * @throws NoSuchPropertyException
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

        $this->accountPreset->checkPasswordPreset(AccountDataGenerator::factory()->buildAccountCreateDto());
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ValidationException
     * @throws NoSuchPropertyException
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

        $this->accountPreset->checkPasswordPreset(AccountDataGenerator::factory()->buildAccountCreateDto());
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ValidationException
     * @throws NoSuchPropertyException
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

        $this->accountPreset->checkPasswordPreset(AccountDataGenerator::factory()->buildAccountCreateDto());
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ValidationException
     * @throws NoSuchPropertyException
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

        $out = $this->accountPreset->checkPasswordPreset($accountDto);

        $this->assertGreaterThan(0, $out->getPassDateChange());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $configData = $this->config->getConfigData();
        $configData->setAccountExpireEnabled(true);

        $this->itemPresetService = $this->createMock(ItemPresetService::class);
        $this->passwordValidator = $this->createMock(ValidatorInterface::class);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);
        $this->accountToUserRepository = $this->createMock(AccountToUserRepository::class);

        $this->accountPreset =
            new AccountPreset(
                $this->application,
                $this->itemPresetService,
                $this->accountToUserGroupRepository,
                $this->accountToUserRepository,
                $configData,
                $this->passwordValidator
            );
    }
}
