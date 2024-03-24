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
use SP\Domain\Account\Ports\AccountToFavoriteRepository;
use SP\Domain\Account\Services\AccountToFavorite;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class AccountToFavoriteServiceTest
 *
 */
#[Group('unitary')]
class AccountToFavoriteTest extends UnitaryTestCase
{

    private AccountToFavoriteRepository|MockObject $accountToFavoriteRepository;
    private AccountToFavorite                      $accountToFavorite;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetForUserId()
    {
        $userId = self::$faker->randomNumber();
        $result = new QueryResult([['userId' => $userId, 'accountId' => self::$faker->randomNumber()]]);

        $this->accountToFavoriteRepository
            ->expects(self::once())
            ->method('getForUserId')
            ->with($userId)
            ->willReturn($result);

        $actual = $this->accountToFavorite->getForUserId($userId);

        $this->assertEquals($result->getDataAsArray(), $actual);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDelete()
    {
        $accountId = self::$faker->randomNumber();
        $userId = self::$faker->randomNumber();
        $out = self::$faker->boolean;

        $this->accountToFavoriteRepository->expects(self::once())
                                          ->method('delete')
                                          ->with($accountId, $userId)
                                          ->willReturn($out);

        $actual = $this->accountToFavorite->delete($accountId, $userId);

        $this->assertEquals($out, $actual);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testAdd()
    {
        $accountId = self::$faker->randomNumber();
        $userId = self::$faker->randomNumber();
        $out = self::$faker->randomNumber();

        $this->accountToFavoriteRepository->expects(self::once())
                                          ->method('add')
                                          ->with($accountId, $userId)
                                          ->willReturn($out);

        $actual = $this->accountToFavorite->add($accountId, $userId);

        $this->assertEquals($out, $actual);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToFavoriteRepository = $this->createMock(AccountToFavoriteRepository::class);

        $this->accountToFavorite =
            new AccountToFavorite($this->application, $this->accountToFavoriteRepository);
    }

}
