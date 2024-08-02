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
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountItemsService;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Services\Account;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\ItemPreset\Models\AccountPrivate;
use SP\Domain\ItemPreset\Models\ItemPreset;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\ItemSearchDataGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\Stubs\AccountRepositoryStub;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountServiceTest
 *
 */
#[Group('unitary')]
class AccountTest extends UnitaryTestCase
{
    private AccountRepository|MockObject $accountRepository;
    private AccountToUserGroupRepository|MockObject $accountToUserGroupRepository;
    private AccountToUserRepository|MockObject $accountToUserRepository;
    private AccountToTagRepository|MockObject $accountToTagRepository;
    private ItemPresetService|MockObject $itemPresetService;
    private AccountHistoryService|MockObject $accountHistoryService;
    private ConfigService|MockObject $configService;
    private AccountCryptService|MockObject $accountCryptService;
    private AccountPresetService|MockObject $accountPresetService;
    private AccountItemsService|MockObject $accountItemsService;
    private Account $account;

    /**
     * @throws ServiceException
     */
    public function testUpdate()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()->buildUserData()->mutate(['isAdminApp' => true])
            )
        );

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
            ->with($id, AccountModel::update($accountUpdateDto), true, true);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(true, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->update($id, $accountUpdateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateUserCannotChangePermissionsWithoutPermission()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isAdminApp' => false, 'isAdminAcc' => false])
            )
        );

        $this->context->getUserProfile()->setAccPermission(false);

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
            ->with($id, AccountModel::update($accountUpdateDto), false, false);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(false, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->update($id, $accountUpdateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateUserCanChangePermissionsWithAdminAcc()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isAdminApp' => false, 'isAdminAcc' => true])
            )
        );

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
            ->with($id, AccountModel::update($accountUpdateDto), true, true);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(true, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->update($id, $accountUpdateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateUserCanChangePermissionsWithProfilePermission()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isAdminApp' => false, 'isAdminAcc' => false])
            )
        );

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
            ->with($id, AccountModel::update($accountUpdateDto), false, false);
        $this->accountItemsService->expects(self::once())->method('updateItems')
                                  ->with(true, $id, $accountUpdateDto);
        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->update($id, $accountUpdateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateWithPresetPrivateForUser()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();
        $itemPreset = new ItemPreset([
                                         'id' => self::$faker->randomNumber(),
                                         'type' => self::$faker->colorName,
                                         'userId' => self::$faker->randomNumber(),
                                         'userGroupId' => self::$faker->randomNumber(),
                                         'userProfileId' => self::$faker->randomNumber(),
                                         'fixed' => 1,
                                         'priority' => self::$faker->randomNumber(),
                                         'data' => serialize(new AccountPrivate(true, true)),
                                     ]);

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'id' => $accountUpdateDto->getUserId(),
                                         'userGroupId' => $accountUpdateDto->getUserGroupId(),
                                         'isAdminApp' => true,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);
        $account = new AccountModel(
            [
                'userId' => $accountUpdateDto->getUserId(),
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
                                    new Callback(function (AccountModel $account) {
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

        $this->account->update($id, $accountUpdateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateWithPresetPrivateForGroup()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountUpdateDto = $accountDataGenerator->buildAccountUpdateDto();
        $itemPreset = new ItemPreset([
                                         'id' => self::$faker->randomNumber(),
                                         'type' => self::$faker->colorName,
                                         'userId' => self::$faker->randomNumber(),
                                         'userGroupId' => self::$faker->randomNumber(),
                                         'userProfileId' => self::$faker->randomNumber(),
                                         'fixed' => 1,
                                         'priority' => self::$faker->randomNumber(),
                                         'data' => serialize(new AccountPrivate(true, true)),
                                     ]);

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'id' => $accountUpdateDto->getUserId(),
                                         'userGroupId' => $accountUpdateDto->getUserGroupId(),
                                         'isAdminApp' => true,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $this->configService->expects(self::once())->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountHistoryService->expects(self::once())->method('create');
        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);
        $account = new AccountModel(
            [
                'userId' => self::$faker->randomNumber(),
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
                                    new Callback(function (AccountModel $account) {
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

        $this->account->update($id, $accountUpdateDto);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetLinked()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getLinked')->with($id);

        $this->account->getLinked($id);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetForUser()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getForUser')->with($id);

        $this->account->getForUser($id);
    }

    /**
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetPasswordForId()
    {
        $account = AccountPassItemWithIdAndName::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccount());

        $this->accountRepository->expects(self::once())
                                ->method('getPasswordForId')
                                ->with($account->getId())
                                ->willReturn(new QueryResult([$account]));

        $this->assertEquals($account, $this->account->getPasswordForId($account->getId()));
    }

    /**
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetPasswordForIdNotFound()
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $this->accountRepository->expects(self::once())->method('getPasswordForId')
                                ->with($account->getId())->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Account not found');

        $this->account->getPasswordForId($account->getId());
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function testUpdatePasswordMasterPass()
    {
        $id = self::$faker->randomNumber();

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $result = new QueryResult(null, 1);

        $this->accountRepository->expects(self::once())->method('updatePassword')
                                ->with($id, $encryptedPassword)
            ->willReturn($result);

        $this->account->updatePasswordMasterPass($id, $encryptedPassword);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function testUpdatePasswordMasterPassError()
    {
        $id = self::$faker->randomNumber();

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountRepository->expects(self::once())->method('updatePassword')
                                ->with($id, $encryptedPassword)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the password');

        $this->account->updatePasswordMasterPass($id, $encryptedPassword);
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();
        $account = AccountDataGenerator::factory()->buildAccount();
        $result = new QueryResult([$account]);

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)->willReturn($result);

        $this->assertEquals($account, $this->account->getById($id));
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetByIdNotFound()
    {
        $id = self::$faker->randomNumber();
        $result = new QueryResult([]);

        $this->accountRepository->expects(self::once())->method('getById')
                                ->with($id)->willReturn($result);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->account->getById($id);
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetByIdEnriched()
    {
        $id = self::$faker->randomNumber();
        $accountDataView = AccountDataGenerator::factory()->buildAccountDataView();
        $result = new QueryResult([$accountDataView]);

        $this->accountRepository->expects(self::once())->method('getByIdEnriched')
                                ->with($id)->willReturn($result);

        $this->assertEquals($accountDataView, $this->account->getByIdEnriched($id));
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetByIdEnrichedError()
    {
        $id = self::$faker->randomNumber();
        $result = new QueryResult([]);

        $this->accountRepository->expects(self::once())->method('getByIdEnriched')
                                ->with($id)->willReturn($result);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->account->getByIdEnriched($id);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateBulk()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'isAdminApp' => true,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $consecutive = array_merge($accountsId, $accountsId);
        sort($consecutive);

        $this->accountRepository->expects(self::exactly(count($consecutive)))->method('getById')
                                ->with(...self::withConsecutive(...array_map(fn($v) => [$v], $consecutive)))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))
                                  ->method('updateItems')
                                  ->with(
                                      ...
                                      self::withConsecutive(
                                          ...
                                          array_map(fn($v) => [true, $v, $accounts[$v]], $accountsId)
                                      )
                                  );

        $this->account->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateBulkCannotChangePermissionsWithoutAdminApp()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'isAdminApp' => false,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $this->accountRepository->expects(self::exactly(count($accountsId)))->method('getById')
                                ->with(...self::withConsecutive(...array_map(fn($v) => [$v], $accountsId)))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))->method('updateItems')
                                  ->with(
                                      ...
                                      self::withConsecutive(
                                          ...
                                          array_map(fn($v) => [false, $v, $accounts[$v]], $accountsId)
                                      )
                                  );

        $this->account->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateBulkCanChangePermissionsWithAdminAcc()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'isAdminApp' => true,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $this->context->setUserProfile(new ProfileData(['accPermission' => false]));

        $consecutive = array_merge($accountsId, $accountsId);
        sort($consecutive);

        $this->accountRepository->expects(self::exactly(count($consecutive)))->method('getById')
                                ->with(...self::withConsecutive(...array_map(fn($v) => [$v], $consecutive)))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))->method('updateItems')
                                  ->with(
                                      ...
                                      self::withConsecutive(
                                          ...
                                          array_map(fn($v) => [true, $v, $accounts[$v]], $accountsId)
                                      )
                                  );

        $this->account->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateBulkCanChangePermissionsWithProfilePermission()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $accounts = array_map(fn() => $accountDataGenerator->buildAccountUpdateDto(), range(0, 4));
        $accountsId = range(0, 4);
        $accountUpdateBulkDto = new AccountUpdateBulkDto($accountsId, $accounts);

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'isAdminApp' => false,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $this->context->setUserProfile(new ProfileData(['accPermission' => true]));

        $consecutive = array_merge($accountsId, $accountsId);
        sort($consecutive);

        $this->accountRepository->expects(self::exactly(count($consecutive)))->method('getById')
                                ->with(...self::withConsecutive(...array_map(fn($v) => [$v], $consecutive)))
                                ->willReturn(new QueryResult([$accountDataGenerator->buildAccount()]));
        $this->configService->expects(self::exactly(count($accountsId)))->method('getByParam')
                            ->with('masterPwd')->willReturn(self::$faker->password);
        $this->accountItemsService->expects(self::exactly(count($accountsId)))->method('updateItems')
                                  ->with(
                                      ...self::withConsecutive(
                                      ...array_map(
                                             fn($v) => [true, $v, $accounts[$v]],
                                             $accountsId
                                         )
                                  )
                                  );

        $this->account->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testWithUsersById()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $users = $accountDataGenerator->buildItemData();
        $accountEnrichedDto = $accountDataGenerator->buildAccountEnrichedDto();

        $this->accountToUserRepository->expects(self::once())->method('getUsersByAccountId')
                                      ->with($accountEnrichedDto->getId())
                                      ->willReturn(new QueryResult($users));

        $out = $this->account->withUsers($accountEnrichedDto);

        $this->assertEquals($users, $out->getUsers());
    }

    /**
     * @throws ServiceException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();
        $password = self::$faker->password;
        $account = AccountDataGenerator::factory()->buildAccount();
        $accountHistoryCreateDto = new AccountHistoryCreateDto($account, false, true, $password);

        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('masterPwd')
                            ->willReturn($password);

        $this->accountRepository->expects(self::once())
                                ->method('getById')
                                ->with($id)
                                ->willReturn(new QueryResult([$account]));
        $this->accountHistoryService->expects(self::once())->method('create')
                                    ->with($accountHistoryCreateDto);

        $this->accountRepository->expects(self::once())
                                ->method('delete')
                                ->with($id)
                                ->willReturn(new QueryResult(null, 1));

        $this->account->delete($id);
    }

    /**
     * @throws ServiceException
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


        $this->accountRepository->expects(self::once())->method('delete')
            ->with($id)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Account not found');

        $this->account->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testIncrementViewCounter()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('incrementViewCounter')
            ->with($id)->willReturn(new QueryResult(null, 1));

        $this->assertTrue($this->account->incrementViewCounter($id));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testIncrementViewCounterNoRows()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('incrementViewCounter')
            ->with($id)
            ->willReturn(new QueryResult(null, 0));

        $this->assertFalse($this->account->incrementViewCounter($id));
    }

    /**
     * @throws SPException
     */
    public function testGetAllBasic()
    {
        $this->accountRepository->expects(self::once())->method('getAll');

        $this->account->getAllBasic();
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetDataForLink()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult([new Simple()]);

        $this->accountRepository->expects(self::once())->method('getDataForLink')
                                ->with($id)->willReturn($queryResult);

        $this->account->getDataForLink($id);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetDataForLinkNotFound()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult();

        $this->accountRepository->expects(self::once())->method('getDataForLink')
                                ->with($id)->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->account->getDataForLink($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testRestoreRemoved()
    {
        $accountHistoryDto = AccountDataGenerator::factory()->buildAccountHistoryDto();

        $this->accountRepository->expects(self::once())->method('createRemoved')
                                ->with(
                                    AccountModel::restoreRemoved(
                                        $accountHistoryDto,
                                        $this->context->getUserData()->getId()
                                    )
                                )
            ->willReturn(new QueryResult(null, 1));

        $this->account->restoreRemoved($accountHistoryDto);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testRestoreRemovedError()
    {
        $accountHistoryDto = AccountDataGenerator::factory()->buildAccountHistoryDto();

        $queryResult = new QueryResult();

        $this->accountRepository->expects(self::once())->method('createRemoved')
                                ->with(
                                    AccountModel::restoreRemoved(
                                        $accountHistoryDto,
                                        $this->context->getUserData()->getId()
                                    )
                                )
                                ->willReturn($queryResult);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error on restoring the account');

        $this->account->restoreRemoved($accountHistoryDto);
    }

    /**
     * @throws ServiceException
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
            ->with($id, AccountModel::updatePassword($accountUpdateDto));

        $this->account->editPassword($id, $accountUpdateDto);
    }

    /**
     * @throws ServiceException
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

        $this->accountRepository->expects(self::once())->method('restoreModified')
                                ->with(
                                    $accountHistoryDto->getAccountId(),
                                    AccountModel::restoreModified(
                                        $accountHistoryDto,
                                        $this->context->getUserData()->getId()
                                    )
                                )
            ->willReturn(new QueryResult(null, 1));

        $this->account->restoreModified($accountHistoryDto);
    }

    /**
     * @throws ServiceException
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


        $this->accountRepository->expects(self::once())->method('restoreModified')
                                ->with(
                                    $accountHistoryDto->getAccountId(),
                                    AccountModel::restoreModified(
                                        $accountHistoryDto,
                                        $this->context->getUserData()->getId()
                                    )
                                )
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error on restoring the account');

        $this->account->restoreModified($accountHistoryDto);
    }

    public function testSearch()
    {
        $itemSearch = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->accountRepository->expects(self::once())->method('search')->with($itemSearch);

        $this->account->search($itemSearch);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testWithTagsById()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $tags = $accountDataGenerator->buildItemData();
        $accountEnrichedDto = $accountDataGenerator->buildAccountEnrichedDto();

        $this->accountToTagRepository->expects(self::once())->method('getTagsByAccountId')
                                     ->with($accountEnrichedDto->getId())
                                     ->willReturn(new QueryResult($tags));

        $out = $this->account->withTags($accountEnrichedDto);

        $this->assertEquals($tags, $out->getTags());
    }

    /**
     * @throws ServiceException
     */
    public function testCreate()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto();

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'isAdminApp' => true,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);

        $this->accountRepository->expects(self::once())->method('create')
            ->with(AccountModel::create($accountCreateDto))
            ->willReturn(new QueryResult(null, 0, $id));

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(true, $id, $accountCreateDto->withEncryptedPassword($encryptedPassword));

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->create($accountCreateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testCreateCannotChangePermissions()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $userData = $this->context->getUserData();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto()
                                                 ->withUserId($userData->getId())
                                                 ->withUserGroupId($userData->getUserGroupId());

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'id' => $userData->getId(),
                                         'userGroupId' => $userData->getUserGroupId(),
                                         'isAdminApp' => false,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn(null);

        $this->accountRepository->expects(self::once())->method('create')
            ->with(AccountModel::create($accountCreateDto))
            ->willReturn(new QueryResult(null, 0, $id));

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(false, $id, $accountCreateDto->withEncryptedPassword($encryptedPassword));

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->create($accountCreateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testCreateWithPrivateForUser()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto();
        $itemPreset = new ItemPreset([
                                         'id' => self::$faker->randomNumber(),
                                         'type' => self::$faker->colorName,
                                         'userId' => self::$faker->randomNumber(),
                                         'userGroupId' => self::$faker->randomNumber(),
                                         'userProfileId' => self::$faker->randomNumber(),
                                         'fixed' => 1,
                                         'priority' => self::$faker->randomNumber(),
                                         'data' => serialize(new AccountPrivate(true, true)),
                                     ]);


        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'id' => $accountCreateDto->getUserId(),
                                         'isAdminApp' => true,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );


        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);

        $this->accountRepository->expects(self::once())->method('create')
                                ->with(
                                    new Callback(function (AccountModel $account) {
                                        return $account->getIsPrivate() === 1 && $account->getIsPrivateGroup() === 0;
                                    }),
                                )
            ->willReturn(new QueryResult(null, 0, $id));

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(
                                      true,
                                      $id,
                                      $accountCreateDto->withEncryptedPassword($encryptedPassword)
                                                       ->withPrivate(true)
                                                       ->withPrivateGroup(false)
                                  );

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->create($accountCreateDto);
    }

    /**
     * @throws ServiceException
     */
    public function testCreateWithPrivateForGroup()
    {
        $id = self::$faker->randomNumber();
        $accountDataGenerator = AccountDataGenerator::factory();
        $accountCreateDto = $accountDataGenerator->buildAccountCreateDto();
        $itemPreset = new ItemPreset([
                                         'id' => self::$faker->randomNumber(),
                                         'type' => self::$faker->colorName,
                                         'userId' => self::$faker->randomNumber(),
                                         'userGroupId' => self::$faker->randomNumber(),
                                         'userProfileId' => self::$faker->randomNumber(),
                                         'fixed' => 1,
                                         'priority' => self::$faker->randomNumber(),
                                         'data' => serialize(new AccountPrivate(true, true)),
                                     ]);

        $this->context->setUserData(
            new UserDataDto(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'userGroupId' => $accountCreateDto->getUserGroupId(),
                                         'isAdminApp' => true,
                                         'isAdminAcc' => false
                                     ]
                                 )
            )
        );

        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $this->accountCryptService->expects(self::once())->method('getPasswordEncrypted')
                                  ->with($accountCreateDto->getPass())
                                  ->willReturn($encryptedPassword);

        $this->itemPresetService->expects(self::once())->method('getForCurrentUser')
                                ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
                                ->willReturn($itemPreset);

        $this->accountRepository->expects(self::once())->method('create')
                                ->with(
                                    new Callback(function (AccountModel $account) {
                                        return $account->getIsPrivate() === 0 && $account->getIsPrivateGroup() === 1;
                                    }),
                                )
            ->willReturn(new QueryResult(null, 0, $id));

        $this->accountItemsService->expects(self::once())->method('addItems')
                                  ->with(
                                      true,
                                      $id,
                                      $accountCreateDto->withEncryptedPassword($encryptedPassword)
                                                       ->withPrivate(false)
                                                       ->withPrivateGroup(true)
                                  );

        $this->accountPresetService->expects(self::once())->method('addPresetPermissions')->with($id);

        $this->account->create($accountCreateDto);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetTotalNumAccounts()
    {
        $num = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getTotalNumAccounts')
            ->willReturn(new QueryResult([new Simple(['num' => $num])]));

        $this->assertEquals($num, $this->account->getTotalNumAccounts());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetPasswordHistoryForId()
    {
        $account = AccountPassItemWithIdAndName::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccount());

        $this->accountRepository->expects(self::once())
                                ->method('getPasswordHistoryForId')
                                ->with($account->getId())
                                ->willReturn(new QueryResult([$account]));

        $this->account->getPasswordHistoryForId($account->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetPasswordHistoryForIdNotFound()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('getPasswordHistoryForId')
                                ->with($id)->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('The account doesn\'t exist');

        $this->account->getPasswordHistoryForId($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetAccountsPassData()
    {
        $this->accountRepository->expects(self::once())->method('getAccountsPassData')
                                ->willReturn(new QueryResult([new Simple()]));

        $this->account->getAccountsPassData();
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $this->accountRepository->expects(self::once())->method('deleteByIdBatch')
            ->with($ids)->willReturn(new QueryResult(null, 1));

        $this->account->deleteByIdBatch($ids);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testDeleteByIdBatchError()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $this->accountRepository->expects(self::once())->method('deleteByIdBatch')
            ->with($ids)->willReturn(new QueryResult(null, 0));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the accounts');

        $this->account->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testWithUserGroupsById()
    {
        $accountDataGenerator = AccountDataGenerator::factory();
        $userGroups = $accountDataGenerator->buildItemData();
        $accountEnrichedDto = $accountDataGenerator->buildAccountEnrichedDto();

        $this->accountToUserGroupRepository->expects(self::once())->method('getUserGroupsByAccountId')
                                           ->with($accountEnrichedDto->getId())
                                           ->willReturn(new QueryResult($userGroups));

        $out = $this->account->withUserGroups($accountEnrichedDto);

        $this->assertEquals($userGroups, $out->getUserGroups());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testIncrementDecryptCounter()
    {
        $id = self::$faker->randomNumber();

        $this->accountRepository->expects(self::once())->method('incrementDecryptCounter')
            ->with($id)
            ->willReturn(new QueryResult(null, 1));

        $this->assertTrue($this->account->incrementDecryptCounter($id));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testIncrementDecryptCounterNoRows()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult();

        $this->accountRepository->expects(self::once())->method('incrementDecryptCounter')
            ->with($id)
            ->willReturn(new QueryResult(null, 0));

        $this->assertFalse($this->account->incrementDecryptCounter($id));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $accountRepositoryMethods = array_filter(
            get_class_methods(AccountRepositoryStub::class),
            static fn(string $method) => $method != 'transactionAware'
        );

        $this->accountRepository = $this->createPartialMock(AccountRepositoryStub::class, $accountRepositoryMethods);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);
        $this->accountToUserRepository = $this->createMock(AccountToUserRepository::class);
        $this->accountToTagRepository = $this->createMock(AccountToTagRepository::class);
        $this->itemPresetService = $this->createMock(ItemPresetService::class);
        $this->accountHistoryService = $this->createMock(AccountHistoryService::class);
        $this->configService = $this->createMock(ConfigService::class);
        $this->accountCryptService = $this->createMock(AccountCryptService::class);
        $this->accountItemsService = $this->createMock(AccountItemsService::class);
        $this->accountPresetService = $this->createMock(AccountPresetService::class);

        $this->account = new Account(
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
