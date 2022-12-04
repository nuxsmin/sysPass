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
use SP\DataModel\AccountHistoryData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Adapters\AccountData;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\AccountPasswordRequest;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Ports\AccountHistoryRepositoryInterface;
use SP\Domain\Account\Services\AccountHistoryService;
use SP\Domain\Common\Services\ServiceException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountHistoryServiceTest
 *
 * @group unitary
 */
class AccountHistoryServiceTest extends UnitaryTestCase
{

    private AccountHistoryService                        $accountHistoryService;
    private MockObject|AccountHistoryRepositoryInterface $accountHistoryRepository;

    public function testCreate()
    {
        $accountData = AccountData::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccountData());
        $dto = new AccountHistoryCreateDto(
            $accountData,
            self::$faker->boolean,
            self::$faker->boolean,
            self::$faker->sha1
        );

        $this->accountHistoryRepository->expects(self::once())->method('create')->with($dto);

        $this->accountHistoryService->create($dto);
    }

    public function testDeleteByAccountIdBatch()
    {
        $ids = [1, 2, 3];

        $this->accountHistoryRepository->expects(self::once())->method('deleteByAccountIdBatch')->with($ids);

        $this->accountHistoryService->deleteByAccountIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetHistoryForAccount()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('getHistoryForAccount')->with($id);

        $this->accountHistoryService->getHistoryForAccount($id);
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetAccountsPassData()
    {
        $this->accountHistoryRepository->expects(self::once())->method('getAccountsPassData');

        $this->accountHistoryService->getAccountsPassData();
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('delete')->with($id)->willReturn(true);

        $this->accountHistoryService->delete($id);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testDeleteError()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('delete')->with($id)->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the account');

        $this->accountHistoryService->delete($id);
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();
        $accountHistoryData =
            AccountHistoryData::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccountHistoryData());
        $queryResult = new QueryResult([$accountHistoryData]);

        $this->accountHistoryRepository->expects(self::once())->method('getById')->with($id)->willReturn($queryResult);

        $this->assertEquals($accountHistoryData, $this->accountHistoryService->getById($id));
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function testGetByIdError()
    {
        $id = self::$faker->randomNumber();
        $queryResult = new QueryResult([]);

        $this->accountHistoryRepository->expects(self::once())->method('getById')->with($id)->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Error while retrieving account\'s data');

        $this->accountHistoryService->getById($id);
    }

    public function testDeleteByIdBatch()
    {
        $ids = [1, 2, 3];

        $this->accountHistoryRepository->expects(self::once())->method('deleteByIdBatch')->with($ids);

        $this->accountHistoryService->deleteByIdBatch($ids);
    }

    public function testSearch()
    {
        $itemSearchData =
            new ItemSearchData(self::$faker->text, self::$faker->randomNumber(), self::$faker->randomNumber());

        $this->accountHistoryRepository->expects(self::once())->method('search')->with($itemSearchData);

        $this->accountHistoryService->search($itemSearchData);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdatePasswordMasterPass()
    {
        $accountPasswordRequest = new AccountPasswordRequest(
            self::$faker->randomNumber(),
            new EncryptedPassword(self::$faker->password, self::$faker->password),
        );

        $this->accountHistoryRepository->expects(self::once())
            ->method('updatePassword')
            ->with($accountPasswordRequest)
            ->willReturn(true);

        $this->accountHistoryService->updatePasswordMasterPass($accountPasswordRequest);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdatePasswordMasterPassError()
    {
        $accountPasswordRequest = new AccountPasswordRequest(
            self::$faker->randomNumber(),
            new EncryptedPassword(self::$faker->password, self::$faker->password),
        );

        $this->accountHistoryRepository->expects(self::once())
            ->method('updatePassword')
            ->with($accountPasswordRequest)
            ->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the password');

        $this->accountHistoryService->updatePasswordMasterPass($accountPasswordRequest);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountHistoryRepository = $this->createMock(AccountHistoryRepositoryInterface::class);

        $this->accountHistoryService = new AccountHistoryService(
            $this->application,
            $this->accountHistoryRepository
        );
    }
}
