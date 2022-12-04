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
use SP\DataModel\ItemPresetData;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Account\Services\AccountPresetService;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetServiceInterface;
use SP\Mvc\Controller\Validators\ValidatorInterface;
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
        $itemPreset = ItemPresetData::buildFromSimpleModel(
            $itemPresetDataGenerator->buildItemPresetData($itemPresetDataGenerator->buildPasswordPreset())
        );
        $itemPreset->fixed = 1;

        $this->itemPresetService
            ->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD)
            ->willReturn($itemPreset);
        $this->passwordValidator
            ->expects(self::once())
            ->method('validate')
            ->with(self::callback(static fn($password) => $password instanceof Password));

        $this->accountPresetService->checkPasswordPreset($this->buildAccountRequest());
    }

    private function buildAccountRequest(): AccountRequest
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = self::$faker->randomNumber();
        $accountRequest->name = self::$faker->name;
        $accountRequest->login = self::$faker->userName;
        $accountRequest->url = self::$faker->url;
        $accountRequest->notes = self::$faker->text;
        $accountRequest->userEditId = self::$faker->randomNumber();
        $accountRequest->passDateChange = self::$faker->unixTime;
        $accountRequest->clientId = self::$faker->randomNumber();
        $accountRequest->categoryId = self::$faker->randomNumber();
        $accountRequest->isPrivate = self::$faker->numberBetween(0, 1);
        $accountRequest->isPrivateGroup = self::$faker->numberBetween(0, 1);
        $accountRequest->parentId = self::$faker->randomNumber();
        $accountRequest->userId = self::$faker->randomNumber();
        $accountRequest->userGroupId = self::$faker->randomNumber();
        $accountRequest->key = self::$faker->password;
        $accountRequest->pass = self::$faker->password(10, 10);

        return $accountRequest;
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
        $itemPreset = ItemPresetData::buildFromSimpleModel(
            $itemPresetDataGenerator->buildItemPresetData($itemPresetDataGenerator->buildPasswordPreset())
        );
        $itemPreset->fixed = 1;

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

        $this->accountPresetService->checkPasswordPreset($this->buildAccountRequest());
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
        $itemPreset = ItemPresetData::buildFromSimpleModel(
            $itemPresetDataGenerator->buildItemPresetData($itemPresetDataGenerator->buildPasswordPreset())
        );

        $itemPreset->fixed = 0;

        $this->itemPresetService
            ->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD)
            ->willReturn($itemPreset);
        $this->passwordValidator
            ->expects(self::never())
            ->method('validate');

        $this->accountPresetService->checkPasswordPreset($this->buildAccountRequest());
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
        $passwordPreset = $itemPresetDataGenerator->buildPasswordPreset();

        $itemPreset = ItemPresetData::buildFromSimpleModel(
            $itemPresetDataGenerator->buildItemPresetData($passwordPreset)
        );
        $itemPreset->fixed = 1;

        $this->itemPresetService
            ->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD)
            ->willReturn($itemPreset);
        $this->passwordValidator
            ->expects(self::once())
            ->method('validate');

        $accountRequest = $this->buildAccountRequest();
        $accountRequest->passDateChange = 0;

        $this->accountPresetService->checkPasswordPreset($accountRequest);

        $this->assertGreaterThan(0, $accountRequest->passDateChange);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $configData = $this->config->getConfigData();
        $configData->setAccountExpireEnabled(true);

        $this->itemPresetService = $this->createMock(ItemPresetServiceInterface::class);
        $this->passwordValidator = $this->createMock(ValidatorInterface::class);

        $this->accountPresetService =
            new AccountPresetService($this->itemPresetService, $configData, $this->passwordValidator);
    }
}
