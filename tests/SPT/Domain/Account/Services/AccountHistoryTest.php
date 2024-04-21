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

namespace SPT\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Ports\AccountHistoryRepository;
use SP\Domain\Account\Services\AccountHistory;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\AccountDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class AccountHistoryServiceTest
 *
 */
#[Group('unitary')]
class AccountHistoryTest extends UnitaryTestCase
{

    private AccountHistory                      $accountHistory;
    private MockObject|AccountHistoryRepository $accountHistoryRepository;

    public function testCreate()
    {
        $accountData = AccountDataGenerator::factory()->buildAccount();
        $dto = new AccountHistoryCreateDto(
            $accountData,
            self::$faker->boolean,
            self::$faker->boolean,
            self::$faker->sha1
        );

        $this->accountHistoryRepository->expects(self::once())->method('create')->with($dto);

        $this->accountHistory->create($dto);
    }

    public function testDeleteByAccountIdBatch()
    {
        $ids = [1, 2, 3];

        $this->accountHistoryRepository->expects(self::once())->method('deleteByAccountIdBatch')->with($ids);

        $this->accountHistory->deleteByAccountIdBatch($ids);
    }

    /**
     * @throws SPException
     */
    public function testGetHistoryForAccount()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('getHistoryForAccount')->with($id);

        $this->accountHistory->getHistoryForAccount($id);
    }

    /**
     * @throws SPException
     */
    public function testGetAccountsPassData()
    {
        $this->accountHistoryRepository->expects(self::once())->method('getAccountsPassData');

        $this->accountHistory->getAccountsPassData();
    }

    /**
     * @throws ServiceException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('delete')->with($id)->willReturn(true);

        $this->accountHistory->delete($id);
    }

    /**
     * @throws ServiceException
     */
    public function testDeleteError()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('delete')->with($id)->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the account');

        $this->accountHistory->delete($id);
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();
        $accountHistoryData =
            AccountHistoryModel::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccountHistoryData());
        $queryResult = new QueryResult([$accountHistoryData]);

        $this->accountHistoryRepository->expects(self::once())->method('getById')->with($id)->willReturn($queryResult);

        $this->assertEquals($accountHistoryData, $this->accountHistory->getById($id));
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetByIdError()
    {
        $id = self::$faker->randomNumber();
        $queryResult = new QueryResult([]);

        $this->accountHistoryRepository->expects(self::once())->method('getById')->with($id)->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Error while retrieving account\'s data');

        $this->accountHistory->getById($id);
    }

    public function testDeleteByIdBatch()
    {
        $ids = [1, 2, 3];

        $this->accountHistoryRepository->expects(self::once())->method('deleteByIdBatch')->with($ids);

        $this->accountHistory->deleteByIdBatch($ids);
    }

    public function testSearch()
    {
        $itemSearchData =
            new ItemSearchDto(self::$faker->text, self::$faker->randomNumber(), self::$faker->randomNumber());

        $this->accountHistoryRepository->expects(self::once())->method('search')->with($itemSearchData);

        $this->accountHistory->search($itemSearchData);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdatePasswordMasterPass()
    {
        $id = self::$faker->randomNumber();
        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password, self::$faker->sha1);

        $this->accountHistoryRepository->expects(self::once())
                                       ->method('updatePassword')
                                       ->with($id, $encryptedPassword)
                                       ->willReturn(true);

        $this->accountHistory->updatePasswordMasterPass($id, $encryptedPassword);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdatePasswordMasterPassError()
    {
        $id = self::$faker->randomNumber();
        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password, self::$faker->sha1);

        $this->accountHistoryRepository->expects(self::once())
                                       ->method('updatePassword')
                                       ->with($id, $encryptedPassword)
                                       ->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the password');

        $this->accountHistory->updatePasswordMasterPass($id, $encryptedPassword);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountHistoryRepository = $this->createMock(AccountHistoryRepository::class);

        $this->accountHistory = new AccountHistory(
            $this->application,
            $this->accountHistoryRepository
        );
    }
}
