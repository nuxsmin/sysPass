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

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\ItemPreset;
use SP\Domain\Account\Ports\AccountCryptServiceInterface;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Account\Ports\AccountItemsServiceInterface;
use SP\Domain\Account\Ports\AccountPresetServiceInterface;
use SP\Domain\Account\Ports\AccountRepositoryInterface;
use SP\Domain\Account\Ports\AccountToTagRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Account\Services\AccountService;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigServiceInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\ItemSearchDataGenerator;
use SP\Tests\Stubs\AccountRepositoryStub;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountServiceTest
 *
 * @group unitary
 */
class AccountServiceTest extends UnitaryTestCase
{
    private AccountRepositoryInterface|MockObject            $accountRepository;
    private AccountToUserGroupRepositoryInterface|MockObject $accountToUserGroupRepository;
    private AccountToUserRepositoryInterface|MockObject      $accountToUserRepository;
    private AccountToTagRepositoryInterface|MockObject       $accountToTagRepository;
    private ItemPresetServiceInterface|MockObject            $itemPresetService;
    private AccountHistoryServiceInterface|MockObject        $accountHistoryService;
    private ConfigServiceInterface|MockObject                $configService;
    private AccountCryptServiceInterface|MockObject          $accountCryptService;
    private AccountPresetServiceInterface|MockObject         $accountPresetService;
    private AccountItemsServiceInterface|MockObject          $accountItemsService;
    private AccountService                                   $accountService;

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdate()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->getUserData()->setIsAdminApp(true);

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);
        $this->accountRepository->expects(self::exactly(2))->method('getById')
                                ->with($id)
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->accountRepository->expects(self::once())->method('update')
                                ->with($id, Account::update($accountUpdateDto), true, true);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(true, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->update($id, $accountUpdateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateUserCannotChangePermissionsWithoutAdminApp()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->getUserData()->setIsAdminApp(false);

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);
        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->accountRepository->expects(self::once())->method('update')
                                ->with($id, Account::update($accountUpdateDto), false, false);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(false, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->update($id, $accountUpdateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateUserCanChangePermissionsWithAdminAcc()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->getUserData()->setIsAdminApp(false);
        $this->context->getUserData()->setIsAdminAcc(true);
        $this->context->getUserProfile()->setAccPermission(false);

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);
        $this->accountRepository->expects(self::exactly(2))->method('getById')
                                ->with($id)
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->accountRepository->expects(self::once())->method('update')
                                ->with($id, Account::update($accountUpdateDto), true, true);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(true, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->update($id, $accountUpdateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateUserCanChangePermissionsWithProfilePermission()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->getUserData()->setIsAdminApp(false);
        $this->context->getUserData()->setIsAdminAcc(false);
        $this->context->getUserProfile()->setAccPermission(true);

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);
        $this->accountRepository->expects(self::exactly(2))->method('getById')
                                ->with($id)
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->accountRepository->expects(self::once())->method('update')
                                ->with($id, Account::update($accountUpdateDto), false, false);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(true, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->update($id, $accountUpdateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateWithPresetPrivateForUser()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();
        $itemPreset = new ItemPreset([
            'id'            => self::$faker->randomNumber(),
            'type'          => self::$faker->colorName,
            'userId'        => self::$faker->randomNumber(),
            'userGroupId'   => self::$faker->randomNumber(),
            'userProfileId' => self::$faker->randomNumber(),
            'fixed'         => 1,
            'priority'      => self::$faker->randomNumber(),
            'data'          => serialize(new AccountPrivate(true, true)),
        ]);

        $userData = $this->context->getUserData();
        $userData->setIsAdminApp(true);
        $userData->setId($accountUpdateDto->getUserId());
        $userData->setUserGroupId($accountUpdateDto->getUserGroupId());

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);
        $account = new Account(
            [
                'userId'      => $accountUpdateDto->getUserId(),
                'userGroupId' => self::$faker->randomNumber(),
            ]
        );

        $this->accountRepository->expects(self::exactly(3))->method('getById')
                                ->with($id)
                                ->willReturnOnConsecutiveCalls(
                                    new QueryResult([$accountDataGenerator->buildAccount()]),
                                    new QueryResult([$accountDataGenerator->buildAccount()]),
                                    new QueryResult([$account]),
                                );
        $this->accountRepository->expects(self::once())->method('update')
                                ->with(
                                    $id,
                                    new Callback(function (Account $account) {
                                        return $account->getIsPrivate() === 1 && $account->getIsPrivateGroup() === 0;
                                    }),
                                    true,
                                    true
                                );
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(
                                      true,
                                      $id,
                                      $accountUpdateDto->withPrivate(true)
                                                       ->withPrivateGroup(false)
                                                       ->withUserGroupId($account->getUserGroupId())
                                  );

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->update($id, $accountUpdateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateWithPresetPrivateForGroup()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();
        $itemPreset = new ItemPreset([
            'id'            => self::$faker->randomNumber(),
            'type'          => self::$faker->colorName,
            'userId'        => self::$faker->randomNumber(),
            'userGroupId'   => self::$faker->randomNumber(),
            'userProfileId' => self::$faker->randomNumber(),
            'fixed'         => 1,
            'priority'      => self::$faker->randomNumber(),
            'data'          => serialize(new AccountPrivate(true, true)),
        ]);

        $userData = $this->context->getUserData();
        $userData->setIsAdminApp(true);
        $userData->setId($accountUpdateDto->getUserId());
        $userData->setUserGroupId($accountUpdateDto->getUserGroupId());

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);
        $account = new Account(
            [
                'userId'      => self::$faker->randomNumber(),
                'userGroupId' => $accountUpdateDto->getUserGroupId(),
            ]
        );

        $this->accountRepository->expects(self::exactly(3))->method('getById')
                                ->with($id)
                                ->willReturnOnConsecutiveCalls(
                                    new QueryResult([$accountDataGenerator->buildAccount()]),
                                    new QueryResult([$accountDataGenerator->buildAccount()]),
                                    new QueryResult([$account]),
                                );
        $this->accountRepository->expects(self::once())->method('update')
                                ->with(
                                    $id,
                                    new Callback(function (Account $account) {
                                        return $account->getIsPrivate() === 0 && $account->getIsPrivateGroup() === 1;
                                    }),
                                    true,
                                    true
                                );
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(
                                      true,
                                      $id,
                                      $accountUpdateDto->withPrivate(false)
                                                       ->withPrivateGroup(true)
                                                       ->withUserId($account->getUserId())
                                  );

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->update($id, $accountUpdateDto);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetLinked()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getLinked')->with($id);

        $this->accountService->getLinked($id);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetForUser()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getForUser')->with($id);

        $this->accountService->getForUser($id);
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetPasswordForId()
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $this->accountRepository->expects(self::once())->method('getPasswordForId')
                                ->with($account->getId())->willReturn(new QueryResult([$account]));

        $this->assertEquals($account, $this->accountService->getPasswordForId($account->getId()));
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetPasswordForIdNotFound()
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $this->accountRepository->expects(self::once())->method('getPasswordForId')
                                ->with($account->getId())->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Account not found');

        $this->accountService->getPasswordForId($account->getId());
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdatePasswordMasterPass()
    {
        $id = self::$faker->randomNumber();

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $result = new QueryResult();
        $result->setAffectedNumRows(1);

        $this->accountRepository->expects(self::once())->method('updatePassword')
                                ->with($id, $encryptedPassword)
                                ->willReturn($result);

        $this->accountService->updatePasswordMasterPass($id, $encryptedPassword);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdatePasswordMasterPassError()
    {
        $id = self::$faker->randomNumber();

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $result = new QueryResult();
        $result->setAffectedNumRows(0);

        $this->accountRepository->expects(self::once())->method('updatePassword')
                                ->with($id, $encryptedPassword)
                                ->willReturn($result);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the password');

        $this->accountService->updatePasswordMasterPass($id, $encryptedPassword);
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();
        $account = AccountDataGenerator::factory()->buildAccount();
        $result = new QueryResult([$account]);

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)->willReturn($result);

        $this->assertEquals($account, $this->accountService->getById($id));
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetByIdNotFound()
    {
        $id = self::$faker->randomNumber();
        $result = new QueryResult([]);

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)->willReturn($result);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->accountService->getById($id);
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetByIdEnriched()
    {
        $id = self::$faker->randomNumber();
        $accountDataView = AccountDataGenerator::factory()->builAccountDataView();
        $result = new QueryResult([$accountDataView]);

        $this->accountRepository->expects(self::once())->method('getByIdEnriched')
                                ->with($id)->willReturn($result);

        $this->assertEquals($accountDataView, $this->accountService->getByIdEnriched($id));
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetByIdEnrichedError()
    {
        $id = self::$faker->randomNumber();
        $result = new QueryResult([]);

        $this->accountRepository->expects(self::once())->method('getByIdEnriched')
                                ->with($id)->willReturn($result);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->accountService->getByIdEnriched($id);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateBulk()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->getUserData()->setIsAdminApp(true);

        $consecutive = array_merge($accountsId, $accountsId);
        sort($consecutive);

        $this->accountRepository->expects(self::exactly(count($consecutive)))->method('getById')
                                ->withConsecutive(...array_map(fn($v) => [$v], $consecutive))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))->method('updateItems')
                                  ->withConsecutive(...array_map(fn($v) => [$v, true, $accounts[$v]], $accountsId));

        $this->accountService->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateBulkCannotChangePermissionsWithoutAdminApp()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->getUserData()->setIsAdminApp(false);

        $this->accountRepository->expects(self::exactly(count($accountsId)))->method('getById')
                                ->withConsecutive(...array_map(fn($v) => [$v], $accountsId))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))->method('updateItems')
                                  ->withConsecutive(...array_map(fn($v) => [$v, false, $accounts[$v]], $accountsId));

        $this->accountService->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateBulkCanChangePermissionsWithAdminAcc()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->getUserData()->setIsAdminApp(false);
        $this->context->getUserData()->setIsAdminAcc(true);
        $this->context->getUserProfile()->setAccPermission(false);

        $consecutive = array_merge($accountsId, $accountsId);
        sort($consecutive);

        $this->accountRepository->expects(self::exactly(count($consecutive)))->method('getById')
                                ->withConsecutive(...array_map(fn($v) => [$v], $consecutive))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))->method('updateItems')
                                  ->withConsecutive(...array_map(fn($v) => [$v, true, $accounts[$v]], $accountsId));

        $this->accountService->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testUpdateBulkCanChangePermissionsWithProfilePermission()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->getUserData()->setIsAdminApp(false);
        $this->context->getUserData()->setIsAdminAcc(false);
        $this->context->getUserProfile()->setAccPermission(true);

        $consecutive = array_merge($accountsId, $accountsId);
        sort($consecutive);

        $this->accountRepository->expects(self::exactly(count($consecutive)))->method('getById')
                                ->withConsecutive(...array_map(fn($v) => [$v], $consecutive))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))->method('updateItems')
                                  ->withConsecutive(...array_map(fn($v) => [$v, true, $accounts[$v]], $accountsId));

        $this->accountService->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testWithUsersById()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $users = $accountDataGenerator->buildItemData();
        $accountEnrichedDto = $accountDataGenerator->buildAccountEnrichedDto();

        $this->accountToUserRepository->expects(self::once())->method('getUsersByAccountId')
                                      ->with($accountEnrichedDto->getId())
                                      ->willReturn(new QueryResult($users));

        $out = $this->accountService->withUsers($accountEnrichedDto);

        $this->assertEquals($users, $out->getUsers());
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();
        $password = self::$faker->password;
        $account = AccountDataGenerator::factory()->buildAccount();
        $accountHistoryCreateDto = new AccountHistoryCreateDto($account, false, true, $password);

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn($password);

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)->willReturn(new QueryResult([$account]));
        $this->accountHistoryService->expects(self::once())->method('create')
                                    ->with($accountHistoryCreateDto);

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(1);

        $this->accountRepository->expects(self::once())->method('delete')
                                ->with($id)->willReturn($queryResult);

        $this->accountService->delete($id);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testDeleteNotFound()
    {
        $id = self::$faker->randomNumber();
        $password = self::$faker->password;
        $account = AccountDataGenerator::factory()->buildAccount();
        $accountHistoryCreateDto = new AccountHistoryCreateDto($account, false, true, $password);

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn($password);

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)->willReturn(new QueryResult([$account]));
        $this->accountHistoryService->expects(self::once())->method('create')
                                    ->with($accountHistoryCreateDto);

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(0);

        $this->accountRepository->expects(self::once())->method('delete')
                                ->with($id)->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Account not found');

        $this->accountService->delete($id);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementViewCounter()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(1);

        $this->accountRepository->expects(self::once())->method('incrementViewCounter')
                                ->with($id)->willReturn($queryResult);

        $this->assertTrue($this->accountService->incrementViewCounter($id));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementViewCounterNoRows()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(0);

        $this->accountRepository->expects(self::once())->method('incrementViewCounter')
                                ->with($id)->willReturn($queryResult);

        $this->assertFalse($this->accountService->incrementViewCounter($id));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetAllBasic()
    {
        $this->accountRepository->expects(self::once())->method('getAll');

        $this->accountService->getAllBasic();
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetDataForLink()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult([new Simple()]);

        $this->accountRepository->expects(self::once())->method('getDataForLink')
                                ->with($id)->willReturn($queryResult);

        $this->accountService->getDataForLink($id);
    }

    /**
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetDataForLinkNotFound()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult();

        $this->accountRepository->expects(self::once())->method('getDataForLink')
                                ->with($id)->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->accountService->getDataForLink($id);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testRestoreRemoved()
    {
        $accountHistoryDto = AccountDataGenerator::factory()->buildAccountHistoryDto();

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(1);

        $this->accountRepository->expects(self::once())->method('createRemoved')
                                ->with(
                                    Account::restoreRemoved($accountHistoryDto, $this->context->getUserData()->getId())
                                )
                                ->willReturn($queryResult);

        $this->accountService->restoreRemoved($accountHistoryDto);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testRestoreRemovedError()
    {
        $accountHistoryDto = AccountDataGenerator::factory()->buildAccountHistoryDto();

        $queryResult = new QueryResult();

        $this->accountRepository->expects(self::once())->method('createRemoved')
                                ->with(
                                    Account::restoreRemoved($accountHistoryDto, $this->context->getUserData()->getId())
                                )
                                ->willReturn($queryResult);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error on restoring the account');

        $this->accountService->restoreRemoved($accountHistoryDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testEditPassword()
    {
        $id = self::$faker->randomNumber();
        $account = AccountDataGenerator::factory()->buildAccount();
        $accountUpdateDto = AccountUpdateDto::fromAccount($account);

        $password = self::$faker->password;

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn($password);

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)->willReturn(new QueryResult([$account]));

        $accountHistoryCreateDto = new AccountHistoryCreateDto($account, true, false, $password);

        $this->accountHistoryService->expects(self::once())->method('create')
                                    ->with($accountHistoryCreateDto);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountUpdateDto->getPass())
                                  ->willReturn(
                                      new EncryptedPassword($accountUpdateDto->getPass(), $accountUpdateDto->getKey())
                                  );

        $this->accountRepository->expects(self::once())->method('editPassword')
                                ->with($id, Account::updatePassword($accountUpdateDto));

        $this->accountService->editPassword($id, $accountUpdateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testRestoreModified()
    {
        $password = self::$faker->password;

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn($password);

        $accountDataGenerator = AccountDataGenerator::factory();
        $account = $accountDataGenerator->buildAccount();

        $accountHistoryDto = $accountDataGenerator->buildAccountHistoryDto();

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($accountHistoryDto->getAccountId())->willReturn(new QueryResult([$account]));

        $accountHistoryCreateDto = new AccountHistoryCreateDto($account, true, false, $password);

        $this->accountHistoryService->expects(self::once())->method('create')
                                    ->with($accountHistoryCreateDto);

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(1);

        $this->accountRepository->expects(self::once())->method('restoreModified')
                                ->with(
                                    $accountHistoryDto->getAccountId(),
                                    Account::restoreModified($accountHistoryDto, $this->context->getUserData()->getId())
                                )
                                ->willReturn($queryResult);

        $this->accountService->restoreModified($accountHistoryDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testRestoreModifiedError()
    {
        $password = self::$faker->password;

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn($password);

        $accountDataGenerator = AccountDataGenerator::factory();
        $account = $accountDataGenerator->buildAccount();

        $accountHistoryDto = $accountDataGenerator->buildAccountHistoryDto();

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($accountHistoryDto->getAccountId())->willReturn(new QueryResult([$account]));

        $accountHistoryCreateDto = new AccountHistoryCreateDto($account, true, false, $password);

        $this->accountHistoryService->expects(self::once())->method('create')
                                    ->with($accountHistoryCreateDto);

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(0);

        $this->accountRepository->expects(self::once())->method('restoreModified')
                                ->with(
                                    $accountHistoryDto->getAccountId(),
                                    Account::restoreModified($accountHistoryDto, $this->context->getUserData()->getId())
                                )
                                ->willReturn($queryResult);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error on restoring the account');

        $this->accountService->restoreModified($accountHistoryDto);
    }

    public function testSearch()
    {
        $itemSearch = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->accountRepository->expects(self::once())->method('search')->with($itemSearch);

        $this->accountService->search($itemSearch);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testWithTagsById()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $tags = $accountDataGenerator->buildItemData();
        $accountEnrichedDto = $accountDataGenerator->buildAccountEnrichedDto();

        $this->accountToTagRepository->expects(self::once())->method('getTagsByAccountId')
                                     ->with($accountEnrichedDto->getId())
                                     ->willReturn(new QueryResult($tags));

        $out = $this->accountService->withTags($accountEnrichedDto);

        $this->assertEquals($tags, $out->getTags());
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testCreate()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto();

        $this->context->getUserData()->setIsAdminApp(true);

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);

        $queryResult = new QueryResult();
        $queryResult->setLastId($id);

        $this->accountRepository->expects(self::once())->method('create')
                                ->with(Account::create($accountCreateDto))
                                ->willReturn($queryResult);

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(true, $id, $accountCreateDto->withEncryptedPassword($encryptedPassword));

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->create($accountCreateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testCreateCannotChangePermissions()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $userData = $this->context->getUserData();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto()->withUserId($userData->getId())
                                                 ->withUserGroupId($userData->getUserGroupId());

        $userData->setIsAdminApp(false);

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);

        $queryResult = new QueryResult();
        $queryResult->setLastId($id);

        $this->accountRepository->expects(self::once())->method('create')
                                ->with(Account::create($accountCreateDto))
                                ->willReturn($queryResult);

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(false, $id, $accountCreateDto->withEncryptedPassword($encryptedPassword));

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->create($accountCreateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testCreateWithPrivateForUser()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto();
        $itemPreset = new ItemPreset([
            'id'            => self::$faker->randomNumber(),
            'type'          => self::$faker->colorName,
            'userId'        => self::$faker->randomNumber(),
            'userGroupId'   => self::$faker->randomNumber(),
            'userProfileId' => self::$faker->randomNumber(),
            'fixed'         => 1,
            'priority'      => self::$faker->randomNumber(),
            'data'          => serialize(new AccountPrivate(true, true)),
        ]);

        $userData = $this->context->getUserData();
        $userData->setIsAdminApp(true);
        $userData->setId($accountCreateDto->getUserId());

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);

        $queryResult = new QueryResult();
        $queryResult->setLastId($id);

        $this->accountRepository->expects(self::once())->method('create')
                                ->with(
                                    new Callback(function (Account $account) {
                                        return $account->getIsPrivate() === 1 && $account->getIsPrivateGroup() === 0;
                                    }),
                                )
                                ->willReturn($queryResult);

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(
                                      true,
                                      $id,
                                      $accountCreateDto->withEncryptedPassword($encryptedPassword)
                                                       ->withPrivate(true)
                                                       ->withPrivateGroup(false)
                                  );

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->create($accountCreateDto);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testCreateWithPrivateForGroup()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto();
        $itemPreset = new ItemPreset([
            'id'            => self::$faker->randomNumber(),
            'type'          => self::$faker->colorName,
            'userId'        => self::$faker->randomNumber(),
            'userGroupId'   => self::$faker->randomNumber(),
            'userProfileId' => self::$faker->randomNumber(),
            'fixed'         => 1,
            'priority'      => self::$faker->randomNumber(),
            'data'          => serialize(new AccountPrivate(true, true)),
        ]);

        $userData = $this->context->getUserData();
        $userData->setIsAdminApp(true);
        $userData->setUserGroupId($accountCreateDto->getUserGroupId());

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);

        $queryResult = new QueryResult();
        $queryResult->setLastId($id);

        $this->accountRepository->expects(self::once())->method('create')
                                ->with(
                                    new Callback(function (Account $account) {
                                        return $account->getIsPrivate() === 0 && $account->getIsPrivateGroup() === 1;
                                    }),
                                )
                                ->willReturn($queryResult);

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(
                                      true,
                                      $id,
                                      $accountCreateDto->withEncryptedPassword($encryptedPassword)
                                                       ->withPrivate(false)
                                                       ->withPrivateGroup(true)
                                  );

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->accountService->create($accountCreateDto);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetTotalNumAccounts()
    {
        $num = self::$faker->randomNumber();

        $queryResult = new QueryResult([new Simple(['num' => $num])]);

        $this->accountRepository->expects(self::once())->method('getTotalNumAccounts')
                                ->willReturn($queryResult);

        $this->assertEquals($num, $this->accountService->getTotalNumAccounts());
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetPasswordHistoryForId()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getPasswordHistoryForId')
                                ->with($id)->willReturn(new QueryResult([new Simple()]));

        $this->accountService->getPasswordHistoryForId($id);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetPasswordHistoryForIdNotFound()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getPasswordHistoryForId')
                                ->with($id)->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->accountService->getPasswordHistoryForId($id);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetAccountsPassData()
    {
        $this->accountRepository->expects(self::once())->method('getAccountsPassData')
                                ->willReturn(new QueryResult([new Simple()]));

        $this->accountService->getAccountsPassData();
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(1);

        $this->accountRepository->expects(self::once())->method('deleteByIdBatch')
                                ->with($ids)->willReturn($queryResult);

        $this->accountService->deleteByIdBatch($ids);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDeleteByIdBatchError()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(0);

        $this->accountRepository->expects(self::once())->method('deleteByIdBatch')
                                ->with($ids)->willReturn($queryResult);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the accounts');

        $this->accountService->deleteByIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testWithUserGroupsById()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $userGroups = $accountDataGenerator->buildItemData();
        $accountEnrichedDto = $accountDataGenerator->buildAccountEnrichedDto();

        $this->accountToUserGroupRepository->expects(self::once())->method('getUserGroupsByAccountId')
                                           ->with($accountEnrichedDto->getId())
                                           ->willReturn(new QueryResult($userGroups));

        $out = $this->accountService->withUserGroups($accountEnrichedDto);

        $this->assertEquals($userGroups, $out->getUserGroups());
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementDecryptCounter()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(1);

        $this->accountRepository->expects(self::once())->method('incrementDecryptCounter')
                                ->with($id)->willReturn($queryResult);

        $this->assertTrue($this->accountService->incrementDecryptCounter($id));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementDecryptCounterNoRows()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult();
        $queryResult->setAffectedNumRows(0);

        $this->accountRepository->expects(self::once())->method('incrementDecryptCounter')
                                ->with($id)->willReturn($queryResult);

        $this->assertFalse($this->accountService->incrementDecryptCounter($id));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $methods = array_diff(get_class_methods(AccountRepositoryStub::class), ['transactionAware']);

        $this->accountRepository = $this->createPartialMock(AccountRepositoryStub::class, $methods);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepositoryInterface::class);
        $this->accountToUserRepository = $this->createMock(AccountToUserRepositoryInterface::class);
        $this->accountToTagRepository = $this->createMock(AccountToTagRepositoryInterface::class);
        $this->itemPresetService = $this->createMock(ItemPresetServiceInterface::class);
        $this->accountHistoryService = $this->createMock(AccountHistoryServiceInterface::class);
        $this->configService = $this->createMock(ConfigServiceInterface::class);
        $this->accountCryptService = $this->createMock(AccountCryptServiceInterface::class);
        $this->accountItemsService = $this->createMock(AccountItemsServiceInterface::class);
        $this->accountPresetService = $this->createMock(AccountPresetServiceInterface::class);

        $this->accountService = new AccountService(
            $this->application,
            $this->accountRepository,
            $this->accountToUserGroupRepository,
            $this->accountToUserRepository,
            $this->accountToTagRepository,
            $this->itemPresetService,
            $this->accountHistoryService,
            $this->accountItemsService,
            $this->accountPresetService,
            $this->configService,
            $this->accountCryptService
        );
    }
}
