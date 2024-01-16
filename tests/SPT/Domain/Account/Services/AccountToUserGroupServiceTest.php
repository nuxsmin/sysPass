<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemData;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Services\AccountToUserGroup;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class AccountToUserGroupServiceTest
 *
 * @group unitary
 */
class AccountToUserGroupServiceTest extends UnitaryTestCase
{

    private AccountToUserGroup                      $accountToUserGroup;
    private AccountToUserGroupRepository|MockObject $accountToUserGroupRepository;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetUserGroupsByAccountId()
    {
        $accountId = self::$faker->randomNumber();

        $result =
            new QueryResult(
                [
                    new ItemData(
                        [
                            'id' => self::$faker->randomNumber(),
                            'name' => self::$faker->colorName,
                            'isEdit' => self::$faker->boolean,
                        ]
                    ),
                ]
            );

        $this->accountToUserGroupRepository
            ->expects(self::once())
            ->method('getUserGroupsByAccountId')
            ->with($accountId)
            ->willReturn($result);

        $actual = $this->accountToUserGroup->getUserGroupsByAccountId($accountId);
        $expected = $result->getData(ItemData::class)->toArray(null, null, true);

        $this->assertTrue($actual[0] instanceof ItemData);
        $this->assertEquals($expected, $actual[0]->toArray(null, null, true));
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetUserGroupsByAccountIdWithNoUserGroups()
    {
        $accountId = self::$faker->randomNumber();

        $result = new QueryResult([]);

        $this->accountToUserGroupRepository
            ->expects(self::once())
            ->method('getUserGroupsByAccountId')
            ->with($accountId)
            ->willReturn($result);

        $actual = $this->accountToUserGroup->getUserGroupsByAccountId($accountId);

        $this->assertEmpty($actual);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);

        $this->accountToUserGroup =
            new AccountToUserGroup($this->application, $this->accountToUserGroupRepository);
    }
}
