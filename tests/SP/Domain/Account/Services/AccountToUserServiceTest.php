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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemData;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Account\Services\AccountToUserService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountToUserServiceTest
 *
 * @group unitary
 */
class AccountToUserServiceTest extends UnitaryTestCase
{

    private AccountToUserRepositoryInterface|MockObject $accountToUserRepository;
    private AccountToUserService                        $accountToUserService;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetUsersByAccountId()
    {
        $accountId = self::$faker->randomNumber();

        $result =
            new QueryResult(
                [
                    new ItemData(
                        [
                            'id'     => self::$faker->randomNumber(),
                            'name'   => self::$faker->colorName,
                            'isEdit' => self::$faker->boolean,
                            'login'  => self::$faker->colorName,
                        ]
                    ),
                ]
            );

        $this->accountToUserRepository
            ->expects(self::once())
            ->method('getUsersByAccountId')
            ->with($accountId)
            ->willReturn($result);

        $actual = $this->accountToUserService->getUsersByAccountId($accountId);
        $expected = $result->getData(ItemData::class)->toArray(null, null, true);

        $this->assertTrue($actual[0] instanceof ItemData);
        $this->assertEquals($expected, $actual[0]->toArray(null, null, true));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetUsersByAccountIdWithNoUsers()
    {
        $accountId = self::$faker->randomNumber();

        $result = new QueryResult([]);

        $this->accountToUserRepository
            ->expects(self::once())
            ->method('getUsersByAccountId')
            ->with($accountId)
            ->willReturn($result);

        $actual = $this->accountToUserService->getUsersByAccountId($accountId);

        $this->assertEmpty($actual);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToUserRepository = $this->createMock(AccountToUserRepositoryInterface::class);

        $this->accountToUserService =
            new AccountToUserService($this->application, $this->accountToUserRepository);
    }
}
