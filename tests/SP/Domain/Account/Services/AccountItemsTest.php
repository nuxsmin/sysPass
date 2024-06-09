<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Services\AccountItems;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountItemsTest
 */
#[Group('unitary')]
class AccountItemsTest extends UnitaryTestCase
{

    private MockObject|AccountToUserGroupRepository $accountToUserGroupRepository;
    private AccountItems                            $accountItems;
    private AccountToUserRepository|MockObject      $accountToUserRepository;
    private MockObject|AccountToTagRepository       $accountToTagRepository;

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testUpdateItems()
    {
        $accountUpdateDto = AccountDataGenerator::factory()->buildAccountUpdateDto();

        $this->accountToUserGroupRepository
            ->expects($this->exactly(2))
            ->method('transactionAware')
            ->with($this->withResolveCallableCallback());

        $this->accountToUserGroupRepository
            ->expects($this->exactly(2))
            ->method('deleteTypeByAccountId')
            ->with(...self::withConsecutive([100, false], [100, true]));

        $this->accountToUserGroupRepository
            ->expects($this->exactly(2))
            ->method('addByType')
            ->with(
                ...
                self::withConsecutive([100, $accountUpdateDto->getUserGroupsView(), false],
                                      [100, $accountUpdateDto->getUserGroupsEdit(), true])
            );

        $this->accountToUserRepository
            ->expects($this->exactly(2))
            ->method('transactionAware')
            ->with($this->withResolveCallableCallback());

        $this->accountToUserRepository
            ->expects($this->exactly(2))
            ->method('deleteTypeByAccountId')
            ->with(...self::withConsecutive([100, false], [100, true]));

        $this->accountToUserRepository
            ->expects($this->exactly(2))
            ->method('addByType')
            ->with(
                ...
                self::withConsecutive([100, $accountUpdateDto->getUsersView(), false],
                                      [100, $accountUpdateDto->getUsersEdit(), true])
            );

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('transactionAware')
            ->with($this->withResolveCallableCallback());

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('deleteByAccountId')
            ->with(100);

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('add')
            ->with(100, $accountUpdateDto->getTags());

        $this->accountItems->updateItems(true, 100, $accountUpdateDto);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testUpdateItemsWithNoItems()
    {
        $accountUpdateDto = AccountDataGenerator::factory()
                                                ->buildAccountUpdateDto()
                                                ->setBatch(
                                                    [
                                                        'usersView',
                                                        'usersEdit',
                                                        'userGroupsView',
                                                        'userGroupsEdit',
                                                        'tags'
                                                    ],
                                                    [null, null, null, null, null]
                                                );

        $this->accountToUserGroupRepository
            ->expects($this->never())
            ->method('transactionAware');

        $this->accountToUserGroupRepository
            ->expects($this->exactly(2))
            ->method('deleteTypeByAccountId')
            ->with(...self::withConsecutive([100, false], [100, true]));

        $this->accountToUserGroupRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToUserRepository
            ->expects($this->never())
            ->method('transactionAware');

        $this->accountToUserRepository
            ->expects($this->exactly(2))
            ->method('deleteTypeByAccountId')
            ->with(...self::withConsecutive([100, false], [100, true]));

        $this->accountToUserRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToTagRepository
            ->expects($this->never())
            ->method('transactionAware');

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('deleteByAccountId')
            ->with(100);

        $this->accountToTagRepository
            ->expects($this->never())
            ->method('add');

        $this->accountItems->updateItems(true, 100, $accountUpdateDto);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testUpdateItemsWithNoPermission()
    {
        $accountUpdateDto = AccountDataGenerator::factory()->buildAccountUpdateDto();

        $this->accountToUserGroupRepository
            ->expects($this->never())
            ->method('transactionAware');

        $this->accountToUserGroupRepository
            ->expects($this->never())
            ->method('deleteTypeByAccountId');

        $this->accountToUserGroupRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToUserRepository
            ->expects($this->never())
            ->method('transactionAware');

        $this->accountToUserRepository
            ->expects($this->never())
            ->method('deleteTypeByAccountId');

        $this->accountToUserRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('transactionAware')
            ->with($this->withResolveCallableCallback());

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('deleteByAccountId')
            ->with(100);

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('add')
            ->with(100, $accountUpdateDto->getTags());

        $this->accountItems->updateItems(false, 100, $accountUpdateDto);
    }

    public function testAddItems()
    {
        $accountCreateDto = AccountDataGenerator::factory()->buildAccountCreateDto();

        $this->accountToUserGroupRepository
            ->expects($this->exactly(2))
            ->method('addByType')
            ->with(
                ...
                self::withConsecutive([100, $accountCreateDto->getUserGroupsView(), false],
                                      [100, $accountCreateDto->getUserGroupsEdit(), true])
            );

        $this->accountToUserRepository
            ->expects($this->exactly(2))
            ->method('addByType')
            ->with(
                ...
                self::withConsecutive([100, $accountCreateDto->getUsersView(), false],
                                      [100, $accountCreateDto->getUsersEdit(), true])
            );

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('add')
            ->with(100, $accountCreateDto->getTags());

        $this->accountItems->addItems(true, 100, $accountCreateDto);
    }

    public function testAddItemsWithNoPermission()
    {
        $accountCreateDto = AccountDataGenerator::factory()->buildAccountCreateDto();

        $this->accountToUserGroupRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToUserRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('add')
            ->with(100, $accountCreateDto->getTags());

        $this->accountItems->addItems(false, 100, $accountCreateDto);
    }

    public function testAddItemsWithException()
    {
        $accountCreateDto = AccountDataGenerator::factory()->buildAccountCreateDto();

        $this->accountToUserGroupRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToUserRepository
            ->expects($this->never())
            ->method('addByType');

        $this->accountToTagRepository
            ->expects($this->once())
            ->method('add')
            ->willThrowException(SPException::error('test'));

        $this->accountItems->addItems(false, 100, $accountCreateDto);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);
        $this->accountToUserRepository = $this->createMock(AccountToUserRepository::class);
        $this->accountToTagRepository = $this->createMock(AccountToTagRepository::class);

        $this->accountItems = new AccountItems(
            $this->application,
            $this->accountToUserGroupRepository,
            $this->accountToUserRepository,
            $this->accountToTagRepository
        );
    }
}
