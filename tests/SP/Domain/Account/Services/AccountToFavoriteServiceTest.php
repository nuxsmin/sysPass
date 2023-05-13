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
use SP\Domain\Account\Ports\AccountToFavoriteRepositoryInterface;
use SP\Domain\Account\Services\AccountToFavoriteService;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountToFavoriteServiceTest
 *
 * @group unitary
 */
class AccountToFavoriteServiceTest extends UnitaryTestCase
{

    private AccountToFavoriteRepositoryInterface|MockObject $accountToFavoriteRepository;
    private AccountToFavoriteService                        $accountToFavoriteService;

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\SPException
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

        $actual = $this->accountToFavoriteService->getForUserId($userId);

        $this->assertEquals($result->getDataAsArray(), $actual);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
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

        $actual = $this->accountToFavoriteService->delete($accountId, $userId);

        $this->assertEquals($out, $actual);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
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

        $actual = $this->accountToFavoriteService->add($accountId, $userId);

        $this->assertEquals($out, $actual);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToFavoriteRepository = $this->createMock(AccountToFavoriteRepositoryInterface::class);

        $this->accountToFavoriteService =
            new AccountToFavoriteService($this->application, $this->accountToFavoriteRepository);
    }

}
